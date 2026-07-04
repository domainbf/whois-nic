/**
 * 搜索联想下拉（复刻 x.rw 体验）
 * - 输入域名前缀 → 自动补全常见后缀（.com/.net/.org/.io/.dev/.ai/.co/.xyz…）
 * - 结合本地"最近查询"记录做相似推荐
 * - 左侧：已建站的域名展示网站 favicon（Google 服务），否则通用链接图标
 * - 右侧：通过轻量 DNS 接口标注"已注册 / 未注册"
 * - 支持键盘上下键选择、回车查询、Esc 关闭、点击外部关闭
 */
(function () {
  "use strict";

  var HISTORY_KEY = "whois-search-history";
  // v2：改用服务端 DNS 状态接口后升级键名，丢弃旧的（可能错误的）客户端 DoH 缓存
  var CACHE_KEY = "whois-dns-status-cache-v2";
  // TLD 数据来自 tlds.js（window.NW_TLDS）；缺失时回退到内置常用后缀
  function tldData() {
    return (
      window.NW_TLDS || {
        popular: ["com", "net", "org", "io", "ai", "co", "dev", "app", "xyz", "cn"],
        all: ["com", "net", "org", "io", "ai", "co", "dev", "app", "xyz", "cn"],
        set: { com: 1, net: 1, org: 1, io: 1, ai: 1, co: 1, dev: 1, app: 1, xyz: 1, cn: 1 },
      }
    );
  }
  var MAX_ITEMS = 10; // 默认（纯标签/完整后缀）展示条数
  var MAX_PREFIX_ITEMS = 40; // 后缀前缀匹配时展示更多，配合下拉滚动查看
  var DEBOUNCE_MS = 180;
  var FETCH_TIMEOUT_MS = 4500; // 超时即回退，避免一直"检测中"
  // 客户端缓存 TTL：已注册变动慢、未注册可能随时被注册
  var TTL_REGISTERED = 12 * 3600 * 1000;
  var TTL_AVAILABLE = 30 * 60 * 1000;

  var LINK_SVG =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>' +
    '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';

  var input, box;
  var items = []; // 当前渲染的候选 [{domain, el, statusEl, iconEl}]
  var activeIndex = -1;
  var debounceTimer = null;
  var statusCache = {}; // domain -> {registered, site, exp}  内存 + localStorage 双层
  var reqSeq = 0;
  var cacheLoaded = false;
  var statusObserver = null; // IntersectionObserver：行进入视口才检测状态

  function readHistory() {
    try {
      var raw = localStorage.getItem(HISTORY_KEY);
      var list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list : [];
    } catch (e) {
      return [];
    }
  }

  // ---- 智能缓存：localStorage 持久化 DNS 状态，避免重复联网 ----
  function loadCache() {
    if (cacheLoaded) return;
    cacheLoaded = true;
    try {
      var raw = localStorage.getItem(CACHE_KEY);
      var data = raw ? JSON.parse(raw) : {};
      var now = Date.now();
      Object.keys(data).forEach(function (d) {
        if (data[d] && data[d].exp > now) statusCache[d] = data[d];
      });
    } catch (e) {
      /* 忽略损坏缓存 */
    }
  }

  var saveTimer = null;
  function saveCache() {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      try {
        // 清理过期项后写回，限制体积
        var now = Date.now();
        var clean = {};
        var keys = Object.keys(statusCache).filter(function (d) {
          return statusCache[d] && statusCache[d].exp > now;
        });
        // 最多保留最近 300 条
        keys.slice(-300).forEach(function (d) {
          clean[d] = statusCache[d];
        });
        localStorage.setItem(CACHE_KEY, JSON.stringify(clean));
      } catch (e) {
        /* 配额不足等：忽略 */
      }
    }, 400);
  }

  // 读取仍然有效的缓存状态；过期或缺失返回 null
  function getCached(domain) {
    var c = statusCache[domain];
    if (c && c.exp > Date.now()) return c;
    return null;
  }

  function putCache(domain, st) {
    var ttl = st.registered ? TTL_REGISTERED : TTL_AVAILABLE;
    statusCache[domain] = {
      registered: !!st.registered,
      site: !!st.site,
      exp: Date.now() + ttl,
    };
    saveCache();
  }

  // 从输入推断"标签"（SLD）与是否已含后缀
  function parseInput(value) {
    var v = value.trim().toLowerCase();
    // 去掉协议与路径
    v = v.replace(/^https?:\/\//, "").replace(/\/.*$/, "").replace(/\s+/g, "");
    return v;
  }

  // 生成候选域名列表
  //  - 纯标签（hello）           → hello + 热门后缀
  //  - 标签 + 完整后缀（hello.com）→ 该域名置顶 + 其他后缀推荐
  //  - 标签 + 后缀前缀（hello.c） → 智能推荐所有以 c 开头的真实后缀（com/co/cn/cc/club…）
  //    此时绝不把 hello.c 当成可查询域名
  function buildCandidates(value) {
    var v = parseInput(value);
    if (!v) return [];

    var data = tldData();
    var TLDS = data.all;
    var POPULAR = data.popular;
    var TLD_SET = data.set;

    var result = [];
    var seen = {};
    function push(d) {
      d = d.toLowerCase();
      if (!seen[d]) {
        seen[d] = true;
        result.push(d);
      }
    }

    // 以最后一个点分割：label 为主体，rest 为后缀部分（可能不完整）
    var dot = v.lastIndexOf(".");
    var label = dot === -1 ? v : v.slice(0, dot);
    var rest = dot === -1 ? "" : v.slice(dot + 1);

    if (!label) return [];

    var limit = MAX_ITEMS;

    if (dot === -1) {
      // 纯标签：拼接热门后缀
      for (var i = 0; i < POPULAR.length; i++) push(label + "." + POPULAR[i]);
    } else if (rest === "") {
      // "hello." → 等同纯标签，推荐热门后缀
      for (var p = 0; p < POPULAR.length; p++) push(label + "." + POPULAR[p]);
    } else if (TLD_SET[rest]) {
      // 完整且真实的后缀 → 该域名置顶，再补充热门后缀
      push(label + "." + rest);
      for (var q = 0; q < POPULAR.length; q++) {
        if (POPULAR[q] !== rest) push(label + "." + POPULAR[q]);
      }
    } else {
      // 后缀不完整（如 .s / .c）→ 前缀匹配真实后缀，可滚动查看更多
      // 以新顶级域（gTLD，多为 3+ 字母）为主，国别后缀（ccTLD，2 字母）其次
      limit = MAX_PREFIX_ITEMS;
      var gtld = [];
      var cctld = [];
      for (var m = 0; m < TLDS.length; m++) {
        var t = TLDS[m];
        if (t.indexOf(rest) !== 0) continue;
        // 含点的多级后缀（com.cn 等）归为次要
        if (t.replace(/\./g, "").length === 2) cctld.push(t);
        else gtld.push(t);
      }
      var ordered = gtld.concat(cctld);
      for (var o = 0; o < ordered.length; o++) push(label + "." + ordered[o]);
      // 没有任何后缀以此开头 → 回退到热门后缀推荐（不展示非法域名）
      if (ordered.length === 0) {
        for (var f = 0; f < POPULAR.length; f++) push(label + "." + POPULAR[f]);
        limit = MAX_ITEMS;
      }
    }

    // 融合最近查询记录：必须以"完整输入值"开头，避免输入 hello.s 时
    // 把历史里的 hello.cc 误置顶（之前只按 label 前缀匹配才会串扰）。
    var hist = readHistory();
    var recent = [];
    for (var k = 0; k < hist.length; k++) {
      var qq = (hist[k].query || "").toLowerCase();
      if (qq && qq.indexOf(".") !== -1 && qq.indexOf(v) === 0 && !seen[qq]) {
        // 仅当历史记录后缀真实存在时才纳入
        var hdot = qq.lastIndexOf(".");
        var htld = qq.slice(hdot + 1);
        if (TLD_SET[htld]) {
          seen[qq] = true;
          recent.push(qq);
        }
      }
    }

    return recent.concat(result).slice(0, limit);
  }

  // favicon 走同源服务端代理 ?api=favicon：服务端在 Vercel(美国)抓取
  // Google/DDG/站点图标再以同源图片返回，国内不被墙、无 CORS、可被浏览器缓存。
  // 这是唯一稳定可用的方案，故只需一个 URL（服务端已内置多源回退）。
  function faviconSources(domain) {
    var form = document.getElementById("form");
    var action =
      (form && form.getAttribute("action")) || window.location.pathname;
    var url;
    try {
      url = new URL(action, window.location.href);
    } catch (e) {
      url = new URL(window.location.href);
    }
    url.search = "";
    url.searchParams.set("api", "favicon");
    url.searchParams.set("domain", domain);
    return [url.toString()];
  }

  function resetPending() {
    pendingQueue = [];
    if (flushTimer) {
      clearTimeout(flushTimer);
      flushTimer = null;
    }
  }

  function clearBox() {
    if (statusObserver) {
      statusObserver.disconnect();
      statusObserver = null;
    }
    resetPending();
    box.innerHTML = "";
    box.hidden = true;
    items = [];
    activeIndex = -1;
  }

  function render(candidates) {
    if (statusObserver) {
      statusObserver.disconnect();
      statusObserver = null;
    }
    box.innerHTML = "";
    items = [];
    activeIndex = -1;

    if (!candidates.length) {
      box.hidden = true;
      return;
    }

    // 懒检测：仅当行滚动进入视口时才发起 DNS 查询，避免一次性查几十个域名
    reqSeq++;
    resetPending();
    if (typeof IntersectionObserver !== "undefined") {
      statusObserver = new IntersectionObserver(
        function (entries) {
          entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            var rec = entry.target.__rec;
            statusObserver.unobserve(entry.target);
            if (rec) detectRecord(rec);
          });
        },
        { root: box, rootMargin: "120px 0px" }
      );
    }

    var checkingLabel = box.getAttribute("data-label-checking") || "";
    var frag = document.createDocumentFragment();

    candidates.forEach(function (domain, idx) {
      var row = document.createElement("button");
      row.type = "button";
      row.className = "nw-suggest-item";
      row.setAttribute("role", "option");
      row.dataset.domain = domain;

      var icon = document.createElement("span");
      icon.className = "nw-suggest-icon";
      icon.innerHTML = LINK_SVG;

      var name = document.createElement("span");
      name.className = "nw-suggest-name";
      name.textContent = domain;

      var status = document.createElement("span");
      status.className = "nw-suggest-status is-checking";
      status.textContent = checkingLabel;

      row.appendChild(icon);
      row.appendChild(name);
      row.appendChild(status);
      frag.appendChild(row);

      var record = { domain: domain, el: row, statusEl: status, iconEl: icon };
      items.push(record);
      row.__rec = record;

      // 已有有效缓存直接套用（秒开，无需联网）；否则挂到观察器懒检测
      var cached = getCached(domain);
      if (cached) {
        applyStatus(record, cached);
      } else if (statusObserver) {
        statusObserver.observe(row);
      }

      row.addEventListener("mousedown", function (e) {
        // mousedown 早于 blur，避免下拉先关闭
        e.preventDefault();
        select(domain);
      });
      row.addEventListener("mouseenter", function () {
        setActive(idx);
      });
    });

    box.appendChild(frag);
    box.hidden = false;

    // 不支持 IntersectionObserver 的浏览器：退回一次性检测（仍受缓存约束）
    if (!statusObserver) {
      items.forEach(function (rec) {
        if (!getCached(rec.domain)) detectRecord(rec);
      });
    }
  }

  function applyStatus(record, st) {
    var registeredLabel = box.getAttribute("data-label-registered") || "";
    var availableLabel = box.getAttribute("data-label-available") || "";
    var s = record.statusEl;
    s.classList.remove("is-checking");

    if (st.registered) {
      s.textContent = registeredLabel;
      s.classList.add("is-registered");
      s.classList.remove("is-available");
    } else {
      s.textContent = availableLabel;
      s.classList.add("is-available");
      s.classList.remove("is-registered");
    }

    // 已建站 → 展示网站 favicon（多源逐个回退，全部失败则保留通用图标）
    if (st.site) {
      loadFavicon(record, faviconSources(record.domain), 0);
    }
  }

  function loadFavicon(record, sources, idx) {
    if (idx >= sources.length) return; // 全部失败 → 保留通用链接图标
    var img = new Image();
    img.width = 18;
    img.height = 18;
    img.alt = "";
    img.loading = "lazy";
    img.referrerPolicy = "no-referrer";

    var done = false;
    // 服务端代理内部已做多源回退（每源最多 5s），这里给足 8s 再放弃
    var timer = setTimeout(function () {
      if (done) return;
      done = true;
      img.src = ""; // 中止加载
      loadFavicon(record, sources, idx + 1);
    }, 8000);

    img.onload = function () {
      if (done) return;
      done = true;
      clearTimeout(timer);
      // 有些服务失败时会返回 1x1 占位图，尺寸过小则视为无效
      if (img.naturalWidth <= 1 || img.naturalHeight <= 1) {
        loadFavicon(record, sources, idx + 1);
        return;
      }
      record.iconEl.innerHTML = "";
      record.iconEl.appendChild(img);
      record.iconEl.classList.add("has-favicon");
    };
    img.onerror = function () {
      if (done) return;
      done = true;
      clearTimeout(timer);
      loadFavicon(record, sources, idx + 1);
    };
    img.src = sources[idx];
  }

  // ---- 状态检测：走同源服务端接口 ?api=domain-status ----
  // 服务端在 Vercel（美国）用 checkdnsrr 判断 NS/A 记录，权威可靠，
  // 且同源无 CORS、国内不被墙——比客户端 DoH 稳定得多（修复 .cc 等误判）。
  var pendingQueue = [];   // 待检测域名 {domain}
  var flushTimer = null;
  var BATCH_SIZE = 12;     // 服务端单次最多处理 12 个

  function apiUrlFor(domains) {
    var form = document.getElementById("form");
    var action = (form && form.getAttribute("action")) || window.location.pathname;
    var url;
    try {
      url = new URL(action, window.location.href);
    } catch (e) {
      url = new URL(window.location.href);
    }
    url.search = "";
    url.searchParams.set("api", "domain-status");
    url.searchParams.set("domains", domains.join(","));
    return url.toString();
  }

  function flushQueue() {
    flushTimer = null;
    if (!pendingQueue.length) return;

    var batch = pendingQueue.splice(0, BATCH_SIZE);
    var domains = batch.map(function (b) {
      return b.domain;
    });
    var seq = reqSeq;

    var controller =
      typeof AbortController !== "undefined" ? new AbortController() : null;
    var timer = setTimeout(function () {
      if (controller) controller.abort();
    }, FETCH_TIMEOUT_MS);

    fetch(apiUrlFor(domains), {
      headers: { Accept: "application/json" },
      signal: controller ? controller.signal : undefined,
    })
      .then(function (r) {
        clearTimeout(timer);
        return r.ok ? r.json() : {};
      })
      .then(function (data) {
        if (seq !== reqSeq) return; // 输入已变，丢弃过期结果
        batch.forEach(function (b) {
          var st = data[b.domain];
          if (st) {
            putCache(b.domain, st);
            applyStatus(b.record, st);
          } else {
            markUnresolved(b.record);
          }
        });
      })
      .catch(function () {
        clearTimeout(timer);
        if (seq !== reqSeq) return;
        batch.forEach(function (b) {
          markUnresolved(b.record);
        });
      });

    // 队列里还有则继续下一批
    if (pendingQueue.length) {
      flushTimer = setTimeout(flushQueue, 0);
    }
  }

  // 单行懒检测：行进入视口时入队，合批请求服务端接口
  function detectRecord(record) {
    var cached = getCached(record.domain);
    if (cached) {
      applyStatus(record, cached);
      return;
    }
    pendingQueue.push({ domain: record.domain, record: record });
    if (!flushTimer) flushTimer = setTimeout(flushQueue, 60);
  }

  // 把某一行的"检测中"标记为未知（隐��标签），避免永远转圈
  function markUnresolved(rec) {
    if (rec.statusEl.classList.contains("is-checking")) {
      rec.statusEl.classList.remove("is-checking");
      rec.statusEl.classList.add("is-unknown");
      rec.statusEl.textContent = "";
    }
  }

  function setActive(idx) {
    if (activeIndex >= 0 && items[activeIndex]) {
      items[activeIndex].el.classList.remove("is-active");
    }
    activeIndex = idx;
    if (activeIndex >= 0 && items[activeIndex]) {
      items[activeIndex].el.classList.add("is-active");
      items[activeIndex].el.setAttribute("aria-selected", "true");
    }
  }

  function select(domain) {
    input.value = domain;
    clearBox();
    var clearBtn = document.getElementById("domain-clear");
    if (clearBtn) clearBtn.classList.add("visible");
    // 触发查询（走 app.js 的 pjax 提交流程）
    var form = document.getElementById("form");
    if (form) {
      if (typeof form.requestSubmit === "function") form.requestSubmit();
      else form.submit();
    }
  }

  function onInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      var val = input.value;
      if (!val.trim()) {
        clearBox();
        return;
      }
      render(buildCandidates(val));
    }, DEBOUNCE_MS);
  }

  function onKeydown(e) {
    if (box.hidden || !items.length) return;
    if (e.key === "ArrowDown") {
      e.preventDefault();
      setActive((activeIndex + 1) % items.length);
    } else if (e.key === "ArrowUp") {
      e.preventDefault();
      setActive((activeIndex - 1 + items.length) % items.length);
    } else if (e.key === "Enter") {
      if (activeIndex >= 0 && items[activeIndex]) {
        e.preventDefault();
        select(items[activeIndex].domain);
      }
    } else if (e.key === "Escape") {
      clearBox();
    }
  }

  // 每次事件时刷新元素引用（pjax 会替换 header 内的输入框与下拉容器）
  function refreshEls() {
    input = document.getElementById("domain");
    box = document.getElementById("nw-suggest");
    return input && box;
  }

  function bind() {
    loadCache(); // 启动时载入本地 DNS 状态缓存

    // 事件委托：绑定到 document，pjax 替换 DOM 后仍然有效
    document.addEventListener("input", function (e) {
      if (!e.target || e.target.id !== "domain") return;
      if (!refreshEls()) return;
      onInput();
    });

    document.addEventListener("keydown", function (e) {
      if (!e.target || e.target.id !== "domain") return;
      if (!refreshEls()) return;
      onKeydown(e);
    });

    document.addEventListener("focusin", function (e) {
      if (!e.target || e.target.id !== "domain") return;
      if (!refreshEls()) return;
      input.setAttribute("autocomplete", "off");
      input.setAttribute("role", "combobox");
      input.setAttribute("aria-autocomplete", "list");
      if (input.value.trim()) render(buildCandidates(input.value));
    });

    // 点击外部关闭
    document.addEventListener("click", function (e) {
      if (!box) return;
      if (!box.contains(e.target) && e.target !== input) clearBox();
    });
  }

  (window.nwReady ||
    function (f) {
      document.addEventListener("DOMContentLoaded", f);
    })(bind);
})();

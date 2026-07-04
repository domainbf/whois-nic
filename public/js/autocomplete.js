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
  // 常见后缀（与参考设计一致，按热度排序）
  var TLDS = ["com", "net", "org", "io", "dev", "ai", "co", "xyz", "app", "cn"];
  var MAX_ITEMS = 8;
  var DEBOUNCE_MS = 180;

  var LINK_SVG =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' +
    '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>' +
    '<path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>';

  var input, box;
  var items = []; // 当前渲染的候选 [{domain, el, statusEl, iconEl}]
  var activeIndex = -1;
  var debounceTimer = null;
  var statusCache = {}; // domain -> {registered, site}
  var reqSeq = 0;

  function readHistory() {
    try {
      var raw = localStorage.getItem(HISTORY_KEY);
      var list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list : [];
    } catch (e) {
      return [];
    }
  }

  // 从输入推断"标签"（SLD）与是否已含后缀
  function parseInput(value) {
    var v = value.trim().toLowerCase();
    // 去掉协议与路径
    v = v.replace(/^https?:\/\//, "").replace(/\/.*$/, "").replace(/\s+/g, "");
    return v;
  }

  // 生成候选域名列表
  function buildCandidates(value) {
    var v = parseInput(value);
    if (!v) return [];

    var result = [];
    var seen = {};
    function push(d) {
      d = d.toLowerCase();
      if (!seen[d]) {
        seen[d] = true;
        result.push(d);
      }
    }

    var dot = v.indexOf(".");
    if (dot === -1) {
      // 纯标签：拼接各常见后缀
      for (var i = 0; i < TLDS.length; i++) push(v + "." + TLDS[i]);
    } else {
      var label = v.slice(0, dot);
      var rest = v.slice(dot + 1);
      // 用户已输入的完整域名优先
      if (rest.indexOf(".") !== -1 || rest.length > 0) push(v);
      // 相似推荐：同标签的其他后缀
      if (label) {
        for (var j = 0; j < TLDS.length; j++) {
          if (TLDS[j] !== rest) push(label + "." + TLDS[j]);
        }
      }
    }

    // 融合最近查询里以该标签开头的记录（去重后置顶）
    var baseLabel = dot === -1 ? v : v.slice(0, dot);
    var hist = readHistory();
    var recent = [];
    for (var k = 0; k < hist.length; k++) {
      var q = (hist[k].query || "").toLowerCase();
      if (q && q.indexOf(".") !== -1 && q.indexOf(baseLabel) === 0 && !seen[q]) {
        seen[q] = true;
        recent.push(q);
      }
    }

    return recent.concat(result).slice(0, MAX_ITEMS);
  }

  function faviconUrl(domain) {
    return (
      "https://www.google.com/s2/favicons?sz=64&domain=" +
      encodeURIComponent(domain)
    );
  }

  function clearBox() {
    box.innerHTML = "";
    box.hidden = true;
    items = [];
    activeIndex = -1;
  }

  function render(candidates) {
    box.innerHTML = "";
    items = [];
    activeIndex = -1;

    if (!candidates.length) {
      box.hidden = true;
      return;
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

      // 已有缓存直接套用
      if (statusCache[domain]) applyStatus(record, statusCache[domain]);

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

    fetchStatuses(candidates);
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

    // 已建站 → 展示网站 favicon
    if (st.site) {
      var img = new Image();
      img.width = 16;
      img.height = 16;
      img.alt = "";
      img.loading = "lazy";
      img.referrerPolicy = "no-referrer";
      img.onload = function () {
        record.iconEl.innerHTML = "";
        record.iconEl.appendChild(img);
        record.iconEl.classList.add("has-favicon");
      };
      img.onerror = function () {
        /* 保留通用图标 */
      };
      img.src = faviconUrl(record.domain);
    }
  }

  function fetchStatuses(candidates) {
    var need = candidates.filter(function (d) {
      return !statusCache[d];
    });
    if (!need.length) return;

    var seq = ++reqSeq;
    var form = document.getElementById("form");
    var action = (form && form.getAttribute("action")) || window.location.pathname;
    var apiUrl;
    try {
      apiUrl = new URL(action, window.location.href);
    } catch (err) {
      apiUrl = new URL(window.location.href);
    }
    apiUrl.search = "";
    apiUrl.searchParams.set("api", "domain-status");
    apiUrl.searchParams.set("domains", need.join(","));

    fetch(apiUrl.toString(), { headers: { Accept: "application/json" } })
      .then(function (r) {
        return r.ok ? r.json() : {};
      })
      .then(function (data) {
        if (seq !== reqSeq) return; // 已有更新的请求，丢弃过期结果
        Object.keys(data).forEach(function (d) {
          statusCache[d] = data[d];
        });
        items.forEach(function (rec) {
          if (statusCache[rec.domain]) applyStatus(rec, statusCache[rec.domain]);
        });
      })
      .catch(function () {
        /* 网络失败：保持"检测中"或忽略 */
      });
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

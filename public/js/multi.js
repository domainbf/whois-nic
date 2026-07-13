// 多域名批量查询：列表已在页面内直接展示各域名可注册/已注册状态。
// 点击某一行时，用 JSON 接口拉取该域名的完整数据，并在弹窗内“原生渲染”
// 详情卡片（不再使用 iframe）。这样彻底解决了旧 iframe 方案的问题：
//  - 弹窗能可靠关闭（不再有 iframe 焦点/事件吞噬）；
//  - “在新标签打开”指向该域名的真实详情页（而非首页）；
//  - 加载/错误/结果三态互斥，绝不叠加显示；
//  - 冷门/限流后缀查询超时可重试，用户始终有出路。
(function () {
  "use strict";

  var modal = document.getElementById("multi-modal");
  if (!modal) return;

  var loading = document.getElementById("multi-modal-loading");
  var loadingText = document.getElementById("multi-modal-loading-text");
  var errorBox = document.getElementById("multi-modal-error");
  var contentBox = document.getElementById("multi-modal-content");
  var retryBtn = document.getElementById("multi-modal-retry");
  var openLink = document.getElementById("multi-modal-open");
  var errorOpenLink = document.getElementById("multi-modal-error-open");
  var titleEl = document.getElementById("multi-modal-title");

  // 本地化标签
  var L = {};
  try {
    var labelEl = document.getElementById("multi-modal-labels");
    if (labelEl) L = JSON.parse(labelEl.textContent || "{}");
  } catch (e) { L = {}; }

  var lastFocused = null;
  var loadTimer = null;
  var slowTimer = null;
  var isOpen = false;
  var reqSeq = 0; // 请求序号，防止过期响应覆盖当前视图
  var currentDomain = "";
  var currentUrl = "";

  var TIMEOUT_MS = 18000;   // 客户端硬超时（早于函数上限）
  var SLOW_HINT_MS = 6000;  // 超过此时长提示“注册局较慢”
  var loadingDefault = (loadingText && loadingText.textContent) || "";
  var slowHint = modal.getAttribute("data-slow") || "";

  function clearTimers() {
    if (loadTimer) { clearTimeout(loadTimer); loadTimer = null; }
    if (slowTimer) { clearTimeout(slowTimer); slowTimer = null; }
  }

  // 三态互斥：loading / content / error
  function showState(state) {
    if (loading) loading.hidden = state !== "loading";
    if (contentBox) contentBox.hidden = state !== "content";
    if (errorBox) errorBox.hidden = state !== "error";
  }

  function esc(s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }

  // 将 ISO / 原始日期截断到 YYYY-MM-DD（无法解析则原样返回）
  function fmtDate(v) {
    if (!v) return "";
    var m = String(v).match(/\d{4}-\d{2}-\d{2}/);
    return m ? m[0] : String(v);
  }

  // 根据 JSON 数据构建详情卡片
  function renderCard(d) {
    var rows = [];

    function field(label, valueHtml) {
      if (!valueHtml) return;
      rows.push(
        '<div class="nw-md-row"><span class="nw-md-label">' + esc(label) +
        '</span><span class="nw-md-value">' + valueHtml + "</span></div>"
      );
    }

    // 状态徽标（可注册 / 已注册）
    var isReg = !!d.registered;
    var badgeText = isReg ? (L.registeredState || "已注册") : (L.available || "可注册");
    var badgeClass = isReg ? "is-reg" : "is-avail";
    var header =
      '<div class="nw-md-hero">' +
      '<span class="nw-md-badge ' + badgeClass + '">' + esc(badgeText) + "</span>" +
      "</div>";

    // 注册商（带官网链接）
    if (d.registrar) {
      var reg = esc(d.registrar);
      if (d.registrarURL) {
        reg = '<a href="' + esc(d.registrarURL) + '" target="_blank" rel="noopener">' + reg + "</a>";
      }
      field(L.registrar || "注册商", reg);
    }

    // 日期
    field(L.creation || "创建日期", esc(fmtDate(d.creationDate)));
    field(L.expiration || "到期日期", esc(fmtDate(d.expirationDate)));
    field(L.updated || "更新日期", esc(fmtDate(d.updatedDate)));

    // 域龄 / 剩余
    if (d.age) field(L.age || "域龄", esc(d.age));
    if (d.remaining) field(L.remaining || "剩余", esc(d.remaining));

    // 状态
    if (d.status && d.status.length) {
      var st = d.status.map(function (s) {
        var txt = esc(s.text || s);
        return '<span class="nw-md-chip">' + txt + "</span>";
      }).join("");
      field(L.status || "域名状态", '<span class="nw-md-chips">' + st + "</span>");
    }

    // NS
    if (d.nameServers && d.nameServers.length) {
      var ns = d.nameServers.map(function (n) {
        return '<span class="nw-md-ns">' + esc(n) + "</span>";
      }).join("");
      field(L.ns || "NS 服务器", '<span class="nw-md-nslist">' + ns + "</span>");
    }

    // DNSSEC
    if (d.dnssec) {
      var signed = /sign|active|yes/i.test(d.dnssec) && !/unsigned/i.test(d.dnssec);
      field(L.dnssec || "DNSSEC", esc(signed ? (L.dnssecSigned || "已签名") : (L.dnssecUnsigned || "未签名")));
    }

    var body = rows.length
      ? '<div class="nw-md-fields">' + rows.join("") + "</div>"
      : '<p class="nw-md-empty">' + esc(L.empty || "—") + "</p>";

    // 查看完整信息（真实详情页链接）
    var footer =
      '<a class="nw-modal-btn nw-md-full" href="' + esc(currentUrl) + '">' +
      esc(L.full || "查看完整信息") +
      '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>' +
      "</a>";

    contentBox.innerHTML = header + body + footer;
  }

  function toError() {
    clearTimers();
    showState("error");
  }

  function load(domain, fullUrl) {
    var seq = ++reqSeq;
    currentDomain = domain;
    currentUrl = fullUrl;

    // 逃生入口 / 完整信息链接
    if (openLink) openLink.href = fullUrl;
    if (errorOpenLink) errorOpenLink.href = fullUrl;

    if (loadingText) loadingText.textContent = loadingDefault;
    showState("loading");

    slowTimer = setTimeout(function () {
      if (seq === reqSeq && loadingText && slowHint) loadingText.textContent = slowHint;
    }, SLOW_HINT_MS);

    var ctrl = typeof AbortController !== "undefined" ? new AbortController() : null;
    loadTimer = setTimeout(function () {
      if (ctrl) try { ctrl.abort(); } catch (e) {}
      if (seq === reqSeq) toError();
    }, TIMEOUT_MS);

    var jsonUrl = fullUrl + (fullUrl.indexOf("?") >= 0 ? "&" : "?") + "json=1";
    fetch(jsonUrl, { signal: ctrl ? ctrl.signal : undefined, headers: { Accept: "application/json" } })
      .then(function (r) { return r.ok ? r.json() : Promise.reject(new Error("HTTP " + r.status)); })
      .then(function (res) {
        if (seq !== reqSeq || !isOpen) return;
        clearTimers();
        if (res && res.code === 0 && res.data) {
          renderCard(res.data);
          showState("content");
        } else {
          toError();
        }
      })
      .catch(function () {
        if (seq === reqSeq && isOpen) toError();
      });
  }

  function openModal(domain, fullUrl) {
    lastFocused = document.activeElement;
    isOpen = true;
    titleEl.textContent = domain;
    if (contentBox) { contentBox.innerHTML = ""; contentBox.scrollTop = 0; }

    modal.hidden = false;
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nw-modal-open");

    load(domain, fullUrl);

    var closeBtn = modal.querySelector(".nw-modal-close");
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    isOpen = false;
    reqSeq++; // 作废进行中的请求
    clearTimers();
    modal.hidden = true;
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nw-modal-open");
    if (contentBox) contentBox.innerHTML = "";
    showState("loading"); // 复位
    if (lastFocused && typeof lastFocused.focus === "function") lastFocused.focus();
  }

  // 列表项点击 → 打开弹窗（保留 href 作为无 JS / 新标签兜底）
  document.addEventListener("click", function (e) {
    var link = e.target.closest ? e.target.closest(".js-multi-detail") : null;
    if (!link) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;
    e.preventDefault();
    var domain = link.getAttribute("data-domain") || "";
    var href = link.getAttribute("href") || "";
    if (domain && href) openModal(domain, href);
  });

  if (retryBtn) {
    retryBtn.addEventListener("click", function () {
      if (currentDomain && currentUrl) load(currentDomain, currentUrl);
    });
  }

  // 关闭：背景 / 关闭按钮（closest 兼容点到 SVG 图标）
  modal.addEventListener("click", function (e) {
    var hit = e.target.closest ? e.target.closest('[data-close="1"]') : null;
    if (hit && modal.contains(hit)) closeModal();
  });

  // Esc 关闭
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !modal.hidden) closeModal();
  });
})();

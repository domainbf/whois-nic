// 多域名列表：点击某项时在弹窗 iframe 内加载该域名的完整查询结果，
// 不再跳转整页。iframe 复用现有查询页（embed 模式，隐藏顶栏/搜索框/页脚）。
//
// 健壮性设计（针对冷门/限流 ccTLD 后缀，其注册局 WHOIS 响应很慢甚至超时）：
//  - 加载时显示提示文案，并始终提供“在新标签打开”的逃生入口，用户不会被困；
//  - iframe 载入后主动检测是否真的取得结果（同源可读 contentDocument），
//    若为超时错误页 / 空白 / 非本站结果，则切换到错误态而非无限空转；
//  - 客户端超时（20s，早于 Vercel 函数 22s 上限）→ 直接切换错误态；
//  - 错误态提供“重试”和“在新标签打开”。
(function () {
  "use strict";

  var modal = document.getElementById("multi-modal");
  if (!modal) return;

  var frame = document.getElementById("multi-modal-frame");
  var loading = document.getElementById("multi-modal-loading");
  var loadingText = document.getElementById("multi-modal-loading-text");
  var errorBox = document.getElementById("multi-modal-error");
  var retryBtn = document.getElementById("multi-modal-retry");
  var openLink = document.getElementById("multi-modal-open");
  var errorOpenLink = document.getElementById("multi-modal-error-open");
  var titleEl = document.getElementById("multi-modal-title");

  var lastFocused = null;
  var loadTimer = null;
  var slowTimer = null;
  var isOpen = false;
  var currentDomain = "";
  var currentUrl = "";

  var TIMEOUT_MS = 20000; // 客户端超时（早于函数 maxDuration=22s）
  var SLOW_HINT_MS = 6000; // 超过此时长提示“注册局较慢”
  var i18n = {
    loading: (loadingText && loadingText.textContent) || "正在查询完整信息…",
    slow: modal.getAttribute("data-slow") || "",
  };

  function baseHref() {
    // 与列表项 href 同源：取第一个详情链接的 pathname 作为查询基址
    var a = document.querySelector(".js-multi-detail");
    if (a) {
      try {
        return new URL(a.getAttribute("href"), window.location.href).pathname;
      } catch (e) {}
    }
    return window.location.pathname;
  }

  function clearTimers() {
    if (loadTimer) { clearTimeout(loadTimer); loadTimer = null; }
    if (slowTimer) { clearTimeout(slowTimer); slowTimer = null; }
  }

  // 显示三态之一：loading / frame / error
  function showState(state) {
    if (loading) loading.toggleAttribute("hidden", state !== "loading");
    if (errorBox) errorBox.toggleAttribute("hidden", state !== "error");
    if (frame) frame.toggleAttribute("hidden", state !== "frame");
  }

  // 检测 iframe 是否真的取得了本站结果（同源可读）。
  // 返回：true=有结果，false=错误/空白/超时页。跨域读取异常时保守视为成功。
  function frameHasResult() {
    try {
      var doc = frame.contentDocument;
      if (!doc || !doc.body) return false;
      // 本站结果页含 <main>；Vercel 超时/错误页不含且文本极短
      if (doc.querySelector("main")) return true;
      var txt = (doc.body.innerText || "").trim();
      return txt.length > 40; // 兜底：有实质内容
    } catch (e) {
      return true; // 无法读取（极少数情况）→ 不误判为错误
    }
  }

  function toError() {
    clearTimers();
    isLoadingDone = true;
    showState("error");
  }

  var isLoadingDone = false;

  function load(domain) {
    currentDomain = domain;
    currentUrl = baseHref() + "?domain=" + encodeURIComponent(domain);
    var embedUrl = currentUrl + "&embed=1";

    // 更新逃生入口链接（新标签打开完整页面，非 embed）
    if (openLink) openLink.href = currentUrl;
    if (errorOpenLink) errorOpenLink.href = currentUrl;

    isLoadingDone = false;
    if (loadingText) loadingText.textContent = i18n.loading;
    showState("loading");

    frame.onload = function () {
      if (!isOpen || isLoadingDone) return;
      // 载入完成：判断是否真的有结果
      if (frameHasResult()) {
        isLoadingDone = true;
        clearTimers();
        showState("frame");
      } else {
        toError();
      }
    };
    frame.setAttribute("title", domain);
    frame.src = embedUrl;

    // 慢速提示
    slowTimer = setTimeout(function () {
      if (!isLoadingDone && loadingText && i18n.slow) loadingText.textContent = i18n.slow;
    }, SLOW_HINT_MS);

    // 硬超时 → 错误态
    loadTimer = setTimeout(function () {
      if (!isLoadingDone) toError();
    }, TIMEOUT_MS);
  }

  function openModal(domain) {
    lastFocused = document.activeElement;
    isOpen = true;
    titleEl.textContent = domain;

    modal.removeAttribute("hidden");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nw-modal-open");

    load(domain);

    var closeBtn = modal.querySelector(".nw-modal-close");
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    isOpen = false;
    isLoadingDone = true;
    clearTimers();
    modal.setAttribute("hidden", "");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nw-modal-open");
    // 释放 iframe，避免后台继续加载
    frame.onload = null;
    frame.src = "about:blank";
    showState("loading"); // 复位，供下次打开
    if (lastFocused && typeof lastFocused.focus === "function") {
      lastFocused.focus();
    }
  }

  // 列表项点击 → 打开弹窗（保留 href 作为无 JS / 新标签页兜底）
  document.addEventListener("click", function (e) {
    var link = e.target.closest ? e.target.closest(".js-multi-detail") : null;
    if (!link) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;
    e.preventDefault();
    var domain = link.getAttribute("data-domain") || "";
    if (domain) openModal(domain);
  });

  // 重试
  if (retryBtn) {
    retryBtn.addEventListener("click", function () {
      if (currentDomain) load(currentDomain);
    });
  }

  // 关闭：点击背景 / 关闭按钮（用 closest 向上查找，兼容点到按钮内 SVG 图标的情况）
  modal.addEventListener("click", function (e) {
    var hit = e.target.closest ? e.target.closest('[data-close="1"]') : null;
    if (hit && modal.contains(hit)) {
      closeModal();
    }
  });

  // Esc 关闭
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && !modal.hasAttribute("hidden")) {
      closeModal();
    }
  });
})();

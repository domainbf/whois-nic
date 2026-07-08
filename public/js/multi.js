// 多域名列表：点击某项时在弹窗 iframe 内加载该域名的完整查询结果，
// 不再跳转整页。iframe 复用现有查询页（embed 模式，隐藏顶栏/搜索框/页脚）。
(function () {
  "use strict";

  var modal = document.getElementById("multi-modal");
  if (!modal) return;

  var frame = document.getElementById("multi-modal-frame");
  var loading = document.getElementById("multi-modal-loading");
  var titleEl = document.getElementById("multi-modal-title");
  var lastFocused = null;

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

  function openModal(domain) {
    lastFocused = document.activeElement;
    titleEl.textContent = domain;

    // 组装 embed 查询地址：?domain=xxx&embed=1
    var url = baseHref() + "?domain=" + encodeURIComponent(domain) + "&embed=1";

    // 重置为加载态
    frame.setAttribute("hidden", "");
    loading.removeAttribute("hidden");
    frame.setAttribute("title", domain);

    frame.onload = function () {
      loading.setAttribute("hidden", "");
      frame.removeAttribute("hidden");
    };
    frame.src = url;

    modal.removeAttribute("hidden");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nw-modal-open");

    var closeBtn = modal.querySelector(".nw-modal-close");
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    modal.setAttribute("hidden", "");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nw-modal-open");
    // 释放 iframe，避免后台继续加载
    frame.src = "about:blank";
    frame.setAttribute("hidden", "");
    loading.removeAttribute("hidden");
    if (lastFocused && typeof lastFocused.focus === "function") {
      lastFocused.focus();
    }
  }

  // 列表项点击 → 打开弹窗（保留 href 作为无 JS / 新标签页兜底）
  document.addEventListener("click", function (e) {
    var link = e.target.closest ? e.target.closest(".js-multi-detail") : null;
    if (!link) return;
    // 允许用户用修饰键在新标签打开
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button === 1) return;
    e.preventDefault();
    var domain = link.getAttribute("data-domain") || "";
    if (domain) openModal(domain);
  });

  // 关闭：背景 / 关闭按钮
  modal.addEventListener("click", function (e) {
    var t = e.target;
    if (t && t.getAttribute && t.getAttribute("data-close") === "1") {
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

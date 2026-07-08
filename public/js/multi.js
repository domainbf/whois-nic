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
  var loadTimer = null;
  var isOpen = false;

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

  function stopLoading() {
    if (loadTimer) {
      clearTimeout(loadTimer);
      loadTimer = null;
    }
    loading.setAttribute("hidden", "");
  }

  function openModal(domain) {
    lastFocused = document.activeElement;
    isOpen = true;
    titleEl.textContent = domain;

    // 组装 embed 查询地址：?domain=xxx&embed=1
    var url = baseHref() + "?domain=" + encodeURIComponent(domain) + "&embed=1";

    // 重置为加载态：iframe 立即可见（在其上覆盖加载圈），内容加载完成后隐藏加载圈
    frame.removeAttribute("hidden");
    loading.removeAttribute("hidden");
    frame.setAttribute("title", domain);

    frame.onload = function () {
      // 只有真正加载了目标页（非 about:blank）才结束加载态
      if (isOpen) stopLoading();
    };
    frame.src = url;

    // 安全兜底：被限流的后缀完整查询可能很慢/超时，最多转 25s 后强制结束加载圈，
    // 避免无限空转；此时展示 iframe 内的任何内容（成功页或错误页）。
    if (loadTimer) clearTimeout(loadTimer);
    loadTimer = setTimeout(function () {
      stopLoading();
    }, 25000);

    modal.removeAttribute("hidden");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("nw-modal-open");

    var closeBtn = modal.querySelector(".nw-modal-close");
    if (closeBtn) closeBtn.focus();
  }

  function closeModal() {
    isOpen = false;
    stopLoading();
    modal.setAttribute("hidden", "");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("nw-modal-open");
    // 释放 iframe，避免后台继续加载
    frame.onload = null;
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

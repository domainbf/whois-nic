(function () {
  "use strict";

  function currentTheme() {
    return document.documentElement.getAttribute("data-theme") === "dark"
      ? "dark"
      : "light";
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    try {
      localStorage.setItem("theme", theme);
    } catch (e) {}
    // 同步浏览器 UI 颜色
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) {
      meta.setAttribute("content", theme === "dark" ? "#000000" : "#ffffff");
    }
  }

  var transitionTimer = null;

  // 仅在用户主动切换时启用平滑过渡，避免首屏加载时的过渡闪烁
  function smoothApply(theme) {
    var root = document.documentElement;
    root.classList.add("theme-transition");
    applyTheme(theme);
    if (transitionTimer) clearTimeout(transitionTimer);
    transitionTimer = setTimeout(function () {
      root.classList.remove("theme-transition");
    }, 320);
  }

  document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.getElementById("theme-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        smoothApply(currentTheme() === "dark" ? "light" : "dark");
      });
    }
  });
})();

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

  document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.getElementById("theme-toggle");
    if (toggle) {
      toggle.addEventListener("click", function () {
        applyTheme(currentTheme() === "dark" ? "light" : "dark");
      });
    }

    // 示例快捷查询：点击填入搜索框并提交
    var chips = document.querySelectorAll(".hero-chip");
    var input = document.getElementById("domain");
    var form = document.getElementById("form");
    chips.forEach(function (chip) {
      chip.addEventListener("click", function () {
        if (input && form) {
          input.value = chip.dataset.domain || chip.textContent.trim();
          form.submit();
        }
      });
    });
  });
})();

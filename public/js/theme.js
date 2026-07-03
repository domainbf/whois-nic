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
      meta.setAttribute("content", theme === "dark" ? "#0a0a0c" : "#ffffff");
    }
  }

  var transitionTimer = null;

  // 经典淡入回退：在不支持 View Transitions 时使用 .theme-transition 做柔和过渡
  function smoothApply(theme) {
    var root = document.documentElement;
    root.classList.add("theme-transition");
    applyTheme(theme);
    if (transitionTimer) clearTimeout(transitionTimer);
    transitionTimer = setTimeout(function () {
      root.classList.remove("theme-transition");
    }, 480);
  }

  function prefersReduced() {
    return (
      window.matchMedia &&
      window.matchMedia("(prefers-reduced-motion: reduce)").matches
    );
  }

  // 以点击位置为圆心做圆形扩散切换（View Transitions API），渐进增强
  function toggleTheme(event) {
    var next = currentTheme() === "dark" ? "light" : "dark";

    if (!document.startViewTransition || prefersReduced()) {
      smoothApply(next);
      return;
    }

    var x =
      event && typeof event.clientX === "number" && event.clientX > 0
        ? event.clientX
        : window.innerWidth - 36;
    var y =
      event && typeof event.clientY === "number" && event.clientY > 0
        ? event.clientY
        : 36;
    var endRadius = Math.hypot(
      Math.max(x, window.innerWidth - x),
      Math.max(y, window.innerHeight - y)
    );

    var transition = document.startViewTransition(function () {
      applyTheme(next);
    });

    transition.ready
      .then(function () {
        document.documentElement.animate(
          {
            clipPath: [
              "circle(0px at " + x + "px " + y + "px)",
              "circle(" + endRadius + "px at " + x + "px " + y + "px)",
            ],
          },
          {
            duration: 480,
            easing: "cubic-bezier(0.4, 0, 0.2, 1)",
            pseudoElement: "::view-transition-new(root)",
          }
        );
      })
      .catch(function () {});
  }

  document.addEventListener("DOMContentLoaded", function () {
    var toggle = document.getElementById("theme-toggle");
    if (toggle) {
      toggle.addEventListener("click", function (e) {
        toggleTheme(e);
      });
    }
  });
})();

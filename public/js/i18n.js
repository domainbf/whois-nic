/**
 * 前端多语言辅助
 * - 暴露 window.I18N.t(key, ...args)（基于 head 注入的 window.__I18N__）
 * - 驱动顶栏语言切换器：展开/收起菜单、切换语言（写 cookie + 重载，保留当前路径与查询）
 */
(function () {
  "use strict";

  var payload = window.__I18N__ || { lang: "zh", dateStyle: "cjk", t: {} };

  function format(str, args) {
    var i = 0;
    return String(str).replace(/%d|%s/g, function () {
      return args && i < args.length ? args[i++] : "";
    });
  }

  // 全局译文访问器，供 dates.js / history.js / app.js 使用
  window.I18N = {
    lang: payload.lang,
    dateStyle: payload.dateStyle,
    t: function (key) {
      var val = (payload.t && payload.t[key] != null) ? payload.t[key] : key;
      var args = Array.prototype.slice.call(arguments, 1);
      return args.length ? format(val, args) : val;
    },
  };

  // 设置语言：写 cookie，并在当前 URL 上设置 ?lang= 后重载（保留路径）
  function switchLang(code) {
    try {
      document.cookie =
        "lang=" + encodeURIComponent(code) + ";path=/;max-age=31536000;samesite=Lax";
    } catch (e) {}
    var url = new URL(window.location.href);
    url.searchParams.set("lang", code);
    window.location.href = url.toString();
  }

  window.addEventListener("DOMContentLoaded", function () {
    var wrap = document.getElementById("lang-switch");
    var toggle = document.getElementById("lang-toggle");
    var menu = document.getElementById("lang-menu");
    if (!wrap || !toggle || !menu) return;

    function openMenu() {
      menu.hidden = false;
      toggle.setAttribute("aria-expanded", "true");
      wrap.classList.add("open");
    }
    function closeMenu() {
      menu.hidden = true;
      toggle.setAttribute("aria-expanded", "false");
      wrap.classList.remove("open");
    }

    toggle.addEventListener("click", function (e) {
      e.stopPropagation();
      if (menu.hidden) openMenu();
      else closeMenu();
    });

    menu.querySelectorAll(".lang-option").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var code = btn.getAttribute("data-lang");
        if (code && code !== payload.lang) switchLang(code);
        else closeMenu();
      });
    });

    // 点击外部或按 Esc 关闭
    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) closeMenu();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && !menu.hidden) {
        closeMenu();
        toggle.focus();
      }
    });
  });
})();

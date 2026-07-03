/**
 * 首页交互 + 局部无刷新查询（pjax）
 * - 提交域名不再整页跳转，而是 fetch 目标页面并只替换 <header> 与 <main>，
 *   页面骨架（顶栏、页脚、样式、库脚本）保持不变，查询更快、加载动画全程流畅。
 * - 全局委托事件绑定于 document，因此 <header>/<form> 被替换后依然有效。
 */
(function () {
  "use strict";

  // 是否支持 pjax（渐进增强：不支持时回退为原生整页导航）
  var PJAX_OK =
    typeof window.fetch === "function" &&
    typeof window.history !== "undefined" &&
    typeof window.history.pushState === "function" &&
    typeof window.DOMParser === "function";

  // 多语言访问器（i18n.js 已暴露 window.I18N；缺失时回退中文）
  function getI18N() {
    return (
      window.I18N || {
        t: function (k, v) {
          var zh = {
            loading_title: "正在查询 " + (v || "") + "…",
          };
          return zh[k] != null ? zh[k] : k;
        },
      }
    );
  }

  // 已加载的库脚本（只需执行一次；初始化脚本每次都重新执行）
  var LIB_SCRIPTS = [
    "popper.min.js",
    "tippy-bundle.umd.min.js",
    "linkify.min.js",
    "linkify-html.min.js",
    "prism.js",
  ];
  var loadedLibs = Object.create(null);
  // 记录首屏已存在的库脚本，避免重复加载
  (function markExisting() {
    var els = document.querySelectorAll('script[src*="public/js/"]');
    for (var i = 0; i < els.length; i++) {
      var name = (els[i].getAttribute("src") || "").split("/").pop();
      if (LIB_SCRIPTS.indexOf(name) !== -1) loadedLibs[name] = true;
    }
  })();

  // 简单的内存缓存：同一会话内重复查询/前进后退可瞬时展示
  var pageCache = Object.create(null);
  var cacheOrder = [];
  var CACHE_MAX = 20;
  function cachePut(url, html) {
    if (!(url in pageCache)) cacheOrder.push(url);
    pageCache[url] = html;
    while (cacheOrder.length > CACHE_MAX) {
      delete pageCache[cacheOrder.shift()];
    }
  }

  var isLoading = false;

  // ---- 加载动画（内联展示于内容区，全程流畅动画） ----
  function showLoadingOverlay(domainValue) {
    if (document.querySelector(".nw-loading")) return;
    var I18N = getI18N();
    var overlay = document.createElement("div");
    overlay.className = "nw-loading";
    overlay.setAttribute("role", "status");
    overlay.setAttribute("aria-live", "polite");
    overlay.innerHTML =
      '<div class="nw-loading-card">' +
      '<span class="nw-loading-spinner" aria-hidden="true"></span>' +
      '<p class="nw-loading-title"></p>' +
      "</div>";

    overlay.querySelector(".nw-loading-title").textContent = I18N.t("loading_title", domainValue);

    var mainEl = document.querySelector("main");
    var historyEl = document.getElementById("search-history");
    if (historyEl) historyEl.setAttribute("hidden", "");
    var staleInfo = document.querySelector(".domain-info-box");
    if (staleInfo) staleInfo.remove();
    if (mainEl) {
      mainEl.innerHTML = "";
      mainEl.appendChild(overlay);
    } else {
      document.body.appendChild(overlay);
    }
  }

  // ---- 顺序加载脚本，保持原始顺序（库先于初始化脚本） ----
  function loadScriptSequential(srcList) {
    return srcList.reduce(function (chain, src) {
      return chain.then(function () {
        return new Promise(function (resolve) {
          var name = src.split("/").pop();
          if (name === "app.js" || name === "theme.js" || name === "i18n.js") {
            resolve();
            return; // 常驻脚本，不重复执行
          }
          if (name === "history.js") {
            resolve();
            return; // 历史逻辑通过 NWHistory API 调用
          }
          if (LIB_SCRIPTS.indexOf(name) !== -1 && loadedLibs[name]) {
            resolve();
            return; // 库脚本仅加载一次
          }
          var el = document.createElement("script");
          el.src = src;
          el.onload = function () {
            if (LIB_SCRIPTS.indexOf(name) !== -1) loadedLibs[name] = true;
            resolve();
          };
          el.onerror = function () {
            resolve();
          };
          document.body.appendChild(el);
        });
      });
    }, Promise.resolve());
  }

  // ---- 用新文档替换 <header> 与 <main>，并刷新标题/状态 ----
  function applyDocument(html) {
    var doc = new DOMParser().parseFromString(html, "text/html");
    var newHeader = doc.querySelector("header");
    var newMain = doc.querySelector("main");
    if (!newMain) throw new Error("pjax: main not found");

    var curHeader = document.querySelector("header");
    if (newHeader && curHeader) {
      curHeader.replaceWith(document.importNode(newHeader, true));
    }
    var curMain = document.querySelector("main");
    curMain.replaceWith(document.importNode(newMain, true));

    var titleEl = doc.querySelector("title");
    if (titleEl) document.title = titleEl.textContent;
    var hasDomain = doc.body.getAttribute("data-has-domain");
    if (hasDomain !== null) document.body.setAttribute("data-has-domain", hasDomain);

    // 收集新页面所需脚本（保持顺序）后依序执行
    var srcs = [];
    var scriptEls = doc.querySelectorAll('script[src*="public/js/"]');
    for (var i = 0; i < scriptEls.length; i++) {
      srcs.push(scriptEls[i].getAttribute("src"));
    }

    return loadScriptSequential(srcs).then(function () {
      // 库脚本仅加载一次，故后续查询需手动对新注入的原始数据重新高亮
      if (window.Prism && typeof window.Prism.highlightAll === "function") {
        try {
          window.Prism.highlightAll();
        } catch (err) {}
      }
      // 同步搜索框清除按钮状态 + 历史记录
      syncSearchBox();
      if (window.NWHistory && typeof window.NWHistory.sync === "function") {
        window.NWHistory.sync();
      }
    });
  }

  // 根据 #domain 是否有值切换清除按钮显示
  function syncSearchBox() {
    var input = document.getElementById("domain");
    var clearBtn = document.getElementById("domain-clear");
    if (input && clearBtn) {
      if (input.value) clearBtn.classList.add("visible");
      else clearBtn.classList.remove("visible");
    }
  }

  // ---- 核心：pjax 加载某个 URL ----
  function pjaxLoad(url, opts) {
    opts = opts || {};
    var push = opts.push !== false;
    var domainForLoader = opts.domain || "";

    if (push) history.pushState({ pjax: true }, "", url);

    // 命中缓存：瞬时展示（仍显示极短加载态以保持一致体验则可省略）
    if (pageCache[url]) {
      isLoading = true;
      applyDocument(pageCache[url])
        .then(function () {
          window.scrollTo({ top: 0, behavior: "auto" });
        })
        .catch(function () {
          window.location.href = url;
        })
        .finally(function () {
          isLoading = false;
        });
      return;
    }

    isLoading = true;
    showLoadingOverlay(domainForLoader);

    fetch(url, {
      headers: { "X-Requested-With": "fetch" },
      credentials: "same-origin",
    })
      .then(function (res) {
        if (!res.ok) throw new Error("pjax fetch failed: " + res.status);
        return res.text();
      })
      .then(function (html) {
        cachePut(url, html);
        return applyDocument(html);
      })
      .then(function () {
        window.scrollTo({ top: 0, behavior: "auto" });
      })
      .catch(function () {
        // 任意失败：回退为整页导航，保证可用性
        window.location.href = url;
      })
      .finally(function () {
        isLoading = false;
      });
  }

  // ---- 事件委托：表单提交 ----
  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!form || form.id !== "form") return;
    if (!PJAX_OK) return; // 回退整页导航

    var input = form.querySelector("#domain");
    var val = input ? input.value.trim() : "";
    if (!val) return; // 交给原生 required 校验

    e.preventDefault();
    if (isLoading) return;

    var searchIcon = document.getElementById("search-icon");
    if (searchIcon) searchIcon.classList.add("searching");

    // 构造目标 URL（保留表单参数，如隐藏的 prices=1）
    var action = form.getAttribute("action") || window.location.pathname;
    var url;
    try {
      url = new URL(action, window.location.href);
    } catch (err) {
      url = new URL(window.location.href);
    }
    var fd = new FormData(form);
    var params = new URLSearchParams();
    fd.forEach(function (v, k) {
      params.set(k, v);
    });
    url.search = params.toString();

    pjaxLoad(url.toString(), { push: true, domain: val });
  });

  // ---- 事件委托：搜索框输入切换清除按钮 ----
  document.addEventListener("input", function (e) {
    if (e.target && e.target.id === "domain") {
      var clearBtn = document.getElementById("domain-clear");
      if (clearBtn) {
        if (e.target.value) clearBtn.classList.add("visible");
        else clearBtn.classList.remove("visible");
      }
    }
  });

  // ---- 事件委托：清除按钮 ----
  document.addEventListener("click", function (e) {
    var clearBtn = e.target.closest ? e.target.closest("#domain-clear") : null;
    if (clearBtn) {
      var input = document.getElementById("domain");
      if (input) {
        input.value = "";
        input.focus();
        clearBtn.classList.remove("visible");
      }
      return;
    }

    // ---- 事件委托：历史记录链接改为 pjax ----
    var histLink = e.target.closest ? e.target.closest(".nw-history-link") : null;
    if (histLink && PJAX_OK) {
      e.preventDefault();
      if (isLoading) return;
      var href = histLink.href;
      // 从链接文本推断域名用于加载文案
      var nameEl = histLink.querySelector(".nw-history-name");
      var dm = nameEl ? nameEl.textContent.trim() : "";
      pjaxLoad(href, { push: true, domain: dm });
      return;
    }

    // ---- 返回顶部 ----
    var backBtn = e.target.closest ? e.target.closest("#back-to-top") : null;
    if (backBtn) {
      window.scrollTo({ behavior: "smooth", top: 0 });
    }
  });

  // ---- 前进/后退 ----
  window.addEventListener("popstate", function () {
    if (!PJAX_OK) return;
    pjaxLoad(window.location.href, { push: false });
  });

  // 首屏建立 pjax 基准状态，保证首次后退能正确恢复
  if (PJAX_OK) {
    try {
      history.replaceState({ pjax: true }, "", window.location.href);
    } catch (err) {}
  }

  // ---- 从 bfcache 恢复时清理残留加载层 ----
  window.addEventListener("pageshow", function (e) {
    if (e.persisted) {
      var existing = document.querySelector(".nw-loading");
      if (existing) existing.remove();
    }
  });

  // ---- 返回顶部按钮显隐（滚动监听） ----
  window.addEventListener("scroll", function () {
    var backToTop = document.getElementById("back-to-top");
    if (!backToTop) return;
    if (document.documentElement.scrollTop > 360) {
      backToTop.classList.add("visible");
    } else {
      backToTop.classList.remove("visible");
    }
  });

  // ---- 首屏初始化搜索框状态 ----
  (window.nwReady || function (f) { window.addEventListener("DOMContentLoaded", f); })(function () {
    syncSearchBox();
  });
})();

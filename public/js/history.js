/**
 * 首页 TODAY 历史查询列表（复刻 next-whois）
 * - 在结果页记录当前查询到 localStorage
 * - 在首页渲染「今天」的历史查询列表
 * - 支持斜杠 "/" 聚焦搜索框、Esc 清除/失焦
 */
(function () {
  "use strict";

  var STORAGE_KEY = "whois-search-history";
  var MAX_ITEMS = 50;

  function loadHistory() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return [];
      var arr = JSON.parse(raw);
      return Array.isArray(arr) ? arr : [];
    } catch (e) {
      return [];
    }
  }

  function saveHistory(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, MAX_ITEMS)));
    } catch (e) {
      /* 忽略写入失败（隐私模式等）*/
    }
  }

  // 根据查询词推断类型徽章
  function detectType(q) {
    var v = (q || "").trim();
    if (/^(\d{1,3}\.){3}\d{1,3}$/.test(v)) return "IPV4";
    if (/:/.test(v) && /^[0-9a-f:]+$/i.test(v)) return "IPV6";
    if (/^as\d+$/i.test(v)) return "ASN";
    if (/\/\d+$/.test(v)) return "CIDR";
    return "DOMAIN";
  }

  function recordSearch(query) {
    var q = (query || "").trim().toLowerCase();
    if (!q) return;
    var list = loadHistory();
    // 去重：移除已存在的同名记录
    list = list.filter(function (item) {
      return item && item.query !== q;
    });
    list.unshift({ query: q, type: detectType(q), ts: Date.now() });
    saveHistory(list);
  }

  function isToday(ts) {
    var d = new Date(ts);
    var now = new Date();
    return (
      d.getFullYear() === now.getFullYear() &&
      d.getMonth() === now.getMonth() &&
      d.getDate() === now.getDate()
    );
  }

  function formatTime(ts) {
    try {
      return new Date(ts).toLocaleTimeString([], {
        hour: "numeric",
        minute: "2-digit",
      });
    } catch (e) {
      return "";
    }
  }

  var GLOBE_SVG =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<circle cx="12" cy="12" r="10"></circle>' +
    '<path d="M2 12h20"></path>' +
    '<path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>' +
    "</svg>";

  function renderHistory() {
    var container = document.getElementById("search-history");
    var listEl = document.getElementById("search-history-list");
    if (!container || !listEl) return;

    var today = loadHistory().filter(function (item) {
      return item && isToday(item.ts);
    });

    if (today.length === 0) {
      container.hidden = true;
      return;
    }

    container.hidden = false;
    listEl.innerHTML = "";

    today.forEach(function (item) {
      var li = document.createElement("li");
      li.className = "nw-history-item";

      var link = document.createElement("a");
      link.className = "nw-history-link";
      // 依赖页面 <base href> 解析为绝对地址
      link.href = encodeURIComponent(item.query);

      link.innerHTML =
        '<span class="nw-history-icon">' + GLOBE_SVG + "</span>" +
        '<span class="nw-history-name"></span>' +
        '<span class="nw-history-meta">' +
        '<span class="nw-history-type">' + item.type + "</span>" +
        '<span class="nw-history-time">' + formatTime(item.ts) + "</span>" +
        "</span>";

      // 安全地写入查询词文本
      link.querySelector(".nw-history-name").textContent = item.query;

      li.appendChild(link);
      listEl.appendChild(li);
    });
  }

  function initHotkeys() {
    var input = document.getElementById("domain");
    if (!input) return;

    document.addEventListener("keydown", function (e) {
      var active = document.activeElement;
      var typing =
        active &&
        (active.tagName === "INPUT" ||
          active.tagName === "TEXTAREA" ||
          active.isContentEditable);

      // "/" 聚焦搜索框
      if (e.key === "/" && !typing) {
        e.preventDefault();
        input.focus();
        input.select();
      }

      // Esc：有内容则清空，否则失焦
      if (e.key === "Escape" && active === input) {
        if (input.value) {
          input.value = "";
          var clearBtn = document.getElementById("domain-clear");
          if (clearBtn) clearBtn.classList.remove("visible");
        } else {
          input.blur();
        }
      }
    });
  }

  window.addEventListener("DOMContentLoaded", function () {
    var hasDomain = document.body.dataset.hasDomain === "1";

    if (hasDomain) {
      // 结果页：记录本次查询
      var input = document.getElementById("domain");
      if (input && input.value) {
        recordSearch(input.value);
      }
    } else {
      // 首页：渲染历史
      renderHistory();
    }

    initHotkeys();
  });
})();

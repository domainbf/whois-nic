(window.nwReady || function (f) { window.addEventListener("DOMContentLoaded", f); })(function () {
  // 多语言访问器（i18n.js 已暴露 window.I18N；缺失时回退到原中文）
  var I18N = window.I18N || {
    lang: "zh",
    dateStyle: "cjk",
    t: function (k, n) {
      var zh = {
        dur_year: n + "年", dur_month: n + "个月", dur_day: n + "天", dur_today: "今天",
        age_title: "已经注册：" + n, remaining_title: "距离过期：" + n,
        date_click_hint: "，点击查看完整时间", time_h: "时", time_m: "分", time_s: "秒",
      };
      return zh[k] != null ? zh[k] : k;
    },
  };

  function formatDuration(seconds) {
    seconds = Number(seconds);
    const years = Math.floor(seconds / (365 * 24 * 60 * 60));
    seconds %= 365 * 24 * 60 * 60;
    const months = Math.floor(seconds / (30 * 24 * 60 * 60));
    seconds %= 30 * 24 * 60 * 60;
    const days = Math.floor(seconds / (24 * 60 * 60));

    const sep = I18N.lang === "en" ? " " : "";
    const formatted = [];
    if (years > 0) formatted.push(I18N.t("dur_year", years));
    if (months > 0) formatted.push(I18N.t("dur_month", months));
    if (days > 0) formatted.push(I18N.t("dur_day", days));
    return formatted.join(sep) || I18N.t("dur_today");
  }

  // 完整日期 + 时分秒（仅当原始数据含具体时间时才追加时分秒）
  function fullDateText(iso8601) {
    const date = new Date(iso8601);
    if (isNaN(date.getTime())) return null;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    // en 用 YYYY-MM-DD；中文/日文用 年月日
    let text =
      I18N.dateStyle === "iso"
        ? `${year}-${month}-${day}`
        : `${year}${I18N.lang === "ja" ? "年" : "年"}${month}月${day}日`;
    if (/\d{2}:\d{2}/.test(iso8601)) {
      const h = String(date.getHours()).padStart(2, "0");
      const m = String(date.getMinutes()).padStart(2, "0");
      const s = String(date.getSeconds()).padStart(2, "0");
      if (I18N.dateStyle === "iso") {
        text += ` ${h}:${m}:${s}`;
      } else {
        text += ` ${h}${I18N.t("time_h")}${m}${I18N.t("time_m")}${s}${I18N.t("time_s")}`;
      }
    }
    return text;
  }

  // 默认显示简洁日期（YYYY-MM-DD，由服务端渲染）；
  // 悬停显示完整时分秒（title 提示），点击在简洁/完整之间切换。
  function enhanceDateElement(elementId) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const iso8601 = el.dataset.iso8601;
    if (!iso8601) return;

    const full = fullDateText(iso8601);
    if (!full) return;

    const simple = el.textContent.trim();
    // 若没有时分秒信息，完整与简洁一致时无需交互
    const hasTime = /\d{2}:\d{2}/.test(iso8601);
    if (!hasTime) {
      el.title = full;
      return;
    }

    el.title = full; // 悬停提示
    el.classList.add("nw-date-interactive");
    el.setAttribute("role", "button");
    el.setAttribute("tabindex", "0");
    el.setAttribute("aria-label", `${simple}${I18N.t("date_click_hint")}`);

    let expanded = false;
    const toggle = function () {
      expanded = !expanded;
      el.textContent = expanded ? full : simple;
    };
    el.addEventListener("click", toggle);
    el.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        toggle();
      }
    });
  }

  enhanceDateElement("creation-date");
  enhanceDateElement("expiration-date");
  enhanceDateElement("updated-date");
  enhanceDateElement("available-date");

  // 域龄药丸：保留紧凑文案（X年X个月X天），通过 title 悬停显示
  const age = document.getElementById("age");
  if (age && age.dataset.seconds) {
    age.title = I18N.t("age_title", formatDuration(age.dataset.seconds));
  }

  const remaining = document.getElementById("remaining");
  if (remaining && remaining.dataset.seconds) {
    const span = remaining.querySelector("span");
    if (span) span.innerText = I18N.t("remaining_title", formatDuration(remaining.dataset.seconds));
  }
});

window.addEventListener("DOMContentLoaded", function () {
  function formatDuration(seconds) {
    seconds = Number(seconds);
    const years = Math.floor(seconds / (365 * 24 * 60 * 60));
    seconds %= 365 * 24 * 60 * 60;
    const months = Math.floor(seconds / (30 * 24 * 60 * 60));
    seconds %= 30 * 24 * 60 * 60;
    const days = Math.floor(seconds / (24 * 60 * 60));

    const formatted = [];
    if (years > 0) formatted.push(`${years}年`);
    if (months > 0) formatted.push(`${months}个月`);
    if (days > 0) formatted.push(`${days}天`);
    return formatted.join("") || "今天";
  }

  // 完整日期 + 时分秒（仅当原始数据含具体时间时才追加时分秒）
  function fullDateText(iso8601) {
    const date = new Date(iso8601);
    if (isNaN(date.getTime())) return null;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    let text = `${year}年${month}月${day}日`;
    if (/\d{2}:\d{2}/.test(iso8601)) {
      const h = String(date.getHours()).padStart(2, "0");
      const m = String(date.getMinutes()).padStart(2, "0");
      const s = String(date.getSeconds()).padStart(2, "0");
      text += ` ${h}时${m}分${s}秒`;
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
    el.setAttribute("aria-label", `${simple}，点击查看完整时间`);

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
    age.title = `已经注册：${formatDuration(age.dataset.seconds)}`;
  }

  const remaining = document.getElementById("remaining");
  if (remaining && remaining.dataset.seconds) {
    const span = remaining.querySelector("span");
    if (span) span.innerText = `距离过期：${formatDuration(remaining.dataset.seconds)}`;
  }
});

window.addEventListener("DOMContentLoaded", async () => {
  const messagePrice = document.getElementById("message-price");

  if (!messagePrice) {
    return;
  }

  const startTime = Date.now();
  const domain = messagePrice.dataset.domain || "";

  // 货币符号映射
  const symbolOf = (cur) => {
    const map = { CNY: "¥", USD: "$", EUR: "€", GBP: "£", JPY: "¥", HKD: "HK$" };
    return map[(cur || "").toUpperCase()] || "";
  };

  // 价格主显示：优先人民币换算值，否则显示原币种价格
  const formatMain = (entry) => {
    if (!entry || entry.price === null || entry.price === undefined) {
      return null;
    }
    if (entry.price_cny !== null && entry.price_cny !== undefined) {
      return `¥${entry.price_cny}`;
    }
    return `${symbolOf(entry.currency)}${entry.price}`;
  };

  // 悬浮提示：展示原始币种价格与最低价注册商
  const formatTip = (entry, label) => {
    if (!entry) {
      return `${label}: 暂无数据`;
    }
    const parts = [];
    if (entry.price !== null && entry.price !== undefined) {
      parts.push(`${entry.currency || ""} ${symbolOf(entry.currency)}${entry.price}`.trim());
    }
    if (entry.registrar) {
      parts.push(`最低价: ${entry.registrar}`);
    }
    return `${label} | ${parts.join(" · ") || "暂无数据"}`;
  };

  const tag = (id, cls, icon, text) =>
    `<button class="message-tag ${cls}" id="${id}">${icon}<span>${text}</span></button>`;

  const icons = {
    register:
      '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>',
    renew:
      '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M22 12A10 10 0 0 0 5.4 5.4"/><path d="M2 12A10 10 0 0 0 18.6 18.6"/></svg>',
    transfer:
      '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>',
  };

  const render = (html) => {
    setTimeout(() => {
      messagePrice.innerHTML = html;
    }, Math.max(0, 500 - (Date.now() - startTime)));
  };

  try {
    const response = await fetch(`/price?domain=${encodeURIComponent(domain)}`);
    if (!response.ok) {
      throw new Error("price request failed");
    }

    const result = await response.json();
    if (result.code !== 200 || !result.data) {
      throw new Error("no price data");
    }

    const d = result.data;
    const items = [
      { key: "register", cls: "message-tag-green", label: "注册" },
      { key: "renew", cls: "message-tag-gray", label: "续费" },
      { key: "transfer", cls: "message-tag-blue", label: "转移" },
    ];

    let innerHTML = "";
    const tips = [];

    for (const it of items) {
      const entry = d[it.key];
      const main = formatMain(entry);
      if (main === null) {
        continue;
      }
      const id = `price-${it.key}`;
      innerHTML += tag(id, it.cls, icons[it.key], `${it.label}: ${main}`);
      tips.push({ id: `#${id}`, content: formatTip(entry, it.label) });
    }

    if (innerHTML === "") {
      throw new Error("empty price data");
    }

    setTimeout(() => {
      messagePrice.innerHTML = innerHTML;
      if (typeof tippy === "function") {
        tips.forEach((t) => tippy(t.id, { content: t.content, placement: "bottom" }));
      }
    }, Math.max(0, 500 - (Date.now() - startTime)));
  } catch {
    render(`<span class="message-tag message-tag-pink">价格获取失败</span>`);
  }
});

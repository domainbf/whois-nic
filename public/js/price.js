(window.nwReady || function (f) { window.addEventListener("DOMContentLoaded", f); })(async () => {
  const messagePrice = document.getElementById("message-price");

  if (!messagePrice) {
    return;
  }

  const domain = messagePrice.dataset.domain || "";

  // 多语言访问器（缺失时回退中文）
  const I18N = window.I18N || {
    t: function (k) {
      const zh = {
        price_register: "注册", price_renew: "续费", price_transfer: "转移",
        price_failed: "价格获取失败", price_nodata: "暂无数据", price_lowest: "最低价注册商",
      };
      return zh[k] != null ? zh[k] : k;
    },
  };

  // 货币符号映射（覆盖常见币种）
  const symbolOf = (cur) => {
    const map = {
      CNY: "¥", USD: "$", EUR: "€", GBP: "£", JPY: "¥", HKD: "HK$",
      AUD: "A$", CAD: "C$", NZD: "NZ$", SGD: "S$", TWD: "NT$", KRW: "₩",
      INR: "₹", RUB: "₽", BRL: "R$", TRY: "₺", ZAR: "R", THB: "฿",
    };
    return map[(cur || "").toUpperCase()] || "";
  };

  // 数字千分位格式化，避免大额数字误读
  const fmtNum = (n) => {
    const num = Number(n);
    if (!isFinite(num)) {
      return String(n);
    }
    return num.toLocaleString("zh-CN", { maximumFractionDigits: 2 });
  };

  // 价格主显示：优先人民币换算值（统一口径，避免外币大额数字误导），否则显示原币种价格
  const formatMain = (entry) => {
    if (!entry || entry.price === null || entry.price === undefined) {
      return null;
    }
    if (entry.price_cny !== null && entry.price_cny !== undefined) {
      return `¥${fmtNum(entry.price_cny)}`;
    }
    return `${symbolOf(entry.currency)}${fmtNum(entry.price)}`;
  };

  // 悬浮提示：明确展示「原币种原价 ≈ 人民币换算价」与最低价注册商，
  // 解决外币大额数字（如 RWF 12,711.87）被误认为人民币价格的困惑。
  const formatTip = (entry, label) => {
    if (!entry) {
      return `${label}: ${I18N.t("price_nodata")}`;
    }
    const parts = [];
    if (entry.price !== null && entry.price !== undefined) {
      const cur = entry.currency || "";
      let priceStr = `${cur} ${symbolOf(cur)}${fmtNum(entry.price)}`.trim();
      // 原币种非人民币且有换算值时，显示换算关系
      if (entry.price_cny !== null && entry.price_cny !== undefined && cur && cur !== "CNY") {
        priceStr += ` ≈ ¥${fmtNum(entry.price_cny)}`;
      }
      parts.push(priceStr);
    }
    if (entry.registrar) {
      parts.push(`${I18N.t("price_lowest")}: ${entry.registrar}`);
    }
    return `${label} | ${parts.join(" · ") || I18N.t("price_nodata")}`;
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

  // 立即渲染，不再人为延迟（加载速度取决于后端聚合，前端不再额外等待）
  // 若该域名被 premium.js 判为溢价并已接管价格（上锁），则普通价不再覆盖，避免竞态。
  const render = (html) => {
    if (messagePrice.dataset.premiumLock === "1") {
      return;
    }
    messagePrice.innerHTML = html;
  };

  try {
    // 客户端超时保护：上游偶发卡顿时不让价格骨架无限停留（10s 后判失败）
    const ctrl = typeof AbortController === "function" ? new AbortController() : null;
    const timer = ctrl ? setTimeout(() => ctrl.abort(), 10000) : null;
    let response;
    try {
      response = await fetch(`/price?domain=${encodeURIComponent(domain)}`, ctrl ? { signal: ctrl.signal } : undefined);
    } finally {
      if (timer) clearTimeout(timer);
    }
    if (!response.ok) {
      throw new Error("price request failed");
    }

    const result = await response.json();
    if (result.code !== 200 || !result.data) {
      throw new Error("no price data");
    }

    const d = result.data;
    const items = [
      { key: "register", cls: "message-tag-price message-tag-price-primary", label: I18N.t("price_register") },
      { key: "renew", cls: "message-tag-price", label: I18N.t("price_renew") },
      { key: "transfer", cls: "message-tag-price", label: I18N.t("price_transfer") },
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

    // 溢价域名已由 premium.js 接管价格，普通价直接放弃渲染
    if (messagePrice.dataset.premiumLock === "1") {
      return;
    }

    // 立即渲染（不再人为延迟）
    messagePrice.innerHTML = innerHTML;

    // 价格行过长时，转移价格会被挤到第二行。检测到换行则移除转移价格，仅保留注册与续费。
    const firstTag = messagePrice.querySelector("button.message-tag");
    const transferEl = messagePrice.querySelector("#price-transfer");
    let activeTips = tips;
    if (firstTag && transferEl && transferEl.offsetTop > firstTag.offsetTop) {
      transferEl.remove();
      activeTips = tips.filter((t) => t.id !== "#price-transfer");
    }

    if (typeof tippy === "function") {
      activeTips.forEach((t) => tippy(t.id, { content: t.content, placement: "bottom" }));
    }
  } catch {
    render(`<span class="message-tag message-tag-pink">${I18N.t("price_failed")}</span>`);
  }
});

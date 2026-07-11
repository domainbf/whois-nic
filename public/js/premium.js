/**
 * 溢价域名检测（前端）
 *
 * 在"可注册"域名结果卡片内异步请求 /premium?domain=，命中溢价时展示
 * 金色"溢价域名"徽章 + 价格（原始货币 + 人民币估算 ≈ ¥…）与数据来源。
 * 非溢价 / 无数据 / 请求失败时静默保持隐藏，不干扰普通可注册结果。
 */
(window.nwReady || function (f) { window.addEventListener("DOMContentLoaded", f); })(async () => {
  const box = document.getElementById("domain-premium");
  if (!box) {
    return;
  }

  const domain = box.dataset.domain || "";
  if (!domain) {
    return;
  }

  // 多语言访问器（缺失时回退中文）
  const I18N = window.I18N || {
    t: function (k) {
      const zh = {
        premium_badge: "溢价域名",
        premium_price: "溢价报价",
        premium_source: "数据来源",
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

  const fmtNum = (n) => {
    const num = Number(n);
    if (!isFinite(num)) {
      return String(n);
    }
    return num.toLocaleString("zh-CN", { maximumFractionDigits: 2 });
  };

  // 皇冠图标（凸显“溢价/高价值”身份）
  const crownIcon =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 18h20"/><path d="m4 15-1-8 5.5 4L12 5l3.5 6L21 7l-1 8z"/></svg>';

  const escapeHtml = (s) =>
    String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  try {
    // 客户端超时保护：上游偶发卡顿时不无限等待（12s 后放弃）
    const ctrl = typeof AbortController === "function" ? new AbortController() : null;
    const timer = ctrl ? setTimeout(() => ctrl.abort(), 12000) : null;
    let response;
    try {
      response = await fetch(`/premium?domain=${encodeURIComponent(domain)}`, ctrl ? { signal: ctrl.signal } : undefined);
    } finally {
      if (timer) clearTimeout(timer);
    }
    if (!response.ok) {
      return;
    }

    const result = await response.json();
    // 仅当确实命中溢价时展示；非溢价 / 无数据静默隐藏
    if (!result || result.code !== 200 || !result.premium) {
      return;
    }

    // 主价格：优先原始货币，其次人民币
    let amountHtml = "";
    if (result.price !== null && result.price !== undefined) {
      const sym = symbolOf(result.currency);
      const cur = result.currency && !sym ? result.currency + " " : "";
      amountHtml = `<span class="domain-premium-amount">${escapeHtml(cur + sym + fmtNum(result.price))}</span>`;
    }

    // 人民币估算（原币种非人民币时展示 ≈ ¥…）
    let cnyHtml = "";
    if (
      result.price_cny !== null &&
      result.price_cny !== undefined &&
      (result.currency || "").toUpperCase() !== "CNY"
    ) {
      cnyHtml = `<span class="domain-premium-cny">≈ ¥${escapeHtml(fmtNum(result.price_cny))}</span>`;
    }

    // 若两者都无价格，仍展示徽章（说明是溢价词但未取到报价）
    const priceRow = amountHtml || cnyHtml
      ? `<div class="domain-premium-price">${amountHtml}${cnyHtml}</div>`
      : "";

    // 数据来源脚注
    const srcMap = { dynadot: "Dynadot", netim: "Netim", "dynadot+netim": "Dynadot + Netim" };
    const srcLabel = srcMap[result.source] || (result.source || "");
    const sourceHtml = srcLabel
      ? `<span class="domain-premium-source">${escapeHtml(I18N.t("premium_source"))}: ${escapeHtml(srcLabel)}</span>`
      : "";

    box.innerHTML =
      `<span class="domain-premium-badge">${crownIcon}<span>${escapeHtml(I18N.t("premium_badge"))}</span></span>` +
      priceRow +
      sourceHtml;
    box.hidden = false;

    // 悬浮说明（若 tippy 可用）：解释溢价含义
    if (typeof tippy === "function") {
      const badge = box.querySelector(".domain-premium-badge");
      if (badge) {
        tippy(badge, { content: I18N.t("premium_tip"), placement: "bottom" });
      }
    }
  } catch {
    // 静默失败：保持隐藏，不影响普通可注册结果展示
  }
});

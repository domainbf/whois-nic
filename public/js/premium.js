/**
 * 溢价域名前端渲染
 *
 * 对"已注册"与"可注册"的单域名查询都会执行：
 *   - 已注册（result.php）：接管 #message-price 价格标签（覆盖普通后缀价），
 *     并在状态行 .nw-status-row 追加金色"溢价域名"徽章。
 *   - 可注册（search-form.php）：填充 #domain-premium 槽位（徽章 + 价格）。
 *
 * 仅当后端 /premium 判定 premium=true 时才介入；普通域名保持原有价格展示不变。
 */
(window.nwReady || function (f) { window.addEventListener("DOMContentLoaded", f); })(async () => {
  const priceSlot = document.getElementById("message-price");   // 已注册上下文
  const availSlot = document.getElementById("domain-premium");  // 可注册上下文
  if (!priceSlot && !availSlot) {
    return;
  }

  const domain =
    (priceSlot && priceSlot.dataset.domain) ||
    (availSlot && availSlot.dataset.domain) ||
    "";
  if (!domain) {
    return;
  }

  // 多语言访问器（缺失时回退中文）
  const FALLBACK_ZH = {
    price_register: "注册", price_renew: "续费", price_transfer: "转移",
    premium_badge: "溢价域名", premium_price: "溢价报价", premium_tip: "",
    premium_source: "数据来源", price_nodata: "暂无数据",
  };
  const I18N = window.I18N || { t: (k) => (FALLBACK_ZH[k] != null ? FALLBACK_ZH[k] : k) };

  const symbolOf = (cur) => {
    const map = {
      CNY: "¥", USD: "$", EUR: "€", GBP: "£", JPY: "¥", HKD: "HK$",
      AUD: "A$", CAD: "C$", NZD: "NZ$", SGD: "S$", TWD: "NT$", KRW: "₩",
    };
    return map[(cur || "").toUpperCase()] || "";
  };
  const fmtNum = (n) => {
    const num = Number(n);
    if (!isFinite(num)) return String(n);
    return num.toLocaleString("zh-CN", { maximumFractionDigits: 2 });
  };
  const escapeHtml = (s) =>
    String(s).replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  // 皇冠图标（溢价标识）
  const CROWN =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 18h20"/><path d="m4 15-1-8 5.5 4L12 5l3.5 6L21 7l-1 8z"/></svg>';

  // ---- 请求溢价数据 ----------------------------------------------------------
  let data;
  try {
    const ctrl = typeof AbortController === "function" ? new AbortController() : null;
    const timer = ctrl ? setTimeout(() => ctrl.abort(), 12000) : null;
    let resp;
    try {
      resp = await fetch(`/premium?domain=${encodeURIComponent(domain)}`, ctrl ? { signal: ctrl.signal } : undefined);
    } finally {
      if (timer) clearTimeout(timer);
    }
    if (!resp.ok) return;
    data = await resp.json();
  } catch {
    return; // 网络/超时失败：静默，保持普通价格展示
  }

  // 非溢价：不介入，普通域名价格由 price.js 正常展示
  if (!data || data.code !== 200 || !data.premium) {
    return;
  }

  const currency = data.currency || "";

  // 价格主显（优先人民币估算，回退原币种）
  const mainText = (amount, cny) => {
    if (cny !== null && cny !== undefined) return `¥${fmtNum(cny)}`;
    if (amount !== null && amount !== undefined) return `${symbolOf(currency)}${fmtNum(amount)}`;
    return null;
  };
  // 原币种原价（用于副显 / 悬浮提示）
  const origText = (amount) => {
    if (amount === null || amount === undefined || !currency) return "";
    return `${currency} ${symbolOf(currency)}${fmtNum(amount)}`.trim();
  };

  const priceRows = [
    { label: I18N.t("price_register"), amount: data.register, cny: data.register_cny, primary: true },
    { label: I18N.t("price_renew"), amount: data.renew, cny: data.renew_cny, primary: false },
  ];

  const srcMap = { dynadot: "Dynadot", netim: "Netim", "dynadot+netim": "Dynadot + Netim" };
  const srcLabel = srcMap[data.source] || (data.source || "");

  const badgeHTML =
    `<span class="nw-premium-badge">${CROWN}<span>${escapeHtml(I18N.t("premium_badge"))}</span></span>`;

  // ===== 已注册上下文：接管价格标签 + 状态行插入徽章 =====
  if (priceSlot) {
    // 上锁：告知 price.js 放弃普通价渲染，避免竞态覆盖
    priceSlot.dataset.premiumLock = "1";

    let tags = "";
    for (const r of priceRows) {
      const main = mainText(r.amount, r.cny);
      if (main === null) continue;
      const cls = "message-tag message-tag-price message-tag-premium" + (r.primary ? " message-tag-premium-primary" : "");
      const orig = origText(r.amount);
      const title = orig ? ` title="${escapeHtml(r.label + ": " + orig)}"` : "";
      tags += `<button class="${cls}"${title}>${CROWN}<span>${escapeHtml(r.label + ": " + main)}</span></button>`;
    }
    if (tags !== "") {
      priceSlot.innerHTML = tags;
    }

    // 状态行追加溢价徽章（去重）
    const statusRow = document.querySelector(".nw-status-row");
    if (statusRow && !statusRow.querySelector(".nw-premium-badge")) {
      statusRow.insertAdjacentHTML("beforeend", badgeHTML);
      const badgeEl = statusRow.querySelector(".nw-premium-badge");
      if (badgeEl && typeof tippy === "function" && I18N.t("premium_tip")) {
        tippy(badgeEl, { content: I18N.t("premium_tip"), placement: "bottom" });
      }
    }
  }

  // ===== 可注册上下文：填充溢价槽位 =====
  if (availSlot) {
    const reg = priceRows[0];
    const main = mainText(reg.amount, reg.cny);
    const orig = origText(reg.amount);
    let html = badgeHTML;
    if (main !== null) {
      html +=
        `<div class="domain-premium-price">` +
        `<span class="domain-premium-amount">${escapeHtml(main)}</span>` +
        (orig ? `<span class="domain-premium-cny">${escapeHtml(orig)}</span>` : "") +
        `</div>`;
    }
    if (srcLabel) {
      html += `<div class="domain-premium-source">${escapeHtml(I18N.t("premium_source") + ": " + srcLabel)}</div>`;
    }
    availSlot.innerHTML = html;
    availSlot.hidden = false;

    if (typeof tippy === "function" && I18N.t("premium_tip")) {
      const badgeEl = availSlot.querySelector(".nw-premium-badge");
      if (badgeEl) tippy(badgeEl, { content: I18N.t("premium_tip"), placement: "bottom" });
    }
  }
});

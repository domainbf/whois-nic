      window.addEventListener("DOMContentLoaded", async () => {
  const messagePrice = document.getElementById("message-price");

  if (!messagePrice) {
    return;
  }

  const startTime = Date.now();

  const domain = messagePrice.dataset.domain || "";

  try {
    const response = await fetch(`https://api.tian.hu/whois.php?domain=${encodeURIComponent(domain)}&action=checkPrice`);

    if (!response.ok) {
      throw new Error();
    }

    const data = await response.json();

    if (data.code !== 200) {
      throw new Error();
    }

    let innerHTML = "";

    const isPremium = data.data.premium === "true";

    let registerUSD = data.data.register_usd;
    let renewUSD = data.data.renew_usd;
    let registerCNY = data.data.register;
    let renewCNY = data.data.renew;

    // 清理数据，如果为 "unknow" 或空，则显示问号或中文提示
    registerUSD = (registerUSD === "unknow" || !registerUSD) ? "?" : registerUSD;
    renewUSD = (renewUSD === "unknow" || !renewUSD) ? "?" : renewUSD;
    registerCNY = (registerCNY === "unknow" || !registerCNY) ? "不可查" : registerCNY; 
    renewCNY = (renewCNY === "unknow" || !renewCNY) ? "不可查" : renewCNY;          

    // --- 1. 议价/特殊 (Premium Icon) ---
    if (isPremium) {
      // 保持原有星形图标，新增中文提示
      innerHTML = `
        <button class="message-tag message-tag-purple" id="price-premium">
          <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
            <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z" />
          </svg>
          <span style="margin-left: 0.3em;">议价/特殊</span>
        </button>
      `;
    }

    // --- 2. 注册价格 (Register Icon & CNY Display) ---
    // 使用一个简单的加号图标作为注册（添加）的象征
    innerHTML += `
      ${innerHTML}
      <button class="message-tag message-tag-gray" id="price-register">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>注册: ¥${registerCNY}</span>
      </button>
    `;

    // --- 3. 续订价格 (Renew Icon & CNY Display) ---
    // 使用一个循环箭头图标作为续订的象征
    innerHTML += `
      <button class="message-tag message-tag-gray" id="price-renew">
        <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
          <path d="M21.5 2v6h-6"/>
          <path d="M2.5 22v-6h6"/>
          <path d="M22 12A10 10 0 0 0 5.4 5.4"/>
          <path d="M2 12A10 10 0 0 0 18.6 18.6"/>
        </svg>
        <span>续订: ¥${renewCNY}</span>
      </button>
    `;
    
    // --- Tooltip 初始化 ---
    setTimeout(() => {
      messagePrice.innerHTML = innerHTML;

      if (isPremium) {
        tippy("#price-premium", {
          content: "Premium (USD: Negotiable/Special)",
          placement: "bottom",
        });
      }
      // 注册：悬停/点击显示美元
      tippy("#price-register", {
        content: `USD: $${registerUSD}`,
        placement: "bottom"
      });
      // 续订：悬停/点击显示美元
      tippy("#price-renew", {
        content: `USD: $${renewUSD}`,
        placement: "bottom"
      });
    }, Math.max(0, 500 - (Date.now() - startTime)));
  } catch {
    setTimeout(() => {
      messagePrice.innerHTML = `<span class="message-tag message-tag-pink">Failed to fetch prices</span>`;
    }, Math.max(0, 500 - (Date.now() - startTime)));
  }
});

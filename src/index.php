const dataSourceWHOIS = document.getElementById("data-source-whois");
        const dataSourceRDAP = document.getElementById("data-source-rdap");
        const rawDataWHOIS = document.getElementById("raw-data-whois");
        const rawDataRDAP = document.getElementById("raw-data-rdap");

        if (dataSourceWHOIS && dataSourceRDAP && rawDataWHOIS && rawDataRDAP) {
          dataSourceWHOIS.addEventListener("click", () => {
            if (dataSourceWHOIS.classList.contains("segmented-item-selected")) {
              return;
            }
            dataSourceWHOIS.classList.add("segmented-item-selected");
            dataSourceRDAP.classList.remove("segmented-item-selected");
            rawDataWHOIS.style.display = "block";
            rawDataRDAP.style.display = "none";
          });
          
          dataSourceRDAP.addEventListener("click", () => {
            if (dataSourceRDAP.classList.contains("segmented-item-selected")) {
              return;
            }
            dataSourceWHOIS.classList.remove("segmented-item-selected");
            dataSourceRDAP.classList.add("segmented-item-selected");
            rawDataWHOIS.style.display = "none";
            rawDataRDAP.style.display = "block";
          });
        }

        function linkifyRawData(element) {
          if (element && typeof linkifyHtml !== 'undefined') {
            element.innerHTML = linkifyHtml(element.innerHTML, {
              rel: "nofollow noopener noreferrer",
              target: "_blank",
              validate: {
                url: (value) => /^https?:\/\//.test(value),
              },
            });
          }
        }

        if (rawDataWHOIS) linkifyRawData(rawDataWHOIS);
        if (rawDataRDAP) linkifyRawData(rawDataRDAP);
      });
    </script>
  <?php endif; ?>
  <?php if ($fetchPrices): ?>
    <script>
      window.addEventListener("DOMContentLoaded", async () => {
        const messagePrice = document.getElementById("message-price");

        if (!messagePrice) {
          return;
        }

        const startTime = Date.now();

        try {
          const response = await fetch("https://api.tian.hu/whois.php?domain=<?= urlencode($domain); ?>&action=checkPrice");

          if (!response.ok) {
            throw new Error();
          }

          const data = await response.json();

          if (data.code !== "200") {
            throw new Error();
          }

          let innerHTML = "";

          const isPremium = data.data.premium === "true";

          if (isPremium) {
            innerHTML = `
              <button class="message-tag message-tag-purple" id="price-premium">
                <svg width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                  <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z" />
                </svg>
              </button>
            `;
          }

          let registerUSD = data.data.register_usd;
          let renewUSD = data.data.renew_usd;
          let registerCNY = data.data.register;
          let renewCNY = data.data.renew;

          registerUSD = registerUSD === "unknow" ? "?" : registerUSD;
          renewUSD = renewUSD === "unknow" ? "?" : renewUSD;
          registerCNY = registerCNY === "unknow" ? "?" : registerCNY;
          renewCNY = renewCNY === "unknow" ? "?" : renewCNY;

          innerHTML = `
            ${innerHTML}
            <button class="message-tag message-tag-gray" id="price-register">
              <span>注册: $${registerUSD}</span>
            </button>
            <button class="message-tag message-tag-gray" id="price-renew">
              <span>续费: $${renewUSD}</span>
            </button>
          `;

          setTimeout(() => {
            messagePrice.innerHTML = innerHTML;

            if (isPremium && typeof tippy !== 'undefined') {
              tippy("#price-premium", {
                content: "溢价",
                placement: "bottom",
              });
            }
            if (typeof tippy !== 'undefined') {
              tippy("#price-register", {
                content: `¥${registerCNY}`,
                placement: "bottom"
              });
              tippy("#price-renew", {
                content: `¥${renewCNY}`,
                placement: "bottom"
              });
            }
          }, Math.max(0, 500 - (Date.now() - startTime)));
        } catch {
          setTimeout(() => {
            messagePrice.innerHTML = `<span class="message-tag message-tag-pink">获取价格失败</span>`;
          }, Math.max(0, 500 - (Date.now() - startTime)));
        }
      });
    </script>
  <?php endif; ?>
  <?= CUSTOM_SCRIPT ?>
</body>

</html>

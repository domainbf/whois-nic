      window.addEventListener("DOMContentLoaded", function() {
        if (typeof tippy !== 'undefined') {
          tippy.setDefaultProps({
            arrow: false,
            offset: [0, 8],
            maxWidth: 200,
            allowHTML: false,
            theme: 'light-border',
            content: (reference) => reference.innerHTML,
          });
        }

        function updateDateElementTooltip(elementId) {
          const element = document.getElementById(elementId);
          if (element) {
            const iso8601 = element.dataset.iso8601;
            if (iso8601) {
              const date = new Date(iso8601);
              const year = date.getFullYear();
              const month = String(date.getMonth() + 1).padStart(2, "0");
              const day = String(date.getDate()).padStart(2, "0");
              const hours = String(date.getHours()).padStart(2, "0");
              const minutes = String(date.getMinutes()).padStart(2, "0");
              const seconds = String(date.getSeconds()).padStart(2, "0");
              const formattedDateTime = `${hours}时${minutes}分${seconds}秒`;


              if (typeof tippy !== 'undefined') {
                tippy(`#${elementId}`, {
                  content: formattedDateTime,
                  placement: "right",
                  appendTo: () => document.body,
                });
              }
            }
          }
        }

        updateDateElementTooltip("creation-date");
        updateDateElementTooltip("expiration-date");
        updateDateElementTooltip("updated-date");
        updateDateElementTooltip("available-date");

        function updateSecondsElementTooltip(elementId, prefix) {
          const element = document.getElementById(elementId);
          if (element) {
            const seconds = element.dataset.seconds;
            if (seconds) {
              let days = seconds / 24 / 60 / 60;
              days = seconds < 0 ? Math.ceil(days) : Math.floor(days);
              if (seconds < 0 && days === 0) {
                days = "-0";
              }
              if (typeof tippy !== 'undefined') {
                tippy(`#${elementId}`, {
                  content: `${prefix}: ${days} 天`,
                  placement: "right",
                });
              }
            }
          }
        }

        updateSecondsElementTooltip("age", "已经注册");
        updateSecondsElementTooltip("remaining", "距离过期");

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

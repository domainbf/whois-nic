    window.addEventListener("DOMContentLoaded", function() {
      const domainElement = document.getElementById("domain");
      const domainClearElement = document.getElementById("domain-clear");

      if (domainElement && domainElement.value) {
        domainClearElement.classList.add("visible");
      }

      if (domainElement) {
        domainElement.addEventListener("input", (e) => {
          if (e.target.value) {
            domainClearElement.classList.add("visible");
          } else {
            domainClearElement.classList.remove("visible");
          }
        });
      }

      if (domainClearElement) {
        domainClearElement.addEventListener("click", () => {
          if (domainElement) {
            domainElement.focus();
            domainElement.select();
            if (!document.execCommand("delete", false)) {
              domainElement.setRangeText("");
            }
            domainClearElement.classList.remove("visible");
          }
        });
      }

      const checkboxNames = ["whois", "rdap", "prices", "beian"];
      const hasDomain = document.body.dataset.hasDomain === "1";
      if (hasDomain) {
        checkboxNames.forEach((name) => {
          const checkbox = document.getElementById(`checkbox-${name}`);
          if (checkbox) {
            localStorage.setItem(`checkbox-${name}`, +checkbox.checked);
          }
        });
      } else {
        const whoisValue = localStorage.getItem("checkbox-whois") || "0";
        const rdapValue = localStorage.getItem("checkbox-rdap") || "0";

        checkboxNames.forEach((name) => {
          const checkbox = document.getElementById(`checkbox-${name}`);
          if (checkbox) {
            // 首次访问（无历史偏好）：默认开启 WHOIS、RDAP 与价格，备案保持关闭
            if (!+whoisValue && !+rdapValue && name !== "beian") {
              checkbox.checked = true;
            } else {
              checkbox.checked = localStorage.getItem(`checkbox-${name}`) === "1";
            }
          }
        });
      }

      const form = document.getElementById("form");
      const searchIcon = document.getElementById("search-icon");
      if (form && searchIcon) {
        form.addEventListener("submit", () => {
          searchIcon.classList.add("searching");
        });
      }

      const backToTop = document.getElementById("back-to-top");
      if (backToTop) {
        backToTop.addEventListener("click", () => {
          window.scrollTo({
            behavior: "smooth",
            top: 0,
          });
        });

        window.addEventListener("scroll", () => {
          if (document.documentElement.scrollTop > 360) {
            if (!backToTop.classList.contains("visible")) {
              backToTop.classList.add("visible");
            }
          } else {
            if (backToTop.classList.contains("visible")) {
              backToTop.classList.remove("visible");
            }
          }
        });
      }
    });

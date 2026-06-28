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

      // 多语言访问器（i18n.js 已暴露 window.I18N；缺失时回退中文）
      const I18N = window.I18N || {
        t: function (k, v) {
          const zh = {
            loading_title: "正在查询 " + (v || "") + "…",
            loading_subtitle: "RDAP · WHOIS · DNS",
            loading_step1: "连接 RDAP 服务器…",
            loading_step2: "查询 WHOIS 数据库…",
            loading_step3: "解析注册信息…",
          };
          return zh[k] != null ? zh[k] : k;
        },
      };

      // 构建并显示查询加载动画（提交后、页面跳转前内联展示在内容区，跳转完成后自然消失）
      function showLoadingOverlay(domainValue) {
        if (document.querySelector(".nw-loading")) return;
        const overlay = document.createElement("div");
        overlay.className = "nw-loading";
        overlay.setAttribute("role", "status");
        overlay.setAttribute("aria-live", "polite");
        overlay.innerHTML =
          '<div class="nw-loading-card">' +
          '<div class="nw-loading-globe">' +
          '<svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">' +
          '<circle class="nw-lg-pulse" cx="60" cy="60" r="34"></circle>' +
          '<circle class="nw-lg-pulse" cx="60" cy="60" r="34"></circle>' +
          '<circle class="nw-lg-sphere" cx="60" cy="60" r="30"></circle>' +
          '<g class="nw-lg-grid">' +
          '<circle cx="60" cy="60" r="30"></circle>' +
          '<line x1="30" y1="60" x2="90" y2="60"></line>' +
          '<ellipse cx="60" cy="60" rx="30" ry="11"></ellipse>' +
          '<ellipse cx="60" cy="60" rx="11" ry="30"></ellipse>' +
          "</g>" +
          '<path class="nw-lg-arc" d="M60 18 a42 42 0 0 1 38 24"></path>' +
          "</svg>" +
          "</div>" +
          '<p class="nw-loading-title"></p>' +
          '<p class="nw-loading-sub"></p>' +
          '<ul class="nw-loading-steps">' +
          '<li class="nw-loading-step is-active"></li>' +
          '<li class="nw-loading-step"></li>' +
          '<li class="nw-loading-step"></li>' +
          "</ul>" +
          "</div>";

        overlay.querySelector(".nw-loading-title").textContent = I18N.t("loading_title", domainValue);
        overlay.querySelector(".nw-loading-sub").textContent = I18N.t("loading_subtitle");
        const steps = overlay.querySelectorAll(".nw-loading-step");
        steps[0].textContent = I18N.t("loading_step1");
        steps[1].textContent = I18N.t("loading_step2");
        steps[2].textContent = I18N.t("loading_step3");

        // 内联展示：清空内容区（移除旧结果），将加载卡放入页面主体，与整体风格一致
        const mainEl = document.querySelector("main");
        const historyEl = document.getElementById("search-history");
        if (historyEl) historyEl.setAttribute("hidden", "");
        // 移除上一次查询遗留的结果提示卡（位于搜索框下方、main 之外）
        const staleInfo = document.querySelector(".domain-info-box");
        if (staleInfo) staleInfo.remove();
        if (mainEl) {
          mainEl.innerHTML = "";
          mainEl.appendChild(overlay);
        } else {
          document.body.appendChild(overlay);
        }

        // 步骤依次点亮，模拟查询进度（实际进度取决于服务端响应）
        let idx = 0;
        const timer = setInterval(function () {
          idx++;
          if (idx >= steps.length) {
            clearInterval(timer);
            return;
          }
          steps[idx].classList.add("is-active");
        }, 900);
      }

      if (form && searchIcon) {
        form.addEventListener("submit", () => {
          searchIcon.classList.add("searching");
          const val = domainElement ? domainElement.value.trim() : "";
          if (val) showLoadingOverlay(val);
        });
      }

      // 从结果页返回上一页时（bfcache 恢复）移除残留的加载层
      window.addEventListener("pageshow", function (e) {
        if (e.persisted) {
          const existing = document.querySelector(".nw-loading");
          if (existing) existing.remove();
        }
      });

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

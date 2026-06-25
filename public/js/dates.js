      window.addEventListener("DOMContentLoaded", function() {
        function formatDuration(seconds) {
          const years = Math.floor(seconds / (365 * 24 * 60 * 60));
          seconds %= (365 * 24 * 60 * 60);
          const months = Math.floor(seconds / (30 * 24 * 60 * 60));
          seconds %= (30 * 24 * 60 * 60);
          const days = Math.floor(seconds / (24 * 60 * 60));
          seconds %= (24 * 60 * 60);
          const hours = Math.floor(seconds / (60 * 60));
          seconds %= (60 * 60);
          const minutes = Math.floor(seconds / 60);
          seconds %= 60;
          const formatted = [];
          if (years > 0) formatted.push(`${years}年`);
          if (months > 0) formatted.push(`${months}个月`);
          if (days > 0) formatted.push(`${days}天`);

          return formatted.join("");
        }

        function updateDateElementText(elementId) {
          const element = document.getElementById(elementId);
          if (element) {
            const iso8601 = element.dataset.iso8601;
            if (iso8601) {
              const date = new Date(iso8601);
              const year = date.getFullYear();
              const month = String(date.getMonth() + 1).padStart(2, "0");
              const day = String(date.getDate()).padStart(2, "0");

              element.innerText = `${year}年${month}月${day}日`;
            }
          }
        }

        updateDateElementText("creation-date");
        updateDateElementText("expiration-date");
        updateDateElementText("updated-date");
        updateDateElementText("available-date");

        const age = document.getElementById("age");
        if (age) {
            const ageSeconds = age.dataset.seconds;
            if (ageSeconds) {
                age.querySelector("span").innerText = `已经注册：${formatDuration(ageSeconds)}`;
            }
        }

        const remaining = document.getElementById("remaining");
        if (remaining) {
            const remainingSeconds = remaining.dataset.seconds;
            if (remainingSeconds) {
                remaining.querySelector("span").innerText = `距离过期：${formatDuration(remainingSeconds)}`;
            }
        }
      });

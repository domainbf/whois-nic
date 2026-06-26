window.addEventListener("DOMContentLoaded", async () => {
    const messageBeiAn = document.getElementById("message-beian");

    if (!messageBeiAn) {
        return;
    }

    messageBeiAn.style.transition = "opacity 0.3s ease";
    messageBeiAn.style.opacity = "0";

    const startTime = Date.now();

    const domain = messageBeiAn.dataset.domain || "";

    try {
        // 修改为您的API地址
        const apiUrl = `https://beian.bug.kz/query/web?search=${encodeURIComponent(domain)}`;
        console.log("正在请求API:", apiUrl);
        
        const response = await fetch(apiUrl);

        if (!response.ok) {
            throw new Error("网络请求失败，状态码: " + response.status);
        }

        const data = await response.json();
        console.log("API响应数据:", data);

        if (data.code !== 200) {
            throw new Error(data.msg || "查询失败");
        }

        let innerHTML = "";
        const beianData = data.params && data.params.list && data.params.list.length > 0 ? data.params.list[0] : null;

        if (beianData) {
            const mainLicence = beianData.mainLicence || "无";
            const domainName = beianData.domain || "未知";
            const serviceLicence = beianData.serviceLicence || "无";
            const natureName = beianData.natureName || "未知";
            const unitName = beianData.unitName || "未知";
            const updateRecordTime = beianData.updateRecordTime ? new Date(beianData.updateRecordTime).toLocaleDateString() : "未知";
            const policeLicence = beianData.policeLicence || "无";

            innerHTML = `
              <div class="beian-info">
                <span class="beian-domain">${domainName}</span>
                <span class="beian-number">${mainLicence}</span>
                <span class="beian-tip">点击查看详情</span>
              </div>
            `;
        } else {
            innerHTML = `
              <div class="beian-info no-beian">
                <span class="beian-domain">${domain}</span>
                <span class="no-beian-text">无备案信息</span>
              </div>
            `;
        }

        setTimeout(() => {
            messageBeiAn.innerHTML = innerHTML;
            messageBeiAn.style.opacity = "1";

            if (beianData && typeof tippy !== 'undefined') {
                tippy(".beian-info", {
                    content: `
                      <div class="beian-tooltip">
                        <div class="tooltip-header">备案详细信息</div>
                        <div class="tooltip-content">
                          <div class="tooltip-item"><span class="tooltip-label">域名:</span><span class="tooltip-value">${beianData.domain || "未知"}</span></div>
                          <div class="tooltip-item"><span class="tooltip-label">备案号:</span><span class="tooltip-value">${beianData.mainLicence || "无"}</span></div>
                          <div class="tooltip-item"><span class="tooltip-label">服务许可证:</span><span class="tooltip-value">${beianData.serviceLicence || "无"}</span></div>
                          <div class="tooltip-item"><span class="tooltip-label">单位性质:</span><span class="tooltip-value">${beianData.natureName || "未知"}</span></div>
                          <div class="tooltip-item"><span class="tooltip-label">主办单位:</span><span class="tooltip-value">${beianData.unitName || "未知"}</span></div>
                          <div class="tooltip-item"><span class="tooltip-label">审核时间:</span><span class="tooltip-value">${beianData.updateRecordTime ? new Date(beianData.updateRecordTime).toLocaleDateString() : "未知"}</span></div>
                          <div class="tooltip-item"><span class="tooltip-label">公安局备案:</span><span class="tooltip-value">${beianData.policeLicence || "无"}</span></div>
                        </div>
                      </div>
                    `,
                    placement: "bottom",
                    allowHTML: true,
                    theme: 'beian-tooltip',
                    maxWidth: 400
                });
            }
        }, Math.max(0, 500 - (Date.now() - startTime)));
    } catch (error) {
        console.error("备案查询错误:", error);
        setTimeout(() => {
            messageBeiAn.innerHTML = `
              <div class="beian-info error">
                <span class="beian-domain">${domain}</span>
                <span class="error-text">获取失败: ${error.message}</span>
              </div>
            `;
            messageBeiAn.style.opacity = "1";
        }, Math.max(0, 500 - (Date.now() - startTime)));
    }
});

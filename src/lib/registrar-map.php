<?php

/**
 * 全球域名注册商官网映射
 *
 * 覆盖亚洲、欧洲、美洲、非洲、大洋洲的大中小型注册商，
 * 用于在 WHOIS/RDAP 未提供 Registrar URL 时，根据注册商名称智能识别官网，
 * 方便用户点击直达对应注册商网站进行注册。
 *
 * 匹配策略：先做精确归一化匹配，再做关键词包含匹配（兼容 "GoDaddy.com, LLC" 等带后缀的写法）。
 */

/**
 * 归一化注册商名称：转小写、去除常见公司后缀与标点，便于匹配。
 */
function registrar_normalize(string $name): string
{
    $s = mb_strtolower(trim($name));
    // 去掉常见公司形式后缀
    $s = preg_replace('/\b(co\.,?\s*ltd\.?|ltd\.?|llc\.?|inc\.?|corp\.?|corporation|company|gmbh|s\.?a\.?s?\.?|s\.?r\.?l\.?|b\.?v\.?|pte\.?|pty\.?|limited|co\.)\b/u', '', $s);
    $s = str_replace(['有限公司', '股份有限公司', '科技', '集团', '（', '）'], '', $s);
    // 去标点与多余空白
    $s = preg_replace('/[^\p{L}\p{N}]+/u', '', $s);
    return $s;
}

/**
 * 注册商关键词 => 官网。键使用小写关键词，匹配时用包含判断。
 * 顺序无关；较具体的关键词应尽量唯一。
 */
function registrar_keyword_map(): array
{
    return [
        // ===== 全球 / 北美 =====
        'godaddy' => 'https://www.godaddy.com',
        'namecheap' => 'https://www.namecheap.com',
        'networksolutions' => 'https://www.networksolutions.com',
        'network solutions' => 'https://www.networksolutions.com',
        'cloudflare' => 'https://www.cloudflare.com',
        'google' => 'https://domains.google',
        'squarespace' => 'https://domains.squarespace.com',
        'markmonitor' => 'https://www.markmonitor.com',
        'csc corporate' => 'https://www.cscdbs.com',
        'cscglobal' => 'https://www.cscdbs.com',
        'name.com' => 'https://www.name.com',
        'namecom' => 'https://www.name.com',
        'tucows' => 'https://www.tucows.com',
        'opensrs' => 'https://opensrs.com',
        'enom' => 'https://www.enom.com',
        'dynadot' => 'https://www.dynadot.com',
        'porkbun' => 'https://porkbun.com',
        'hover' => 'https://www.hover.com',
        'namesilo' => 'https://www.namesilo.com',
        'epik' => 'https://www.epik.com',
        'register.com' => 'https://www.register.com',
        'registercom' => 'https://www.register.com',
        'ionos' => 'https://www.ionos.com',
        '1&1' => 'https://www.ionos.com',
        'sav.com' => 'https://www.sav.com',
        'spaceship' => 'https://www.spaceship.com',
        'wix' => 'https://www.wix.com',
        'shopify' => 'https://www.shopify.com',
        'bluehost' => 'https://www.bluehost.com',
        'hostgator' => 'https://www.hostgator.com',
        'dreamhost' => 'https://www.dreamhost.com',
        'web.com' => 'https://www.web.com',
        'rebel' => 'https://www.rebel.com',
        'pananames' => 'https://www.pananames.com',
        'gname' => 'https://www.gname.com',
        'megalayer' => 'https://www.megalayer.com',
        'namebright' => 'https://www.namebright.com',

        // ===== 中国 =====
        'alibaba' => 'https://wanwang.aliyun.com',
        'aliyun' => 'https://wanwang.aliyun.com',
        'hichina' => 'https://wanwang.aliyun.com',
        '阿里' => 'https://wanwang.aliyun.com',
        '万网' => 'https://wanwang.aliyun.com',
        'tencent' => 'https://dnspod.cloud.tencent.com',
        'dnspod' => 'https://www.dnspod.cn',
        '腾讯' => 'https://dnspod.cloud.tencent.com',
        'west263' => 'https://www.west.cn',
        'west.cn' => 'https://www.west.cn',
        '西部数码' => 'https://www.west.cn',
        'xinnet' => 'https://www.xinnet.com',
        '新网' => 'https://www.xinnet.com',
        'ename' => 'https://www.ename.net',
        '易名' => 'https://www.ename.net',
        'huawei' => 'https://www.huaweicloud.com',
        '华为' => 'https://www.huaweicloud.com',
        'baidu' => 'https://cloud.baidu.com',
        '百度' => 'https://cloud.baidu.com',
        'bizcn' => 'https://www.bizcn.com',
        '商务中国' => 'https://www.bizcn.com',
        '22.cn' => 'https://www.22.cn',
        '爱名' => 'https://www.22.cn',
        'juming' => 'https://www.juming.com',
        '聚名' => 'https://www.juming.com',
        'nicenic' => 'https://www.nicenic.net',
        'zhcn' => 'https://www.zh-cn.com',
        '景安' => 'https://www.zhujiwu.com',
        'volcengine' => 'https://www.volcengine.com',
        '火山引擎' => 'https://www.volcengine.com',

        // ===== 日本 / 韩国 =====
        'gmo' => 'https://www.onamae.com',
        'onamae' => 'https://www.onamae.com',
        'お名前' => 'https://www.onamae.com',
        'sakura' => 'https://www.sakura.ad.jp',
        'value-domain' => 'https://www.value-domain.com',
        'value domain' => 'https://www.value-domain.com',
        'interlink' => 'https://muumuu-domain.com',
        'muumuu' => 'https://muumuu-domain.com',
        'gabia' => 'https://www.gabia.com',
        'hosting.kr' => 'https://www.hosting.kr',
        'whois.co.kr' => 'https://whois.co.kr',
        'inames' => 'https://www.inames.co.kr',

        // ===== 东南亚 / 南亚 =====
        'resellerclub' => 'https://www.resellerclub.com',
        'bigrock' => 'https://www.bigrock.in',
        'net4' => 'https://www.net4.in',
        'znetlive' => 'https://www.znetlive.com',
        'exabytes' => 'https://www.exabytes.com',
        'vodien' => 'https://www.vodien.com',
        'crazy domains' => 'https://www.crazydomains.com',
        'crazydomains' => 'https://www.crazydomains.com',

        // ===== 欧洲 =====
        'ovh' => 'https://www.ovhcloud.com',
        'gandi' => 'https://www.gandi.net',
        'hetzner' => 'https://www.hetzner.com',
        'united-domains' => 'https://www.united-domains.de',
        'united domains' => 'https://www.united-domains.de',
        'key-systems' => 'https://www.key-systems.net',
        'key systems' => 'https://www.key-systems.net',
        'internetx' => 'https://www.internetx.com',
        'eurodns' => 'https://www.eurodns.com',
        'openprovider' => 'https://www.openprovider.com',
        'hosting concepts' => 'https://www.openprovider.com',
        'realtime register' => 'https://www.realtimeregister.com',
        'realtimeregister' => 'https://www.realtimeregister.com',
        'ascio' => 'https://www.ascio.com',
        '123-reg' => 'https://www.123-reg.co.uk',
        '123 reg' => 'https://www.123-reg.co.uk',
        'fasthosts' => 'https://www.fasthosts.co.uk',
        'aruba' => 'https://www.aruba.it',
        'register.it' => 'https://www.register.it',
        'registerit' => 'https://www.register.it',
        'one.com' => 'https://www.one.com',
        'onecom' => 'https://www.one.com',
        'loopia' => 'https://www.loopia.com',
        'active 24' => 'https://www.active24.com',
        'active24' => 'https://www.active24.com',
        'namebay' => 'https://www.namebay.com',
        'netim' => 'https://www.netim.com',
        'infomaniak' => 'https://www.infomaniak.com',
        'strato' => 'https://www.strato.de',
        'domaindiscount24' => 'https://www.domaindiscount24.com',
        'hexonet' => 'https://www.hexonet.net',
        'cronon' => 'https://www.cronon.net',
        'nameshield' => 'https://www.nameshield.com',
        'safebrands' => 'https://www.safebrands.com',
        'mijndomein' => 'https://www.mijndomein.nl',
        'transip' => 'https://www.transip.nl',
        'combell' => 'https://www.combell.com',

        // ===== 非洲 =====
        'aos rwanda' => 'https://market.aos.rw',
        'web4africa' => 'https://www.web4africa.com',
        'truehost' => 'https://truehost.com',
        'hostafrica' => 'https://www.hostafrica.com',
        'domains.co.za' => 'https://domains.co.za',
        'afrihost' => 'https://www.afrihost.com',
        'sostec' => 'https://www.sostec.so',

        // ===== 美洲（拉美）=====
        'registro.br' => 'https://registro.br',
        'registrobr' => 'https://registro.br',
        'nic mexico' => 'https://www.nic.mx',
        'nic.mx' => 'https://www.nic.mx',
        'donweb' => 'https://donweb.com',
        'neubox' => 'https://www.neubox.com',

        // ===== 大洋洲 =====
        'netregistry' => 'https://www.netregistry.com.au',
        'ventraip' => 'https://ventraip.com.au',
        'synergy wholesale' => 'https://synergywholesale.com',
        'synergywholesale' => 'https://synergywholesale.com',
    ];
}

/**
 * 根据注册商名称查找其官网。找不到返回空字符串。
 */
function registrar_website(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }

    $map = registrar_keyword_map();
    $normName = registrar_normalize($name);
    $lower = mb_strtolower($name);

    // 1) 归一化精确匹配
    foreach ($map as $kw => $url) {
        if (registrar_normalize($kw) === $normName) {
            return $url;
        }
    }

    // 2) 关键词包含匹配（原始小写串包含关键词，或归一化串互相包含）
    foreach ($map as $kw => $url) {
        $kwLower = mb_strtolower($kw);
        $kwNorm = registrar_normalize($kw);
        if ($kwNorm === '') {
            continue;
        }
        if (strpos($lower, $kwLower) !== false || ($kwNorm !== '' && strpos($normName, $kwNorm) !== false)) {
            return $url;
        }
    }

    return '';
}

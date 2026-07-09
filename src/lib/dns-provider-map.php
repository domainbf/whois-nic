<?php

/**
 * 全球 DNS / 名称服务器（NS）提供商识别映射
 *
 * 根据域名的 NS 主机名（如 net.dnspod.net、ns1.cloudflare.com）识别其背后的
 * DNS 托管 / 云厂商 / 注册商，返回统一的品牌名称与官网链接，便于在结果页
 * 展示"DNS 提供商"徽标并点击直达。
 *
 * 匹配策略：对 NS 主机名做小写化后，按“子串包含”匹配关键词。
 * 关键词应尽量选取 NS 主机名中稳定、唯一的片段（多为提供商主域名或专有子域）。
 * 顺序有意义：更具体 / 更易冲突的关键词应排在更靠前，命中即返回。
 */

/**
 * DNS 提供商关键词表。
 * 键：出现在 NS 主机名中的稳定子串（小写）。
 * 值：[显示名称, 官网 URL]。
 */
function dns_provider_keyword_map(): array
{
    return [
        // ===== CDN / 全球云厂商 =====
        'ns.cloudflare'   => ['Cloudflare', 'https://www.cloudflare.com'],
        'cloudflare'      => ['Cloudflare', 'https://www.cloudflare.com'],
        'awsdns'          => ['AWS Route 53', 'https://aws.amazon.com/route53'],
        'amazonaws'       => ['AWS Route 53', 'https://aws.amazon.com/route53'],
        'azure-dns'       => ['Azure DNS', 'https://azure.microsoft.com/products/dns'],
        'azuredns'        => ['Azure DNS', 'https://azure.microsoft.com/products/dns'],
        'ns.google'       => ['Google Cloud DNS', 'https://cloud.google.com/dns'],
        'googledomains'   => ['Google Domains', 'https://domains.google'],
        'google.com'      => ['Google', 'https://domains.google'],
        'googledns'       => ['Google', 'https://cloud.google.com/dns'],
        'vercel-dns'      => ['Vercel', 'https://vercel.com'],
        'netlify'         => ['Netlify', 'https://www.netlify.com'],
        'nsone.net'       => ['NS1 (IBM)', 'https://ns1.com'],
        'nsone'           => ['NS1 (IBM)', 'https://ns1.com'],
        'ultradns'        => ['UltraDNS (Vercara)', 'https://vercara.com'],
        'akamai'          => ['Akamai', 'https://www.akamai.com'],
        'akam.net'        => ['Akamai', 'https://www.akamai.com'],
        'akadns'          => ['Akamai', 'https://www.akamai.com'],
        'edgekey'         => ['Akamai', 'https://www.akamai.com'],
        'fastly'          => ['Fastly', 'https://www.fastly.com'],
        'incapdns'        => ['Imperva (Incapsula)', 'https://www.imperva.com'],
        'impervadns'      => ['Imperva', 'https://www.imperva.com'],
        'cdn77'           => ['CDN77', 'https://www.cdn77.com'],
        'stackpath'       => ['StackPath', 'https://www.stackpath.com'],
        'gcorelabs'       => ['Gcore', 'https://gcore.com'],
        'gcore'           => ['Gcore', 'https://gcore.com'],
        'bunny'           => ['BunnyCDN', 'https://bunny.net'],
        'bunnyinfra'      => ['BunnyCDN', 'https://bunny.net'],

        // ===== 云 / VPS 主机商 =====
        'digitalocean'    => ['DigitalOcean', 'https://www.digitalocean.com'],
        'linode'          => ['Linode (Akamai)', 'https://www.linode.com'],
        'vultr'           => ['Vultr', 'https://www.vultr.com'],
        'he.net'          => ['Hurricane Electric', 'https://dns.he.net'],
        'oraclecloud'     => ['Oracle Cloud', 'https://www.oracle.com/cloud/networking/dns'],
        'oracle'          => ['Oracle Cloud', 'https://www.oracle.com/cloud/networking/dns'],
        'hetzner'         => ['Hetzner', 'https://www.hetzner.com'],
        'your-server.de'  => ['Hetzner', 'https://www.hetzner.com'],
        'ovh.net'         => ['OVHcloud', 'https://www.ovhcloud.com'],
        'ovh.ca'          => ['OVHcloud', 'https://www.ovhcloud.com'],
        'anycast.me'      => ['OVHcloud', 'https://www.ovhcloud.com'],
        'scaleway'        => ['Scaleway', 'https://www.scaleway.com'],
        'online.net'      => ['Scaleway', 'https://www.scaleway.com'],
        'contabo'         => ['Contabo', 'https://contabo.com'],
        'ionos'           => ['IONOS', 'https://www.ionos.com'],
        'ui-dns'          => ['IONOS', 'https://www.ionos.com'],
        'kasserver'       => ['All-Inkl', 'https://all-inkl.com'],
        'netcup'          => ['netcup', 'https://www.netcup.de'],
        'leaseweb'        => ['Leaseweb', 'https://www.leaseweb.com'],

        // ===== 中国厂商 =====
        'dnspod'          => ['DNSPod（腾讯云）', 'https://www.dnspod.cn'],
        'qcloud'          => ['腾讯云', 'https://cloud.tencent.com'],
        'tencent'         => ['腾讯云', 'https://cloud.tencent.com'],
        'alidns'          => ['阿里云', 'https://www.aliyun.com'],
        'aliyun'          => ['阿里云', 'https://www.aliyun.com'],
        'hichina'         => ['阿里云（万网）', 'https://wanwang.aliyun.com'],
        'dnsv'            => ['阿里云', 'https://www.aliyun.com'],
        'huaweicloud'     => ['华为云', 'https://www.huaweicloud.com'],
        'myhuaweicloud'   => ['华为云', 'https://www.huaweicloud.com'],
        'baidubce'        => ['百度智能云', 'https://cloud.baidu.com'],
        'bdydns'          => ['百度智能云', 'https://cloud.baidu.com'],
        'jdcloud'         => ['京东云', 'https://www.jdcloud.com'],
        'volcdns'         => ['火山引擎', 'https://www.volcengine.com'],
        'volcengine'      => ['火山引擎', 'https://www.volcengine.com'],
        'dnspai'          => ['帝恩思 DNSPai', 'https://www.dnspai.com'],
        'cloudxns'        => ['CloudXNS', 'https://www.cloudxns.net'],
        'dnsdun'          => ['DNSdun', 'https://www.dnsdun.com'],
        'dns.la'          => ['DNS.LA', 'https://www.dns.la'],
        '51dns'           => ['帝恩思', 'https://www.dnsla.com'],
        'ename.com'       => ['易名', 'https://www.ename.net'],
        'ename.net'       => ['易名', 'https://www.ename.net'],
        'west.cn'         => ['西部数码', 'https://www.west.cn'],
        'myhostadmin'     => ['西部数码', 'https://www.west.cn'],
        'xincache'        => ['新网', 'https://www.xinnet.com'],
        'xinnet'          => ['新网', 'https://www.xinnet.com'],
        'cnmsn'           => ['美橙互联', 'https://www.cndns.com'],
        '22.cn'           => ['爱名网', 'https://www.22.cn'],
        'sfndns'          => ['三网 DNS', 'https://www.sfn.cn'],

        // ===== 注册商 / DNS 托管 =====
        'domaincontrol'   => ['GoDaddy', 'https://www.godaddy.com'],
        'godaddy'         => ['GoDaddy', 'https://www.godaddy.com'],
        'registrar-servers' => ['Namecheap', 'https://www.namecheap.com'],
        'namecheap'       => ['Namecheap', 'https://www.namecheap.com'],
        'name-services'   => ['eNom', 'https://www.enom.com'],
        'enom'            => ['eNom', 'https://www.enom.com'],
        'dnsowl'          => ['NameSilo', 'https://www.namesilo.com'],
        'namesilo'        => ['NameSilo', 'https://www.namesilo.com'],
        'dynadot'         => ['Dynadot', 'https://www.dynadot.com'],
        'porkbun'         => ['Porkbun', 'https://porkbun.com'],
        'name.com'        => ['Name.com', 'https://www.name.com'],
        'gandi.net'       => ['Gandi', 'https://www.gandi.net'],
        'hostinger'       => ['Hostinger', 'https://www.hostinger.com'],
        'bluehost'        => ['Bluehost', 'https://www.bluehost.com'],
        'hostgator'       => ['HostGator', 'https://www.hostgator.com'],
        'siteground'      => ['SiteGround', 'https://www.siteground.com'],
        'wordpress'       => ['WordPress.com', 'https://wordpress.com/domains'],
        'wixdns'          => ['Wix', 'https://www.wix.com'],
        'squarespace'     => ['Squarespace', 'https://domains.squarespace.com'],
        'shopify'         => ['Shopify', 'https://www.shopify.com'],
        'wpengine'        => ['WP Engine', 'https://wpengine.com'],
        'flywheel'        => ['Flywheel', 'https://getflywheel.com'],
        'dreamhost'       => ['DreamHost', 'https://www.dreamhost.com'],
        'namebright'      => ['NameBright', 'https://www.namebright.com'],
        'name-servicesdns' => ['eNom', 'https://www.enom.com'],
        'domains.google'  => ['Google Domains', 'https://domains.google'],
        'spaceship'       => ['Spaceship', 'https://www.spaceship.com'],
        'dns.registrar.amazon' => ['Amazon Registrar', 'https://registrar.amazon.com'],
        'reg.ru'          => ['REG.RU', 'https://www.reg.ru'],
        'regruhosting'    => ['REG.RU', 'https://www.reg.ru'],
        'nic.ru'          => ['RU-CENTER', 'https://www.nic.ru'],
        'beget'           => ['Beget', 'https://beget.com'],
        'timeweb'         => ['Timeweb', 'https://timeweb.com'],
        'onamae'          => ['お名前.com (GMO)', 'https://www.onamae.com'],
        'gmoserver'       => ['GMO', 'https://www.gmo.jp'],
        'sakura.ne.jp'    => ['さくら (SAKURA)', 'https://www.sakura.ad.jp'],
        'value-domain'    => ['Value Domain', 'https://www.value-domain.com'],
        'gabia'           => ['Gabia', 'https://www.gabia.com'],
        'cafe24'          => ['Cafe24', 'https://www.cafe24.com'],
        'gname'           => ['Gname', 'https://www.gname.com'],
        'webnic'          => ['WebNIC', 'https://www.webnic.cc'],
        'ovhcloud'        => ['OVHcloud', 'https://www.ovhcloud.com'],
        'combell'         => ['Combell', 'https://www.combell.com'],
        'transip'         => ['TransIP', 'https://www.transip.nl'],
        'openprovider'    => ['Openprovider', 'https://www.openprovider.com'],
        'infomaniak'      => ['Infomaniak', 'https://www.infomaniak.com'],
        'loopia'          => ['Loopia', 'https://www.loopia.com'],
        'one.com'         => ['One.com', 'https://www.one.com'],
        'strato'          => ['STRATO', 'https://www.strato.de'],
        'hosteurope'      => ['Host Europe', 'https://www.hosteurope.de'],
        'domainfactory'   => ['DomainFactory', 'https://www.df.eu'],
        'inwx'            => ['INWX', 'https://www.inwx.com'],
        'core-networks'   => ['Core Networks', 'https://www.core-networks.de'],
        'schlundtech'     => ['SchlundTech', 'https://www.schlundtech.com'],
        'zone.eu'         => ['Zone', 'https://www.zone.eu'],

        // ===== 专业 DNS 服务商 =====
        'dnsmadeeasy'     => ['DNS Made Easy', 'https://dnsmadeeasy.com'],
        'easydns'         => ['easyDNS', 'https://easydns.com'],
        'zilore'          => ['Zilore', 'https://zilore.com'],
        'constellix'      => ['Constellix', 'https://constellix.com'],
        'nsproxy'         => ['NSProxy', 'https://www.nsproxy.com'],
        'clouddns'        => ['ClouDNS', 'https://www.cloudns.net'],
        'dnsimple'        => ['DNSimple', 'https://dnsimple.com'],
        'dns.he.net'      => ['Hurricane Electric', 'https://dns.he.net'],
        'rage4'           => ['Rage4 DNS', 'https://rage4.com'],
        'geoscaling'      => ['GeoScaling', 'https://www.geoscaling.com'],
        'luadns'          => ['LuaDNS', 'https://www.luadns.com'],
        'dns.com'         => ['DNS.COM', 'https://www.dns.com'],
        'p0ns'            => ['Bunny DNS', 'https://bunny.net'],
        'zoneedit'        => ['ZoneEdit', 'https://www.zoneedit.com'],
        'no-ip'           => ['No-IP', 'https://www.noip.com'],
        'dnsexit'         => ['DNSExit', 'https://www.dnsexit.com'],
        'afraid.org'      => ['FreeDNS', 'https://freedns.afraid.org'],
        'duckdns'         => ['DuckDNS', 'https://www.duckdns.org'],
        'desec.io'        => ['deSEC', 'https://desec.io'],
        'selectel'        => ['Selectel', 'https://selectel.ru'],
        'yandex'          => ['Yandex', 'https://connect.yandex.com'],
        'mail.ru'         => ['VK (Mail.ru)', 'https://hosting.vk.com'],

        // ===== 电信 / ISP / 其他区域 =====
        'worldnic'        => ['Network Solutions', 'https://www.networksolutions.com'],
        'networksolutions' => ['Network Solutions', 'https://www.networksolutions.com'],
        'register.com'    => ['Register.com', 'https://www.register.com'],
        'domainmonster'   => ['Domain Monster', 'https://www.domainmonster.com'],
        'markmonitor'     => ['MarkMonitor', 'https://www.markmonitor.com'],
        'cscdns'          => ['CSC', 'https://www.cscdbs.com'],
        'nsone.info'      => ['NS1 (IBM)', 'https://ns1.com'],
        'centralnic'      => ['CentralNic', 'https://www.centralnic.com'],
        'key-systems'     => ['Key-Systems', 'https://www.key-systems.net'],
        'rrpproxy'        => ['RRPproxy', 'https://www.rrpproxy.net'],
        'internetx'       => ['InterNetX', 'https://www.internetx.com'],
        'ascio'           => ['Ascio', 'https://www.ascio.com'],
        'nameshield'      => ['Nameshield', 'https://www.nameshield.com'],
        'eurodns'         => ['EuroDNS', 'https://www.eurodns.com'],
        'gransy'          => ['Gransy', 'https://subreg.cz'],
        'wedos'           => ['WEDOS', 'https://www.wedos.com'],
        'forpsi'          => ['FORPSI', 'https://www.forpsi.com'],
        'active24'        => ['Active 24', 'https://www.active24.com'],
        'registro.br'     => ['Registro.br', 'https://registro.br'],
        'locaweb'         => ['Locaweb', 'https://www.locaweb.com.br'],
        'uolhost'         => ['UOL Host', 'https://www.uolhost.uol.com.br'],
        'kinghost'        => ['KingHost', 'https://king.host'],
        'nic.mx'          => ['NIC México', 'https://www.nic.mx'],
        'crazydomains'    => ['Crazy Domains', 'https://www.crazydomains.com'],
        'ventraip'        => ['VentraIP', 'https://ventraip.com.au'],
        'synergywholesale' => ['Synergy Wholesale', 'https://synergywholesale.com'],
        'melbourneit'     => ['Melbourne IT', 'https://www.melbourneit.com.au'],
        'afrihost'        => ['Afrihost', 'https://www.afrihost.com'],
        'web4africa'      => ['Web4Africa', 'https://www.web4africa.com'],
        'truehost'        => ['Truehost', 'https://truehost.com'],
        'exabytes'        => ['Exabytes', 'https://www.exabytes.com'],
        'niagahoster'     => ['Niagahoster', 'https://www.niagahoster.co.id'],
        'matbao'          => ['Mắt Bão', 'https://www.matbao.net'],
        'pavietnam'       => ['PA Vietnam', 'https://www.pavietnam.vn'],
        'resellerclub'    => ['ResellerClub', 'https://www.resellerclub.com'],
        'bigrock'         => ['BigRock', 'https://www.bigrock.in'],
        'hostinger.in'    => ['Hostinger', 'https://www.hostinger.in'],
    ];
}

/**
 * 根据单个 NS 主机名识别 DNS 提供商。
 * 返回 ['name' => 显示名, 'url' => 官网]，无法识别时返回 ['name' => '', 'url' => '']。
 */
function dns_provider_detect(string $nameserver): array
{
    $n = strtolower(trim($nameserver));
    if ($n === '') {
        return ['name' => '', 'url' => ''];
    }
    foreach (dns_provider_keyword_map() as $kw => $info) {
        if (strpos($n, $kw) !== false) {
            return ['name' => $info[0], 'url' => $info[1]];
        }
    }
    return ['name' => '', 'url' => ''];
}

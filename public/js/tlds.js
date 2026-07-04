/**
 * 全球顶级域名（TLD）数据集，供搜索联想使用。
 * - POPULAR：无后缀输入时默认推荐（按热度排序）
 * - ALL：用于校验"是否为真实后缀"与前缀智能推荐（大致按热度排序，
 *   前缀匹配时热门后缀自然靠前）。涵盖主流 gTLD / new gTLD / ccTLD。
 *
 * 挂载到 window.NW_TLDS，autocomplete.js 会读取；如需扩展直接往数组里加即可。
 */
(function () {
  "use strict";

  var POPULAR = [
    "com", "net", "org", "io", "ai", "co", "dev", "app", "xyz", "cn",
    "com.cn", "top", "vip", "cc", "me", "info", "biz", "tv", "online", "site",
  ];

  // 按热度大致排序；前缀匹配（startsWith）会优先返回排在前面的热门后缀
  var ALL = [
    // ---- 主流 gTLD ----
    "com", "net", "org", "info", "biz", "pro", "name", "mobi", "asia", "tel",
    // ---- 热门 new gTLD ----
    "io", "ai", "co", "dev", "app", "xyz", "top", "vip", "club", "shop",
    "store", "online", "site", "tech", "cloud", "space", "website", "fun",
    "live", "life", "world", "today", "news", "blog", "wiki", "art", "design",
    "studio", "agency", "media", "digital", "network", "systems", "solutions",
    "services", "group", "team", "company", "center", "email", "chat", "link",
    "click", "one", "run", "page", "web", "host", "domains", "download",
    "software", "code", "tools", "games", "game", "play", "video", "music",
    "photo", "photos", "pics", "pictures", "gallery", "graphics", "cool",
    "plus", "pub", "red", "ink", "wtf", "rocks", "ninja", "guru", "expert",
    "coach", "academy", "school", "college", "education", "institute",
    "university", "training", "courses", "study", "science", "engineer",
    "engineering", "energy", "finance", "financial", "fund", "capital",
    "money", "cash", "credit", "loans", "insurance", "bank", "trade",
    "market", "markets", "exchange", "business", "enterprises", "ventures",
    "holdings", "partners", "management", "consulting", "marketing", "sales",
    "deals", "sale", "discount", "coupons", "gift", "gifts", "shopping",
    "boutique", "fashion", "clothing", "shoes", "jewelry", "watch", "luxury",
    "beauty", "hair", "makeup", "salon", "spa", "fitness", "yoga", "health",
    "healthcare", "clinic", "dental", "care", "medical", "doctor", "surgery",
    "hospital", "pharmacy", "diet", "fit", "run",
    "food", "menu", "restaurant", "cafe", "coffee", "bar", "pub", "beer",
    "wine", "kitchen", "recipes", "pizza", "catering",
    "travel", "tours", "vacations", "holiday", "flights", "cruises", "hotel",
    "hotels", "villas", "rentals", "camp", "city", "town", "place", "land",
    "house", "homes", "home", "estate", "realty", "properties", "property",
    "apartments", "condos", "villa", "build", "builders", "construction",
    "contractors", "furniture", "garden", "kitchen", "lighting", "tools",
    "law", "legal", "lawyer", "attorney", "tax", "accountant", "accountants",
    "auto", "car", "cars", "autos", "bike", "motorcycles", "boats", "yachts",
    "tires", "parts", "repair", "mechanic",
    "family", "kids", "baby", "mom", "dad", "wedding", "dating", "love",
    "singles", "party", "events", "tickets", "band", "dance", "fans", "fan",
    "community", "social", "forum", "reviews", "guide", "directory", "tips",
    "how", "faq", "help", "support", "contact", "email",
    // ---- 中国相关 ----
    "cn", "com.cn", "net.cn", "org.cn", "gov.cn", "ac.cn", "xn--fiqs8s",
    "xn--fiqz9s", "xn--55qx5d", "xn--io0a7i", "xn--55qw42g",
    "wang", "ren", "xin", "site", "shop", "fun", "ltd", "group", "cool",
    // ---- 常见 ccTLD ----
    "us", "uk", "co.uk", "org.uk", "me.uk", "ca", "au", "com.au", "net.au",
    "de", "fr", "es", "it", "nl", "be", "ch", "at", "se", "no", "dk", "fi",
    "pl", "cz", "sk", "hu", "ro", "gr", "pt", "ie", "ru", "ua", "by",
    "jp", "co.jp", "kr", "co.kr", "hk", "com.hk", "tw", "com.tw", "sg",
    "com.sg", "my", "com.my", "id", "co.id", "th", "co.th", "vn", "com.vn",
    "ph", "in", "co.in", "pk", "bd", "lk", "np",
    "br", "com.br", "mx", "com.mx", "ar", "com.ar", "cl", "co", "com.co",
    "pe", "ve", "ec", "uy", "bo",
    "za", "co.za", "ng", "ke", "eg", "ma", "gh", "tz",
    "nz", "co.nz", "il", "co.il", "tr", "com.tr", "ir", "sa", "com.sa",
    "ae", "co.ae", "qa", "kw", "bh", "om", "jo", "lb",
    "is", "lt", "lv", "ee", "hr", "si", "rs", "ba", "mk", "al", "md", "ge",
    "am", "az", "kz", "uz", "mn",
    "eu", "asia", "tv", "cc", "me", "co", "io", "ai", "sh", "ac", "gg", "je",
    "im", "fm", "am", "to", "ws", "nu", "la", "gl", "ly", "so", "st", "gs",
    "vc", "ag", "bz", "ms", "tc", "pw", "cx", "dj", "mu", "re", "yt",
    // 更多 ccTLD（补全常见字母开头，尤其 s 开头国别）
    "se", "sg", "sk", "si", "su", "sn", "sm", "sb", "sv", "sr", "sc", "sz",
    "sd", "sy", "ss", "sl", "sx", "sj", "so", "st",
    "af", "ad", "ao", "ai", "aw", "ax", "ba", "bf", "bg", "bh", "bi", "bj",
    "bm", "bn", "bo", "bq", "bs", "bt", "bw", "cf", "cg", "cd", "ci", "ck",
    "cm", "cr", "cu", "cv", "cw", "cy", "dm", "do", "dz", "er", "et", "fj",
    "fk", "fo", "ga", "gd", "gf", "gi", "gm", "gn", "gp", "gq", "gt", "gu",
    "gw", "gy", "hm", "hn", "ht", "iq", "ki", "km", "kn", "kp", "kr", "ky",
    "kg", "kh", "lc", "li", "lr", "ls", "lu", "mc", "mg", "mh", "ml", "mm",
    "mo", "mp", "mq", "mr", "mt", "mv", "mw", "mz", "na", "nc", "ne", "nf",
    "ni", "nr", "pa", "pf", "pg", "pm", "pn", "pr", "ps", "py", "rw", "sy",
    "td", "tf", "tg", "tj", "tk", "tl", "tm", "tn", "tt", "tz", "ug", "uz",
    "va", "vg", "vi", "vu", "wf", "ye", "zm", "zw",
    // ---- 品牌 / 专业 ----
    "google", "app", "dev", "page", "new", "how", "soy", "meme", "boo",
    "gle", "prof", "phd", "rsvp", "day", "eat", "channel", "fly", "here",
    "ing", "mov", "nexus", "you", "zip", "foo", "dad", "esq", "search",
  ];

  // 去重（保留首次出现顺序 → 保留热度序）
  var seen = {};
  var all = [];
  for (var i = 0; i < ALL.length; i++) {
    var t = ALL[i].toLowerCase();
    if (!seen[t]) {
      seen[t] = true;
      all.push(t);
    }
  }

  window.NW_TLDS = {
    popular: POPULAR,
    all: all,
    set: seen, // 快速判断某后缀是否为真实 TLD
  };
})();

<?php
define("DEFAULT_EXTENSION", getenv("DEFAULT_EXTENSION") ?: "");

define("SITE_TITLE", getenv("SITE_TITLE") ?: "愚蠢的域名查询网站");

define("SITE_SHORT_TITLE", getenv("SITE_SHORT_TITLE") ?: "WHOIS");

define("SITE_DESCRIPTION", getenv("SITE_DESCRIPTION") ?: "A simple WHOIS domain lookup website with strong TLD compatibility.");

define("SITE_KEYWORDS", getenv("SITE_KEYWORDS") ?: "whois, rdap, domain lookup, open source, api, tld, cctld, .com, .net, .org");

define("SITE_PASSWORD", getenv("SITE_PASSWORD") ?: "");

define("BASE", getenv("BASE") ?: "/");

define("CUSTOM_HEAD", getenv("CUSTOM_HEAD") ?: "");

define("CUSTOM_SCRIPT", getenv("CUSTOM_SCRIPT") ?: "");

define("CUSTOM_HEAD_LOGIN", getenv("CUSTOM_HEAD_LOGIN") ?: "");

define("CUSTOM_SCRIPT_LOGIN", getenv("CUSTOM_SCRIPT_LOGIN") ?: "");

define("HOSTED_ON", getenv("HOSTED_ON") ?: "");

define("HOSTED_ON_URL", getenv("HOSTED_ON_URL") ?: "");

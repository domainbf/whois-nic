<?php
// nic.md（摩尔多瓦）WHOIS 采用按列对齐格式，字段名内含多个空格，例如：
//   Domain  name:   hello.md
//   Domain state:   Inactive RenewProhibited
//   Registered on:  2014-03-31
//   Expires on:     2027-03-31
//   NameServer:     ns1.atom.com
//
// 早期版本曾重写 getBaseRegExp 为 "/(?:$pattern)(.+)/i"——该正则不消费冒号，
// 会把冒号连同其后内容一起捕获（域名变成 ":"、状态变成 ": OK"、NS 变成 ":"），
// 导致页面上域名标题、状态标签、NS 值全部异常。
//
// 现基类 Parser::getBaseRegExp 已足够健壮：字段名空格容错（[\t ]+）、正确消费冒号，
// 且关键词表已覆盖 .md 所需的全部字段：
//   domain name / registered on / expires on / domain state / nameserver
// 基类 getStatus 也支持将同一行空格分隔的多个状态词拆分为独立可翻译项。
// 因此 .md 无需任何特殊解析逻辑，直接继承基类即可正确解析。
class ParserMD extends Parser
{
}

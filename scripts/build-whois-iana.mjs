#!/usr/bin/env node
// Rebuild src/data/whois-servers-iana.json from authoritative IANA sources.
//
// IANA does not publish a single WHOIS-server JSON file. The authoritative
// data lives in the IANA Root Zone Database, where each TLD page exposes a
// "WHOIS Server:" field. This script:
//   1. Downloads the official TLD list (tlds-alpha-by-domain.txt).
//   2. Fetches each TLD's root-db page and extracts the WHOIS Server field.
//   3. Writes a sorted { tld: whoisServer } map (empty string when none).
//
// The output format (2-space indent, alphabetically sorted keys) matches the
// existing file so day-to-day diffs stay minimal.

import { writeFile } from "node:fs/promises";
import { fileURLToPath } from "node:url";
import { dirname, join } from "node:path";

const __dirname = dirname(fileURLToPath(import.meta.url));
const OUT_FILE = join(__dirname, "..", "src", "data", "whois-servers-iana.json");

const TLD_LIST_URL = "https://data.iana.org/TLD/tlds-alpha-by-domain.txt";
const ROOT_DB = (tld) => `https://www.iana.org/domains/root/db/${tld}.html`;

const CONCURRENCY = 12;
const MAX_RETRIES = 3;
const TIMEOUT_MS = 20000;
const USER_AGENT = "whois-nic-updater/1.0 (+https://github.com/hellouy/whois-nic)";

async function fetchText(url, { retries = MAX_RETRIES } = {}) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), TIMEOUT_MS);
    try {
      const res = await fetch(url, {
        headers: { "user-agent": USER_AGENT, accept: "text/plain,text/html" },
        signal: controller.signal,
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      return await res.text();
    } catch (err) {
      if (attempt === retries) throw err;
      await new Promise((r) => setTimeout(r, 500 * attempt));
    } finally {
      clearTimeout(timer);
    }
  }
}

function parseTldList(text) {
  return text
    .split("\n")
    .map((l) => l.trim())
    .filter((l) => l && !l.startsWith("#"))
    // The list is punycode/uppercase; keep the ASCII (xn--) form, lowercased.
    .map((l) => l.toLowerCase());
}

function extractWhoisServer(html) {
  // The root-db page renders: <b>WHOIS Server:</b> whois.nic.example<br/>
  const match = html
    .replace(/\r?\n/g, " ")
    .match(/WHOIS Server:<\/b>\s*([^<\s]+)/i);
  if (!match) return "";
  const server = match[1].trim().toLowerCase();
  return server && server !== "null" ? server : "";
}

async function mapWithConcurrency(items, limit, worker) {
  const results = new Array(items.length);
  let index = 0;
  let done = 0;
  async function run() {
    while (index < items.length) {
      const current = index++;
      results[current] = await worker(items[current], current);
      done++;
      if (done % 100 === 0 || done === items.length) {
        process.stderr.write(`  ...${done}/${items.length}\n`);
      }
    }
  }
  await Promise.all(Array.from({ length: limit }, run));
  return results;
}

async function main() {
  process.stderr.write("Downloading IANA TLD list...\n");
  const tlds = parseTldList(await fetchText(TLD_LIST_URL));
  process.stderr.write(`Found ${tlds.length} TLDs. Fetching WHOIS servers...\n`);

  const entries = await mapWithConcurrency(tlds, CONCURRENCY, async (tld) => {
    try {
      const html = await fetchText(ROOT_DB(tld));
      return [tld, extractWhoisServer(html)];
    } catch (err) {
      // Preserve the TLD with an empty server rather than dropping it, so a
      // transient failure never silently removes a TLD from the dataset.
      process.stderr.write(`  ! ${tld}: ${err.message}\n`);
      return [tld, ""];
    }
  });

  entries.sort((a, b) => (a[0] < b[0] ? -1 : a[0] > b[0] ? 1 : 0));
  const map = Object.fromEntries(entries);

  const withServer = entries.filter(([, s]) => s).length;
  process.stderr.write(
    `Done. ${entries.length} TLDs, ${withServer} with a WHOIS server.\n`
  );

  // Basic sanity guard: bail out if the fetch clearly failed en masse, to
  // avoid overwriting good data with a broken (mostly-empty) dataset.
  if (withServer < entries.length * 0.4) {
    throw new Error(
      `Refusing to write: only ${withServer}/${entries.length} TLDs resolved a WHOIS server (network issue?).`
    );
  }

  await writeFile(OUT_FILE, JSON.stringify(map, null, 2) + "\n", "utf8");
  process.stderr.write(`Wrote ${OUT_FILE}\n`);
}

main().catch((err) => {
  process.stderr.write(`Fatal: ${err.stack || err.message}\n`);
  process.exit(1);
});

#!/usr/bin/env node

/**
 * Automated Accessibility (A11y) Test Runner for Antragsgrün
 *
 * Runs Pa11y (WCAG 2.1 AA with HTML CodeSniffer and Axe runners) against
 * configured application endpoints.
 *
 * Usage:
 *   node bin/test-a11y.mjs
 *   node bin/test-a11y.mjs --url=http://localhost:12380/
 *   node bin/test-a11y.mjs --url=http://localhost:12380/ --url=http://localhost:12380/motion/1
 *   node bin/test-a11y.mjs --threshold=5
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import pa11y from 'pa11y';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const rootDir = path.resolve(__dirname, '..');

// Parse CLI flags (--url may be given multiple times)
const args = process.argv.slice(2);
const customUrls = [];
let threshold = 0;

for (const arg of args) {
  if (arg.startsWith('--url=')) {
    customUrls.push(arg.slice(arg.indexOf('=') + 1));
  } else if (arg.startsWith('--threshold=')) {
    threshold = parseInt(arg.slice(arg.indexOf('=') + 1), 10) || 0;
  }
}

// Load pa11y.json configuration if present
const configPath = path.join(rootDir, 'pa11y.json');
let config = {
  standard: 'WCAG2AA',
  runners: ['htmlcs', 'axe'],
  timeout: 30000,
  wait: 500,
  chromeLaunchConfig: {
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  },
};

if (fs.existsSync(configPath)) {
  try {
    const loaded = JSON.parse(fs.readFileSync(configPath, 'utf8'));
    config = { ...config, ...loaded };
  } catch (e) {
    console.warn(`[a11y] Warning: Could not parse pa11y.json: ${e.message}`);
  }
}

const targetUrls = customUrls.length > 0
  ? customUrls
  : [
      process.env.A11Y_BASE_URL || 'http://localhost:12380/',
    ];

console.log(`\n🔍 Running Automated A11y (WCAG 2.1 AA) Scans...\n`);

let totalErrors = 0;

for (const url of targetUrls) {
  console.log(`Scanning: ${url}`);
  try {
    const results = await pa11y(url, config);
    const errors = results.issues.filter((i) => i.type === 'error');
    const warnings = results.issues.filter((i) => i.type === 'warning');
    const notices = results.issues.filter((i) => i.type === 'notice');

    console.log(`  → Errors: ${errors.length}, Warnings: ${warnings.length}, Notices: ${notices.length}`);

    if (errors.length > 0) {
      totalErrors += errors.length;
      for (const err of errors.slice(0, 10)) {
        console.log(`    ❌ [${err.code}] ${err.message} (${err.selector})`);
      }
      if (errors.length > 10) {
        console.log(`    ... and ${errors.length - 10} more issues.`);
      }
    }
  } catch (err) {
    console.error(`  ❌ Scan failed on ${url}: ${err.message}`);
    process.exit(1);
  }
}

console.log(`\n═══════════════════════════════════════════════════════════════`);
console.log(`Total A11y Errors: ${totalErrors} (Threshold: ${threshold})`);
console.log(`═══════════════════════════════════════════════════════════════\n`);

if (totalErrors > threshold) {
  console.error(`❌ A11y validation failed with ${totalErrors} errors (exceeded threshold of ${threshold}).`);
  process.exit(1);
} else {
  console.log(`✅ A11y validation passed!`);
  process.exit(0);
}

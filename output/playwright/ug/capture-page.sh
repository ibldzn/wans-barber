#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 2 || $# -gt 4 ]]; then
  echo "Usage: $0 <url> <path> [height] [width]" >&2
  exit 1
fi

url="$1"
path="$2"
height="${3:-1600}"
width="${4:-1440}"

export CODEX_HOME="${CODEX_HOME:-$HOME/.codex}"
export PLAYWRIGHT_CLI_SESSION="${PLAYWRIGHT_CLI_SESSION:-ugdoc}"

pwcli="$CODEX_HOME/skills/playwright/scripts/playwright_cli.sh"

"$pwcli" run-code "async page => {
  await page.setViewportSize({ width: ${width}, height: ${height} });
  await page.goto('${url}');
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(900);
  await page.screenshot({ path: '${path}', fullPage: true });
}"

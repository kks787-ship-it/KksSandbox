#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/../.."
OUT="${DIRECTORY_OUTPUT:-$(mktemp -d)}"
mkdir -p "$OUT"
PHP="${DIRECTORY_PHP:-php}"
"$PHP" tests/directory/render.php > "$OUT/directory-preview.html"
DIRECTORY_READ_ONLY=1 "$PHP" tests/directory/render.php > "$OUT/directory-readonly.html"
git show 6883b29034b18581e81bf8cadaf52aef9c01a774:wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-ui.php > "$OUT/original-ui.php"
DIRECTORY_UI_SOURCE="$OUT/original-ui.php" "$PHP" tests/directory/render.php > "$OUT/original.html"
python3 tests/directory/compare.py "$OUT/original.html" "$OUT/directory-preview.html"
node --check wp-content/plugins/csf-scheduling/assets/directory.js
node tests/directory/browser.js "$OUT"
echo "Synthetic preview and screenshots: $OUT"

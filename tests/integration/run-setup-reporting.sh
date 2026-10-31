#!/usr/bin/env bash
# Isolated copy of the disposable LOCAL SQLite site; never use a Plesk installation.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
LOCAL="$ROOT/.wp-local/site"
[[ -f "$LOCAL/wp-load.php" && -f "$ROOT/.wp-local/wp-cli.phar" ]] || { echo 'Install the local fixture site first.' >&2; exit 1; }
TMP="$(mktemp -d /tmp/csf-setup-regression.XXXXXX)"
SERVER_PID=''
cleanup() {
  [[ -z "$SERVER_PID" ]] || { kill "$SERVER_PID" 2>/dev/null || true; wait "$SERVER_PID" 2>/dev/null || true; }
  rm -rf "$TMP"
}
trap cleanup EXIT
mkdir "$TMP/site"
cp -a "$LOCAL/." "$TMP/site/"
# Set a separate DB_PATH before loading WordPress, regardless of copied SQLite path manifests.
php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" config set DB_PATH "$TMP/test.sqlite" --quiet
python3 - "$LOCAL/wp-content/database" "$TMP/test.sqlite" <<'PY'
import sqlite3,sys
from pathlib import Path
candidates=[p for p in Path(sys.argv[1]).rglob('*.sqlite') if p.is_file()]
assert len(candidates)==1, 'Expected exactly one local SQLite fixture database'
source=sqlite3.connect(candidates[0].resolve().as_uri()+'?mode=ro',uri=True)
target=sqlite3.connect(sys.argv[2])
source.backup(target)
target.close();source.close()
PY
CSF_SETUP_TEST_ROOT="$TMP" php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" \
  eval-file "$ROOT/tests/integration/setup-reporting.php"
CSF_SETUP_TEST_ROOT="$TMP" php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" \
  eval-file "$ROOT/tests/integration/inquiry.php"
if [[ "${CSF_INQUIRY_BROWSER:-0}" == 1 ]]; then
  TEST_PORT="$(python3 - <<'PY'
import socket
with socket.socket() as s:
    s.bind(('127.0.0.1',0))
    print(s.getsockname()[1])
PY
)"
  TEST_BASE="http://127.0.0.1:$TEST_PORT"
  php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" option update home "$TEST_BASE" --quiet
  php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" option update siteurl "$TEST_BASE" --quiet
  # The PHP checks left the form enabled only on this separate synthetic fixture DB.
  php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" eval 'global $wpdb; foreach ($wpdb->get_col($wpdb->prepare("SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like(CSF_Core_Inquiry::PREFIX)."%")) as $key) { delete_option($key); }' --quiet
  mkdir -p "$TMP/site/wp-content/mu-plugins"
  cat > "$TMP/site/wp-content/mu-plugins/csf-inquiry-test-mail.php" <<'PHP'
<?php
// This file exists only in the disposable fixture, never in the application release.
add_filter('pre_wp_mail', static function () {
    if ('local' !== wp_get_environment_type() || 0 !== strpos(FQDB, '/tmp/csf-setup-regression.')) { return false; }
    file_put_contents(getenv('CSF_INQUIRY_TEST_MAIL_LOG'), "mock accepted\n", FILE_APPEND | LOCK_EX);
    return true;
});
PHP
  CSF_WP_ROOT="$TMP/site" CSF_INQUIRY_TEST_MAIL_LOG="$TMP/mail.log" \
    php -S "127.0.0.1:$TEST_PORT" -t "$TMP/site" "$ROOT/scripts/dev/router.php" > "$TMP/server.log" 2>&1 &
  SERVER_PID=$!
  for attempt in {1..20}; do
    if curl --silent --fail "$TEST_BASE/contact/" --output /dev/null; then break; fi
    sleep 0.2
  done
  CSF_INQUIRY_TEST_BASE="$TEST_BASE" CSF_INQUIRY_TEST_MAIL_LOG="$TMP/mail.log" node "$ROOT/tests/e2e/inquiry.js"
fi

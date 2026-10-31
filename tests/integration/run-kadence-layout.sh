#!/usr/bin/env bash
# Real Kadence only, isolated SQLite fixture; never initialize a remote WordPress site.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
SOURCE="$ROOT/.wp-local/site"
KADENCE_ZIP="${KADENCE_ZIP:?Point to a trusted Kadence 1.5.2 release ZIP}"
[[ -f "$SOURCE/wp-load.php" && -f "$KADENCE_ZIP" ]] || { echo 'Local fixture or theme ZIP missing.' >&2; exit 1; }
TMP="$(mktemp -d "$ROOT/.wp-local/kadence-regression.XXXXXX")"
SERVER_PID=''
cleanup() {
  [[ -z "$SERVER_PID" ]] || { kill "$SERVER_PID" 2>/dev/null || true; wait "$SERVER_PID" 2>/dev/null || true; }
  rm -rf "$TMP"
}
trap cleanup EXIT
cp -a "$SOURCE" "$TMP/site"
mkdir -p "$TMP/site/wp-content/mu-plugins"
printf '%s\n' '<?php // Disposable fixture only: never send real mail.' "add_filter('pre_wp_mail', '__return_false');" > "$TMP/site/wp-content/mu-plugins/csf-no-test-mail.php"
php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" config set DB_PATH "$TMP/test.sqlite" --quiet
TEST_PORT="$(python3 - "$SOURCE/wp-content/database" "$TMP/test.sqlite" "$KADENCE_ZIP" "$TMP/site/wp-content/themes" <<'PY'
from pathlib import Path
import socket,sqlite3,sys,zipfile
files=list(Path(sys.argv[1]).rglob('*.sqlite')); assert len(files)==1
with sqlite3.connect(files[0].resolve().as_uri()+'?mode=ro',uri=True) as source,sqlite3.connect(sys.argv[2]) as target:source.backup(target)
with zipfile.ZipFile(sys.argv[3]) as z:
    assert all(n.startswith('kadence/') and '..' not in Path(n).parts for n in z.namelist())
    z.extractall(sys.argv[4])
with socket.socket() as s:
    s.bind(('127.0.0.1',0));print(s.getsockname()[1])
PY
)"
wp_fixture() { php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" "$@"; }
[[ "$(wp_fixture eval 'echo wp_get_environment_type();')" == local ]] || { echo 'Local environment required.' >&2; exit 1; }
CSF_KADENCE_DB="$TMP/test.sqlite" wp_fixture eval 'if (FQDB !== getenv("CSF_KADENCE_DB")) { WP_CLI::error("Isolated SQLite database required."); }'
wp_fixture plugin deactivate csf-emergency --quiet
wp_fixture theme activate kadence --quiet
[[ "$(wp_fixture theme get kadence --field=version)" == 1.5.2 ]] || { echo 'Exact Kadence 1.5.2 required.' >&2; exit 1; }
# Native theme configuration, fixture only. With no logo, the text fallback must stay
# visible on tablet too; the untouched default hides it and creates an unnamed home link.
wp_fixture eval 'set_theme_mod("logo_layout", array("include"=>array("desktop"=>"logo_title","tablet"=>"logo_title","mobile"=>"logo_title"),"layout"=>array("desktop"=>"standard","tablet"=>"standard","mobile"=>"standard")));'
echo 'Local fixture: native Kadence site-title fallback enabled at all breakpoints; no real-site configuration changed.'
TEST_BASE="http://127.0.0.1:$TEST_PORT"
wp_fixture option update home "$TEST_BASE" --quiet
wp_fixture option update siteurl "$TEST_BASE" --quiet
wp_fixture option delete csf_core_inquiry --quiet || true
wp_fixture csf setup > "$TMP/setup.log"
CSF_WP_ROOT="$TMP/site" php -S "127.0.0.1:$TEST_PORT" -t "$TMP/site" "$ROOT/scripts/dev/router.php" > "$TMP/server.log" 2>&1 &
SERVER_PID=$!
for attempt in {1..30}; do
  if curl --fail --silent "$TEST_BASE/" --output /dev/null; then break; fi
  sleep 0.2
done
CSF_LAYOUT_TEST_BASE="$TEST_BASE" node "$ROOT/tests/e2e/kadence-layout.js"
CSF_REVIEW_URL="$TEST_BASE" node "$ROOT/tests/e2e/kadence-review.js"
export CSF_WP_PATH="$TMP/site" CSF_BASE_URL="$TEST_BASE" CSF_KADENCE_LAYOUT=1
node "$ROOT/tests/e2e/run.js" --only=editor
node "$ROOT/tests/e2e/run.js" --only=content

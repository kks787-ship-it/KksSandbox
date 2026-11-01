#!/usr/bin/env bash
# Tests only an isolated copy of the existing disposable LOCAL WordPress fixture.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
command -v php >/dev/null || { echo 'BLOCKED: PHP CLI is unavailable; scheduling runtime tests did not run.' >&2; exit 1; }
LOCAL="$ROOT/.wp-local/site"
[[ -f "$LOCAL/wp-load.php" && -f "$ROOT/.wp-local/wp-cli.phar" ]] || { echo 'Missing existing local fixture.' >&2; exit 1; }
TMP="$(mktemp -d /tmp/csf-booking-regression.XXXXXX)"
trap 'rm -rf "$TMP"' EXIT
mkdir "$TMP/site"
cp -a "$LOCAL/." "$TMP/site/"
php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" config set DB_PATH "$TMP/test.sqlite" --quiet
python3 - "$LOCAL/wp-content/database" "$TMP/test.sqlite" <<'PY'
import sqlite3,sys
from pathlib import Path
paths=list(Path(sys.argv[1]).rglob('*.sqlite'))
assert len(paths)==1, 'Expected exactly one disposable fixture DB'
source=sqlite3.connect(paths[0].resolve().as_uri()+'?mode=ro',uri=True)
target=sqlite3.connect(sys.argv[2]);source.backup(target);target.close();source.close()
PY
# Deliberately require the existing fixture to be safe; never activate emergency or enable mail.
CSF_BOOKING_TEST_ROOT="$TMP" php "$ROOT/.wp-local/wp-cli.phar" --path="$TMP/site" eval-file "$ROOT/tests/integration/booking-contact.php"

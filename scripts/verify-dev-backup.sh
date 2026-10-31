#!/usr/bin/env bash
# Verify backup integrity and target binding. Does not perform or certify a restore.
set -euo pipefail
BACKUP="${1:?Usage: $0 backup-directory wordpress-path expected-url restore-evidence}"
TARGET="${2:?WordPress path required}"
URL="${3:?Expected development URL required}"
EVIDENCE="${4:?Restore evidence file required}"
BACKUP="$(realpath -e "$BACKUP")"
TARGET="$(realpath -e "$TARGET")"
case "$BACKUP/" in "$TARGET/"*) echo 'Refusing: backup is inside the WordPress document root.' >&2; exit 1;; esac
[[ -s "$EVIDENCE" ]] || { echo 'Refusing: scratch-restore evidence is missing.' >&2; exit 1; }
[[ "$(cat "$BACKUP/target-path.txt")" == "$TARGET" ]] || { echo 'Refusing: backup target path mismatch.' >&2; exit 1; }
[[ "$(cat "$BACKUP/target-url.txt")" == "$URL" ]] || { echo 'Refusing: backup target URL mismatch.' >&2; exit 1; }
# A fresh backup is required immediately before installation; restore evidence can refer
# to a prior successful drill of this backup method, with this backup's integrity checked now.
AGE=$(( $(date +%s) - $(stat -c %Y "$BACKUP/database.sql.gz") ))
(( AGE >= 0 && AGE <= 86400 )) || { echo 'Refusing: database backup must be less than 24 hours old.' >&2; exit 1; }
for file in database.sql.gz wp-content.tar.gz target-path.txt target-url.txt wp-version.txt plugins.csv; do
 [[ -f "$BACKUP/$file" && ! -L "$BACKUP/$file" ]] || { echo "Missing backup file: $file" >&2; exit 1; }
 # Require the fixed checksum set; never process arbitrary filenames from a supplied manifest.
 [[ "$(awk -v name="$file" '$2 == name { n++ } END { print n+0 }' "$BACKUP/SHA256SUMS")" == 1 ]] || { echo "Missing or repeated checksum: $file" >&2; exit 1; }
done
[[ "$(wc -l < "$BACKUP/SHA256SUMS")" == 6 ]] || { echo 'Unexpected checksum manifest entries.' >&2; exit 1; }
(cd "$BACKUP" && sha256sum --check --strict SHA256SUMS)
gzip -t "$BACKUP/database.sql.gz"
tar -tzf "$BACKUP/wp-content.tar.gz" > /dev/null
[[ "$(gzip -cd "$BACKUP/database.sql.gz" | wc -c)" -gt 0 ]] || { echo 'Refusing: empty database export.' >&2; exit 1; }
echo 'Backup integrity and target binding verified; owner-recorded restore evidence is present.'

#!/usr/bin/env bash
# Back up the DEVELOPMENT WordPress site: database export + wp-content archive.
#
#   bash backup-dev.sh /path/to/dev/wordpress /path/to/backup/dir
#
# Writes only into the backup directory. Refuses to run if WP_ENVIRONMENT_TYPE is
# "production", so it cannot be pointed at the live site by accident.
set -euo pipefail

WP_PATH="${1:?Usage: $0 /path/to/dev/wordpress /path/to/backup/dir}"
DEST="${2:?Usage: $0 /path/to/dev/wordpress /path/to/backup/dir}"
WP="wp --path=$WP_PATH"

ENV_TYPE="$($WP eval 'echo wp_get_environment_type();' 2>/dev/null || echo unknown)"
if [[ "$ENV_TYPE" == "production" ]]; then
	echo "Refusing: this site reports WP_ENVIRONMENT_TYPE=production. Set it to 'development' or 'staging' in the dev wp-config.php." >&2
	exit 1
fi

STAMP="$(date -u +%Y%m%d-%H%M%S)"
OUT="$DEST/csf-dev-$STAMP"
mkdir -p "$OUT"
chmod 700 "$OUT"

echo "Exporting database…"
$WP db export "$OUT/database.sql" --quiet
gzip "$OUT/database.sql"

echo "Archiving wp-content…"
tar -czf "$OUT/wp-content.tar.gz" -C "$WP_PATH" wp-content

$WP core version > "$OUT/wp-version.txt"
$WP plugin list --format=csv > "$OUT/plugins.csv"
( cd "$OUT" && sha256sum ./*.gz > SHA256SUMS )

echo "Backup written to $OUT"
echo "Copy it off the server (it contains personal data — store it encrypted)."

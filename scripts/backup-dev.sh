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
WP=(wp "--path=$WP_PATH")

if ! ENV_TYPE="$("${WP[@]}" eval 'echo wp_get_environment_type();')"; then
	echo "Refusing: could not verify the target WordPress environment." >&2
	exit 1
fi
case "$ENV_TYPE" in
	local|development|staging) ;;
	*) echo "Refusing: target must report local, development, or staging." >&2; exit 1 ;;
esac

umask 077

STAMP="$(date -u +%Y%m%d-%H%M%S)"
OUT="$DEST/csf-dev-$STAMP"
mkdir -p "$OUT"
chmod 700 "$OUT"

echo "Exporting database…"
"${WP[@]}" db export "$OUT/database.sql" --quiet
gzip "$OUT/database.sql"

echo "Archiving wp-content…"
tar -czf "$OUT/wp-content.tar.gz" -C "$WP_PATH" wp-content

"${WP[@]}" core version > "$OUT/wp-version.txt"
"${WP[@]}" plugin list --format=csv > "$OUT/plugins.csv"
( cd "$OUT" && sha256sum ./*.gz > SHA256SUMS )

echo "Backup written to $OUT"
echo "Copy it off the server (it contains personal data — store it encrypted)."

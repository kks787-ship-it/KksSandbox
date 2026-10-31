#!/usr/bin/env bash
# Copy the custom plugins from this repository to the DEVELOPMENT site.
#
#   bash deploy-plugins.sh /path/to/dev/wordpress            # dry run (default)
#   bash deploy-plugins.sh /path/to/dev/wordpress --only-core # public-site update only
#   bash deploy-plugins.sh /path/to/dev/wordpress --apply    # actually copy
#
# Only touches wp-content/plugins/csf-*. Refuses to deploy to a production environment.
set -euo pipefail

WP_PATH="${1:?Usage: $0 /path/to/dev/wordpress [--only-core] [--apply]}"
shift
APPLY=""
EXPECTED_URL=""
BACKUP=""
RESTORE_EVIDENCE=""
PLUGINS=(csf-emergency csf-core)
while (( $# )); do
 case "$1" in
  --apply) APPLY=--apply; shift ;;
  --only-core) PLUGINS=(csf-core); shift ;;
  --expected-url|--backup|--restore-evidence)
   (( $# >= 2 )) || { echo "Missing value for $1" >&2; exit 1; }
   case "$1" in
    --expected-url) EXPECTED_URL="$2" ;;
    --backup) BACKUP="$2" ;;
    --restore-evidence) RESTORE_EVIDENCE="$2" ;;
   esac
   shift 2 ;;
  *) echo "Unknown option: $1" >&2; exit 1 ;;
 esac
done
WP_PATH="$(realpath -e "$WP_PATH")"
REPO="$(cd "$(dirname "$0")/.." && pwd)"

if ! ENV_TYPE="$(wp --path="$WP_PATH" eval 'echo wp_get_environment_type();')"; then
	echo "Refusing: could not verify the target WordPress environment." >&2
	exit 1
fi
case "$ENV_TYPE" in
	local|development|staging) ;;
	*) echo "Refusing: target must report local, development, or staging." >&2; exit 1 ;;
esac
if [[ -n "$EXPECTED_URL" ]]; then
 ACTUAL_URL="$(wp --path="$WP_PATH" option get home)"
 ACTUAL_SITEURL="$(wp --path="$WP_PATH" option get siteurl)"
 [[ "$ACTUAL_URL" == "$EXPECTED_URL" && "$ACTUAL_SITEURL" == "$EXPECTED_URL" ]] || { echo "Refusing: target URL does not match --expected-url." >&2; exit 1; }
fi
if [[ "$APPLY" == "--apply" ]]; then
 [[ -n "$EXPECTED_URL" && -n "$BACKUP" && -n "$RESTORE_EVIDENCE" ]] || {
  echo "Refusing: --apply requires --expected-url, --backup and --restore-evidence." >&2; exit 1;
 }
 bash "$REPO/scripts/verify-dev-backup.sh" "$BACKUP" "$WP_PATH" "$EXPECTED_URL" "$RESTORE_EVIDENCE"
fi
for plugin in "${PLUGINS[@]}"; do
 [[ -d "$REPO/wp-content/plugins/$plugin" ]] || { echo "Missing plugin source: $plugin" >&2; exit 1; }
 [[ ! -L "$WP_PATH/wp-content" && ! -L "$WP_PATH/wp-content/plugins" && ! -L "$WP_PATH/wp-content/plugins/$plugin" ]] || {
  echo "Refusing: target plugin path contains a symlink; resolve and review the actual target." >&2; exit 1;
 }
done
printf 'Target: %s\n' "$WP_PATH"

# Git/ZIP checkouts may be private (0700/0600). Web-served plugin code needs readable
# files and traversable directories; keep the destination account's ownership.
# These flags apply only to selected plugin trees, never wp-config.php or uploads.
RSYNC_FLAGS=(-a --no-owner --no-group --chmod=D755,F644 --delete --exclude '.git' --itemize-changes)
[[ "$APPLY" == "--apply" ]] || RSYNC_FLAGS+=(--dry-run)

for plugin in "${PLUGINS[@]}"; do
	echo "== $plugin"
	rsync "${RSYNC_FLAGS[@]}" "$REPO/wp-content/plugins/$plugin/" "$WP_PATH/wp-content/plugins/$plugin/"
done

if [[ "$APPLY" != "--apply" ]]; then
	echo "Dry run only. Re-run with --apply to copy files."
else
	echo "Plugin files copied after backup verification. Activation and site setup are separate steps."
fi

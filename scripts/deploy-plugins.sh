#!/usr/bin/env bash
# Copy the custom plugins from this repository to the DEVELOPMENT site.
#
#   bash deploy-plugins.sh /path/to/dev/wordpress            # dry run (default)
#   bash deploy-plugins.sh /path/to/dev/wordpress --apply    # actually copy
#
# Only touches wp-content/plugins/csf-*. Refuses to deploy to a production environment.
set -euo pipefail

WP_PATH="${1:?Usage: $0 /path/to/dev/wordpress [--apply]}"
APPLY="${2:-}"
REPO="$(cd "$(dirname "$0")/.." && pwd)"

if ! ENV_TYPE="$(wp --path="$WP_PATH" eval 'echo wp_get_environment_type();')"; then
	echo "Refusing: could not verify the target WordPress environment." >&2
	exit 1
fi
case "$ENV_TYPE" in
	local|development|staging) ;;
	*) echo "Refusing: target must report local, development, or staging." >&2; exit 1 ;;
esac
if [[ -n "$APPLY" && "$APPLY" != "--apply" ]]; then
	echo "Unknown option: $APPLY" >&2
	exit 1
fi

RSYNC_FLAGS=(-a --delete --exclude '.git' --itemize-changes)
[[ "$APPLY" == "--apply" ]] || RSYNC_FLAGS+=(--dry-run)

for plugin in csf-emergency csf-core; do
	echo "== $plugin"
	rsync "${RSYNC_FLAGS[@]}" "$REPO/wp-content/plugins/$plugin/" "$WP_PATH/wp-content/plugins/$plugin/"
done

if [[ "$APPLY" != "--apply" ]]; then
	echo "Dry run only. Re-run with --apply to copy files."
else
	echo "Deployed. Run a backup first next time if you have not: scripts/backup-dev.sh"
fi

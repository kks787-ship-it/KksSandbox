#!/usr/bin/env bash
# Read-only commands for the DEVELOPMENT subscription. No deployment or configuration edits.
# WordPress bootstrap can execute existing must-use plugins/drop-ins: review these first.
set -uo pipefail
WP_PATH="${1:?Usage: $0 /exact/development/wordpress}"
WP_PATH="$(realpath -e "$WP_PATH")" || exit 1
[[ "$(id -u)" != 0 ]] || { echo 'Run as the development subscription user, not root.' >&2; exit 1; }
WP=(wp "--path=$WP_PATH" --skip-plugins --skip-themes)
command -v wp >/dev/null || { echo 'WP-CLI missing; use WordPress Site Health and Plesk domain settings.' >&2; exit 1; }
TYPE="$("${WP[@]}" eval 'echo wp_get_environment_type();')" || exit 1
case "$TYPE" in development|staging|local) ;; *) echo 'Refusing: target is production or unverified.' >&2; exit 1;; esac
FAILURES=0
section() { printf '\n## %s\n\n' "$1"; }
code() {
 printf '```\n'
 if "$@"; then :; else FAILURES=$((FAILURES+1)); printf 'CHECK FAILED: %s\n' "$1"; fi
 printf '```\n'
}
printf '# Development inspection\n\nGenerated: %s\nTarget: %s\nEnvironment: %s\n' "$(date -u '+%Y-%m-%d %H:%M UTC')" "$WP_PATH" "$TYPE"
section 'Versions and resource availability'
code uname -srm
code df -h "$WP_PATH"
code php -v
code php -m
code php -r 'foreach (["memory_limit","max_execution_time","upload_max_filesize","post_max_size","max_input_vars","opcache.enable"] as $key) printf("%s=%s\n",$key,ini_get($key));'
code "${WP[@]}" core version --extra
code "${WP[@]}" db query 'SELECT VERSION() AS server_version;'
section 'Safe WordPress configuration and inventory'
for key in home siteurl template stylesheet permalink_structure blog_public; do
 printf '%s:\n' "$key"
 code "${WP[@]}" option get "$key"
done
# Never dump wp-config or arbitrary option tables.
for key in WP_DEBUG WP_DEBUG_DISPLAY DISALLOW_FILE_EDIT WP_ENVIRONMENT_TYPE; do
 printf '%s (undefined is reported explicitly):\n' "$key"
 if "${WP[@]}" config has "$key"; then code "${WP[@]}" config get "$key"; else echo 'undefined'; fi
done
code "${WP[@]}" theme list --fields=name,status,version
code "${WP[@]}" plugin list --fields=name,status,version
code "${WP[@]}" cron event list --fields=hook,next_run_relative
section 'Ownership and permissions'
code stat -c '%A %U:%G %n' "$WP_PATH" "$WP_PATH/wp-content" "$WP_PATH/wp-content/plugins"
code find "$WP_PATH/wp-content" -perm -o+w -not -type l -print -quit
section 'Plesk manual observations required'
printf '%s\n' 'Record domain PHP handler/version/extensions (CLI may differ), HTTPS certificate, nginx/Apache arrangement, rewrite behavior, cache exclusions, separate development DB, subscription user, document root, backup schedule/off-server retention, restore-drill evidence and disk quota.'
printf '\nInspection commands completed; %s checks failed. No configuration changes requested.\n' "$FAILURES"
(( FAILURES == 0 ))

#!/usr/bin/env bash
# Corner Stone Farm — READ-ONLY development environment inspection.
#
# Run on the VPS against the DEVELOPMENT WordPress install only:
#   bash inspect-environment.sh /var/www/vhosts/<dev-domain>/httpdocs > env-report.md
#
# This script never writes to WordPress, the database, Plesk, or web server config.
# It only reads versions, settings, and status. Review the output before sharing:
# it may contain hostnames and paths (it never prints passwords or salts).
set -uo pipefail

WP_PATH="${1:-}"
if [[ -z "$WP_PATH" || ! -f "$WP_PATH/wp-config.php" && ! -f "$WP_PATH/../wp-config.php" ]]; then
	echo "Usage: $0 /path/to/dev/wordpress   (directory containing wp-config.php)" >&2
	exit 1
fi

have() { command -v "$1" >/dev/null 2>&1; }
section() { printf '\n## %s\n\n' "$1"; }
code() { printf '```\n'; "$@" 2>&1 | sed 's/^/  /' | head -n 200; printf '```\n'; }

WP=""
if have wp; then
	WP="wp --path=$WP_PATH --skip-plugins --skip-themes"
	# Use the file owner so wp-cli does not run as root against the site.
	OWNER="$(stat -c %U "$WP_PATH")"
	if [[ "$(id -u)" == "0" && "$OWNER" != "root" ]]; then
		WP="sudo -u $OWNER -- $WP"
	fi
fi

echo "# Corner Stone Farm — Development Environment Report"
echo
echo "Generated: $(date -u '+%Y-%m-%d %H:%M UTC') on $(hostname)"
echo "WordPress path: \`$WP_PATH\`"

section "Operating system & resources"
code uname -srm
[[ -f /etc/os-release ]] && code grep -E '^(PRETTY_NAME|VERSION_ID)=' /etc/os-release
code nproc
code free -h
code df -h "$WP_PATH"
have plesk && code plesk version

section "PHP"
code php -v
code php -r 'foreach (["memory_limit","max_execution_time","upload_max_filesize","post_max_size","max_input_vars","opcache.enable"] as $k) printf("%-22s %s\n", $k, ini_get($k));'
code php -m
echo "_Note: the CLI PHP may differ from the PHP version Plesk assigns to the domain. Confirm in Plesk → Domains → PHP Settings, or with the WordPress Site Health screen._"

section "Database server"
if have mysql; then code mysql --version; elif have mariadb; then code mariadb --version; else echo "mysql client not found"; fi
[[ -n "$WP" ]] && code $WP db query "SELECT VERSION() AS server_version;" --skip-column-names

section "Web server"
if have nginx; then code nginx -v; fi
if have apache2ctl; then code apache2ctl -v; elif have httpd; then code httpd -v; fi
[[ -f "$WP_PATH/.htaccess" ]] && { echo "\`.htaccess\` present:"; code cat "$WP_PATH/.htaccess"; } || echo ".htaccess not found in WordPress root."

section "WordPress core"
if [[ -n "$WP" ]]; then
	code $WP core version --extra
	code $WP core verify-checksums
	code $WP option get home
	code $WP option get siteurl
	code $WP option get permalink_structure
	code $WP option get blog_public
	code $WP config list --fields=name,value --format=table WP_DEBUG WP_DEBUG_LOG WP_DEBUG_DISPLAY DISALLOW_FILE_EDIT WP_ENVIRONMENT_TYPE WP_MEMORY_LIMIT WP_AUTO_UPDATE_CORE DISABLE_WP_CRON FORCE_SSL_ADMIN
	code $WP cron event list --fields=hook,next_run_relative --format=table
else
	echo "wp-cli not installed — install it (https://wp-cli.org) or use Site Health → Info and paste the report."
fi

section "Themes & plugins"
if [[ -n "$WP" ]]; then
	code $WP theme list --fields=name,status,version,update_version
	code $WP plugin list --fields=name,status,version,update_version,auto_update
	code $WP plugin list --status=must-use --fields=name,version
fi

section "Filesystem permissions"
code stat -c '%A %U:%G %n' "$WP_PATH" "$WP_PATH/wp-content" "$WP_PATH/wp-content/uploads" "$WP_PATH/wp-content/plugins" "$WP_PATH/wp-config.php"
echo "World-writable files/dirs under wp-content (should be none):"
code find "$WP_PATH/wp-content" -perm -o+w -not -type l -print -quit

section "HTTPS & certificate"
if [[ -n "$WP" ]]; then
	HOME_URL="$($WP option get home 2>/dev/null)"
	HOST="$(echo "$HOME_URL" | sed -E 's#^https?://##; s#/.*##')"
	echo "Home URL: $HOME_URL"
	if have openssl && [[ -n "$HOST" ]]; then
		code bash -c "echo | openssl s_client -servername $HOST -connect $HOST:443 2>/dev/null | openssl x509 -noout -subject -issuer -dates"
	fi
	if have curl; then
		code curl -sSI "$HOME_URL/" --max-time 15
		echo "Permalink rewrite check (expect 404 from WordPress, not the web server):"
		code curl -s -o /dev/null -w '%{http_code}\n' "$HOME_URL/csf-permalink-check-$(date +%s)/" --max-time 15
		echo "Mixed content check (http:// asset references on the home page):"
		code bash -c "curl -s --max-time 15 '$HOME_URL/' | grep -oE '(src|href)=\"http://[^\"]+' | sort -u | head -20"
	fi
fi

section "Backups"
have plesk && code bash -c "plesk bin pleskbackup --help >/dev/null 2>&1 && echo 'Plesk backup CLI available' ; ls -la /var/lib/psa/dumps 2>/dev/null | tail -5"
echo "_Also record: Plesk scheduled backup settings (Tools & Settings → Backup Manager), retention, and whether backups are stored off-server._"

section "Development tooling"
for t in git composer node npm wp rsync unzip; do printf -- '- %s: %s\n' "$t" "$(command -v $t >/dev/null && $t --version 2>/dev/null | head -1 || echo 'not installed')"; done

echo
echo "_End of report. Nothing was modified._"

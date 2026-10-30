#!/usr/bin/env bash
# Build a disposable LOCAL WordPress site for development and automated tests.
#
#   bash scripts/local-dev.sh install     # build .wp-local/ (WordPress + SQLite + plugins + setup)
#   bash scripts/local-dev.sh start       # serve on http://localhost:8890
#   bash scripts/local-dev.sh stop
#   bash scripts/local-dev.sh reset       # delete .wp-local/site and rebuild
#
# Environment:
#   WP_VERSION=7.1.4       WordPress version
#   KADENCE_ZIP=/path.zip  Install the real Kadence theme from a zip (recommended when available).
#                          Without it, the TEST-ONLY stand-in theme in tests/fixtures is used.
#   PORT=8890
#
# Never points at a remote site. Uses SQLite, so no database server is needed.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOCAL="$ROOT/.wp-local"
SITE="$LOCAL/site"
PORT="${PORT:-8890}"
URL="http://localhost:$PORT"
WP_VERSION="${WP_VERSION:-7.1.4}"
WP="php $LOCAL/wp-cli.phar --path=$SITE --allow-root"

need() { command -v "$1" >/dev/null || { echo "Missing: $1" >&2; exit 1; }; }

install() {
	need php; need git; need curl
	mkdir -p "$LOCAL"
	[[ -f "$LOCAL/wp-cli.phar" ]] || curl -sSLo "$LOCAL/wp-cli.phar" https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar

	if [[ ! -f "$SITE/wp-load.php" ]]; then
		echo "Downloading WordPress $WP_VERSION…"
		if ! $WP core download --version="$WP_VERSION" --quiet 2>/dev/null; then
			echo "wordpress.org unreachable; using the johnpbloch/wordpress-core git mirror."
			git clone -q --depth 1 --branch "$WP_VERSION" https://github.com/johnpbloch/wordpress-core.git "$SITE"
			rm -rf "$SITE/.git"
		fi
	fi

	# SQLite database integration (monorepo since v3; older releases keep the plugin at the root).
	local src="$LOCAL/sqlite-src" sqlite="$SITE/wp-content/plugins/sqlite-database-integration"
	if [[ ! -d "$src" ]]; then
		local tag
		tag="$(git ls-remote --tags https://github.com/WordPress/sqlite-database-integration.git | awk -F/ '{print $3}' | grep -E '^v?[0-9.]+$' | sort -V | tail -1)"
		git clone -q --depth 1 --branch "$tag" https://github.com/WordPress/sqlite-database-integration.git "$src"
	fi
	local plugin_dir="$src"
	[[ -d "$src/packages/plugin-sqlite-database-integration" ]] && plugin_dir="$src/packages/plugin-sqlite-database-integration"
	ln -sfn "$plugin_dir" "$sqlite"
	sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$plugin_dir#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" "$plugin_dir/db.copy" > "$SITE/wp-content/db.php"

	ln -sfn "$ROOT/wp-content/plugins/csf-core" "$SITE/wp-content/plugins/csf-core"
	ln -sfn "$ROOT/wp-content/plugins/csf-emergency" "$SITE/wp-content/plugins/csf-emergency"

	if [[ ! -f "$SITE/wp-config.php" ]]; then
		$WP config create --dbname=wp --dbuser=x --dbpass=x --skip-check --quiet
		$WP config set WP_ENVIRONMENT_TYPE local
		$WP config set DISALLOW_FILE_EDIT true --raw
	fi
	if ! $WP core is-installed 2>/dev/null; then
		$WP core install --url="$URL" --title="Corner Stone Farm" --admin_user=admin --admin_password=localtest123 --admin_email=dev@example.invalid --skip-email
		# Set explicitly: WP-CLI can otherwise derive a URL containing the install directory.
		$WP option update home "$URL"
		$WP option update siteurl "$URL"
		$WP option update blog_public 0
		# Remove WordPress demo content (never migrate demo content).
		$WP post delete "$($WP post list --post_type=page --name=sample-page --field=ID)" --force >/dev/null 2>&1 || true
		$WP post delete "$($WP post list --post_type=post --name=hello-world --field=ID)" --force >/dev/null 2>&1 || true
		$WP option update blogdescription "Where Confidence Grows."
	fi

	if [[ -n "${KADENCE_ZIP:-}" ]]; then
		$WP theme install "$KADENCE_ZIP" --activate
	else
		ln -sfn "$ROOT/tests/fixtures/themes/csf-kadence-standin" "$SITE/wp-content/themes/csf-kadence-standin"
		$WP theme activate csf-kadence-standin
		echo "NOTE: using the TEST-ONLY Kadence stand-in theme. Set KADENCE_ZIP to test with Kadence."
	fi

	$WP plugin activate csf-core csf-emergency
	$WP csf setup --set-permalinks
	echo "Installed. Admin: $URL/wp-admin (admin / localtest123 — local only)."
}

start() {
	[[ -f "$LOCAL/server.pid" ]] && kill -0 "$(cat "$LOCAL/server.pid")" 2>/dev/null && { echo "Already running on $URL"; return; }
	CSF_WP_ROOT="$SITE" nohup php -S "localhost:$PORT" -t "$SITE" "$ROOT/scripts/dev/router.php" > "$LOCAL/server.log" 2>&1 &
	echo $! > "$LOCAL/server.pid"
	sleep 1
	echo "Serving $URL (log: .wp-local/server.log)"
}

stop() {
	[[ -f "$LOCAL/server.pid" ]] && kill "$(cat "$LOCAL/server.pid")" 2>/dev/null || true
	rm -f "$LOCAL/server.pid"
}

case "${1:-}" in
	install) install ;;
	start) start ;;
	stop) stop ;;
	reset) stop; rm -rf "$SITE"; install ;;
	*) echo "Usage: $0 install|start|stop|reset" >&2; exit 1 ;;
esac

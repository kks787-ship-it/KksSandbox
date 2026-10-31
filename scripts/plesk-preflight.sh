#!/usr/bin/env bash
# Read-only WordPress commands; run as the dev subscription user. No SSH or Plesk edits.
set -euo pipefail
TARGET="$(realpath -e "${1:?Usage: $0 /exact/dev/wordpress https://exact-dev-host}")"
EXPECTED="${2:?Expected HTTPS development URL required}"
[[ "$EXPECTED" == https://* ]] || { echo 'Expected URL must use HTTPS.' >&2; exit 1; }
WP=(wp "--path=$TARGET" --skip-plugins --skip-themes)
TYPE="$("${WP[@]}" eval 'echo wp_get_environment_type();')"
case "$TYPE" in development|staging) ;; *) echo 'Refusing: Plesk preflight requires development or staging.' >&2; exit 1;; esac
[[ "$("${WP[@]}" option get home)" == "$EXPECTED" && "$("${WP[@]}" option get siteurl)" == "$EXPECTED" ]] || { echo 'Target URL mismatch; stop and investigate.' >&2; exit 1; }
[[ "$("${WP[@]}" option get blog_public)" == 0 ]] || { echo 'Development search indexing must be disabled.' >&2; exit 1; }
[[ "$("${WP[@]}" option get template)" == kadence ]] || { echo 'Real Kadence parent theme must be active (a child theme is permitted).' >&2; exit 1; }
"${WP[@]}" eval '
if ( version_compare( PHP_VERSION, "8.1", "<" ) || version_compare( get_bloginfo("version"), "6.6", "<" ) ) { fwrite(STDERR,"Need PHP >=8.1 and WordPress >=6.6 for this release.\n"); exit(1); }
foreach (array("mysqli","json","mbstring","dom","xml","curl","openssl","fileinfo","filter","hash") as $extension) {
 if (!extension_loaded($extension)) { fwrite(STDERR,"Missing required extension: ".$extension."\n"); exit(1); }
}
if (!extension_loaded("gd") && !extension_loaded("imagick")) { fwrite(STDERR,"Need GD or Imagick for farm photography.\n"); exit(1); }
echo "CLI PHP/WordPress extensions and versions pass. Verify the same in the domain PHP handler.\n";
'
"${WP[@]}" theme get kadence --fields=name,status,version
"${WP[@]}" db query 'SELECT VERSION() AS database_version;'
"${WP[@]}" option get permalink_structure
printf 'Preflight passed for %s at %s; no installation performed.\n' "$EXPECTED" "$TARGET"

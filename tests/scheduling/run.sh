#!/usr/bin/env bash
# Disposable LOCAL WordPress + MariaDB/MySQL; never reads a farm config or database.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
[[ -f "$ROOT/.wp-local/site/wp-load.php" && -f "$ROOT/.wp-local/wp-cli.phar" ]] || { echo 'Provide the existing local WordPress core and WP-CLI fixture first.' >&2; exit 1; }
D=(docker --host=unix:///var/run/docker.sock)
STAMP="csf-foundation-$$"
cleanup(){ "${D[@]}" rm -f "$STAMP-db" >/dev/null 2>&1 || true; "${D[@]}" volume rm "$STAMP-site" >/dev/null 2>&1 || true; "${D[@]}" network rm "$STAMP-net" >/dev/null 2>&1 || true; }
trap cleanup EXIT
"${D[@]}" build -q -t csf-scheduling-php81 "$ROOT/tests/scheduling"
"${D[@]}" network create --internal "$STAMP-net" >/dev/null
"${D[@]}" volume create "$STAMP-site" >/dev/null
"${D[@]}" run -d --name "$STAMP-db" --network "$STAMP-net" --network-alias fixture-db --tmpfs /var/lib/mysql -e MYSQL_ROOT_PASSWORD=fixture-only -e MYSQL_ROOT_HOST=% -e MYSQL_DATABASE=csf_fixture "${CSF_TEST_DB_IMAGE:-mariadb:10.6}" >/dev/null
for attempt in $(seq 1 40); do
 if "${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); "$DB" -h127.0.0.1 -uroot -pfixture-only csf_fixture -e "SELECT 1"' >/dev/null 2>&1; then break; fi
 [[ "$attempt" != 40 ]] || { echo 'Disposable database failed to start.' >&2; exit 1; }
 sleep 1
done
PHP=("${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test")
"${PHP[@]}" -e CSF_TEST_PHASE=install csf-scheduling-php81 bash /repo/tests/scheduling/container.sh
# Genuine logical backup/restore rehearsal, wholly inside the disposable DB container.
"${D[@]}" exec "$STAMP-db" sh -c 'DUMP=$(command -v mariadb-dump || command -v mysqldump); "$DUMP" -uroot -pfixture-only --single-transaction csf_fixture > /tmp/before-foundation.sql'
"${PHP[@]}" -e CSF_TEST_PHASE=validate csf-scheduling-php81 bash /repo/tests/scheduling/container.sh
"${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); for name in settings resources appointments allocations commands audit; do "$DB" -uroot -pfixture-only csf_fixture -e "DROP TABLE fixture_csf_sched_$name"; done; "$DB" -uroot -pfixture-only csf_fixture < /tmp/before-foundation.sql'
"${PHP[@]}" csf-scheduling-php81 php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval 'global $wpdb; if (DB_NAME!=="csf_fixture" || is_plugin_active("csf-scheduling/csf-scheduling.php") || $wpdb->get_var("SHOW TABLES LIKE \"fixture_csf_sched_%\"") || $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_value LIKE \"%csf_schedule_%\"")!=0) { WP_CLI::error("Fixture restore mismatch"); } echo "LOGICAL_BACKUP_RESTORE_PASS\n";'

#!/usr/bin/env bash
# Disposable LOCAL WordPress + MariaDB/MySQL; never reads a farm config or database.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
[[ -f "$ROOT/.wp-local/scheduling-core/wp-load.php" && -f "$ROOT/.wp-local/wp-cli.phar" ]] || { echo 'Provide unmodified WordPress core in .wp-local/scheduling-core and the existing WP-CLI fixture first.' >&2; exit 1; }
D=(docker --host=unix:///var/run/docker.sock)
STAMP="csf-foundation-$$"
cleanup(){ "${D[@]}" rm -f "$STAMP-web" "$STAMP-db" >/dev/null 2>&1 || true; "${D[@]}" volume rm "$STAMP-site" >/dev/null 2>&1 || true; "${D[@]}" network rm "$STAMP-net" >/dev/null 2>&1 || true; }
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
"${PHP[@]}" -e CSF_TEST_PHASE=foundation csf-scheduling-php81 bash /repo/tests/scheduling/container.sh
"${D[@]}" exec "$STAMP-db" sh -c 'DUMP=$(command -v mariadb-dump || command -v mysqldump); "$DUMP" -uroot -pfixture-only --single-transaction csf_fixture > /tmp/before-v2.sql'
"${PHP[@]}" -e CSF_TEST_PHASE=calendar csf-scheduling-php81 bash /repo/tests/scheduling/container.sh
# The web fixture is bound to loopback only, never publicly served.
if [[ "${CSF_TEST_BROWSER:-0}" == 1 ]]; then
 "${D[@]}" run -d --name "$STAMP-web" --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" csf-scheduling-php81 php -S 0.0.0.0:8899 -t /test/site >/dev/null
 for attempt in $(seq 1 30); do
  if "${D[@]}" exec "$STAMP-web" php -r '$s=@fsockopen("127.0.0.1",8899);if(!$s){exit(1);}fclose($s);' >/dev/null 2>&1; then break; fi
  sleep .2
 done
 CSF_TEST_WEB_CONTAINER="$STAMP-web" node "$ROOT/tests/scheduling/browser.js"
 "${D[@]}" rm -f "$STAMP-web" >/dev/null
fi
"${PHP[@]}" csf-scheduling-php81 bash /repo/tests/scheduling/migration-race.sh
# Restore the real populated v1 logical backup with v2 code still present: access must fail closed.
"${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); for name in settings resources appointments allocations commands audit riders horses availability restrictions details history slots participants; do "$DB" -uroot -pfixture-only csf_fixture -e "DROP TABLE IF EXISTS fixture_csf_sched_$name"; done; "$DB" -uroot -pfixture-only csf_fixture < /tmp/before-v2.sql'
"${PHP[@]}" csf-scheduling-php81 php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root --user=1 eval '$s=CSF_Scheduling_Schema::inspect(1);$before=json_decode(file_get_contents("/test/v1-settings.json"),true);if($s["errors"]||$s["missing"]||$s["version"]!==1||CSF_Scheduling_Access::can()||CSF_Scheduling_Schema::settings()!==$before){WP_CLI::error("v1 restore mismatch");}echo "POPULATED_V1_BACKUP_RESTORE_PASS_V2_ACCESS_DENIED\n";'

"${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); for name in settings resources appointments allocations commands audit riders horses availability restrictions details history slots participants; do "$DB" -uroot -pfixture-only csf_fixture -e "DROP TABLE IF EXISTS fixture_csf_sched_$name"; done; "$DB" -uroot -pfixture-only csf_fixture < /tmp/before-foundation.sql'
"${PHP[@]}" csf-scheduling-php81 php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval 'global $wpdb; if (DB_NAME!=="csf_fixture" || is_plugin_active("csf-scheduling/csf-scheduling.php") || $wpdb->get_var("SHOW TABLES LIKE \"fixture_csf_sched_%\"") || $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_value LIKE \"%csf_schedule_%\"")!=0) { WP_CLI::error("Fixture restore mismatch"); } echo "LOGICAL_BACKUP_RESTORE_PASS\n";'

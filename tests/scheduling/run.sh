#!/usr/bin/env bash
# Disposable LOCAL WordPress + MariaDB/MySQL; never reads a farm config or database.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
[[ -f "$ROOT/.wp-local/scheduling-core/wp-load.php" && -f "$ROOT/.wp-local/wp-cli.phar" ]] || { echo 'Provide unmodified WordPress core in .wp-local/scheduling-core and the existing WP-CLI fixture first.' >&2; exit 1; }
CSF_PHP_VERSION="${CSF_TEST_PHP_VERSION:-8.1}"
case "$CSF_PHP_VERSION" in 8.1|8.3) ;; *) echo 'Unsupported local PHP test version.' >&2; exit 1 ;; esac
PHP_IMAGE="csf-scheduling-php${CSF_PHP_VERSION//./}"
D=(docker --host=unix:///var/run/docker.sock)
# Chromium treats concurrent fixture network creation/removal as ERR_NETWORK_CHANGED.
exec 8>"$ROOT/.wp-local/docker-fixture.lock"
flock 8
python3 - "$ROOT" <<'PYV2'
import pathlib,subprocess,hashlib,sys
root=pathlib.Path(sys.argv[1]);pin='6f5a9bf51747399a309b39637a135841fe4cf2a1'
for row in (root/'docs/test-results/scheduling-staff-files.sha256').read_text().splitlines():
 digest,path=row.split();data=subprocess.check_output(['git','-C',str(root),'show',pin+':'+path]);assert hashlib.sha256(data).hexdigest()==digest
 target=root/'.wp-local/permissions-v2-source'/path;target.parent.mkdir(parents=True,exist_ok=True);target.write_bytes(data)
PYV2
STAMP="csf-foundation-$$"
cleanup(){ "${D[@]}" rm -f "$STAMP-web" "$STAMP-db" >/dev/null 2>&1 || true; "${D[@]}" volume rm "$STAMP-site" >/dev/null 2>&1 || true; "${D[@]}" network rm "$STAMP-net" >/dev/null 2>&1 || true; }
trap cleanup EXIT
"${D[@]}" build -q --build-arg "CSF_TEST_PHP_VERSION=$CSF_PHP_VERSION" -t "$PHP_IMAGE" "$ROOT/tests/scheduling"
"${D[@]}" run --rm --network none "$PHP_IMAGE" php -r 'echo "CALENDAR_TEST_PHP=".PHP_VERSION."\n";'
"${D[@]}" network create --internal "$STAMP-net" >/dev/null
"${D[@]}" volume create "$STAMP-site" >/dev/null
"${D[@]}" run -d --name "$STAMP-db" --network "$STAMP-net" --network-alias fixture-db --tmpfs /var/lib/mysql -e MYSQL_ROOT_PASSWORD=fixture-only -e MYSQL_ROOT_HOST=% -e MYSQL_DATABASE=csf_fixture "${CSF_TEST_DB_IMAGE:-mariadb:10.6}" >/dev/null
for attempt in $(seq 1 40); do
 if "${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); "$DB" -h127.0.0.1 -uroot -pfixture-only csf_fixture -e "SELECT 1"' >/dev/null 2>&1; then break; fi
 [[ "$attempt" != 40 ]] || { echo 'Disposable database failed to start.' >&2; exit 1; }
 sleep 1
done
PHP=("${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test")
"${PHP[@]}" -e CSF_TEST_PHASE=install "$PHP_IMAGE" bash /repo/tests/scheduling/container.sh
# Genuine logical backup/restore rehearsal, wholly inside the disposable DB container.
"${D[@]}" exec "$STAMP-db" sh -c 'DUMP=$(command -v mariadb-dump || command -v mysqldump); "$DUMP" -uroot -pfixture-only --single-transaction csf_fixture > /tmp/before-foundation.sql'
"${PHP[@]}" -e CSF_TEST_PHASE=foundation "$PHP_IMAGE" bash /repo/tests/scheduling/container.sh
"${D[@]}" exec "$STAMP-db" sh -c 'DUMP=$(command -v mariadb-dump || command -v mysqldump); "$DUMP" -uroot -pfixture-only --single-transaction csf_fixture > /tmp/before-v2.sql'
"${PHP[@]}" -e CSF_TEST_PHASE=calendar "$PHP_IMAGE" bash /repo/tests/scheduling/container.sh
# The web fixture is bound to loopback only, never publicly served.
if [[ "${CSF_TEST_BROWSER:-0}" == 1 ]]; then
 exec 9>"$ROOT/.wp-local/browser-relay.lock"
 flock 9
 "${D[@]}" run -d --name "$STAMP-web" --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" "$PHP_IMAGE" php -S 0.0.0.0:8899 -t /test/site >/dev/null
 for attempt in $(seq 1 30); do
  if "${D[@]}" exec "$STAMP-web" php -r '$s=@fsockopen("127.0.0.1",8899);if(!$s){exit(1);}fclose($s);' >/dev/null 2>&1; then break; fi
  sleep .2
 done
 CSF_TEST_WEB_CONTAINER="$STAMP-web" node "$ROOT/tests/scheduling/browser.js"
 CSF_TEST_WEB_CONTAINER="$STAMP-web" node "$ROOT/tests/scheduling/permissions-browser.js"
 "${D[@]}" rm -f "$STAMP-web" >/dev/null
 flock -u 9
fi
# Preserve the populated v3 fixture, test a genuine populated v2 upgrade and old-code fail-closed, then restore v3.
"${D[@]}" exec "$STAMP-db" sh -c 'DUMP=$(command -v mariadb-dump || command -v mysqldump); "$DUMP" -uroot -pfixture-only --single-transaction csf_fixture > /tmp/before-permission-upgrade.sql'
"${PHP[@]}" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/permissions-migration.php
"${PHP[@]}" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root --skip-plugins=csf-scheduling eval-file /repo/tests/scheduling/permissions-v2-read.php
"${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); "$DB" -uroot -pfixture-only csf_fixture < /tmp/before-permission-upgrade.sql'
"${PHP[@]}" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root --user=1 eval 'global $wpdb;$before=json_decode(file_get_contents("/test/v3-before-permission-migration.json"),true);foreach(CSF_Scheduling_Schema::definitions()as$n=>$d){$t=CSF_Scheduling_Schema::table($n);if($before[$n]!==$wpdb->get_results("SELECT * FROM `$t` ORDER BY 1",ARRAY_A)){WP_CLI::error("V3 restore data mismatch");}}if(!CSF_Scheduling_Schema::inspect()["ready"]||!CSF_Scheduling_Access::can("team_assign")){WP_CLI::error("V3 restore lost owner or schema");}echo "POPULATED_V3_LOGICAL_BACKUP_RESTORE_OWNER_AND_DATA_PASS\n";'
"${PHP[@]}" "$PHP_IMAGE" bash /repo/tests/scheduling/migration-race.sh
# Restore the real populated v1 logical backup with v2 code still present: access must fail closed.
"${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); for name in settings resources appointments allocations commands audit riders horses availability restrictions details history slots participants authorization; do "$DB" -uroot -pfixture-only csf_fixture -e "DROP TABLE IF EXISTS fixture_csf_sched_$name"; done; "$DB" -uroot -pfixture-only csf_fixture < /tmp/before-v2.sql'
"${PHP[@]}" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root --user=1 eval '$s=CSF_Scheduling_Schema::inspect(1);$before=json_decode(file_get_contents("/test/v1-settings.json"),true);if($s["errors"]||$s["missing"]||$s["version"]!==1||CSF_Scheduling_Access::can()||CSF_Scheduling_Schema::settings()!==$before){WP_CLI::error("v1 restore mismatch");}echo "POPULATED_V1_BACKUP_RESTORE_PASS_V2_ACCESS_DENIED\n";'

"${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); for name in settings resources appointments allocations commands audit riders horses availability restrictions details history slots participants authorization; do "$DB" -uroot -pfixture-only csf_fixture -e "DROP TABLE IF EXISTS fixture_csf_sched_$name"; done; "$DB" -uroot -pfixture-only csf_fixture < /tmp/before-foundation.sql'
"${PHP[@]}" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval 'global $wpdb; if (DB_NAME!=="csf_fixture" || is_plugin_active("csf-scheduling/csf-scheduling.php") || $wpdb->get_var("SHOW TABLES LIKE \"fixture_csf_sched_%\"") || $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_value LIKE \"%csf_schedule_%\" OR meta_value LIKE \"%csf_dr_%\"")!=0) { WP_CLI::error("Fixture restore mismatch"); } echo "LOGICAL_BACKUP_RESTORE_PASS\n";'

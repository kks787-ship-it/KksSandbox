#!/usr/bin/env bash
# Local-only real-provider integration; no farm config, published service or fake MFA adapter.
set -euo pipefail
umask 077
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
[[ -f "$ROOT/.wp-local/site/wp-includes/version.php" && -f "$ROOT/.wp-local/mfa-official/two-factor/two-factor.php" ]] || { echo 'Prepare pinned local WordPress and provider source first.' >&2; exit 1; }
D=(docker --host=unix:///var/run/docker.sock)
PHP_IMAGE="${CSF_TEST_PHP_IMAGE:-csf-scheduling-php81}"
[[ "$PHP_IMAGE" =~ ^csf-scheduling-php(81|83)$ ]] || { echo "Unreviewed local PHP fixture image" >&2; exit 1; }
# Keep other fixture lifecycles from changing network interfaces during browser assertions.
exec 8>"$ROOT/.wp-local/docker-fixture.lock"
flock 8
STAMP="csf-foundation-$$"
cleanup(){ "${D[@]}" logs "$STAMP-web" > "$ROOT/.wp-local/mfa-last-server.log" 2>&1 || true; "${D[@]}" rm -f "$STAMP-web" "$STAMP-db" >/dev/null 2>&1 || true; "${D[@]}" volume rm "$STAMP-site" >/dev/null 2>&1 || true; "${D[@]}" network rm "$STAMP-net" >/dev/null 2>&1 || true; }
trap cleanup EXIT
"${D[@]}" network create --internal "$STAMP-net" >/dev/null
"${D[@]}" volume create "$STAMP-site" >/dev/null
"${D[@]}" run -d --name "$STAMP-db" --network "$STAMP-net" --network-alias fixture-db --tmpfs /var/lib/mysql -e MYSQL_ROOT_PASSWORD=fixture-only -e MYSQL_ROOT_HOST=% -e MYSQL_DATABASE=csf_fixture "${CSF_TEST_DB_IMAGE:-mariadb:10.6}" >/dev/null
for attempt in $(seq 1 40); do
 if "${D[@]}" exec "$STAMP-db" sh -c 'DB=$(command -v mariadb || command -v mysql); "$DB" -h127.0.0.1 -uroot -pfixture-only csf_fixture -e "SELECT 1"' >/dev/null 2>&1; then break; fi
 [[ "$attempt" != 40 ]] || exit 1
 sleep 1
done
"${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" "$PHP_IMAGE" bash /repo/tests/scheduling/mfa-install.sh
if [[ "${CSF_TEST_FACILITY_SETTINGS:-0}" == 1 ]]; then
 "${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/settings-parse.php
fi
if [[ "${CSF_TEST_NATIVE_INSTALL_ONLY:-0}" == 1 ]]; then
 for operation in activate verify; do
  "${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" -e CSF_TEST_MFA_INSTALL_OPERATION="$operation" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/mfa-install-native.php
 done
 exit 0
fi
if [[ "${CSF_TEST_NATIVE_MAIL_ONLY:-0}" == 1 ]]; then
 for native_case in preview dispatch wrong_binding no_approval unexpected_sender_hook unexpected_recipient_hook wrong_sapi preview_case dispatch_case; do
  "${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" -e CSF_TEST_NATIVE_CASE="$native_case" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/native-mail-runtime.php
 done
 exit 0
fi
if [[ "${CSF_TEST_PROVIDER_POLICY_ONLY:-0}" == 1 ]]; then
 for trigger in 0 1; do
  "${D[@]}" run --rm --network "$STAMP-net" --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" -e CSF_TEST_PROVIDER_TRIGGER="$trigger" "$PHP_IMAGE" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/provider-policy.php
 done
 exit 0
fi
"${D[@]}" run -d --name "$STAMP-web" --network "$STAMP-net" -e PHP_CLI_SERVER_WORKERS=4 --mount "type=bind,src=$ROOT,dst=/repo,readonly" --mount "type=volume,src=$STAMP-site,dst=/test" "$PHP_IMAGE" php -S 0.0.0.0:8899 -t /test/site >/dev/null
for attempt in $(seq 1 30); do
 if "${D[@]}" exec "$STAMP-web" php -r '$s=@fsockopen("127.0.0.1",8899);if(!$s){exit(1);}fclose($s);' >/dev/null 2>&1; then break; fi
 [[ "$attempt" != 30 ]] || exit 1
 sleep .2
done
# Let the managed network monitor observe this fixture's own startup before Chromium launches.
sleep 2
exec 9>"$ROOT/.wp-local/browser-relay.lock"
flock 9
if [[ "${CSF_TEST_SECURITY_ONLY:-0}" != 1 ]]; then CSF_TEST_WEB_CONTAINER="$STAMP-web" node "$ROOT/tests/scheduling/mfa-browser.js";fi
if [[ "${CSF_TEST_SECURITY:-0}" == 1 ]]; then CSF_TEST_WEB_CONTAINER="$STAMP-web" node "$ROOT/tests/scheduling/security-browser.js";fi
flock -u 9
if [[ "${CSF_TEST_RESOURCE_HOURS:-0}" == 1 ]]; then
 "${D[@]}" exec -e CSF_RESOURCE_RACE=setup "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/resource-hours-race.php
 declare -A pids
 for worker in booking closure; do
  "${D[@]}" exec -e CSF_RESOURCE_RACE="$worker" "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/resource-hours-race.php &
  pids[$worker]=$!
 done
 wait "${pids[booking]}"
 wait "${pids[closure]}"
 "${D[@]}" exec -e CSF_RESOURCE_RACE=verify "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/resource-hours-race.php
fi
if [[ "${CSF_TEST_CATALOG:-0}" == 1 || "${CSF_TEST_RESOURCE_HOURS:-0}" == 1 ]]; then
 "${D[@]}" exec -e CSF_TEST_CATALOG_RECOVERY=before "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/catalog-recovery.php
 "${D[@]}" exec "$STAMP-db" sh -c 'DUMP=$(command -v mariadb-dump || command -v mysqldump); "$DUMP" -uroot -pfixture-only --single-transaction --skip-comments csf_fixture > /tmp/catalog.sql'
 "${D[@]}" exec "$STAMP-db" sh -c 'CLIENT=$(command -v mariadb || command -v mysql); "$CLIENT" -uroot -pfixture-only csf_fixture < /tmp/catalog.sql; rm /tmp/catalog.sql'
 "${D[@]}" exec -e CSF_TEST_CATALOG_RECOVERY=after "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval-file /repo/tests/scheduling/catalog-recovery.php
fi
"${D[@]}" exec -e CSF_TEST_MFA_DISABLED=1 "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval 'if(CSF_MFA_Session::ready()||CSF_MFA_Session::enrolled(true,1)||CSF_MFA_Session::verified(true,1)){throw new RuntimeException("disabled adapter granted access");}echo "MFA_DISABLED_DEFAULT_DENY_PASS\n";'
"${D[@]}" exec "$STAMP-web" php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root eval 'global $wp_version;echo "MFA_STACK WordPress=".$wp_version." PHP=".PHP_VERSION." SQL=".$GLOBALS["wpdb"]->db_version()."\n";'

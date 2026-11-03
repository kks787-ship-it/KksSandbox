#!/usr/bin/env bash
# LOCAL synthetic fixture only. No farm records, credentials, ports or outbound transport.
set -euo pipefail
root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
[[ -f "$root/vendor/bin/phpcs" ]]
stamp="csf-workflows-$$"
cleanup(){ docker rm -f "$stamp-db" >/dev/null 2>&1 || true; docker network rm "$stamp-net" >/dev/null 2>&1 || true; }
trap cleanup EXIT
docker run --rm --network none --mount "type=bind,src=$root,dst=/repo,readonly" csf-scheduling-php81 bash -c 'php /repo/vendor/bin/phpcs --standard=/repo/phpcs.xml.dist /repo/wp-content/plugins/csf-ranch-workflows && find /repo/wp-content/plugins/csf-ranch-workflows -name "*.php" -print0 | xargs -0 -n1 php -l'
docker network create --internal "$stamp-net" >/dev/null
docker run -d --name "$stamp-db" --network "$stamp-net" --network-alias fixture-db --tmpfs /var/lib/mysql -e MYSQL_ROOT_PASSWORD=fixture-only -e MYSQL_ROOT_HOST=% -e MYSQL_DATABASE=ranch_fixture "${CSF_TEST_DB_IMAGE:-mariadb:10.6}" >/dev/null
for attempt in $(seq 1 40); do
 if docker exec "$stamp-db" sh -c 'DB=$(command -v mariadb || command -v mysql); "$DB" -h127.0.0.1 -uroot -pfixture-only ranch_fixture -e "SELECT 1"' >/dev/null 2>&1; then break; fi
 [[ "$attempt" != 40 ]] || exit 1
 sleep 1
done
docker run --rm --network "$stamp-net" --mount "type=bind,src=$root,dst=/repo,readonly" -e CSF_WORKFLOW_FIXTURE=local-only csf-scheduling-php81 php /repo/tests/workflows/integration.php

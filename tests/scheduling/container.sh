#!/usr/bin/env bash
set -euo pipefail
if [[ "${CSF_TEST_PHASE:-all}" == install || "${CSF_TEST_PHASE:-all}" == all ]]; then
mkdir -p /test/site
tar --exclude=wp-config.php --exclude=wp-content -C /repo/.wp-local/scheduling-core -cf - . | tar -xf - -C /test/site
mkdir -p /test/site/wp-content/plugins /test/site/wp-content/themes
ln -s /repo/wp-content/plugins/csf-core /test/site/wp-content/plugins/csf-core
ln -s /repo/wp-content/plugins/csf-emergency /test/site/wp-content/plugins/csf-emergency
ln -s /repo/wp-content/plugins/csf-scheduling /test/site/wp-content/plugins/csf-scheduling
cp -a /repo/tests/fixtures/themes/csf-kadence-standin /test/site/wp-content/themes/
cat > /test/site/wp-config.php <<'PHP'
<?php
// Synthetic credentials for a non-published, internal Docker test network only.
define('DB_NAME','csf_fixture');define('DB_USER','root');define('DB_PASSWORD','fixture-only');define('DB_HOST','fixture-db');
define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');define('WP_ENVIRONMENT_TYPE',getenv('CSF_TEST_ENV') ?: 'local');
define('CSF_SCHEDULING_MIGRATION_ALLOWED',true);define('CSF_SCHEDULING_MASTER_BOOTSTRAP_ALLOWED',true);define('DISABLE_WP_CRON',true);
$table_prefix='fixture_';if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/');}require_once ABSPATH.'wp-settings.php';
PHP
mkdir -p /test/site/wp-content/mu-plugins
cp /repo/tests/scheduling/auth-fixture.php /test/site/wp-content/mu-plugins/auth-fixture.php
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
"${WP[@]}" core install --url=http://localhost:8899 --title=FoundationFixture --admin_user=fixture_operator --admin_password=fixture-only --admin_email=fixture@example.invalid --skip-email --quiet
"${WP[@]}" option update show_avatars 0 --quiet
"${WP[@]}" theme activate csf-kadence-standin --quiet
"${WP[@]}" plugin activate csf-core --quiet
fi
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
[[ "${CSF_TEST_PHASE:-all}" != install ]] || exit 0
if [[ "${CSF_TEST_PHASE:-all}" != calendar ]]; then
find /repo/wp-content/plugins/csf-scheduling -name '*.php' -print0 | xargs -0 -n1 php -l
"${WP[@]}" eval-file /repo/tests/scheduling/foundation.php

fi
if [[ "${CSF_TEST_PHASE:-all}" == foundation ]]; then
 "${WP[@]}" --user=1 eval 'if(DB_NAME!=="csf_fixture"){WP_CLI::error("Fixture required");}global $wpdb;foreach(array_diff(array_keys(CSF_Scheduling_Schema::definitions()),array_keys(CSF_Scheduling_Schema::definitions(1)))as$n){$t=CSF_Scheduling_Schema::table($n);$wpdb->query("DROP TABLE `$t`");}$wpdb->update(CSF_Scheduling_Schema::table("settings"),["schema_version"=>1],["id"=>1]);file_put_contents("/test/v1-settings.json",wp_json_encode(CSF_Scheduling_Schema::settings()));'
 exit 0
fi
"${WP[@]}" eval-file /repo/tests/scheduling/calendar.php
bash /repo/tests/scheduling/races.sh
php /repo/tests/scheduling/horse-correction-unit.php
"${WP[@]}" eval-file /repo/tests/scheduling/horse-correction.php
php /repo/tests/scheduling/horse-lifecycle-unit.php
php /repo/tests/scheduling/horse-delete-unit.php
php /repo/tests/scheduling/resource-hours-unit.php
php /repo/tests/scheduling/closure-edit-unit.php
php /repo/tests/scheduling/visit-plan-unit.php
php /repo/tests/scheduling/family-command-unit.php
php /repo/tests/scheduling/dashboard-unit.php
php /repo/tests/scheduling/notifications-unit.php
php /repo/tests/scheduling/email-sink-unit.php
"${WP[@]}" eval-file /repo/tests/scheduling/horse-lifecycle.php
bash /repo/tests/scheduling/horse-lifecycle-races.sh
"${WP[@]}" eval-file /repo/tests/scheduling/family-visit.php
bash /repo/tests/scheduling/family-races.sh
"${WP[@]}" eval-file /repo/tests/scheduling/usability.php
bash /repo/tests/scheduling/usability-races.sh
"${WP[@]}" eval-file /repo/tests/scheduling/permissions.php
bash /repo/tests/scheduling/permission-races.sh
"${WP[@]}" --user=1 csf scheduling migrate > /test/migration-preview.json
"${WP[@]}" --user=1 csf scheduling health > /test/health.json
printf 'CLI_PREVIEW_AND_HEALTH_PASS\n'
if "${WP[@]}" --user=1 csf scheduling migrate --apply --expected-url=https://wrong.invalid > /test/wrong-target.log 2>&1; then echo 'ERROR: wrong URL accepted'; exit 1; fi
grep -q 'Exact home/siteurl required' /test/wrong-target.log
if "${WP[@]}" --user=1 csf scheduling master --user-id=1 --owner-verified --revision=0 --command-id=00000000-0000-4000-8000-000000000011 --expected-url=https://wrong.invalid > /test/wrong-master-target.log 2>&1; then echo 'ERROR: wrong owner-bootstrap URL accepted'; exit 1; fi
grep -q 'Exact home/siteurl required for owner identity ceremony' /test/wrong-master-target.log
printf 'MASTER_WRONG_TARGET_DENIED\n'
if "${WP[@]}" --user=1 csf scheduling master --user-id=1 --owner-verified --revision=0 --command-id=00000000-0000-4000-8000-000000000012 --expected-url=http://localhost:8899 > /test/master-retired.log 2>&1; then echo 'ERROR: retired CLI ownership accepted';exit 1;fi
grep -q 'CLI ownership mutation retired' /test/master-retired.log
printf 'MASTER_CLI_RETIRED_DENIED\n'

CSF_TEST_ENV=production "${WP[@]}" --user=1 eval '$v=CSF_Scheduling_Schema::migrate();if(!is_wp_error($v)||$v->get_error_code()!=="migration_denied"){WP_CLI::error("Production migration not blocked");}echo "PRODUCTION_MIGRATION_DENIED\n";'

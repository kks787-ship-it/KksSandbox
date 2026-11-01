#!/usr/bin/env bash
set -euo pipefail
if [[ "${CSF_TEST_PHASE:-all}" != validate ]]; then
mkdir -p /test/site
tar --exclude=wp-config.php --exclude=wp-content -C /repo/.wp-local/site -cf - . | tar -xf - -C /test/site
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
define('CSF_SCHEDULING_MIGRATION_ALLOWED',true);define('DISABLE_WP_CRON',true);
$table_prefix='fixture_';if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/');}require_once ABSPATH.'wp-settings.php';
PHP
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
"${WP[@]}" core install --url=http://localhost:8899 --title=FoundationFixture --admin_user=fixture_operator --admin_password=fixture-only --admin_email=fixture@example.invalid --skip-email --quiet
"${WP[@]}" theme activate csf-kadence-standin --quiet
"${WP[@]}" plugin activate csf-core --quiet
fi
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
[[ "${CSF_TEST_PHASE:-all}" != install ]] || exit 0
php /repo/vendor/bin/phpcs --standard=/repo/phpcs.xml.dist /repo/wp-content/plugins/csf-scheduling
find /repo/wp-content/plugins/csf-scheduling -name '*.php' -print0 | xargs -0 -n1 php -l
"${WP[@]}" eval-file /repo/tests/scheduling/foundation.php
"${WP[@]}" --user=1 csf scheduling migrate > /test/migration-preview.json
"${WP[@]}" --user=1 csf scheduling health > /test/health.json
printf 'CLI_PREVIEW_AND_HEALTH_PASS\n'
if "${WP[@]}" --user=1 csf scheduling migrate --apply --expected-url=https://wrong.invalid > /test/wrong-target.log 2>&1; then echo 'ERROR: wrong URL accepted'; exit 1; fi
grep -q 'Exact home/siteurl required' /test/wrong-target.log
CSF_TEST_ENV=production "${WP[@]}" --user=1 eval '$v=CSF_Scheduling_Schema::migrate();if(!is_wp_error($v)||$v->get_error_code()!=="migration_denied"){WP_CLI::error("Production migration not blocked");}echo "PRODUCTION_MIGRATION_DENIED\n";'


# A true two-worker migration race starts from an empty owned schema, on this fixture only.
"${WP[@]}" --user=1 eval 'if (DB_NAME!=="csf_fixture" || wp_get_environment_type()!=="local") { WP_CLI::error("Fixture required"); } global $wpdb; foreach(CSF_Scheduling_Schema::definitions() as $name=>$def){$t=CSF_Scheduling_Schema::table($name);$wpdb->query("DROP TABLE `$t`");}'
"${WP[@]}" eval-file /repo/tests/scheduling/concurrent.php > /test/worker1.log 2>&1 &
WORKER1=$!
"${WP[@]}" eval-file /repo/tests/scheduling/concurrent.php > /test/worker2.log 2>&1 &
WORKER2=$!
wait "$WORKER1"
wait "$WORKER2"
"${WP[@]}" eval 'global $wpdb; $t=CSF_Scheduling_Schema::table("audit"); if (!CSF_Scheduling_Schema::inspect()["ready"] || (int)$wpdb->get_var("SELECT COUNT(*) FROM `$t`")!==1) { WP_CLI::error("Migration race duplicated or lost audit"); } echo "TWO_WORKER_MIGRATION_PASS_ONE_AUDIT\n";'

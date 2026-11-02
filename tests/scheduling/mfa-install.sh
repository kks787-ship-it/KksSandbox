#!/usr/bin/env bash
set -euo pipefail
mkdir -p /test/site
tar --exclude=wp-config.php --exclude=wp-content -C /repo/.wp-local/site -cf - . | tar -xf - -C /test/site
mkdir -p /test/site/wp-content/plugins /test/site/wp-content/themes /test/site/wp-content/mu-plugins
for name in csf-core csf-emergency csf-scheduling csf-mfa; do ln -s "/repo/wp-content/plugins/$name" "/test/site/wp-content/plugins/$name"; done
ln -s /repo/.wp-local/mfa-official/two-factor /test/site/wp-content/plugins/two-factor
cp -a /repo/tests/fixtures/themes/csf-kadence-standin /test/site/wp-content/themes/
cp /repo/tests/scheduling/mfa-fixture.php /test/site/wp-content/mu-plugins/
cat > /test/site/wp-config.php <<'PHP'
<?php
define('DB_NAME','csf_fixture');define('DB_USER','root');define('DB_PASSWORD','fixture-only');define('DB_HOST','fixture-db');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');
define('WP_ENVIRONMENT_TYPE','local');define('CSF_MFA_ENABLED',!getenv('CSF_TEST_MFA_DISABLED'));define('CSF_SCHEDULING_MIGRATION_ALLOWED',true);define('CSF_SCHEDULING_IDENTITY_PATH','/test/identity-invitation.json');define('CSF_MFA_ENFORCE_ADMIN',file_exists('/test/admin-enforced'));define('DISABLE_WP_CRON',true);define('WP_DEBUG',true);define('WP_DEBUG_DISPLAY',false);
$table_prefix='fixture_';if(!defined('ABSPATH')){define('ABSPATH',__DIR__.'/');}require_once ABSPATH.'wp-settings.php';
PHP
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
"${WP[@]}" core install --url=http://localhost:8899 --title=MFAFixture --admin_user=fixture_operator --admin_password=fixture-only --admin_email=fixture@example.invalid --skip-email --quiet
"${WP[@]}" option update show_avatars 0 --quiet
"${WP[@]}" theme activate csf-kadence-standin --quiet
"${WP[@]}" plugin activate csf-core csf-scheduling two-factor csf-mfa --quiet
"${WP[@]}" eval-file /repo/tests/scheduling/mfa-setup.php

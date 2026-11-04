<?php
// Synthetic fixture only. No real transport, account, or protected-site configuration.
if (ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('Disposable fixture required');
}
$operation = getenv('CSF_TEST_MFA_INSTALL_OPERATION');
if (!in_array($operation, array('activate', 'verify'), true)) { throw new RuntimeException('Fixture operation'); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
deactivate_plugins(array('csf-scheduling/csf-scheduling.php'), true);
if ($operation === 'activate') { deactivate_plugins(array('two-factor/two-factor.php', 'csf-mfa/csf-mfa.php'), true); }
$template = file_get_contents('/repo/scripts/pilot/mfa_activation.php.txt');
$template = str_replace("PHP_SAPI === 'fpm-fcgi'", "PHP_SAPI === 'cli'", $template);
$template = str_replace("require_once \$p['root'] . '/wp-load.php';", '/* Disposable WP-CLI fixture already loaded. */', $template);
if (defined('DISABLE_WP_CRON')) { $template = str_replace("define('DISABLE_WP_CRON', true);", '/* Fixture cron already disabled. */', $template); }
$parameters = array('root' => '/test/site', 'url' => get_option('home'), 'uid' => posix_geteuid(), 'operation' => $operation, 'before_plugins_sha256' => hash('sha256', maybe_serialize(get_option('active_plugins'))));
eval('?>' . str_replace('__PARAMETERS__', base64_encode(wp_json_encode($parameters)), $template));
if ($out['state'] !== ($operation === 'activate' ? 'activated' : 'verified') || $out['enforcement'] !== false) { throw new RuntimeException('Native activation fixture failed'); }
echo "\nMFA_NATIVE_INSTALL_CASE_PASS=$operation\n";

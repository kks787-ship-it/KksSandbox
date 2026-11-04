<?php
// Research fixture ONLY. Not a deployable policy or an authentication adapter.
if (!defined('ABSPATH') || ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local') {
    throw new RuntimeException('Disposable fixture required');
}
global $checks;
$checks = 0;
function policy_check($ok, $label) {
    global $checks;
    if (!$ok) { throw new RuntimeException($label); }
    ++$checks;
    echo "PASS $label\n";
}
$trigger = getenv('CSF_TEST_PROVIDER_TRIGGER') === '1';
$id = wp_create_user($trigger ? 'policy_trigger' : 'policy_research', 'fixture-only', 'policy-' . ($trigger ? 'trigger' : 'research') . '@example.invalid');
policy_check(!is_wp_error($id), 'synthetic policy identity created only in disposable fixture');
$user = get_userdata($id);
$original = $user->user_pass;
$totp = Two_Factor_Totp::get_instance();
$totp->set_user_totp_key($id, Two_Factor_Totp::generate_key());
update_user_meta($id, '_two_factor_enabled_providers', array('Two_Factor_Totp', 'Two_Factor_Backup_Codes'));
update_user_meta($id, '_two_factor_provider', 'Two_Factor_Totp');
$codes = Two_Factor_Backup_Codes::get_instance()->generate_codes($user);
$mail = array();
add_filter('pre_wp_mail', static function ($result, $attributes) use (&$mail) {
    foreach ((array) $attributes['to'] as $recipient) {
        if (!str_ends_with($recipient, '@example.invalid')) { throw new RuntimeException('Non-fixture recipient blocked'); }
    }
    $mail[] = $attributes;
    return false; // Capture attempted mail, never transport it or claim delivered.
}, PHP_INT_MAX, 2);

// Compare the unmodified provider baseline with the EXISTING pinned adapter's supported hook.
// Temporarily removing that hook is restricted to this disposable research process.
if (!$trigger) {
    remove_filter('two_factor_providers', array('CSF_MFA_Session', 'providers'), PHP_INT_MAX);
    update_user_meta($id, '_two_factor_enabled_providers', array('Retired_Fixture_Provider'));
    $native = Two_Factor_Core::get_available_providers_for_user($user);
    policy_check(is_array($native) && isset($native['Two_Factor_Email']), 'native provider-loss policy offers email fallback');
    add_filter('two_factor_providers', array('CSF_MFA_Session', 'providers'), PHP_INT_MAX);
}
if (!$trigger) {
    $closed = Two_Factor_Core::get_available_providers_for_user($user);
    policy_check(is_wp_error($closed) && $closed->get_error_code() === 'no_available_2fa_methods', 'existing enabled adapter fails closed after provider loss');
    policy_check(!isset(Two_Factor_Core::get_supported_providers_for_user(1)['Two_Factor_Email']), 'existing adapter restriction also applies to other WordPress identities');
    policy_check(apply_filters('two_factor_fallback_provider_for_user', 'Two_Factor_Email', $id) === 'Two_Factor_Email', 'vendor fallback setting unchanged but email provider unavailable');
    update_user_meta($id, '_two_factor_enabled_providers', array('Two_Factor_Totp', 'Two_Factor_Backup_Codes'));
    $available = Two_Factor_Core::get_available_providers_for_user($user);
    policy_check(is_array($available) && isset($available['Two_Factor_Totp'], $available['Two_Factor_Backup_Codes']) && !isset($available['Two_Factor_Email']), 'alternative retains configured authenticator and backup methods only');
    policy_check(Two_Factor_Backup_Codes::get_instance()->validate_code($user, $codes[0]), 'alternative retains actual backup-code recovery');
    policy_check(!Two_Factor_Backup_Codes::get_instance()->validate_code($user, $codes[0]), 'consumed backup code is rejected');
    policy_check(!CSF_MFA_Session::verified(true, $id), 'enrollment and direct backup validation do not create session proof');
    update_user_meta($id, Two_Factor_Core::USER_FAILED_LOGIN_ATTEMPTS_KEY, 29);
    policy_check(!Two_Factor_Core::should_reset_password($id), 'alternative retains native below-threshold behavior');
    update_user_meta($id, Two_Factor_Core::USER_FAILED_LOGIN_ATTEMPTS_KEY, 30);
    policy_check(Two_Factor_Core::should_reset_password($id), 'alternative retains native reset threshold of thirty');
    update_user_meta($id, Two_Factor_Core::USER_RATE_LIMIT_KEY, time());
    policy_check(Two_Factor_Core::get_user_time_delay($user) === 900 && Two_Factor_Core::is_user_rate_limited($user), 'native backoff and fifteen-minute cap remain enforced');
    policy_check(count($mail) === 0 && get_userdata($id)->user_pass === $original, 'provider selection alone sends no mail and changes no password');
    $blocked = false;
    try { wp_mail('blocked@outside.example', 'Fixture rejection', 'Must never leave fixture'); }
    catch (RuntimeException $error) { $blocked = true; }
    policy_check($blocked && count($mail) === 0, 'non-fixture mail recipient is rejected before transport');
    remove_filter('two_factor_providers', array('CSF_MFA_Session', 'providers'), PHP_INT_MAX);
    policy_check(isset(Two_Factor_Core::get_supported_providers_for_user($user)['Two_Factor_Email']), 'disabling adapter registry hook restores native provider selection in this research process');
    add_filter('two_factor_providers', array('CSF_MFA_Session', 'providers'), PHP_INT_MAX);
    echo "PROVIDER_POLICY_CHECKS=$checks\n";
    return;
}

// Exercise the real provider's terminating failed-factor path, not a mock reset implementation.
wp_set_current_user(0);
update_user_meta($id, Two_Factor_Core::USER_FAILED_LOGIN_ATTEMPTS_KEY, 29);
delete_user_meta($id, Two_Factor_Core::USER_RATE_LIMIT_KEY);
$_POST['authcode'] = 'invalid-fixture-code';
// WP-CLI does not load wp-login.php's presentation helpers. No auth logic is replaced.
if (!function_exists('login_header')) { function login_header() {} }
if (!function_exists('login_footer')) { function login_footer() {} }
ob_start();
register_shutdown_function(static function () use ($id, $original, &$mail) {
    while (ob_get_level()) { ob_end_clean(); }
    try {
        $fatal = error_get_last();
        if ($fatal && in_array($fatal['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) { throw new RuntimeException('Unexpected PHP fatal'); }
        policy_check(get_userdata($id)->user_pass !== $original, 'actual thirtieth failed factor resets synthetic password');
        policy_check(!wp_check_password('fixture-only', get_userdata($id)->user_pass, $id), 'old synthetic password is rejected after native reset');
        policy_check((bool) get_user_meta($id, Two_Factor_Core::USER_PASSWORD_WAS_RESET_KEY, true), 'native password-reset flag recorded');
        policy_check(count($mail) === 2, 'native user and administrator mail attempts captured without delivery');
        policy_check($mail[0]['to'] !== $mail[1]['to'], 'native notification paths target distinct fixture recipients');
        policy_check(!get_user_meta($id, Two_Factor_Core::USER_FAILED_LOGIN_ATTEMPTS_KEY, true), 'native reset clears failed-factor counter');
        policy_check(!CSF_MFA_Session::verified(true, $id), 'reset and notifications never supply Digital Ranch proof');
        echo "PROVIDER_THRESHOLD_CHECKS=7\n";
    } catch (Throwable $error) { echo "FAIL provider threshold assertions\n"; exit(1); }
});
Two_Factor_Core::process_provider($totp, $user, true);
throw new RuntimeException('Expected native reset path to terminate');

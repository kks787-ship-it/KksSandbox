<?php
/** Local-only inquiry regressions. All mail is mocked; no external delivery. */

$csf_root = getenv( 'CSF_SETUP_TEST_ROOT' );
if ( ! $csf_root || 0 !== strpos( $csf_root, '/tmp/csf-setup-regression.' ) || ABSPATH !== $csf_root . '/site/' || FQDB !== $csf_root . '/test.sqlite' || 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Use run-setup-reporting.sh; this test requires its isolated fixture.' );
}
$csf_checks = 0;
$csf_check = static function ( $ok, $name ) use ( &$csf_checks ) {
	if ( ! $ok ) { throw new RuntimeException( $name ); }
	++$csf_checks;
	WP_CLI::log( 'PASS: ' . $name );
};
$csf_mail = array();
$csf_transport = static function ( $result, $atts ) use ( &$csf_mail ) { $csf_mail[] = $atts; return true; };
add_filter( 'pre_wp_mail', $csf_transport, 10, 2 );
$csf_challenge = static function ( $offset = -5 ) {
	$value = ( time() + $offset ) . ':' . wp_generate_uuid4();
	return $value . ':' . hash_hmac( 'sha256', $value, wp_salt( 'nonce' ) );
};
$csf_input = static function () use ( $csf_challenge ) {
	return array( '_csf_nonce' => wp_create_nonce( CSF_Core_Inquiry::ACTION ), 'challenge' => $csf_challenge(), 'website' => '', 'name' => 'Synthetic Visitor', 'email' => 'visitor@example.invalid', 'message' => "A synthetic question.\nNo real personal information.", 'consent' => '1' );
};
$csf_clear = static function () {
	global $wpdb;
	foreach ( $wpdb->get_col( $wpdb->prepare( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( CSF_Core_Inquiry::PREFIX ) . '%' ) ) as $key ) { delete_option( $key ); }
};
$csf_rejected = static function ( $input, $code, $name, $ip = '192.0.2.1' ) use ( $csf_check, &$csf_mail ) {
	$count = count( $csf_mail );
	$result = CSF_Core_Inquiry::submit( $input, $ip );
	$csf_check( is_wp_error( $result ) && $code === $result->get_error_code() && count( $csf_mail ) === $count, $name );
};
wp_set_current_user( 0 );
delete_option( CSF_Core_Inquiry::OPTION );
$csf_clear();
$csf_rejected( $csf_input(), 'unavailable', 'disabled default rejects mail' );
$csf_check( '' === CSF_Core_Inquiry::render(), 'disabled form is absent for visitors' );
update_option( CSF_Core_Inquiry::OPTION, array( 'recipient' => 'inbox@example.invalid', 'enabled' => 1, 'delivery_verified' => 0 ) );
$csf_rejected( $csf_input(), 'unavailable', 'enabling without delivery verification is rejected' );
$csf_changed = CSF_Core_Inquiry::sanitize( array( 'recipient' => 'changed@example.invalid', 'enabled' => 1, 'delivery_verified' => 1 ) );
$csf_check( ! $csf_changed['enabled'] && ! $csf_changed['delivery_verified'], 'recipient change clears verification and enablement' );
$csf_same = CSF_Core_Inquiry::sanitize( array( 'recipient' => 'inbox@example.invalid', 'enabled' => 1, 'delivery_verified' => 1 ) );
$csf_check( $csf_same['enabled'] && $csf_same['delivery_verified'], 'saved recipient can be explicitly verified and enabled' );
$csf_bad = CSF_Core_Inquiry::sanitize( array( 'recipient' => "inbox@example.invalid\r\nBcc: other@example.invalid", 'enabled' => 1, 'delivery_verified' => 1 ) );
$csf_check( '' === $csf_bad['recipient'] && ! $csf_bad['enabled'], 'recipient header injection rejected' );
update_option( CSF_Core_Inquiry::OPTION, array( 'recipient' => 'inbox@example.invalid', 'enabled' => 1, 'delivery_verified' => 1 ) );
foreach ( array( '_csf_nonce', 'challenge', 'website', 'name', 'email', 'message', 'consent' ) as $csf_field ) {
	$csf_data = $csf_input(); $csf_data[ $csf_field ] = array( 'unexpected' );
	$csf_rejected( $csf_data, 'invalid', 'array input rejected: ' . $csf_field );
}
foreach ( array(
	array( '_csf_nonce', 'bad', 'invalid nonce' ),
	array( 'challenge', $csf_challenge( 20 ), 'future challenge' ),
	array( 'challenge', $csf_challenge( 0 ), 'instant submission' ),
	array( 'challenge', $csf_challenge( -7201 ), 'expired challenge' ),
	array( 'challenge', $csf_challenge() . 'tampered', 'tampered challenge' ),
	array( 'website', 'spam.example.invalid', 'honeypot' ),
	array( 'name', '   ', 'empty name' ),
	array( 'name', str_repeat( 'a', 121 ), 'oversized name' ),
	array( 'email', 'invalid email', 'invalid email' ),
	array( 'email', "visitor@example.invalid\r\nBcc: other@example.invalid", 'reply-to injection' ),
	array( 'message', str_repeat( 'a', 4001 ), 'oversized message' ),
	array( 'message', '  ', 'empty message' ),
	array( 'consent', '0', 'missing reply permission' ),
) as $csf_case ) {
	$csf_data = $csf_input(); $csf_data[ $csf_case[0] ] = $csf_case[1];
	$csf_rejected( $csf_data, 'invalid', $csf_case[2] . ' does not send' );
}
$csf_rejected( $csf_input(), 'limited', 'missing server client address fails closed', '' );
$csf_good = $csf_input();
$csf_good['to'] = 'attacker@example.invalid';
$csf_good['subject'] = "Injected\r\nBcc: attacker@example.invalid";
$csf_check( true === CSF_Core_Inquiry::submit( $csf_good, '192.0.2.1' ), 'valid inquiry accepted by mocked transport' );
$csf_sent = $csf_mail[0];
$csf_check( 'inbox@example.invalid' === $csf_sent['to'] && 'Corner Stone Farm website inquiry' === $csf_sent['subject'] && array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: visitor@example.invalid' ) === $csf_sent['headers'] && ! $csf_sent['attachments'], 'fixed recipient/subject, safe Reply-To, no attachments or auto-reply' );
$csf_rejected( $csf_good, 'limited', 'replay cannot send twice', '192.0.2.2' );
$csf_three = true;
for ( $csf_i = 0; $csf_i < 3; ++$csf_i ) { $csf_three = $csf_three && true === CSF_Core_Inquiry::submit( $csf_input(), '192.0.2.3' ); }
$csf_check( $csf_three, 'per-client allowance accepts three distinct inquiries' );
$csf_rejected( $csf_input(), 'limited', 'fourth client inquiry rejected', '192.0.2.3' );
$csf_clear();
$csf_twenty = true;
for ( $csf_i = 1; $csf_i <= 20; ++$csf_i ) { $csf_twenty = $csf_twenty && true === CSF_Core_Inquiry::submit( $csf_input(), '198.51.100.' . $csf_i ); }
$csf_check( $csf_twenty, 'site window allows twenty synthetic inquiries' );
$csf_rejected( $csf_input(), 'limited', 'site cap rejects twenty-first inquiry', '198.51.100.21' );
$csf_clear();
remove_filter( 'pre_wp_mail', $csf_transport );
$csf_no_mail = static fn() => false;
add_filter( 'pre_wp_mail', $csf_no_mail );
$csf_result = CSF_Core_Inquiry::submit( $csf_input(), '192.0.2.4' );
$csf_check( is_wp_error( $csf_result ) && 'mail_failed' === $csf_result->get_error_code(), 'transport rejection never reports success' );
remove_filter( 'pre_wp_mail', $csf_no_mail );
add_filter( 'pre_wp_mail', $csf_transport, 10, 2 );
$csf_form = CSF_Core_Inquiry::render();
$csf_check( false !== strpos( $csf_form, 'method="post"' ) && false !== strpos( $csf_form, 'maxlength="4000"' ) && false === strpos( $csf_form, 'inbox@example.invalid' ), 'bounded form does not publish recipient' );
$csf_check( false !== strpos( do_blocks( '<!-- wp:csf/inquiry-form /-->' ), 'Send an inquiry' ), 'inquiry block renders through WordPress' );
add_option( CSF_Core_Inquiry::PREFIX . 'expired-fixture', (string) ( time() - 10 ), '', false );
add_option( CSF_Core_Inquiry::PREFIX . 'future-fixture', (string) ( time() + 3600 ), '', false );
add_option( 'unrelated-fixture-option', 'keep', '', false );
CSF_Core_Inquiry::cleanup();
$csf_check( false === get_option( CSF_Core_Inquiry::PREFIX . 'expired-fixture' ) && get_option( CSF_Core_Inquiry::PREFIX . 'future-fixture' ) && 'keep' === get_option( 'unrelated-fixture-option' ), 'cleanup removes only expired inquiry keys' );
global $wpdb;
$csf_keys = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value, autoload FROM $wpdb->options WHERE option_name LIKE %s", $wpdb->esc_like( CSF_Core_Inquiry::PREFIX ) . '%' ) );
$csf_private = true;
foreach ( $csf_keys as $csf_key ) { $csf_private = $csf_private && ctype_digit( $csf_key->option_value ) && ! in_array( $csf_key->autoload, array( 'yes', 'on', 'auto-on' ), true ) && false === strpos( $csf_key->option_name, '192.0.2.' ); }
$csf_check( $csf_private && wp_next_scheduled( 'csf_inquiry_cleanup' ), 'rate keys contain only expiry and salted identifiers, do not autoload, and have scheduled cleanup' );
WP_CLI::success( $csf_checks . ' isolated inquiry checks passed; all mail mocked.' );

<?php
/** Confirmation integrity checks on the isolated local fixture; never send mail. */

$csf_root = getenv( 'CSF_SETUP_TEST_ROOT' );
if ( ! $csf_root || 0 !== strpos( $csf_root, '/tmp/csf-setup-regression.' ) || ABSPATH !== $csf_root . '/site/' || FQDB !== $csf_root . '/test.sqlite' || 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Use run-setup-reporting.sh; isolated fixture required.' );
}
wp_set_current_user( 0 );
update_option( CSF_Core_Inquiry::OPTION, array( 'recipient' => 'inbox@example.invalid', 'enabled' => 1, 'delivery_verified' => 1 ) );
$csf_checks = 0;
$csf_check = static function ( $ok, $name ) use ( &$csf_checks ) {
	if ( ! $ok ) { throw new RuntimeException( $name ); }
	++$csf_checks;
	WP_CLI::log( 'PASS: ' . $name );
};
$csf_mail_calls = 0;
add_filter( 'pre_wp_mail', static function () use ( &$csf_mail_calls ) { ++$csf_mail_calls; return false; } );
$csf_notice = static function ( $receipt = null ) {
	unset( $_GET['csf_inquiry'] );
	if ( null !== $receipt ) { $_GET['csf_inquiry'] = $receipt; }
	return false !== strpos( CSF_Core_Inquiry::render(), 'role="status"' );
};
// Fixture-only signing authority tests expiry/site binding without calling a live provider.
$csf_receipt = static function ( $offset = 0, $site = null ) {
	$value = ( time() + $offset ) . ':' . wp_generate_uuid4();
	$site = $site ?? home_url( '/contact/' );
	return $value . ':' . hash_hmac( 'sha256', 'csf-inquiry-confirmation-v1|' . $site . '|' . $value, wp_salt( 'nonce' ) );
};
$csf_check( ! $csf_notice(), 'ordinary GET has no acknowledgment' );
foreach ( array( 'submitted', '', 'forged:receipt:value', array( 'submitted' ), str_repeat( 'a', 1000 ) ) as $csf_fake ) {
	$csf_check( ! $csf_notice( $csf_fake ), 'fabricated or malformed public query cannot acknowledge a submission' );
}
$csf_check( ! $csf_notice( CSF_Core_Inquiry::challenge() ), 'public anti-spam challenge cannot be repurposed as a success receipt' );
$csf_valid = $csf_receipt();
$csf_check( ! $csf_notice( substr( $csf_valid, 0, -1 ) . ( '0' === substr( $csf_valid, -1 ) ? '1' : '0' ) ), 'tampered signature is rejected' );
$csf_check( ! $csf_notice( $csf_receipt( -CSF_Core_Inquiry::RECEIPT_TTL ) ), 'receipt expires at the five-minute boundary' );
$csf_check( ! $csf_notice( $csf_receipt( 30 ) ), 'future receipt is rejected' );
$csf_check( ! $csf_notice( $csf_receipt( 0, 'https://other.example.invalid/contact/' ) ), 'receipt is bound to this site even when salts are shared' );
$csf_check( $csf_notice( $csf_valid ), 'valid server-signed receipt displays acknowledgment once' );
$csf_check( ! $csf_notice( $csf_valid ), 'replayed receipt cannot display another acknowledgment' );
$csf_second = $csf_receipt();
update_option( CSF_Core_Inquiry::OPTION, array( 'recipient' => 'inbox@example.invalid', 'enabled' => 0, 'delivery_verified' => 1 ) );
$csf_check( ! $csf_notice( $csf_second ), 'valid receipt never enables a disabled form or displays its acknowledgment' );
update_option( CSF_Core_Inquiry::OPTION, array( 'recipient' => 'inbox@example.invalid', 'enabled' => 1, 'delivery_verified' => 1 ) );
$csf_check( $csf_notice( $csf_second ), 'disabled rendering did not consume the receipt' );
$csf_key = CSF_Core_Inquiry::PREFIX . 'receipt_' . hash( 'sha256', $csf_valid );
$csf_check( ctype_digit( get_option( $csf_key ) ) && false === strpos( $csf_key, $csf_valid ), 'consumed receipt retains only digest and numeric expiry' );
$csf_check( 0 === $csf_mail_calls, 'confirmation rendering and forged GETs never send mail' );
unset( $_GET['csf_inquiry'] );
WP_CLI::success( $csf_checks . ' confirmation integrity checks passed; no mail sent.' );

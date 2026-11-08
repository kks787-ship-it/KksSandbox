<?php
/** Billing component.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Reviewed configuration from server-managed constants; no built-in products or terms. */
final class CSF_Billing_Config {
	/** Unconfigured is closed. No production or live PayPal adapter exists.
	 */
	public static function enabled() {
		return defined( 'CSF_BILLING_SANDBOX_ENABLED' ) && true === CSF_BILLING_SANDBOX_ENABLED && in_array( wp_get_environment_type(), array( 'local', 'development' ), true );
	}
	/** Non-secret catalog may be reviewed in deployment config; empty by default.
	 */
	public static function catalog() {
		return defined( 'CSF_BILLING_CATALOG_JSON' ) ? json_decode( CSF_BILLING_CATALOG_JSON, true, 32, JSON_THROW_ON_ERROR ) : array(
			'products' => array(),
			'terms'    => null,
		);
	}
	/** Reviewed local page path, never a browser-provided redirect.
	 *
	 * @return string
	 */
	public static function return_url() {
		$path = defined( 'CSF_BILLING_PAGE_PATH' ) ? CSF_BILLING_PAGE_PATH : '/gift-certificates/';
		CSF_Billing_Domain::need( is_string( $path ) && preg_match( '~^/(?:[a-z0-9-]+/)+$~D', $path ), 'configuration_incomplete' );
		$url = home_url( $path );
		CSF_Billing_Domain::need( str_starts_with( $url, 'https://' ), 'configuration_incomplete' );
		return $url;
	}

	/** Read a mandatory secret without returning it to the browser.
	 *
	 * @param string $name Allowlisted identifier.
	 */
	public static function secret( $name ) {
		CSF_Billing_Domain::need( defined( $name ) && is_string( constant( $name ) ) && strlen( constant( $name ) ) >= 8, 'configuration_incomplete' );
		return constant( $name );
	}
	/** AES-GCM data key, separately provisioned; never silently generate/replace one.
	 */
	public static function key() {
		// Binary key/envelope or OAuth Basic authentication encoding; never evaluated as code.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$key = base64_decode( self::secret( 'CSF_BILLING_DATA_KEY' ), true );
		CSF_Billing_Domain::need( is_string( $key ) && 32 === strlen( $key ) && function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' ), 'encryption_unavailable' );
		return $key;
	}
	/** Authenticated encryption for private certificate number/contact evidence.
	 *
	 * @param string $plain Private plaintext.
	 */
	public static function seal( $plain ) {
		$key    = self::key();
		$nonce  = random_bytes( 12 );
		$tag    = '';
		$cipher = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, 'csf-billing-v1' );
		CSF_Billing_Domain::need( false !== $cipher, 'encryption_unavailable' );
		// Binary key/envelope or OAuth Basic authentication encoding; never evaluated as code.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return base64_encode( $nonce . $tag . $cipher );
	}
	/** Authorized callers only.
	 *
	 * @param string $sealed Authenticated encrypted envelope.
	 */
	public static function open( $sealed ) {
		$key = self::key();
		// Binary key/envelope or OAuth Basic authentication encoding; never evaluated as code.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$raw = base64_decode( $sealed, true );
		CSF_Billing_Domain::need( is_string( $raw ) && strlen( $raw ) >= 28, 'private_data_unavailable' );
		$plain = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ), 'csf-billing-v1' );
		CSF_Billing_Domain::need( false !== $plain, 'private_data_unavailable' );
		return $plain;
	}
}

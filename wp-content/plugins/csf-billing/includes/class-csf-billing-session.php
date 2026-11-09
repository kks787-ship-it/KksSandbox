<?php
/** Short-lived, site-bound anonymous checkout credentials.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** A WordPress guest nonce alone is not customer authorization. */
final class CSF_Billing_Session {
	const COOKIE = '__Host-csf-gift-session';
	/** Issue a bounded signed cookie value; caller sets secure HTTP cookie attributes.
	 *
	 * @return string
	 */
	public static function issue() {
		$body = bin2hex( random_bytes( 32 ) ) . '.' . ( time() + 7200 );
		return $body . '.' . self::mac( 'cookie|' . $body );
	}
	/** Validate age, signature and exact shape before deriving customer authority.
	 *
	 * @param string $value Candidate cookie.
	 * @return string
	 */
	public static function validate( $value ) {
		CSF_Billing_Domain::need( is_string( $value ) && preg_match( '/^([a-f0-9]{64})\.([0-9]{10})\.([a-f0-9]{64})$/D', $value, $parts ), 'session_required' );
		CSF_Billing_Domain::need( (int) $parts[2] > time() && (int) $parts[2] <= time() + 7200 && hash_equals( self::mac( 'cookie|' . $parts[1] . '.' . $parts[2] ), $parts[3] ), 'session_required' );
		return $value;
	}
	/** Private database binding; the bearer cookie never enters the ledger.
	 *
	 * @param string $value Valid signed cookie.
	 * @return string
	 */
	public static function digest( $value ) {
		return hash( 'sha256', self::validate( $value ) );
	}
	/** CSRF proof exposed only to same-origin, uncached requests.
	 *
	 * @param string $value Valid signed cookie.
	 * @return string
	 */
	public static function csrf( $value ) {
		return self::mac( 'csrf|' . self::validate( $value ) );
	}
	/** Require exact browser origin, JSON and TLS; ignore forwarded host/IP headers.
	 *
	 * @param WP_REST_Request $request Current request.
	 */
	public static function origin( $request ) {
		$home   = wp_parse_url( home_url( '/' ) );
		$origin = 'https://' . $home['host'] . ( isset( $home['port'] ) && 443 !== $home['port'] ? ':' . $home['port'] : '' );
		CSF_Billing_Domain::need( is_ssl() && 'https' === ( $home['scheme'] ?? '' ) && 'POST' === $request->get_method() && hash_equals( $origin, $request->get_header( 'origin' ) ) && preg_match( '~^application/json(?:\s*;|$)~i', $request->get_header( 'content-type' ) ) && strlen( $request->get_body() ) <= 16384, 'request_forbidden' );
	}
	/** Read the bearer cookie without putting it in URLs, JavaScript or logs.
	 *
	 * @return string
	 */
	public static function current() {
		// Strict signature/shape validation is the sanitizer for this opaque credential.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		return self::validate( isset( $_COOKIE[ self::COOKIE ] ) ? wp_unslash( $_COOKIE[ self::COOKIE ] ) : '' );
	}
	/** Site-specific purpose-separated HMAC.
	 *
	 * @param string $text Fixed-purpose payload.
	 * @return string
	 */
	private static function mac( $text ) {
		return hash_hmac( 'sha256', home_url( '/' ) . '|' . $text, CSF_Billing_Config::key() );
	}
}

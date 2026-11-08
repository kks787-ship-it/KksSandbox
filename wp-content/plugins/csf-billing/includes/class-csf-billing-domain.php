<?php
/** Gift-certificate invariants.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** Pure validation and immutable payment bindings. */
final class CSF_Billing_Domain {
	/** Enforce a named invariant.
	 *
	 * @param bool   $condition Required invariant.
	 * @param string $code Compiled error identifier.
	 * @throws CSF_Billing_Failure On a failed invariant.
	 */
	public static function need( $condition, $code = 'invalid_request' ) {
		if ( ! $condition ) {
			throw new CSF_Billing_Failure( esc_html( $code ) );
		}
	}
	/** Require a canonical command/order UUID.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function uuid( $value ) {
		self::need( is_string( $value ) && 1 === preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/D', $value ) );
		return $value;
	}
	/** Provider IDs are never URLs or SQL fragments.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function provider_id( $value ) {
		self::need( is_string( $value ) && 1 === preg_match( '/^[A-Z0-9]{8,32}$/D', $value ), 'provider_binding_invalid' );
		return $value;
	}
	/** Integer minor units; no floating point or client price.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function minor( $value ) {
		self::need( is_string( $value ) && 1 === preg_match( '/^(0|[1-9][0-9]{0,6})\.[0-9]{2}$/D', $value ), 'amount_invalid' );
		$parts = explode( '.', $value );
		return (int) $parts[0] * 100 + (int) $parts[1];
	}
	/** Exact decimal representation for PayPal USD.
	 *
	 * @param int $minor Positive USD minor units.
	 */
	public static function amount( $minor ) {
		self::need( is_int( $minor ) && $minor > 0 && $minor <= 100000000 );
		return intdiv( $minor, 100 ) . '.' . str_pad( (string) ( $minor % 100 ), 2, '0', STR_PAD_LEFT );
	}
	/** Only approved single-payment packages; no guessed annual/quarterly mechanics.
	 *
	 * @param array $product Reviewed product snapshot.
	 */
	public static function product( $product ) {
		self::need( is_array( $product ) && count( $product ) === 9 && ! array_diff( array( 'id', 'revision', 'label', 'rides', 'minutes_per_ride', 'amount_minor', 'currency', 'payment_mode', 'approved' ), array_keys( $product ) ), 'catalog_unapproved' );
		self::uuid( $product['id'] );
		self::need( true === $product['approved'] && 'one_time' === $product['payment_mode'] && 'USD' === $product['currency'], 'catalog_unapproved' );
		foreach ( array( 'revision', 'rides', 'minutes_per_ride', 'amount_minor' ) as $key ) {
			self::need( is_int( $product[ $key ] ) && $product[ $key ] > 0, 'catalog_invalid' );
		}
		self::need( $product['rides'] <= 100 && $product['minutes_per_ride'] <= 240 && 0 === $product['minutes_per_ride'] % 15 && is_string( $product['label'] ) && strlen( $product['label'] ) <= 160 && strlen( trim( $product['label'] ) ) > 0, 'catalog_invalid' );
		self::amount( $product['amount_minor'] );
		return $product;
	}
	/** Approval is explicit; retain exact terms bytes and their digest in each purchase.
	 *
	 * @param array $terms Reviewed immutable terms snapshot.
	 */
	public static function terms( $terms ) {
		self::need( is_array( $terms ) && isset( $terms['version'], $terms['text'], $terms['sha256'], $terms['approved'], $terms['legal_reviewed'] ), 'terms_unapproved' );
		self::need( true === $terms['approved'] && true === $terms['legal_reviewed'] && is_string( $terms['version'] ) && preg_match( '/^[A-Za-z0-9._-]{1,64}$/D', $terms['version'] ) && is_string( $terms['text'] ) && strlen( $terms['text'] ) >= 20 && strlen( $terms['text'] ) <= 30000, 'terms_unapproved' );
		self::need( is_string( $terms['sha256'] ) && hash_equals( hash( 'sha256', $terms['text'] ), $terms['sha256'] ), 'terms_unapproved' );
		return $terms;
	}
	/** Bind consent to the currently reviewed text, never a browser boolean alone.
	 *
	 * @param array $input Submitted consent evidence.
	 * @param array $terms Reviewed immutable terms snapshot.
	 */
	public static function consent( $input, $terms ) {
		self::terms( $terms );
		self::need( true === ( $input['accepted'] ?? false ) && ( $input['terms_version'] ?? null ) === $terms['version'] && is_string( $input['terms_hash'] ?? null ) && hash_equals( $terms['sha256'], $input['terms_hash'] ), 'terms_acceptance_required' );
	}
	/** Issuance requires exact server-fetched order and capture, including merchant.
	 *
	 * @param array  $local Immutable local purchase bindings.
	 * @param array  $order Server-fetched provider order.
	 * @param array  $capture Server-fetched provider capture.
	 * @param string $merchant Expected sandbox merchant identifier.
	 */
	public static function paid( $local, $order, $capture, $merchant ) {
		self::provider_id( $merchant );
		self::uuid( $local['id'] );
		self::provider_id( $local['provider_order_id'] );
		self::need( ( $order['id'] ?? null ) === $local['provider_order_id'] && 'CAPTURE' === ( $order['intent'] ?? null ) && 'COMPLETED' === ( $order['status'] ?? null ) && count( $order['purchase_units'] ?? array() ) === 1, 'payment_not_verified' );
		$unit = $order['purchase_units'][0];
		self::need( ( $unit['reference_id'] ?? null ) === $local['id'] && ( $unit['custom_id'] ?? null ) === $local['id'] && ( $unit['invoice_id'] ?? null ) === 'CSF-' . $local['id'] && ( $unit['payee']['merchant_id'] ?? null ) === $merchant, 'payment_binding_mismatch' );
		self::need( ( $unit['amount']['currency_code'] ?? null ) === $local['currency'] && self::minor( $unit['amount']['value'] ?? '' ) === (int) $local['amount_minor'], 'payment_amount_mismatch' );
		$captures = $unit['payments']['captures'] ?? array();
		self::need( count( $captures ) === 1 && 'COMPLETED' === ( $captures[0]['status'] ?? null ) && true === ( $captures[0]['final_capture'] ?? false ), 'payment_not_completed' );
		$id = self::provider_id( $captures[0]['id'] ?? '' );
		self::need( ( $capture['id'] ?? null ) === $id && 'COMPLETED' === ( $capture['status'] ?? null ) && true === ( $capture['final_capture'] ?? false ) && ( $capture['supplementary_data']['related_ids']['order_id'] ?? null ) === $local['provider_order_id'], 'payment_not_completed' );
		foreach ( array( $captures[0], $capture ) as $item ) {
			self::need( ( $item['amount']['currency_code'] ?? null ) === $local['currency'] && self::minor( $item['amount']['value'] ?? '' ) === (int) $local['amount_minor'], 'payment_amount_mismatch' );
		}
		return $id;
	}
	/** Canonical object ordering preserves exact text bytes and list ordering.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function canonical( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( ! array_is_list( $value ) ) {
			ksort( $value );
		}
		return array_map( array( __CLASS__, 'canonical' ), $value );
	}

	/** Strict immutable JSON encoding.
	 *
	 * @param mixed $value Candidate value.
	 */
	public static function json( $value ) {
		return wp_json_encode( self::canonical( $value ), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}
}

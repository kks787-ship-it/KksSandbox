<?php
/** Bounded MIME serialization for an isolated sink; no transport or remote attachment loading.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Materialize private attachments without temporary public files or live mail calls. */
final class CSF_Billing_Mime {
	/** Encode one opaque attachment with RFC2045 line lengths.
	 *
	 * @param string $bytes Exact bytes.
	 * @return string
	 */
	private static function encoded( $bytes ) {
		// RFC2045 requires base64 for binary attachments; this does not execute content.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return rtrim( chunk_split( base64_encode( $bytes ), 76, "\r\n" ) ) . "\r\n";
	}
	/** Build only a synthetic .invalid-address message; callers must keep resulting bytes private.
	 *
	 * @param array       $message Immutable message snapshot.
	 * @param string      $id Stable dispatch digest.
	 * @param string|null $pdf Verified PDF bytes for buyer, null for administration.
	 * @return string
	 */
	public static function build( $message, $id, $pdf = null ) {
		CSF_Billing_Domain::need( is_string( $id ) && preg_match( '/^[a-f0-9]{64}$/D', $id ), 'invalid_dispatch' );
		$to = $message['to'] ?? null;
		CSF_Billing_Domain::need( is_string( $to ) && strlen( $to ) <= 254 && filter_var( $to, FILTER_VALIDATE_EMAIL ) && str_ends_with( strtolower( $to ), '.invalid' ) && ! preg_match( '/[\r\n]/', $to ), 'synthetic_recipient_required' );
		$buyer = 'Your test gift certificate' === ( $message['subject'] ?? '' );
		CSF_Billing_Domain::need( $buyer || 'Test gift certificate sale' === ( $message['subject'] ?? '' ), 'invalid_subject' );
		CSF_Billing_Domain::uuid( $message['order_id'] ?? '' );
		CSF_Billing_Domain::need( is_int( $message['amount_minor'] ?? null ) && $message['amount_minor'] > 0 && 'USD' === ( $message['currency'] ?? null ), 'invalid_receipt' );
		CSF_Billing_Domain::need( $buyer ? ( is_string( $pdf ) && strlen( $pdf ) <= 2000000 && str_starts_with( $pdf, '%PDF-1.4' ) && hash_equals( $message['pdf_sha256'] ?? '', hash( 'sha256', $pdf ) ) ) : null === $pdf, 'pdf_unavailable' );
		$date = $message['date'] ?? '';
		CSF_Billing_Domain::need( is_string( $date ) && preg_match( '/^[A-Z][a-z]{2}, [0-9]{2} [A-Z][a-z]{2} [0-9]{4} [0-9]{2}:[0-9]{2}:[0-9]{2} \+0000$/D', $date ), 'invalid_receipt' );
		$boundary = 'csf_' . $id;
		$body     = "SYNTHETIC TEST ONLY - no delivery or valid sale.\nOrder: " . $message['order_id'] . "\nPurchased value: " . CSF_Billing_Domain::amount( $message['amount_minor'] ) . " USD\n";
		if ( $buyer ) {
			$terms = $message['terms'] ?? null;
			CSF_Billing_Domain::need( is_array( $terms ) && is_string( $terms['text'] ?? null ) && strlen( $terms['text'] ) <= 40000 && hash_equals( $terms['sha256'] ?? '', hash( 'sha256', $terms['text'] ) ) && is_string( $message['instructions'] ?? null ) && strlen( $message['instructions'] ) <= 4000 && is_string( $message['accepted_at'] ?? null ), 'invalid_receipt' );
			$body .= 'Accepted terms: ' . $terms['version'] . "\nAccepted at: " . $message['accepted_at'] . "\n" . $message['instructions'] . "\nKeep the attached certificate number private. This is not a booking confirmation.\n";
		}
		$mime  = 'From: Corner Stone Farm Test <no-reply@fixture.invalid>' . "\r\nTo: $to\r\nSubject: " . $message['subject'] . "\r\nDate: $date\r\nMessage-ID: <$id@fixture.invalid>\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
		$mime .= "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . self::encoded( $body );
		if ( $buyer ) {
			$mime .= "--$boundary\r\nContent-Type: application/pdf\r\nContent-Disposition: attachment; filename=\"gift-certificate.pdf\"\r\nContent-Transfer-Encoding: base64\r\n\r\n" . self::encoded( $pdf );
			$mime .= "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Disposition: attachment; filename=\"accepted-terms.txt\"\r\nContent-Transfer-Encoding: base64\r\n\r\n" . self::encoded( $terms['text'] );
		}
		return $mime . "--$boundary--\r\n";
	}
}

<?php
/** Printable certificates and held delivery intents; no mail transport.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Immutable purchase presentation with private, authenticated contact envelopes. */
final class CSF_Billing_Fulfillment {
	/** Compiled versioned themes; never accept buyer HTML, CSS or URLs. */
	public static function designs() {
		return array(
			'pasture-v1' => 'Pasture green',
			'sunset-v1'  => 'Sunset gold',
			'classic-v1' => 'Classic ink',
		);
	}
	/** Validate buyer choices before payment; missing contact remains explicitly held.
	 *
	 * @param array $input Checkout fields.
	 * @return array
	 */
	public static function selection( $input ) {
		$design = $input['design'] ?? 'pasture-v1';
		$email  = $input['buyer_email'] ?? '';
		CSF_Billing_Domain::need( is_string( $design ) && isset( self::designs()[ $design ] ), 'invalid_design' );
		CSF_Billing_Domain::need( is_string( $email ) && strlen( $email ) <= 254 && ! preg_match( '/[\r\n]/', $email ) && ( '' === $email || filter_var( $email, FILTER_VALIDATE_EMAIL ) ), 'invalid_buyer_email' );
		return array(
			'design'      => $design,
			'buyer_email' => $email,
		);
	}
	/** Read reviewed signature artwork only from private operator configuration.
	 *
	 * @return array|null
	 */
	public static function signature() {
		if ( ! defined( 'CSF_BILLING_SIGNATURE_JSON' ) ) {
			return null;
		}
		$s = json_decode( CSF_BILLING_SIGNATURE_JSON, true, 8, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::need( is_array( $s ) && true === ( $s['authorized'] ?? false ) && is_string( $s['authorization_ref'] ?? null ) && preg_match( '/^[a-zA-Z0-9-]{8,80}$/D', $s['authorization_ref'] ), 'signature_unapproved' );
		CSF_Billing_Domain::need( is_string( $s['png_base64'] ?? null ) && strlen( $s['png_base64'] ) <= 350000 && is_string( $s['sha256'] ?? null ), 'signature_invalid' );
		// Only raster PNG bytes; never SVG, HTML, scripts or a remotely fetched URL.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$raw = base64_decode( $s['png_base64'], true );
		CSF_Billing_Domain::need( is_string( $raw ) && str_starts_with( $raw, "\x89PNG\r\n\x1a\n" ) && hash_equals( hash( 'sha256', $raw ), $s['sha256'] ), 'signature_invalid' );
		$size = getimagesizefromstring( $raw );
		CSF_Billing_Domain::need( is_array( $size ) && IMAGETYPE_PNG === $size[2] && $size[0] <= 2400 && $size[1] <= 1200, 'signature_invalid' );
		return $s;
	}
	/** Freeze artwork, design and instructions with the purchase; seal private bytes.
	 *
	 * @param array $selection Validated checkout selection.
	 * @return array
	 */
	public static function snapshot( $selection ) {
		$signature    = self::signature();
		$instructions = defined( 'CSF_BILLING_SCHEDULING_INSTRUCTIONS' ) ? CSF_BILLING_SCHEDULING_INSTRUCTIONS : '';
		CSF_Billing_Domain::need( is_string( $instructions ) && strlen( $instructions ) <= 4000, 'instructions_invalid' );
		return array(
			'version'          => 1,
			'design'           => $selection['design'],
			'contact_sealed'   => '' === $selection['buyer_email'] ? null : CSF_Billing_Config::seal( $selection['buyer_email'] ),
			'signature_sealed' => null === $signature ? null : CSF_Billing_Config::seal( CSF_Billing_Domain::json( $signature ) ),
			'instructions'     => $instructions,
		);
	}
	/** Append two distinct held intents inside the issuance transaction, never send mail.
	 *
	 * @param object $store Locked billing store.
	 * @param string $certificate Certificate UUID.
	 * @param array  $document Immutable purchase document.
	 */
	public static function queue( $store, $certificate, $document ) {
		foreach ( array( 'buyer', 'administration' ) as $audience ) {
			$id = hash( 'sha256', 'fulfillment:' . $certificate . ':' . $audience );
			CSF_Billing_Domain::need( ! $store->row( 'commands', $id ), 'delivery_conflict' );
			$intent = array(
				'version'        => 1,
				'certificate_id' => $certificate,
				'order_id'       => $document['id'],
				'audience'       => $audience,
				'state'          => 'held',
				'reason'         => 'delivery_not_qualified',
			);
			$store->insert(
				'commands',
				array(
					'id'           => $id,
					'payload_hash' => hash( 'sha256', CSF_Billing_Domain::json( $intent ) ),
					'result'       => CSF_Billing_Domain::json( $intent ),
					'created_at'   => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}
		$store->audit(
			'certificate_delivery_held',
			0,
			$certificate,
			array(
				'order_id'  => $document['id'],
				'audiences' => array( 'buyer', 'administration' ),
			)
		);
	}
	/** Rehearse one delivery in a local synthetic sink; never call a mail transport.
	 *
	 * @param object $store Transactional ledger.
	 * @param string $certificate Certificate UUID.
	 * @param string $audience Compiled recipient kind.
	 * @return array Minimized sink evidence, not a delivered receipt.
	 */
	public static function sink( $store, $certificate, $audience ) {
		CSF_Billing_Domain::uuid( $certificate );
		CSF_Billing_Domain::need( defined( 'CSF_BILLING_SYNTHETIC_SINK' ) && true === CSF_BILLING_SYNTHETIC_SINK && 'local' === wp_get_environment_type(), 'sink_disabled' );
		CSF_Billing_Domain::need( in_array( $audience, array( 'buyer', 'administration' ), true ), 'invalid_audience' );
		$actor = CSF_Billing_Access::require_cap( 'csf_billing_manage' );
		return $store->command(
			hash( 'sha256', 'fulfillment_sink:' . $certificate . ':' . $audience ),
			array(
				'certificate_id' => $certificate,
				'audience'       => $audience,
				'actor'          => $actor,
			),
			function ( $locked ) use ( $certificate, $audience, $actor ) {
				$intent = $locked->row( 'commands', hash( 'sha256', 'fulfillment:' . $certificate . ':' . $audience ) );
				$cert   = $locked->row( 'certificates', $certificate );
				CSF_Billing_Domain::need( $intent && $cert && 'active' === $cert['status'], 'delivery_unavailable' );
				$order = $locked->row( 'orders', $cert['order_id'] );
				CSF_Billing_Domain::need( $order && 'issued' === $order['status'] && ! empty( $order['capture_id'] ), 'delivery_unavailable' );
				$d = json_decode( $order['document'], true, 32, JSON_THROW_ON_ERROR );
				$f = $d['fulfillment'] ?? null;
				CSF_Billing_Domain::need( is_array( $f ) && ! empty( $f['contact_sealed'] ), 'delivery_setup_required' );
				$buyer = CSF_Billing_Config::open( $f['contact_sealed'] );
				CSF_Billing_Domain::need( filter_var( $buyer, FILTER_VALIDATE_EMAIL ) && str_ends_with( strtolower( $buyer ), '.invalid' ), 'synthetic_recipient_required' );
				if ( 'buyer' === $audience ) {
					$html    = self::render(
						array(
							'status'             => 'issued',
							'certificate_number' => CSF_Billing_Config::open( $cert['number_sealed'] ),
							'presentation'       => $f,
							'package'            => $d['product']['label'],
							'purchased'          => (int) $cert['purchased'],
							'amount_minor'       => $d['amount_minor'],
							'terms'              => $d['terms'],
						)
					);
					$message = array(
						'recipient'         => $buyer,
						'certificate_html'  => $html,
						'receipt_reference' => $order['id'],
						'accepted_at'       => $d['accepted_at'],
						'terms'             => $d['terms'],
						'instructions'      => $f['instructions'],
					);
				} else {
					// Synthetic administrator is fixed; never use WP admin_email as an implicit recipient.
					$message = array(
						'recipient'         => 'administration@fixture.invalid',
						'receipt_reference' => $order['id'],
						'amount_minor'      => $d['amount_minor'],
						'currency'          => $d['currency'],
					);
				}
				$evidence = array(
					'state'          => 'sink_recorded',
					'delivered'      => false,
					'certificate_id' => $certificate,
					'audience'       => $audience,
				);
				// Sink payload is encrypted in the ledger, not exposed in response, audit or logs.
				$locked->insert(
					'commands',
					array(
						'id'           => hash( 'sha256', 'fulfillment_sink_payload:' . $certificate . ':' . $audience ),
						'payload_hash' => hash_hmac( 'sha256', CSF_Billing_Domain::json( $message ), CSF_Billing_Config::key() ),
						'result'       => CSF_Billing_Domain::json( array( 'sealed' => CSF_Billing_Config::seal( CSF_Billing_Domain::json( $message ) ) ) ),
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
				$locked->audit( 'certificate_delivery_sink_recorded', $actor, $certificate, $evidence );
				return $evidence;
			},
			static function () use ( $actor ) {
				CSF_Billing_Domain::need( CSF_Billing_Access::require_cap( 'csf_billing_manage' ) === $actor, 'billing_access_required' );
			}
		);
	}
	/** Render only an authorized issued receipt. No remote assets, customer email or scripts.
	 *
	 * @param array $receipt Authorized minimized receipt with frozen presentation.
	 * @return string
	 */
	public static function render( $receipt ) {
		CSF_Billing_Domain::need( 'issued' === ( $receipt['status'] ?? null ) && is_string( $receipt['certificate_number'] ?? null ) && preg_match( '/^CSF-[A-F0-9]{32}$/D', $receipt['certificate_number'] ), 'certificate_unavailable' );
		$f = $receipt['presentation'] ?? null;
		CSF_Billing_Domain::need( is_array( $f ) && 1 === ( $f['version'] ?? null ) && isset( self::designs()[ $f['design'] ?? '' ] ), 'presentation_unavailable' );
		$colors    = array(
			'pasture-v1' => '#294124',
			'sunset-v1'  => '#87551b',
			'classic-v1' => '#24384b',
		);
		$signature = '<p>Signature pending authorized artwork setup</p>';
		if ( ! empty( $f['signature_sealed'] ) ) {
			$s = json_decode( CSF_Billing_Config::open( $f['signature_sealed'] ), true, 8, JSON_THROW_ON_ERROR );
			CSF_Billing_Domain::need( true === ( $s['authorized'] ?? false ) && is_string( $s['png_base64'] ?? null ) && preg_match( '/^[a-zA-Z0-9+\/=]+$/D', $s['png_base64'] ), 'signature_invalid' );
			$signature = '<img alt="Beth’s authorized signature" style="max-width:240px;max-height:120px" src="data:image/png;base64,' . esc_attr( $s['png_base64'] ) . '">';
		}
		$instructions = '' === $f['instructions'] ? 'Scheduling instructions pending owner setup. This certificate does not confirm a booking.' : $f['instructions'];
		return '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="referrer" content="no-referrer"><meta http-equiv="Content-Security-Policy" content="default-src &#39;none&#39;; img-src data:; style-src &#39;unsafe-inline&#39;; base-uri &#39;none&#39;; form-action &#39;none&#39;"><title>Test gift certificate</title><style>body{font:18px Georgia,serif;padding:2rem;color:#20271b}article{border:8px double ' . $colors[ $f['design'] ] . ';padding:2rem;max-width:850px;margin:auto}h1{color:' . $colors[ $f['design'] ] . '}p{white-space:pre-wrap;overflow-wrap:anywhere}@media print{body{padding:0}article{break-inside:avoid}}</style><article><p>SANDBOX TEST — NOT VALID FOR RIDES</p><h1>Corner Stone Farm</h1><h2>Gift Certificate</h2><p>' . esc_html( $receipt['package'] ) . '</p><p>Certificate number: ' . esc_html( $receipt['certificate_number'] ) . '</p><p>Originally purchased rides: ' . esc_html( (string) $receipt['purchased'] ) . '</p><p>Purchased value: ' . esc_html( CSF_Billing_Domain::amount( $receipt['amount_minor'] ) ) . ' USD</p>' . $signature . '<h2>Scheduling</h2><p>' . esc_html( $instructions ) . '</p><p>Issued under accepted terms ' . esc_html( $receipt['terms']['version'] ) . '. Current balance and eligibility are checked when used. Keep this number private.</p></article></html>';
	}
}

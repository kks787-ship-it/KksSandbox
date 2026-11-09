<?php
/** Durable synthetic dispatch rehearsal. No mail/network adapter is installed.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Claim, sink acceptance and reconciliation are separate durable steps. */
final class CSF_Billing_Dispatch {
	/** Require explicitly enabled local synthetic use and current private management. */
	private static function guard() {
		CSF_Billing_Domain::need( defined( 'CSF_BILLING_SYNTHETIC_SINK' ) && true === CSF_BILLING_SYNTHETIC_SINK && 'local' === wp_get_environment_type(), 'sink_disabled' );
		return CSF_Billing_Access::require_cap( 'csf_billing_manage' );
	}
	/** Claim one generation/audience without assuming a message was delivered.
	 *
	 * @param object $store Billing store.
	 * @param string $order_id Purchase UUID.
	 * @param string $audience Buyer or administration.
	 * @return array Minimized durable identity.
	 */
	public static function claim( $store, $order_id, $audience ) {
		self::guard();
		CSF_Billing_Domain::need( in_array( $audience, array( 'buyer', 'administration' ), true ), 'invalid_audience' );
		$pdf = CSF_Billing_PDF::document( $store, $order_id, null );
		$id  = hash( 'sha256', 'dispatch:' . $pdf['artifact_id'] . ':' . $audience );
		return $store->command(
			$id,
			array(
				'order_id'    => $order_id,
				'artifact_id' => $pdf['artifact_id'],
				'audience'    => $audience,
			),
			function ( $locked ) use ( $order_id, $audience, $pdf, $id ) {
				$order = $locked->row( 'orders', $order_id );
				$cert  = $locked->find( 'certificates', 'order_id', $order_id );
				CSF_Billing_Domain::need( $order && $cert && 'issued' === $order['status'] && 'active' === $cert['status'] && hash( 'sha256', 'pdf:' . $cert['id'] . ':' . $cert['number_hash'] ) === $pdf['artifact_id'], 'delivery_stale' );
				$d     = json_decode( $order['document'], true, 32, JSON_THROW_ON_ERROR );
				$email = CSF_Billing_Config::open( $d['fulfillment']['contact_sealed'] ?? '' );
				CSF_Billing_Domain::need( filter_var( $email, FILTER_VALIDATE_EMAIL ) && str_ends_with( strtolower( $email ), '.invalid' ), 'synthetic_recipient_required' );
				// Subject never contains the bearer; the administration bundle never contains it anywhere.
				$message = array(
					'to'           => 'buyer' === $audience ? $email : 'administration@fixture.invalid',
					'subject'      => 'buyer' === $audience ? 'Your test gift certificate' : 'Test gift certificate sale',
					'order_id'     => $order_id,
					'amount_minor' => $d['amount_minor'],
					'currency'     => $d['currency'],
				);
				if ( 'buyer' === $audience ) {
					$message['pdf_artifact'] = $pdf['artifact_id'];
					$message['pdf_sha256']   = $pdf['sha256'];
					$message['terms']        = $d['terms'];
					$message['accepted_at']  = $d['accepted_at'];
					$message['instructions'] = $d['fulfillment']['instructions'];
				}
				$state = array(
					'version'        => 1,
					'dispatch_id'    => $id,
					'certificate_id' => $cert['id'],
					'number_hash'    => $cert['number_hash'],
					'order_id'       => $order_id,
					'audience'       => $audience,
					'state'          => 'claimed',
					'attempt'        => wp_generate_uuid4(),
					'message_sealed' => CSF_Billing_Config::seal( CSF_Billing_Domain::json( $message ) ),
					'delivered'      => false,
				);
				$locked->insert(
					'commands',
					array(
						'id'           => hash( 'sha256', 'dispatch_state:' . $id ),
						'payload_hash' => hash( 'sha256', $id ),
						'result'       => CSF_Billing_Domain::json( $state ),
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
				$locked->audit(
					'certificate_dispatch_claimed',
					self::guard(),
					$cert['id'],
					array(
						'dispatch_id' => $id,
						'attempt'     => $state['attempt'],
						'number_hash' => $cert['number_hash'],
						'audience'    => $audience,
					)
				);
				return array(
					'dispatch_id' => $id,
					'attempt'     => $state['attempt'],
					'delivered'   => false,
				);
			},
			array( __CLASS__, 'authorize' )
		);
	}
	/** Public callback for transaction guard, never authorization via caller-provided identity. */
	public static function authorize() {
		self::guard();
	}
	/** Accept exactly once in the durable local sink, or reconcile its existing receipt.
	 *
	 * @param object $store Billing store.
	 * @param string $id Dispatch digest.
	 * @param string $attempt Exact claimant token.
	 * @param bool   $accept Whether to accept into the synthetic sink or reconcile only.
	 * @return array Truthful state; delivered is always false.
	 */
	public static function step( $store, $id, $attempt, $accept = false ) {
		self::guard();
		CSF_Billing_Domain::need( is_string( $id ) && preg_match( '/^[a-f0-9]{64}$/D', $id ) && is_bool( $accept ), 'invalid_dispatch' );
		CSF_Billing_Domain::uuid( $attempt );
		return $store->command(
			hash( 'sha256', 'dispatch_step:' . wp_generate_uuid4() ),
			array(
				'dispatch_id' => $id,
				'attempt'     => $attempt,
				'accept'      => $accept,
			),
			function ( $locked ) use ( $id, $attempt, $accept ) {
				$key = hash( 'sha256', 'dispatch_state:' . $id );
				$row = $locked->row( 'commands', $key );
				CSF_Billing_Domain::need( $row, 'delivery_unavailable' );
				$state = json_decode( $row['result'], true, 16, JSON_THROW_ON_ERROR );
				CSF_Billing_Domain::need( hash_equals( $state['attempt'], $attempt ), 'dispatch_claim_conflict' );
				$cert        = $locked->row( 'certificates', $state['certificate_id'] );
				$order       = $locked->row( 'orders', $state['order_id'] );
				$valid       = $cert && $order && 'active' === $cert['status'] && 'issued' === $order['status'] && hash_equals( $cert['number_hash'], $state['number_hash'] );
				$receipt_key = hash( 'sha256', 'dispatch_sink_receipt:' . $id );
				$receipt     = $locked->row( 'commands', $receipt_key );
				if ( ! $valid ) {
					$state['state'] = 'superseded';
				} elseif ( $receipt ) {
					$state['state'] = 'sink_recorded';
				} elseif ( $accept && 'claimed' === $state['state'] ) {
					// Validate private message and attached bytes now, before accepting into the sink.
					$message = json_decode( CSF_Billing_Config::open( $state['message_sealed'] ), true, 16, JSON_THROW_ON_ERROR );
					CSF_Billing_Domain::need( str_ends_with( strtolower( $message['to'] ), '.invalid' ) && ! str_contains( $message['subject'], 'CSF-' ), 'synthetic_recipient_required' );
					if ( 'buyer' === $state['audience'] ) {
						$artifact = $locked->row( 'commands', $message['pdf_artifact'] );
						CSF_Billing_Domain::need( $artifact, 'pdf_unavailable' );
						$a     = json_decode( $artifact['result'], true, 16, JSON_THROW_ON_ERROR );
						$bytes = CSF_Billing_Config::open( $a['sealed'] );
						CSF_Billing_Domain::need( $a['number_hash'] === $cert['number_hash'] && hash_equals( $message['pdf_sha256'], hash( 'sha256', $bytes ) ), 'pdf_unavailable' );
					}
					$locked->insert(
						'commands',
						array(
							'id'           => $receipt_key,
							'payload_hash' => hash( 'sha256', $state['message_sealed'] ),
							'result'       => CSF_Billing_Domain::json(
								array(
									'dispatch_id'   => $id,
									'attempt'       => $attempt,
									'sink_accepted' => true,
									'delivered'     => false,
								)
							),
							'created_at'   => gmdate( 'Y-m-d H:i:s' ),
						)
					);
					// Caller may crash here; a later reconciliation uses this durable receipt, not another send.
					$state['state'] = 'sink_recorded';
				} elseif ( 'claimed' === $state['state'] ) {
					$state['state'] = 'uncertain';
				}
				$locked->update( 'commands', $key, array( 'result' => CSF_Billing_Domain::json( $state ) ) );
				$locked->audit(
					'certificate_dispatch_reconciled',
					self::guard(),
					$state['certificate_id'],
					array(
						'dispatch_id' => $id,
						'state'       => $state['state'],
						'delivered'   => false,
					)
				);
				return array(
					'dispatch_id' => $id,
					'state'       => $state['state'],
					'delivered'   => false,
				);
			},
			array( __CLASS__, 'authorize' )
		);
	}
}

<?php
/** Private ledger application commands for gated sandbox checkout.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** Audited commands; public access is gated by the separate HTTP/session boundary. */
final class CSF_Billing_Service {
	/** Dedicated billing store.
	 *
	 * @var CSF_Billing_Store
	 */
	private $store;
	/** Constructor accepts a transaction-compatible fixture store in standalone tests.
	 *
	 * @param CSF_Billing_Store $store Transactional ledger store.
	 */
	public function __construct( $store ) {
		$this->store = $store;
	}
	/** Prepare immutable purchase evidence before any external request.
	 *
	 * @param string $command Idempotent command UUID.
	 * @param string $session_hash Digest of secret customer session.
	 * @param array  $product Reviewed product snapshot.
	 * @param array  $terms Reviewed immutable terms snapshot.
	 * @param array  $consent Exact-version affirmative acceptance.
	 */
	public function prepare( $command, $session_hash, $product, $terms, $consent ) {
		CSF_Billing_Domain::need( CSF_Billing_Config::enabled(), 'sandbox_disabled' );
		CSF_Billing_Config::key();
		foreach ( array( 'CSF_PAYPAL_SANDBOX_CLIENT_ID', 'CSF_PAYPAL_SANDBOX_CLIENT_SECRET', 'CSF_PAYPAL_SANDBOX_MERCHANT_ID' ) as $name ) {
			CSF_Billing_Config::secret( $name );
		}
		$selection    = CSF_Billing_Fulfillment::selection( $consent );
		$presentation = CSF_Billing_Fulfillment::snapshot( $selection );
		$valuation    = CSF_Billing_Value::policy();
		CSF_Billing_Domain::need( true === $valuation['approved'], 'valuation_unapproved' );
		$catalog = CSF_Billing_Config::catalog();
		CSF_Billing_Domain::need( in_array( $product, $catalog['products'] ?? array(), true ) && hash_equals( CSF_Billing_Domain::json( $catalog['terms'] ?? null ), CSF_Billing_Domain::json( $terms ) ), 'catalog_unapproved' );
		CSF_Billing_Domain::uuid( $command );
		CSF_Billing_Domain::need( is_string( $session_hash ) && preg_match( '/^[a-f0-9]{64}$/D', $session_hash ) );
		$product = CSF_Billing_Domain::product( $product );
		CSF_Billing_Value::allocation( $product['amount_minor'], $product['rides'], $valuation );
		$terms = CSF_Billing_Domain::terms( $terms );
		CSF_Billing_Domain::consent( $consent, $terms );
		$payload = array(
			'kind'                 => 'prepare',
			'presentation_binding' => hash_hmac( 'sha256', CSF_Billing_Domain::json( array( $selection, CSF_Billing_Fulfillment::signature(), $presentation['instructions'] ) ), CSF_Billing_Config::key() ),
			'session_hash'         => $session_hash,
			'product'              => $product,
			'terms'                => $terms,
			'valuation'            => $valuation,
		);
		return $this->store->command(
			hash( 'sha256', 'prepare:' . $command ),
			$payload,
			function ( $store ) use ( $session_hash, $product, $terms, $valuation, $presentation ) {
				$id       = wp_generate_uuid4();
				$document = array(
					'id'                 => $id,
					'product'            => $product,
					'fulfillment'        => $presentation,
					'terms'              => $terms,
					'valuation'          => $valuation,
					'accepted_at'        => gmdate( 'Y-m-d\TH:i:s\Z' ),
					'amount_minor'       => $product['amount_minor'],
					'currency'           => $product['currency'],
					'create_request_id'  => wp_generate_uuid4(),
					'capture_request_id' => wp_generate_uuid4(),
				);
				$store->insert(
					'orders',
					array(
						'id'           => $id,
						'session_hash' => $session_hash,
						'status'       => 'prepared',
						'revision'     => 1,
						'document'     => CSF_Billing_Domain::json( $document ),
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
				$store->audit(
					'purchase_prepared',
					0,
					$id,
					array(
						'product_id'       => $product['id'],
						'product_revision' => $product['revision'],
						'terms_hash'       => $terms['sha256'],
					)
				);
				return array( 'order_id' => $id );
			}
		);
	}
	/** Reserve one outbound attempt durably; uncertainty cannot cause blind repeated charges.
	 *
	 * @param string $command Idempotent command UUID.
	 * @param string $id Exact object identifier.
	 * @param string $session_hash Digest of secret customer session.
	 * @param string $phase Compiled external operation phase.
	 */
	public function claim( $command, $id, $session_hash, $phase ) {
		CSF_Billing_Domain::uuid( $command );
		CSF_Billing_Domain::uuid( $id );
		CSF_Billing_Domain::need( in_array( $phase, array( 'create', 'capture' ), true ) );
		return $this->store->command(
			hash( 'sha256', 'claim:' . $command ),
			array(
				'kind'    => 'claim',
				'id'      => $id,
				'session' => $session_hash,
				'phase'   => $phase,
				'attempt' => wp_generate_uuid4(),
			),
			function ( $store ) use ( $id, $session_hash, $phase ) {
				$row = $store->row( 'orders', $id );
				CSF_Billing_Domain::need( $row && hash_equals( $row['session_hash'], $session_hash ), 'purchase_unavailable' );
				CSF_Billing_Domain::need( ( 'create' === $phase ? 'prepared' : 'approved' ) === $row['status'], 'reconciliation_required' );
				$store->update(
					'orders',
					$id,
					array(
						'status'   => $phase . '_uncertain',
						'revision' => (int) $row['revision'] + 1,
					)
				);
				$store->audit( $phase . '_claimed', 0, $id, array() );
				return array(
					'claimed'  => true,
					'order'    => $row,
					'document' => json_decode( $row['document'], true, 32, JSON_THROW_ON_ERROR ),
				);
			}
		);
	}
	/** Create a sandbox provider order after a single durable local claim.
	 *
	 * @param string $command Idempotent command UUID.
	 * @param string $id Exact object identifier.
	 * @param string $session_hash Digest of secret customer session.
	 */
	public function create( $command, $id, $session_hash ) {
		CSF_Billing_Domain::need( CSF_Billing_Config::enabled(), 'sandbox_disabled' );
		$claim       = $this->claim( $command, $id, $session_hash, 'create' );
		$provider    = new CSF_Billing_PayPal();
		$order       = $provider->create( $claim['document'], $claim['document']['create_request_id'] );
		$provider_id = CSF_Billing_Domain::provider_id( $order['id'] ?? '' );
		// Independently fetch before binding. A lost response leaves create_uncertain for review.
		$order = $provider->order( $provider_id );
		CSF_Billing_Domain::need( ( $order['id'] ?? null ) === $provider_id, 'payment_binding_mismatch' );
		self::bound_order( $claim['document'], $order );
		CSF_Billing_Domain::need( in_array( $order['status'] ?? null, array( 'CREATED', 'PAYER_ACTION_REQUIRED', 'APPROVED' ), true ), 'provider_state_conflict' );
		return $this->store->command(
			hash( 'sha256', 'bind:' . $provider_id ),
			array(
				'kind'        => 'bind',
				'id'          => $id,
				'provider_id' => $provider_id,
			),
			function ( $store ) use ( $id, $provider_id ) {
				$row = $store->row( 'orders', $id );
				CSF_Billing_Domain::need( $row && 'create_uncertain' === $row['status'], 'purchase_state_conflict' );
				$store->update(
					'orders',
					$id,
					array(
						'status'            => 'awaiting_approval',
						'provider_order_id' => $provider_id,
						'revision'          => (int) $row['revision'] + 1,
					)
				);
				$store->audit( 'provider_order_bound', 0, $id, array() );
				return array(
					'order_id'          => $id,
					'provider_order_id' => $provider_id,
				);
			}
		);
	}
	/** Bind approval to server-fetched order; claim once and reconcile uncertain captures.
	 *
	 * @param string $command Idempotent command UUID.
	 * @param string $id Exact object identifier.
	 * @param string $session_hash Digest of secret customer session.
	 */
	public function capture( $command, $id, $session_hash ) {
		CSF_Billing_Domain::need( CSF_Billing_Config::enabled(), 'sandbox_disabled' );
		$local = $this->read_order( $id );
		CSF_Billing_Domain::need( $local && is_string( $session_hash ) && hash_equals( $local['session_hash'], $session_hash ), 'purchase_unavailable' );
		if ( in_array( $local['status'], array( 'issued', 'capture_uncertain' ), true ) ) {
			return $this->reconcile( $id );
		}
		$provider = new CSF_Billing_PayPal();
		$order    = $provider->order( $local['provider_order_id'] ?? '' );
		CSF_Billing_Domain::need( ( $order['id'] ?? null ) === $local['provider_order_id'], 'payment_binding_mismatch' );
		self::bound_order( json_decode( $local['document'], true, 32, JSON_THROW_ON_ERROR ), $order );
		if ( 'COMPLETED' === ( $order['status'] ?? null ) ) {
			return $this->reconcile( $id ); }
		CSF_Billing_Domain::need( 'APPROVED' === ( $order['status'] ?? null ), 'payer_approval_required' );
		$this->store->command(
			hash( 'sha256', 'approval:' . $id ),
			array(
				'kind' => 'approval',
				'id'   => $id,
			),
			function ( $store ) use ( $id ) {
				$row = $store->row( 'orders', $id );
				CSF_Billing_Domain::need( $row && in_array( $row['status'], array( 'awaiting_approval', 'approved' ), true ), 'purchase_state_conflict' );
				if ( 'approved' === $row['status'] ) {
					return array( 'approved' => true );
				}
				$store->update(
					'orders',
					$id,
					array(
						'status'   => 'approved',
						'revision' => (int) $row['revision'] + 1,
					)
				);
				$store->audit( 'payer_approval_verified', 0, $id, array() );
				return array( 'approved' => true );
			}
		);
		$claim = $this->claim( $command, $id, $session_hash, 'capture' );
		$provider->capture( $claim['order']['provider_order_id'], $claim['document']['capture_request_id'] );
		return $this->reconcile( $id );
	}
	/** Exact expected order binding before asking PayPal to capture.
	 *
	 * @param array $local Immutable local purchase bindings.
	 * @param array $order Server-fetched provider order.
	 */
	private static function bound_order( $local, $order ) {
		$units = $order['purchase_units'] ?? array();
		CSF_Billing_Domain::need( 'CAPTURE' === ( $order['intent'] ?? null ) && is_array( $units ) && count( $units ) === 1, 'payment_binding_mismatch' );
		$unit = $units[0];
		CSF_Billing_Domain::need( ( $unit['reference_id'] ?? null ) === $local['id'] && ( $unit['custom_id'] ?? null ) === $local['id'] && ( $unit['invoice_id'] ?? null ) === 'CSF-' . $local['id'] && ( $unit['payee']['merchant_id'] ?? null ) === CSF_Billing_Config::secret( 'CSF_PAYPAL_SANDBOX_MERCHANT_ID' ), 'payment_binding_mismatch' );
		CSF_Billing_Domain::need( ( $unit['amount']['currency_code'] ?? null ) === $local['currency'] && CSF_Billing_Domain::minor( $unit['amount']['value'] ?? '' ) === $local['amount_minor'], 'payment_amount_mismatch' );
	}
	/** Read only inside a checked transaction; receipt contains no certificate secret.
	 *
	 * @param string $id Exact object identifier.
	 */
	private function read_order( $id ) {
		CSF_Billing_Domain::uuid( $id );
		return $this->store->snapshot(
			function ( $store ) use ( $id ) {
				return $store->row( 'orders', $id );
			}
		);
	}
	/** Resume a bound order without recreating a provider purchase.
	 *
	 * @param string $command Command UUID.
	 * @param string $id Local order UUID.
	 * @param string $session_hash Customer session digest.
	 * @return array
	 */
	public function checkout( $command, $id, $session_hash ) {
		CSF_Billing_Domain::need( CSF_Billing_Config::enabled(), 'sandbox_disabled' );
		CSF_Billing_Domain::uuid( $command );
		$row = $this->read_order( $id );
		CSF_Billing_Domain::need( $row && hash_equals( $row['session_hash'], $session_hash ), 'purchase_unavailable' );
		if ( in_array( $row['status'], array( 'awaiting_approval', 'approved' ), true ) ) {
			return array(
				'order_id'          => $id,
				'provider_order_id' => $row['provider_order_id'],
			);
		}
		return $this->create( $command, $id, $session_hash );
	}

	/** Recover a lost create response only after independently verifying exact existing provider bindings.
	 *
	 * @param string $command Command UUID.
	 * @param string $id Local order UUID.
	 * @param string $provider_id Existing sandbox order ID from the merchant record.
	 * @param int    $revision Expected local order revision.
	 * @return array
	 */
	public function recover_created( $command, $id, $provider_id, $revision ) {
		$actor = CSF_Billing_Access::require_cap( 'csf_billing_manage' );
		CSF_Billing_Domain::uuid( $command );
		CSF_Billing_Domain::provider_id( $provider_id );
		CSF_Billing_Domain::need( is_int( $revision ), 'invalid_request' );
		$payload  = array(
			'kind'        => 'recover',
			'actor'       => $actor,
			'id'          => $id,
			'provider_id' => $provider_id,
			'revision'    => $revision,
		);
		$previous = $this->store->snapshot(
			function ( $store ) use ( $command, $payload ) {
				$entry = $store->row( 'commands', hash( 'sha256', 'recover:' . $command ) );
				if ( ! $entry ) {
					return null; }
				CSF_Billing_Domain::need( hash_equals( $entry['payload_hash'], hash( 'sha256', CSF_Billing_Domain::json( $payload ) ) ), 'command_conflict' );
				return json_decode( $entry['result'], true, 16, JSON_THROW_ON_ERROR );
			},
			function () use ( $actor ) {
				CSF_Billing_Domain::need( CSF_Billing_Access::require_cap( 'csf_billing_manage' ) === $actor, 'billing_access_required' );
			}
		);
		if ( null !== $previous ) {
			return $previous; }
		$local = $this->read_order( $id );
		CSF_Billing_Domain::need( $local && ( 'create_uncertain' === $local['status'] || ( $local['provider_order_id'] ?? null ) === $provider_id ), 'recovery_state_conflict' );
		$order = ( new CSF_Billing_PayPal() )->order( $provider_id );
		CSF_Billing_Domain::need( ( $order['id'] ?? null ) === $provider_id && in_array( $order['status'] ?? null, array( 'CREATED', 'PAYER_ACTION_REQUIRED', 'APPROVED', 'COMPLETED' ), true ), 'payment_binding_mismatch' );
		self::bound_order( json_decode( $local['document'], true, 32, JSON_THROW_ON_ERROR ), $order );
		$result = $this->store->command(
			hash( 'sha256', 'recover:' . $command ),
			array(
				'kind'        => 'recover',
				'actor'       => $actor,
				'id'          => $id,
				'provider_id' => $provider_id,
				'revision'    => $revision,
			),
			function ( $store ) use ( $id, $provider_id, $revision, $actor, $order ) {
				$row = $store->row( 'orders', $id );
				CSF_Billing_Domain::need( $row && 'create_uncertain' === $row['status'] && (int) $row['revision'] === $revision, 'recovery_state_conflict' );
				$status = in_array( $order['status'], array( 'APPROVED', 'COMPLETED' ), true ) ? 'approved' : 'awaiting_approval';
				$store->update(
					'orders',
					$id,
					array(
						'provider_order_id' => $provider_id,
						'status'            => $status,
						'revision'          => $revision + 1,
					)
				);
				$store->audit( 'lost_provider_order_recovered', $actor, $id, array( 'provider_order_id' => $provider_id ) );
				return array(
					'order_id' => $id,
					'status'   => $status,
				);
			},
			function () use ( $actor ) {
				CSF_Billing_Domain::need( CSF_Billing_Access::require_cap( 'csf_billing_manage' ) === $actor, 'billing_access_required' );
			}
		);
		return $result;
	}

	/** Issue after direct server fetch, never accept a caller's supplied payment object.
	 *
	 * @param string $id Exact object identifier.
	 */
	public function reconcile( $id ) {
		CSF_Billing_Domain::uuid( $id );
		// Network I/O is deliberately outside ledger transactions.
		$local = $this->read_order( $id );
		CSF_Billing_Domain::need( $local && ! empty( $local['provider_order_id'] ) && in_array( $local['status'], array( 'awaiting_approval', 'approved', 'capture_uncertain', 'issued' ), true ), 'purchase_unavailable' );
		$provider   = new CSF_Billing_PayPal();
		$order      = $provider->order( $local['provider_order_id'] );
		$capture_id = CSF_Billing_Domain::provider_id( $order['purchase_units'][0]['payments']['captures'][0]['id'] ?? '' );
		$capture    = $provider->capture_details( $capture_id );
		$merchant   = CSF_Billing_Config::secret( 'CSF_PAYPAL_SANDBOX_MERCHANT_ID' );
		$document   = json_decode( $local['document'], true, 32, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::paid( array_merge( $document, array( 'provider_order_id' => $local['provider_order_id'] ) ), $order, $capture, $merchant );
		return $this->store->command(
			hash( 'sha256', 'issue:' . $capture_id ),
			array(
				'kind'       => 'issue',
				'order_id'   => $id,
				'capture_id' => $capture_id,
			),
			function ( $store ) use ( $id, $order, $capture, $merchant ) {
				$row = $store->row( 'orders', $id );
				CSF_Billing_Domain::need( $row && in_array( $row['status'], array( 'awaiting_approval', 'approved', 'capture_uncertain' ), true ), 'purchase_state_conflict' );
				$document = json_decode( $row['document'], true, 32, JSON_THROW_ON_ERROR );
				$verified = CSF_Billing_Domain::paid( array_merge( $document, array( 'provider_order_id' => $row['provider_order_id'] ) ), $order, $capture, $merchant );
				CSF_Billing_Domain::need( ! $store->row( 'commands', hash( 'sha256', 'capture_hold:' . $verified ) ), 'payment_on_hold' );
				$certificate = wp_generate_uuid4();
				$number      = 'CSF-' . strtoupper( bin2hex( random_bytes( 16 ) ) );
				$store->insert(
					'certificates',
					array(
						'id'            => $certificate,
						'order_id'      => $id,
						'number_hash'   => hash( 'sha256', $number ),
						'number_sealed' => CSF_Billing_Config::seal( $number ),
						'purchased'     => $document['product']['rides'],
						'redeemed'      => 0,
						'value_json'    => CSF_Billing_Domain::json( CSF_Billing_Value::initial( $document['amount_minor'], $document['product']['rides'], $document['valuation'] ?? array() ) ),
						'status'        => 'active',
						'revision'      => 1,
					)
				);
				$store->update(
					'orders',
					$id,
					array(
						'status'     => 'issued',
						'capture_id' => $verified,
						'revision'   => (int) $row['revision'] + 1,
					)
				);
				$store->audit(
					'certificate_issued',
					0,
					$certificate,
					array(
						'order_id' => $id,
						'rides'    => $document['product']['rides'],
					)
				);
				CSF_Billing_Fulfillment::queue( $store, $certificate, $document );
				return array( 'certificate_id' => $certificate );
			}
		);
	}
	/** Verify and deduplicate provider events behind the separately gated webhook route.
	 *
	 * @param string $raw Exact bounded event body.
	 * @param array  $headers Signature headers.
	 * @return array
	 */
	public function webhook( $raw, $headers ) {
		$provider = new CSF_Billing_PayPal();
		$event    = $provider->verify( $raw, $headers );
		$type     = $event['event_type'] ?? '';
		if ( in_array( $type, array( 'CUSTOMER.DISPUTE.CREATED', 'CUSTOMER.DISPUTE.UPDATED', 'CUSTOMER.DISPUTE.RESOLVED' ), true ) ) {
			return $this->dispute( $event, $provider );
		}
		CSF_Billing_Domain::need( in_array( $type, array( 'PAYMENT.CAPTURE.COMPLETED', 'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED', 'PAYMENT.CAPTURE.PENDING', 'PAYMENT.CAPTURE.DENIED' ), true ), 'event_review_required' );
		// Refund resources have a refund ID, not a capture ID. Missing binding stays held.
		$capture_id = 'PAYMENT.CAPTURE.REFUNDED' === $type ? ( $event['resource']['supplementary_data']['related_ids']['capture_id'] ?? '' ) : ( $event['resource']['id'] ?? '' );
		$capture    = $provider->capture_details( CSF_Billing_Domain::provider_id( $capture_id ) );
		CSF_Billing_Domain::need( ( $capture['id'] ?? null ) === $capture_id, 'payment_binding_mismatch' );
		$order_id = CSF_Billing_Domain::provider_id( $capture['supplementary_data']['related_ids']['order_id'] ?? '' );
		$payload  = array(
			'kind'       => 'webhook',
			'event_hash' => hash( 'sha256', CSF_Billing_Domain::json( $event ) ),
		);
		$result   = $this->store->command(
			hash( 'sha256', 'event:' . $event['id'] ),
			$payload,
			function ( $store ) use ( $order_id, $capture_id, $type, $event ) {
				$row    = $store->find( 'orders', 'provider_order_id', $order_id );
				$freeze = in_array( $type, array( 'PAYMENT.CAPTURE.REFUNDED', 'PAYMENT.CAPTURE.REVERSED', 'PAYMENT.CAPTURE.DENIED' ), true );
				if ( $freeze ) {
					self::capture_hold( $store, $capture_id, $order_id, $event['id'] ); }
				if ( ! $row && $freeze ) {
					return array(
						'frozen'            => true,
						'pending'           => false,
						'review_required'   => true,
						'unmapped_count'    => 1,
						'provider_event_id' => $event['id'],
					); }
				CSF_Billing_Domain::need( $row && ( empty( $row['capture_id'] ) || $row['capture_id'] === $capture_id ), 'payment_binding_mismatch' );
				if ( $freeze ) {
					$store->update(
						'orders',
						$row['id'],
						array(
							'status'   => 'frozen',
							'revision' => (int) $row['revision'] + 1,
						)
					);
					$certificate = $store->find( 'certificates', 'order_id', $row['id'] );
					if ( $certificate ) {
						$store->update(
							'certificates',
							$certificate['id'],
							array(
								'status'   => 'frozen',
								'revision' => (int) $certificate['revision'] + 1,
							)
						);
					}
				}
				$store->audit( $freeze ? 'payment_credits_frozen' : 'payment_event_verified', 0, $row['id'], array( 'event_type' => $type ) );
				return array(
					'order_id' => $row['id'],
					'frozen'   => $freeze,
					'pending'  => 'PAYMENT.CAPTURE.PENDING' === $type,
				);
			}
		);
		// A previous verified event does not cache payment truth; completion rechecks the provider.
		return $result['frozen'] || $result['pending'] ? $result : $this->reconcile( $result['order_id'] );
	}

	/** Freeze locally bound disputed captures; even resolved disputes need manual review.
	 *
	 * @param array              $event Cryptographically verified provider event.
	 * @param CSF_Billing_PayPal $provider Fixed sandbox provider.
	 * @return array
	 */
	private function dispute( $event, $provider ) {
		$transactions = $event['resource']['disputed_transactions'] ?? null;
		CSF_Billing_Domain::need( is_array( $transactions ) && count( $transactions ) > 0 && count( $transactions ) <= 20, 'event_review_required' );
		$bindings = array();
		foreach ( $transactions as $transaction ) {
			$id      = CSF_Billing_Domain::provider_id( $transaction['seller_transaction_id'] ?? '' );
			$capture = $provider->capture_details( $id );
			CSF_Billing_Domain::need( ( $capture['id'] ?? null ) === $id, 'payment_binding_mismatch' );
			$bindings[ $id ] = CSF_Billing_Domain::provider_id( $capture['supplementary_data']['related_ids']['order_id'] ?? '' );
		}
		return $this->store->command(
			hash( 'sha256', 'event:' . $event['id'] ),
			array(
				'kind'       => 'webhook',
				'event_hash' => hash( 'sha256', CSF_Billing_Domain::json( $event ) ),
			),
			function ( $store ) use ( $bindings, $event ) {
				$held    = array();
				$unknown = 0;
				foreach ( $bindings as $capture_id => $provider_order ) {
					self::capture_hold( $store, $capture_id, $provider_order, $event['id'] );
					$row = $store->find( 'orders', 'provider_order_id', $provider_order );
					if ( ! $row ) {
						++$unknown;
						continue; }
					CSF_Billing_Domain::need( empty( $row['capture_id'] ) || $row['capture_id'] === $capture_id, 'payment_binding_mismatch' );
					$store->update(
						'orders',
						$row['id'],
						array(
							'status'   => 'frozen',
							'revision' => (int) $row['revision'] + 1,
						)
					);
					$certificate = $store->find( 'certificates', 'order_id', $row['id'] );
					if ( $certificate ) {
						$store->update(
							'certificates',
							$certificate['id'],
							array(
								'status'   => 'frozen',
								'revision' => (int) $certificate['revision'] + 1,
							)
						); }
					$store->audit( 'disputed_credits_frozen', 0, $row['id'], array( 'event_type' => $event['event_type'] ) );
					$held[] = $row['id'];
				}
				return array(
					'frozen_orders'     => $held,
					'unmapped_count'    => $unknown,
					'review_required'   => true,
					'provider_event_id' => $event['id'],
				);
			}
		);
	}

	/** Preserve negative provider evidence independently of local order-binding timing.
	 *
	 * @param CSF_Billing_Store $store Locked ledger.
	 * @param string            $capture Capture ID.
	 * @param string            $order Provider order ID.
	 * @param string            $event Verified provider event ID.
	 */
	private static function capture_hold( $store, $capture, $order, $event ) {
		$key = hash( 'sha256', 'capture_hold:' . $capture );
		if ( ! $store->row( 'commands', $key ) ) {
			$evidence = array(
				'capture_id'        => $capture,
				'provider_order_id' => $order,
				'provider_event_id' => $event,
			);
			$store->insert(
				'commands',
				array(
					'id'           => $key,
					'payload_hash' => hash( 'sha256', CSF_Billing_Domain::json( $evidence ) ),
					'result'       => CSF_Billing_Domain::json( $evidence ),
					'created_at'   => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}
	}

	/** Pure redemption proposal, shared by actual mutation and adversarial tests.
	 *
	 * @param array $row Current locked certificate row.
	 * @param int   $rides Requested ride debit.
	 * @param int   $revision Expected certificate revision.
	 */
	public static function redemption( $row, $rides, $revision ) {
		CSF_Billing_Domain::need( $row && 'active' === $row['status'] && is_int( $rides ) && $rides > 0 && is_int( $revision ) && $revision === (int) $row['revision'], 'redemption_conflict' );
		CSF_Billing_Domain::need( (int) $row['redeemed'] >= 0 && (int) $row['purchased'] >= (int) $row['redeemed'] && $rides <= (int) $row['purchased'] - (int) $row['redeemed'], 'insufficient_rides' );
		return array(
			'redeemed' => (int) $row['redeemed'] + $rides,
			'revision' => $revision + 1,
		);
	}
	/** Atomic ride debit; no booking, waiver clearance, email or payment side effect.
	 *
	 * @param string $command Idempotent command UUID.
	 * @param string $id Exact object identifier.
	 * @param int    $rides Requested ride debit.
	 * @param int    $revision Expected certificate revision.
	 * @param string $ride_reference Operator-attested ride reference UUID.
	 */
	public function redeem( $command, $id, $rides, $revision, $ride_reference ) {
		$actor = CSF_Billing_Access::require_cap( 'csf_billing_redeem' );
		CSF_Billing_Domain::uuid( $command );
		CSF_Billing_Domain::uuid( $id );
		CSF_Billing_Domain::uuid( $ride_reference );
		return $this->store->command(
			hash( 'sha256', 'redeem:' . $command ),
			array(
				'kind'           => 'redeem',
				'actor'          => $actor,
				'id'             => $id,
				'rides'          => $rides,
				'command'        => $command,
				'revision'       => $revision,
				'ride_reference' => $ride_reference,
			),
			function ( $store ) use ( $id, $rides, $revision, $ride_reference, $actor, $command ) {
				$row                  = $store->row( 'certificates', $id );
				$update               = self::redemption( $row, $rides, $revision );
				$value_debit          = CSF_Billing_Value::debit( $row, $rides );
				$update['value_json'] = CSF_Billing_Domain::json( $value_debit['value'] );
				$ride_key             = hash( 'sha256', 'ride:' . $id . ':' . $ride_reference );
				CSF_Billing_Domain::need( ! $store->row( 'commands', $ride_key ), 'ride_already_recorded' );
				$store->insert(
					'commands',
					array(
						'id'           => $ride_key,
						'payload_hash' => hash( 'sha256', $id . ':' . $ride_reference ),
						'result'       => '{}',
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
				$store->update( 'certificates', $id, $update );
				$store->audit(
					'rides_redeemed',
					$actor,
					$id,
					array(
						'rides'          => $rides,
						'command'        => $command,
						'ride_reference' => $ride_reference,
						'slots'          => $value_debit['slots'],
						'value_minor'    => $value_debit['value_minor'],
						'revision'       => $update['revision'],
					)
				);
				return array(
					'certificate_id' => $id,
					'command'        => $command,
					'rides'          => $rides,
					'remaining'      => (int) $row['purchased'] - $update['redeemed'],
					'slots'          => $value_debit['slots'],
					'value_minor'    => $value_debit['value_minor'],
					'revision'       => $update['revision'],
				);
			},
			function () use ( $actor ) {
				CSF_Billing_Domain::need( CSF_Billing_Access::require_cap( 'csf_billing_redeem' ) === $actor, 'billing_access_required' );
			}
		);
	}
	/** Reverse a mistaken debit exactly once, preserving its original audit and ride tombstone.
	 *
	 * @param string $command Correction command UUID.
	 * @param string $id Certificate UUID.
	 * @param string $original Original redemption command UUID.
	 * @param int    $revision Expected certificate revision.
	 * @param string $reason Compiled correction reason.
	 * @return array
	 */
	public function correct( $command, $id, $original, $revision, $reason ) {
		$actor = CSF_Billing_Access::require_cap( 'csf_billing_manage' );
		foreach ( array( $command, $id, $original ) as $uuid ) {
			CSF_Billing_Domain::uuid( $uuid ); }
		CSF_Billing_Domain::need( in_array( $reason, array( 'duplicate_entry', 'mistaken_ride' ), true ) && is_int( $revision ), 'invalid_request' );
		return $this->store->command(
			hash( 'sha256', 'correct:' . $command ),
			array(
				'kind'     => 'correct',
				'actor'    => $actor,
				'id'       => $id,
				'original' => $original,
				'revision' => $revision,
				'reason'   => $reason,
			),
			function ( $store ) use ( $id, $original, $revision, $actor, $reason ) {
				$row   = $store->row( 'certificates', $id );
				$entry = $store->row( 'commands', hash( 'sha256', 'redeem:' . $original ) );
				CSF_Billing_Domain::need( $row && $entry && (int) $row['revision'] === $revision, 'correction_conflict' );
				$debit = json_decode( $entry['result'], true, 16, JSON_THROW_ON_ERROR );
				CSF_Billing_Domain::need( ( $debit['certificate_id'] ?? null ) === $id && ( $debit['command'] ?? null ) === $original && is_int( $debit['rides'] ?? null ) && $debit['rides'] > 0 && $debit['rides'] <= (int) $row['redeemed'], 'correction_conflict' );
				$key = hash( 'sha256', 'reversed:' . $original );
				CSF_Billing_Domain::need( ! $store->row( 'commands', $key ), 'already_corrected' );
				$store->insert(
					'commands',
					array(
						'id'           => $key,
						'payload_hash' => hash( 'sha256', $original ),
						'result'       => '{}',
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
				$value    = CSF_Billing_Value::reverse( $row, $debit );
				$redeemed = (int) $row['redeemed'] - $debit['rides'];
				$store->update(
					'certificates',
					$id,
					array(
						'redeemed'   => $redeemed,
						'value_json' => CSF_Billing_Domain::json( $value ),
						'revision'   => $revision + 1,
					)
				);
				$store->audit(
					'ride_debit_corrected',
					$actor,
					$id,
					array(
						'original_command' => $original,
						'value_minor'      => $debit['value_minor'],
						'slots'            => $debit['slots'],
						'rides'            => $debit['rides'],
						'reason'           => $reason,
					)
				);
				return array(
					'certificate_id' => $id,
					'remaining'      => (int) $row['purchased'] - $redeemed,
					'revision'       => $revision + 1,
				);
			},
			function () use ( $actor ) {
				CSF_Billing_Domain::need( CSF_Billing_Access::require_cap( 'csf_billing_manage' ) === $actor, 'billing_access_required' );
			}
		);
	}
	/** Reserve remaining value or record an independently performed cash payment; never pays money.
	 *
	 * @param string $command Idempotency UUID.
	 * @param string $id Certificate UUID.
	 * @param int    $revision Expected revision.
	 * @param string $action Compiled workflow transition.
	 * @param string $request Original reservation UUID, or command for a new reservation.
	 * @param int    $amount Exact remaining/requested cents.
	 * @param string $evidence Private external evidence record UUID; never bank/customer details.
	 * @param bool   $attested Explicit operator attestation.
	 */
	public function cash( $command, $id, $revision, $action, $request, $amount, $evidence, $attested ) {
		$actor = CSF_Billing_Access::require_cap( 'csf_billing_manage' );
		foreach ( array( $command, $id, $request, $evidence ) as $uuid ) {
			CSF_Billing_Domain::uuid( $uuid );}
		CSF_Billing_Domain::need( is_int( $revision ) && is_int( $amount ) && $amount > 0 && true === $attested && in_array( $action, array( 'reserve', 'uncertain', 'record_paid', 'cancel_unpaid' ), true ), 'invalid_request' );
		$payload = compact( 'actor', 'id', 'revision', 'action', 'request', 'amount', 'evidence', 'attested' );
		return $this->store->command(
			hash( 'sha256', 'cash:' . $command ),
			$payload,
			function ( $store ) use ( $actor, $id, $revision, $action, $request, $amount, $evidence, $command ) {
				$row = $store->row( 'certificates', $id );
				CSF_Billing_Domain::need( $row && (int) $row['revision'] === $revision, 'cash_conflict' );
				$v = CSF_Billing_Value::read( $row );
				if ( 'reserve' === $action ) {
					CSF_Billing_Domain::need( 'active' === $row['status'] && null === $v['pending'] && CSF_Billing_Value::remaining( $v ) === $amount, 'cash_conflict' );
					$key = hash( 'sha256', 'cash_request:' . $request );
					CSF_Billing_Domain::need( ! $store->row( 'commands', $key ), 'cash_request_used' );
					$store->insert(
						'commands',
						array(
							'id'           => $key,
							'payload_hash' => hash( 'sha256', $id . ':' . $request ),
							'result'       => '{}',
							'created_at'   => gmdate( 'Y-m-d H:i:s' ),
						)
					);
					$v['pending'] = array(
						'id'           => $request,
						'amount_minor' => $amount,
						'state'        => 'reserved',
					);
				} else {
					CSF_Billing_Domain::need( null !== $v['pending'] && $request === $v['pending']['id'] && $amount === $v['pending']['amount_minor'], 'cash_conflict' );
					// A provider freeze cannot erase an actual cash payout or release the certificate.
					if ( 'uncertain' === $action ) {
						$v['pending']['state'] = 'uncertain';} elseif ( 'record_paid' === $action ) {
									$key = hash( 'sha256', 'cash_evidence:' . $evidence );
											CSF_Billing_Domain::need( ! $store->row( 'commands', $key ), 'cash_evidence_used' );
									$store->insert(
										'commands',
										array(
											'id'           => $key,
											'payload_hash' => hash( 'sha256', $id . ':' . $request ),
											'result'       => '{}',
											'created_at'   => gmdate( 'Y-m-d H:i:s' ),
										)
									);
									$v['cash_paid_minor'] += $amount;
											$v['pending']  = null;
						} else {
							$v['pending'] = null;}
				}
				$store->update(
					'certificates',
					$id,
					array(
						'value_json' => CSF_Billing_Domain::json( $v ),
						'revision'   => $revision + 1,
					)
				);
				$store->audit(
					'cash_' . $action,
					$actor,
					$id,
					array(
						'command'      => $command,
						'request'      => $request,
						'amount_minor' => $amount,
						'currency'     => 'USD',
						'evidence'     => $evidence,
						'revision'     => $revision + 1,
					)
				);
				return array(
					'certificate_id'   => $id,
					'revision'         => $revision + 1,
					'action'           => $action,
					'request'          => $request,
					'remaining_minor'  => CSF_Billing_Value::remaining( $v ),
					'pending'          => $v['pending'],
					'payment_executed' => false,
				);
			},
			static function () use ( $actor ) {
				CSF_Billing_Domain::need( CSF_Billing_Access::require_cap( 'csf_billing_manage' ) === $actor, 'billing_access_required' );
			}
		);
	}
}

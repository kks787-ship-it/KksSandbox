<?php
/** Default-off buyer consent for a staff-reviewed exact lesson offer.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Buyer consent never impersonates staff or reserves calendar resources by itself. */
final class CSF_Billing_Offer {
	/** Separate owner-controlled gate; no automatic public booking activation. */
	public static function enabled() {
		return defined( 'CSF_BILLING_CUSTOMER_BOOKING_ENABLED' ) && true === CSF_BILLING_CUSTOMER_BOOKING_ENABLED && defined( 'CSF_BILLING_BOOKING_ENABLED' ) && true === CSF_BILLING_BOOKING_ENABLED;
	}
	/** Require an explicit reviewed policy even in a test environment. */
	private static function gate() {
		CSF_Billing_Domain::need( self::enabled(), 'customer_booking_disabled' );
		CSF_Billing_Booking::policy();
	}
	/** Read an owned active offer under a ledger transaction.
	 *
	 * @param object      $store Locked ledger.
	 * @param string      $id Offer UUID.
	 * @param string      $order_id Purchase UUID.
	 * @param string|null $session Buyer session digest, or null for current verified staff.
	 * @return array
	 */
	public static function owned( $store, $id, $order_id, $session ) {
		self::gate();
		CSF_Billing_Domain::uuid( $id );
		CSF_Billing_Domain::uuid( $order_id );
		if ( null === $session ) {
			CSF_Billing_Access::require_cap( 'csf_billing_redeem' ); }
		$order = $store->row( 'orders', $order_id );
		$row   = $store->row( 'commands', hash( 'sha256', 'booking_offer:' . $id ) );
		CSF_Billing_Domain::need( $order && $row && 'issued' === $order['status'] && ( null === $session || ( is_string( $session ) && hash_equals( $order['session_hash'], $session ) ) ), 'offer_unavailable' );
		$offer = json_decode( $row['result'], true, 16, JSON_THROW_ON_ERROR );
		$cert  = $store->row( 'certificates', $offer['certificate_id'] );
		CSF_Billing_Domain::need( hash_equals( $offer['policy_sha256'], hash( 'sha256', CSF_Billing_Domain::json( CSF_Billing_Booking::policy() ) ) ) && $offer['offer_id'] === $id && $offer['order_id'] === $order_id && $cert && $cert['order_id'] === $order_id && 'active' === $cert['status'] && hash_equals( $cert['number_hash'], $offer['number_hash'] ), 'offer_unavailable' );
		$plain = CSF_Billing_Config::open( $offer['body_sealed'] );
		CSF_Billing_Domain::need( hash_equals( $offer['body_sha256'], hash( 'sha256', $plain ) ), 'offer_changed' );
		return $offer;
	}
	/** Prepare an immutable exact appointment after the real scheduler preview succeeds.
	 *
	 * @param object $store Ledger.
	 * @param string $command UUID doubles as opaque offer reference.
	 * @param string $order_id Purchase UUID.
	 * @param array  $body Reviewed appointment.
	 * @param int    $revision Current facility revision.
	 * @return array
	 */
	public static function create( $store, $command, $order_id, $body, $revision ) {
		self::gate();
		$actor = CSF_Billing_Access::require_cap( 'csf_billing_redeem' );
		CSF_Billing_Domain::uuid( $command );
		CSF_Billing_Domain::uuid( $order_id );
		CSF_Billing_Domain::need( is_array( $body ) && 0 === ( $body['expected'] ?? null ) && 'confirmed' === ( $body['state'] ?? null ), 'invalid_offer' );
		// Preview owns a rollback transaction; never nest it inside the ledger writer.
		$prior = $store->snapshot( static fn( $locked ) => $locked->row( 'commands', hash( 'sha256', 'offer_create:' . $command ) ) );
		if ( ! $prior ) {
			$preview = CSF_Scheduling_Store::preview( $body, $revision, wp_generate_uuid4() );
			CSF_Billing_Domain::need( ! is_wp_error( $preview ), 'offer_unavailable' );
		}
		return $store->command(
			hash( 'sha256', 'offer_create:' . $command ),
			array(
				'order_id'    => $order_id,
				'appointment' => $body,
				'actor'       => $actor,
			),
			function ( $locked ) use ( $command, $order_id, $body, $actor ) {
				$order = $locked->row( 'orders', $order_id );
				$cert  = $locked->find( 'certificates', 'order_id', $order_id );
				CSF_Billing_Domain::need( $order && $cert && 'issued' === $order['status'] && 'active' === $cert['status'], 'offer_unavailable' );
				$d = json_decode( $order['document'], true, 32, JSON_THROW_ON_ERROR );
				$p = CSF_Billing_Booking::policy();
				CSF_Billing_Domain::need( count( $body['riders'] ) === 1 && count( $body['rides'] ) === 1 && in_array( $body['service_type'], $p['products'][ $d['product']['id'] ] ?? array(), true ) && strtotime( $body['start'] ) > time() && strtotime( $body['end'] ) - strtotime( $body['start'] ) === 60 * $d['product']['minutes_per_ride'], 'offer_unavailable' );
				$json  = CSF_Billing_Domain::json( $body );
				$offer = array(
					'version'        => 1,
					'offer_id'       => $command,
					'order_id'       => $order_id,
					'certificate_id' => $cert['id'],
					'number_hash'    => $cert['number_hash'],
					'body_sha256'    => hash( 'sha256', $json ),
					'policy_sha256'  => hash( 'sha256', CSF_Billing_Domain::json( CSF_Billing_Booking::policy() ) ),
					'body_sealed'    => CSF_Billing_Config::seal( $json ),
					'actor'          => $actor,
				);
				$locked->insert(
					'commands',
					array(
						'id'           => hash( 'sha256', 'booking_offer:' . $command ),
						'payload_hash' => hash( 'sha256', $json ),
						'result'       => CSF_Billing_Domain::json( $offer ),
						'created_at'   => gmdate( 'Y-m-d H:i:s' ),
					)
				);
				$locked->audit(
					'booking_offer_created',
					$actor,
					$cert['id'],
					array(
						'offer_id'    => $command,
						'body_sha256' => $offer['body_sha256'],
					)
				);
				return array(
					'offer_id' => $command,
					'state'    => 'awaiting_buyer_consent',
					'reserved' => false,
				);
			},
			static function () {
				CSF_Billing_Access::require_cap( 'csf_billing_redeem' ); }
		);
	}
	/** Minimized buyer view: no private rider, resource or staff identities.
	 *
	 * @param object $store Ledger.
	 * @param string $id Offer UUID.
	 * @param string $order_id Purchase UUID.
	 * @param string $session Buyer session digest.
	 * @return array
	 */
	public static function view( $store, $id, $order_id, $session ) {
		return $store->snapshot(
			function ( $locked ) use ( $id, $order_id, $session ) {
				$o       = self::owned( $locked, $id, $order_id, $session );
				$b       = json_decode( CSF_Billing_Config::open( $o['body_sealed'] ), true, 32, JSON_THROW_ON_ERROR );
				$used    = $locked->row( 'commands', hash( 'sha256', 'booking_offer_consumed:' . $id ) );
				$consent = $locked->row( 'commands', hash( 'sha256', 'booking_consent:' . $id ) );
				$state   = 'awaiting_buyer_consent';
				if ( $consent ) {
					$state = 'awaiting_staff_confirmation'; }
				if ( $used ) {
					$reservations = CSF_Billing_Booking::reservations( $locked, $o['certificate_id'] );
					$state        = $reservations[ $b['uuid'] ]['state'] ?? 'reconciliation_required';
				}
				return array(
					'offer_id'         => $id,
					'service'          => $b['service_type'],
					'start'            => $b['start'],
					'end'              => $b['end'],
					'state'            => $state,
					'consent_required' => ! $consent && ! $used,
				);
			}
		);
	}
	/** Record buyer consent only; failed/stale booking can never strand value here.
	 *
	 * @param object $store Ledger.
	 * @param string $command Request UUID.
	 * @param string $id Offer UUID.
	 * @param string $order_id Purchase UUID.
	 * @param string $session Session digest.
	 * @param string $number Buyer-entered certificate code.
	 * @param bool   $attested Explicit consent.
	 * @return array
	 */
	public static function accept( $store, $command, $id, $order_id, $session, $number, $attested ) {
		CSF_Billing_Domain::uuid( $command );
		CSF_Billing_Domain::need( true === $attested && is_string( $number ) && preg_match( '/^CSF-[A-F0-9]{32}$/D', $number ), 'offer_unavailable' );
		$hash = hash( 'sha256', $number );
		return $store->command(
			hash( 'sha256', 'offer_accept:' . $command ),
			array(
				'offer_id'    => $id,
				'order_id'    => $order_id,
				'session'     => $session,
				'number_hash' => $hash,
			),
			function ( $locked ) use ( $id, $order_id, $session ) {
				$o   = self::owned( $locked, $id, $order_id, $session );
				$key = hash( 'sha256', 'booking_consent:' . $id );
				if ( ! $locked->row( 'commands', $key ) ) {
					$d = json_decode( $locked->row( 'orders', $order_id )['document'], true, 32, JSON_THROW_ON_ERROR );
					$r = array(
						'version'        => 1,
						'offer_id'       => $id,
						'certificate_id' => $o['certificate_id'],
						'number_hash'    => $o['number_hash'],
						'body_sha256'    => $o['body_sha256'],
						'terms_sha256'   => $d['terms']['sha256'],
						'accepted_at'    => gmdate( 'c' ),
					);
					$locked->insert(
						'commands',
						array(
							'id'           => $key,
							'payload_hash' => hash( 'sha256', CSF_Billing_Domain::json( $r ) ),
							'result'       => CSF_Billing_Domain::json( $r ),
							'created_at'   => gmdate( 'Y-m-d H:i:s' ),
						)
					);
					$locked->audit(
						'booking_offer_consented',
						0,
						$o['certificate_id'],
						array(
							'offer_id'    => $id,
							'body_sha256' => $o['body_sha256'],
						)
					);
				}
				return array(
					'offer_id' => $id,
					'state'    => 'awaiting_staff_confirmation',
					'reserved' => false,
				);
			},
			static function () use ( $store, $id, $order_id, $session, $hash ) {
				$o = self::owned( $store, $id, $order_id, $session );
				CSF_Billing_Domain::need( hash_equals( $o['number_hash'], $hash ), 'offer_unavailable' );
				$b = json_decode( CSF_Billing_Config::open( $o['body_sealed'] ), true, 32, JSON_THROW_ON_ERROR );
				CSF_Billing_Domain::need( strtotime( $b['start'] ) > time(), 'offer_unavailable' );
			}
		);
	}
	/** Current verified staff confirm the exact consented body through the real scheduler.
	 *
	 * @param object $store Ledger.
	 * @param string $id Offer UUID.
	 * @param string $order_id Purchase UUID.
	 * @param int    $revision Current facility revision.
	 * @param string $command Idempotent scheduler command.
	 * @return array|WP_Error
	 */
	public static function confirm( $store, $id, $order_id, $revision, $command ) {
		$o = $store->snapshot( static fn( $locked ) => self::owned( $locked, $id, $order_id, null ) );
		$b = json_decode( CSF_Billing_Config::open( $o['body_sealed'] ), true, 32, JSON_THROW_ON_ERROR );
		return CSF_Billing_Booking::submit_offer( $b, $revision, $command, $o['number_hash'], $id );
	}
	/** Consume consent inside the outer transaction, after validating the current certificate.
	 *
	 * @param object $store Joined ledger.
	 * @param string $id Offer UUID.
	 * @param array  $cert Current certificate.
	 * @param array  $body Exact appointment.
	 * @param string $command Scheduler command.
	 */
	public static function consume( $store, $id, $cert, $body, $command ) {
		$o   = self::owned( $store, $id, $cert['order_id'], null );
		$row = $store->row( 'commands', hash( 'sha256', 'booking_consent:' . $id ) );
		CSF_Billing_Domain::need( $row, 'buyer_consent_required' );
		$c        = json_decode( $row['result'], true, 16, JSON_THROW_ON_ERROR );
		$document = json_decode( $store->row( 'orders', $cert['order_id'] )['document'], true, 32, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::need( $c['offer_id'] === $id && hash_equals( $row['payload_hash'], hash( 'sha256', CSF_Billing_Domain::json( $c ) ) ) && hash_equals( $document['terms']['sha256'], $c['terms_sha256'] ), 'offer_changed' );
		$hash = hash( 'sha256', CSF_Billing_Domain::json( $body ) );
		CSF_Billing_Domain::need( $o['certificate_id'] === $cert['id'] && $c['certificate_id'] === $cert['id'] && $c['number_hash'] === $cert['number_hash'] && $c['body_sha256'] === $hash && $o['body_sha256'] === $hash, 'offer_changed' );
		$key = hash( 'sha256', 'booking_offer_consumed:' . $id );
		CSF_Billing_Domain::need( ! $store->row( 'commands', $key ), 'offer_already_used' );
		$r = array(
			'offer_id'       => $id,
			'certificate_id' => $cert['id'],
			'booking_uuid'   => $body['uuid'],
			'command'        => $command,
		);
		$store->insert(
			'commands',
			array(
				'id'           => $key,
				'payload_hash' => $hash,
				'result'       => CSF_Billing_Domain::json( $r ),
				'created_at'   => gmdate( 'Y-m-d H:i:s' ),
			)
		);
		$store->audit( 'booking_offer_confirmed', get_current_user_id(), $cert['id'], $r );
	}
}

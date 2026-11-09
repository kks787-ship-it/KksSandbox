<?php
/** Certificate reservations joined to the existing scheduling transaction.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Private pilot only: customer identity/waiver/public booking remains independently held. */
final class CSF_Billing_Booking {
	/** Current outer scheduler command, never a durable bearer.
	 *
	 * @var array|null
	 */
	private static $context = null;
	/** Bind a vetted payment request for the duration of one scheduler operation.
	 *
	 * @param array|null $context Validated request plus command.
	 */
	public static function context( $context ) {
		self::$context = $context;
	}
	/** Reviewed policy is mandatory; no cancellation policy is invented.
	 *
	 * @return array
	 */
	public static function policy() {
		CSF_Billing_Domain::need( defined( 'CSF_BILLING_BOOKING_POLICY_JSON' ), 'booking_policy_pending' );
		$p = json_decode( CSF_BILLING_BOOKING_POLICY_JSON, true, 16, JSON_THROW_ON_ERROR );
		CSF_Billing_Domain::need( is_array( $p ) && true === ( $p['approved'] ?? false ) && is_string( $p['version'] ?? null ) && preg_match( '/^[a-zA-Z0-9._-]{1,64}$/D', $p['version'] ) && is_array( $p['products'] ?? null ) && is_array( $p['release_initiators'] ?? null ), 'booking_policy_pending' );
		return $p;
	}
	/** Private booking command; raw certificate number is converted before scheduler receipts.
	 *
	 * @param array  $body Scheduling body.
	 * @param int    $revision Facility revision.
	 * @param string $command UUID.
	 * @param string $transition Reserve, complete or correct completion.
	 * @param string $number Certificate bearer (reserve only).
	 * @return array|WP_Error
	 */
	public static function submit( $body, $revision, $command, $transition, $number = '' ) {
		CSF_Billing_Domain::need( defined( 'CSF_BILLING_BOOKING_ENABLED' ) && true === CSF_BILLING_BOOKING_ENABLED, 'booking_disabled' );
		CSF_Billing_Access::require_cap( 'correct' === $transition ? 'csf_billing_manage' : 'csf_billing_redeem' );
		CSF_Billing_Domain::need( in_array( $transition, array( 'reserve', 'complete', 'correct' ), true ), 'invalid_booking_transition' );
		CSF_Billing_Domain::uuid( $command );
		self::policy();
		CSF_Billing_Domain::need( 'reserve' !== $transition || ( is_string( $number ) && preg_match( '/^CSF-[A-F0-9]{32}$/D', $number ) ), 'certificate_unavailable' );
		$payment = array(
			'transition'  => $transition,
			'number_hash' => 'reserve' === $transition ? hash( 'sha256', $number ) : null,
			'command'     => $command,
		);
		return CSF_Scheduling_Store::calendar( 'appointment_saved', $body, $revision, $command, $payment );
	}
	/** Staff confirmation of an immutable buyer-consented offer, never a public staff grant.
	 *
	 * @param array  $body Reviewed appointment.
	 * @param int    $revision Facility revision.
	 * @param string $command Command UUID.
	 * @param string $hash Current certificate hash.
	 * @param string $offer Offer UUID.
	 * @return array|WP_Error
	 */
	public static function submit_offer( $body, $revision, $command, $hash, $offer ) {
		$payment = array(
			'transition'  => 'reserve',
			'number_hash' => $hash,
			'command'     => $command,
			'offer_id'    => $offer,
		);
		self::authorize( $payment );
		return CSF_Scheduling_Store::calendar( 'appointment_saved', $body, $revision, $command, $payment );
	}
	/** Recheck participant authorization before initial execution and exact scheduler replay.
	 *
	 * @param array|null $payment Scheduler participant request.
	 */
	public static function authorize( $payment ) {
		if ( null === $payment ) {
			return; }
		CSF_Billing_Domain::need( defined( 'CSF_BILLING_BOOKING_ENABLED' ) && true === CSF_BILLING_BOOKING_ENABLED && is_array( $payment ) && in_array( count( $payment ), array( 3, 4 ), true ) && in_array( $payment['transition'] ?? null, array( 'reserve', 'complete', 'correct' ), true ), 'booking_disabled' );
		CSF_Billing_Domain::uuid( $payment['command'] ?? '' );
		if ( 4 === count( $payment ) ) {
			CSF_Billing_Domain::need( CSF_Billing_Offer::enabled() && 'reserve' === $payment['transition'], 'customer_booking_disabled' );
			CSF_Billing_Domain::uuid( $payment['offer_id'] ?? '' );
		}
		CSF_Billing_Domain::need( 'reserve' !== $payment['transition'] || ( is_string( $payment['number_hash'] ?? null ) && preg_match( '/^[a-f0-9]{64}$/D', $payment['number_hash'] ) ), 'certificate_unavailable' );
		self::policy();
		CSF_Billing_Access::require_cap( 'correct' === $payment['transition'] ? 'csf_billing_manage' : 'csf_billing_redeem' );
	}
	/** Read the per-certificate reservation projection under the billing lock.
	 *
	 * @param object $store Locked store.
	 * @param string $id Certificate UUID.
	 * @return array
	 */
	public static function reservations( $store, $id ) {
		$row     = $store->row( 'commands', hash( 'sha256', 'booking_reservations:' . $id ) );
		$records = $row ? json_decode( $row['result'], true, 32, JSON_THROW_ON_ERROR ) : array();
		CSF_Billing_Domain::need( is_array( $records ), 'reservation_reconciliation_required' );
		foreach ( $records as $booking => $record ) {
			CSF_Billing_Domain::uuid( $booking );
			CSF_Billing_Domain::need( is_array( $record ) && 6 === count( $record ) && in_array( $record['state'] ?? null, array( 'reserved', 'used', 'released' ), true ) && is_array( $record['slots'] ?? null ) && array_is_list( $record['slots'] ) && 1 === count( $record['slots'] ) && is_int( $record['slots'][0] ) && $record['slots'][0] >= 0 && $record['slots'][0] < 100 && is_int( $record['value_minor'] ?? null ) && $record['value_minor'] > 0 && is_int( $record['revision'] ?? null ) && $record['revision'] > 0 && true === ( $record['policy']['approved'] ?? false ), 'reservation_reconciliation_required' );
		}
		return $records;
	}
	/** Reserved original ordinals remain paid value, unavailable for another spend.
	 *
	 * @param object $store Locked store.
	 * @param string $id Certificate UUID.
	 * @return array
	 */
	public static function slots( $store, $id ) {
		$slots = array();
		foreach ( self::reservations( $store, $id ) as $r ) {
			if ( 'reserved' === $r['state'] ) {
				$slots = array_merge( $slots, $r['slots'] );
			}
		}
		CSF_Billing_Domain::need( count( array_unique( $slots ) ) === count( $slots ), 'reservation_reconciliation_required' );
		return $slots;
	}
	/** Run inside the scheduler's transaction; never starts or commits another transaction.
	 *
	 * @param string     $kind Calendar mutation.
	 * @param array      $body Validated appointment body.
	 * @param array|null $before Previous private detail.
	 * @param array      $detail New detail, updated with an invariant marker.
	 * @param string     $zone Authoritative appointment timezone.
	 */
	public static function participate( $kind, $body, $before, &$detail, $zone ) {
		$ctx = self::$context;
		self::authorize( $ctx );
		if ( ! $ctx && empty( $before['billing_certificate_id'] ) ) {
			return;
		}
		$p     = self::policy();
		$actor = CSF_Billing_Access::require_cap( ( $ctx['transition'] ?? '' ) === 'correct' ? 'csf_billing_manage' : 'csf_billing_redeem' );
		$store = new CSF_Billing_Store();
		$store->join_calendar(
			function ( $locked ) use ( $kind, $body, $before, &$detail, $ctx, $p, $actor, $zone ) {
				$transition = 'appointment_canceled' === $kind ? 'cancel' : ( $ctx['transition'] ?? '' );
				$cert       = 'reserve' === $transition ? $locked->find( 'certificates', 'number_hash', $ctx['number_hash'] ) : $locked->row( 'certificates', $before['billing_certificate_id'] ?? '' );
				CSF_Billing_Domain::need( $cert, 'certificate_unavailable' );
				$id    = $cert['id'];
				$all   = self::reservations( $locked, $id );
				$prior = $all[ $body['uuid'] ] ?? null;
				$v     = CSF_Billing_Value::read( $cert );
				if ( 'reserve' === $transition ) {
					CSF_Billing_Domain::need( ! $prior && empty( $before['billing_certificate_id'] ) && 'active' === $cert['status'] && 'confirmed' === $detail['state'] && count( $body['riders'] ) === 1 && count( $body['rides'] ) === 1, 'booking_reservation_conflict' );
					$order = $locked->row( 'orders', $cert['order_id'] );
					$d     = json_decode( $order['document'], true, 32, JSON_THROW_ON_ERROR );
					CSF_Billing_Domain::need( 'issued' === $order['status'] && in_array( $body['service_type'], $p['products'][ $d['product']['id'] ] ?? array(), true ), 'certificate_service_ineligible' );
					$start = CSF_Scheduling_Time::parse( $body['start'], $zone );
					$end   = CSF_Scheduling_Time::parse( $body['end'], $zone );
					CSF_Billing_Domain::need( $start->getTimestamp() > time() && $end->getTimestamp() - $start->getTimestamp() === 60 * $d['product']['minutes_per_ride'], 'certificate_service_ineligible' );
					$debit = CSF_Billing_Value::debit( $cert, 1, self::slots( $locked, $id ) );
					if ( isset( $ctx['offer_id'] ) ) {
						CSF_Billing_Offer::consume( $locked, $ctx['offer_id'], $cert, $body, $ctx['command'] ); }
					$all[ $body['uuid'] ] = array(
						'state'              => 'reserved',
						'slots'              => $debit['slots'],
						'value_minor'        => $debit['value_minor'],
						'policy'             => $p,
						'revision'           => 1,
						'completion_command' => null,
					);
				} else {
					CSF_Billing_Domain::need( $prior && in_array( $transition, array( 'cancel', 'complete', 'correct' ), true ), 'booking_transition_required' );
					if ( 'cancel' === $transition ) {
						CSF_Billing_Domain::need( 'reserved' === $prior['state'] && in_array( $body['initiator'], $prior['policy']['release_initiators'], true ), 'cancellation_policy_review_required' );
						$all[ $body['uuid'] ]['state'] = 'released';
					} else {
						// Completion/correction must not edit the booked service, riders, resources or times.
						foreach ( array( 'title', 'service_type', 'state', 'start', 'end', 'hold_expires', 'resources', 'riders', 'rides' ) as $field ) {
							CSF_Billing_Domain::need( $body[ $field ] === $before[ $field ], 'booking_edit_requires_review' );
						}
						CSF_Billing_Domain::need( CSF_Scheduling_Time::parse( $before['end'], $zone )->getTimestamp() <= time(), 'lesson_not_completed' );
						if ( 'complete' === $transition ) {
							CSF_Billing_Domain::need( 'reserved' === $prior['state'] && 'active' === $cert['status'] && null === $v['pending'] && ! array_intersect( $prior['slots'], $v['used_slots'] ), 'booking_completion_conflict' );
							$v['used_slots'] = array_merge( $v['used_slots'], $prior['slots'] );
							sort( $v['used_slots'] );
							$command = $ctx['command'];
							$receipt = array(
								'certificate_id' => $id,
								'command'        => $command,
								'rides'          => 1,
								'slots'          => $prior['slots'],
								'value_minor'    => $prior['value_minor'],
								'booking_uuid'   => $body['uuid'],
							);
							$locked->insert(
								'commands',
								array(
									'id'           => hash( 'sha256', 'redeem:' . $command ),
									'payload_hash' => hash( 'sha256', CSF_Billing_Domain::json( $receipt ) ),
									'result'       => CSF_Billing_Domain::json( $receipt ),
									'created_at'   => gmdate( 'Y-m-d H:i:s' ),
								)
							);
							$locked->insert(
								'commands',
								array(
									'id'           => hash( 'sha256', 'ride:' . $id . ':' . $command ),
									'payload_hash' => hash( 'sha256', $id . ':' . $command ),
									'result'       => '{}',
									'created_at'   => gmdate( 'Y-m-d H:i:s' ),
								)
							);
							$locked->audit( 'rides_redeemed', $actor, $id, $receipt + array( 'ride_reference' => $command ) );
							$all[ $body['uuid'] ]['state']              = 'used';
							$all[ $body['uuid'] ]['completion_command'] = $command;
						} else {
							CSF_Billing_Domain::need( 'used' === $prior['state'] && null === $v['pending'], 'booking_correction_conflict' );
							$original = $prior['completion_command'];
							$debit    = json_decode( $locked->row( 'commands', hash( 'sha256', 'redeem:' . $original ) )['result'], true );
							$v        = CSF_Billing_Value::reverse( $cert, $debit );
							$locked->insert(
								'commands',
								array(
									'id'           => hash( 'sha256', 'reversed:' . $original ),
									'payload_hash' => hash( 'sha256', $original ),
									'result'       => '{}',
									'created_at'   => gmdate( 'Y-m-d H:i:s' ),
								)
							);
							$locked->audit(
								'ride_debit_corrected',
								$actor,
								$id,
								array(
									'original_command' => $original,
									'rides'            => 1,
									'slots'            => $prior['slots'],
									'value_minor'      => $prior['value_minor'],
									'reason'           => 'mistaken_ride',
								)
							);
							$all[ $body['uuid'] ]['state'] = 'reserved';
						}
					}
					++$all[ $body['uuid'] ]['revision'];
				}
				$key    = hash( 'sha256', 'booking_reservations:' . $id );
				$record = array( 'result' => CSF_Billing_Domain::json( $all ) );
				if ( $locked->row( 'commands', $key ) ) {
					$locked->update( 'commands', $key, $record );
				} else {
					$locked->insert(
						'commands',
						$record + array(
							'id'           => $key,
							'payload_hash' => hash( 'sha256', $id ),
							'created_at'   => gmdate( 'Y-m-d H:i:s' ),
						)
					);
				}
				$locked->update(
					'certificates',
					$id,
					array(
						'value_json' => CSF_Billing_Domain::json( $v ),
						'redeemed'   => count( $v['used_slots'] ),
						'revision'   => (int) $cert['revision'] + 1,
					)
				);
				$locked->audit(
					'booking_credit_' . $transition,
					$actor,
					$id,
					array(
						'booking_uuid' => $body['uuid'],
						'reservation'  => $all[ $body['uuid'] ],
						'initiator'    => 'cancel' === $transition ? $body['initiator'] : null,
					)
				);
				$detail['billing_certificate_id'] = $id;
				$detail['billing_credit_state']   = $all[ $body['uuid'] ]['state'];
			}
		);
	}
}

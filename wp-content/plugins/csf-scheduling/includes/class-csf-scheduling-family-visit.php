<?php
/** Atomic family visits with a common state and hold expiry through the existing Store transaction.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Coordinate this private scheduling operation through existing access and transaction boundaries. */
class CSF_Scheduling_Family_Visit {
	/** Context for the active transaction only.
	 *
	 * @var array|string|null
	 */
	private static $current = null;
	/** Calculated resource windows keyed by lesson UUID.
	 *
	 * @var array
	 */
	private static $plans = array();

	/** No new grants: every individual operation must be authorized.
	 *
	 * @param array $body Validated command body.
	 */
	public static function authorize( $body ) {
		if ( ! is_array( $body['items'] ?? null ) || ! array_is_list( $body['items'] ) || ! count( $body['items'] ) || count( $body['items'] ) > 20 ) {
			return false;
		}
		foreach ( $body['items'] as $item ) {
			if ( ! is_array( $item ) || ! in_array( $item['kind'] ?? '', array( 'appointment_saved', 'appointment_canceled' ), true ) || ! is_array( $item['body'] ?? null ) || ! CSF_Scheduling_Permissions::authorize( $item['kind'], $item['body'] ) ) {
				return false;
			}
		}
		return true;
	}

	/** Current transaction-only context; cannot be supplied as appointment fields.
	 *
	 * @param string $uuid Stable record UUID.
	 */
	public static function context( $uuid ) {
		return null !== self::$current && CSF_Scheduling_Store::in_calendar_transaction() && isset( self::$plans[ $uuid ] ) ? array(
			'visit_uuid'            => self::$current,
			'resource_reservations' => self::$plans[ $uuid ],
		) : null;
	}

	/** Read a group only when every active child is visible to this session.
	 *
	 * @param string $uuid Stable record UUID.
	 */
	public static function read( $uuid ) {
		global $wpdb;
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $uuid ) && CSF_Scheduling_Access::can( 'calendar_view' ), 'forbidden' );
		$row = CSF_Scheduling_Calendar::coordination_record( $uuid, 'family_visit' );
		$d   = $row ? json_decode( $row['body_json'], true ) : null;
		CSF_Scheduling_Calendar::require( 'family_visit' === ( $d['record_type'] ?? null ) && is_array( $d['active_members'] ) && count( $d['active_members'] ) <= 20, 'not_found' );
		// Empty visits still require scope over their retained history before reuse.
		if ( ! $d['active_members'] ) {
			CSF_Scheduling_Calendar::require( ! empty( $d['all_members'] ), 'not_found' );
			foreach ( $d['all_members'] as $id ) {
				$a = CSF_Scheduling_Calendar::find( 'appointments', $id );
				CSF_Scheduling_Calendar::require( $a && CSF_Scheduling_Permissions::appointment_visible( $a ), 'forbidden' ); }
		}
		$items = array();
		foreach ( $d['active_members'] as $id ) {
			$a = CSF_Scheduling_Calendar::find( 'appointments', $id );
			CSF_Scheduling_Calendar::require( $a && CSF_Scheduling_Permissions::appointment_visible( $a ), 'forbidden' );
			$detail = CSF_Scheduling_Calendar::find( 'details', $id );
			CSF_Scheduling_Calendar::require( (bool) $detail, 'not_found' );
			$b = json_decode( $detail['body_json'], true );
			CSF_Scheduling_Calendar::require( ( $b['visit_uuid'] ?? null ) === $uuid, 'invalid_transition' );
			unset( $b['visit_uuid'], $b['resource_reservations'], $b['buffer_overrides'] );
			$b['expected'] = (int) $a['revision'];
			$items[]       = array(
				'kind' => 'appointment_saved',
				'body' => $b,
			);
		}
		return array(
			'uuid'        => $uuid,
			'expected'    => (int) $row['revision'],
			'same_family' => true,
			'items'       => $items,
		);
	}

	/** Full active membership replacement; omitted members are never silently removed.
	 *
	 * @param array $body Validated command body.
	 * @param array $settings Locked facility settings.
	 */
	public static function apply( $body, $settings ) {
		global $wpdb;
		CSF_Scheduling_Calendar::require( null === self::$current && CSF_Scheduling_Store::in_calendar_transaction() && self::authorize( $body ), 'forbidden' );
		CSF_Scheduling_Calendar::keys( $body, array( 'uuid', 'expected', 'same_family', 'items' ) );
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $body['uuid'] ) && true === $body['same_family'] );
		$old      = CSF_Scheduling_Calendar::find( 'details', $body['uuid'] );
		$previous = $old ? json_decode( $old['body_json'], true ) : null;
		CSF_Scheduling_Calendar::require( ! CSF_Scheduling_Calendar::find( 'appointments', $body['uuid'] ) && ( ! $old || 'family_visit' === ( $previous['record_type'] ?? null ) ), 'invalid_transition' );
		CSF_Scheduling_Calendar::require( is_int( $body['expected'] ) && ( $old ? (int) $old['revision'] : 0 ) === $body['expected'], 'stale_appointment' );
		$p = json_decode( $settings['policy_json'], true );
		CSF_Scheduling_Calendar::require( ! CSF_Scheduling_Policy::missing( $p ), 'policy_incomplete' );
		// Reusing an empty visit must not bypass visibility of its historical members.
		if ( $old && empty( $previous['active_members'] ) ) {
			self::read( $body['uuid'] ); }
		$state    = null;
		$expiry   = null;
		$members  = array();
		$active   = array();
		$lessons  = array();
		$existing = array();
		foreach ( $body['items'] as $item ) {
			CSF_Scheduling_Calendar::keys( $item, array( 'kind', 'body' ) );
			$b  = $item['body'];
			$id = $b['uuid'] ?? null;
			CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $id ) && $id !== $body['uuid'] && ! isset( $members[ $id ] ) );
			$members[ $id ] = true;
			$a              = CSF_Scheduling_Calendar::find( 'appointments', $id );
			$d              = CSF_Scheduling_Calendar::find( 'details', $id );
			$detail         = $d ? json_decode( $d['body_json'], true ) : null;
			CSF_Scheduling_Calendar::require( ! $a || ( 'canceled' !== $a['state'] && ( empty( $detail['visit_uuid'] ) || ( $detail['visit_uuid'] === $body['uuid'] && in_array( $id, $previous['active_members'] ?? array(), true ) ) ) ), 'invalid_transition' );
			CSF_Scheduling_Calendar::require( ! $d || $a, 'invalid_transition' );
			CSF_Scheduling_Calendar::require( is_int( $b['expected'] ?? null ) && ( $a ? (int) $a['revision'] : 0 ) === $b['expected'], 'stale_appointment' );
			if ( $a ) {
				$existing[] = (int) $a['id'];
			}
			if ( 'appointment_canceled' === $item['kind'] ) {
				CSF_Scheduling_Calendar::require( (bool) $a, 'invalid_transition' );
				continue;
			}
			// All reserved siblings expire together: no partial expiry can expose an unreserved buffer.
			CSF_Scheduling_Calendar::require( in_array( $b['state'] ?? null, array( 'requested', 'held', 'confirmed' ), true ) && array_key_exists( 'hold_expires', $b ), 'visit_state' );
			$child_expiry = 'held' === $b['state'] ? CSF_Scheduling_Time::utc( CSF_Scheduling_Time::parse( $b['hold_expires'], $p['timezone'] ) ) : null;
			CSF_Scheduling_Calendar::require( ( 'held' === $b['state'] || null === $b['hold_expires'] ) && ( null === $state || ( $state === $b['state'] && $expiry === $child_expiry ) ), 'visit_state' );
			$state  = $b['state'];
			$expiry = $child_expiry;
			$s      = $p['services'][ $b['service_type'] ?? '' ] ?? null;
			CSF_Scheduling_Calendar::require( $s && $s['enabled'], 'policy_incomplete' );
			$a         = CSF_Scheduling_Time::parse( $b['start'], $p['timezone'] );
			$z         = CSF_Scheduling_Time::parse( $b['end'], $p['timezone'] );
			$resources = array();
			CSF_Scheduling_Calendar::require( is_array( $b['resources'] ?? null ) && array_is_list( $b['resources'] ) && count( $b['resources'] ) <= 100 );
			foreach ( $b['resources'] as $uuid ) {
				$r = CSF_Scheduling_Calendar::find( 'resources', $uuid );
				CSF_Scheduling_Calendar::require( $r && (int) $r['active'] && in_array( $r['kind'], array( 'instructor', 'arena', 'location' ), true ), 'not_found' );
				$travel      = $s['requires_travel'] ? $p['travel_buffer_minutes'] : 0;
				$resources[] = array(
					'uuid'             => $uuid,
					'kind'             => $r['kind'],
					'mandatory_before' => $travel,
					'mandatory_after'  => $travel,
					'shareable'        => ! $s['requires_travel'],
				);
			}
			$active[]  = $id;
			$lessons[] = array(
				'uuid'       => $id,
				'visit_uuid' => $body['uuid'],
				'start'      => $a->getTimestamp(),
				'end'        => $z->getTimestamp(),
				'resources'  => $resources,
			);
		}
		CSF_Scheduling_Calendar::require( ! array_diff( $previous['active_members'] ?? array(), array_keys( $members ) ), 'visit_members_required' );
		$plan = CSF_Scheduling_Visit_Plan::calculate( $lessons );
		CSF_Scheduling_Calendar::require( ! $plan['conflicts'], 'resource_conflict' );
		// Clear ONLY this complete, authorized group's old reservations under Store's lock.
		// Any later failure rolls back these deletions and all child writes together.
		foreach ( $existing as $id ) {
			CSF_Scheduling_Calendar::clear_reservations( $id );
		}
		self::$current = $body['uuid'];
		self::$plans   = array_fill_keys( array_keys( $members ), array() );
		foreach ( $plan['lessons'] as $lesson ) {
			self::$plans[ $lesson['uuid'] ] = $lesson['reservations'];
		}
		try {
			$results = array();
			// Validate horse workload in chronological order, independent of form order.
			$items = $body['items'];
			usort(
				$items,
				static function ( $a, $b ) use ( $p ) {
					$x = 'appointment_canceled' === $a['kind'] ? 0 : CSF_Scheduling_Time::parse( $a['body']['start'], $p['timezone'] )->getTimestamp();
					$y = 'appointment_canceled' === $b['kind'] ? 0 : CSF_Scheduling_Time::parse( $b['body']['start'], $p['timezone'] )->getTimestamp();
					return $x <=> $y;
				}
			);
			foreach ( $items as $item ) {
				$results[] = CSF_Scheduling_Calendar::apply( $item['kind'], $item['body'], $settings );
			}
			$document = array(
				'record_type'      => 'family_visit',
				'same_family'      => true,
				'state'            => $state ?? 'canceled',
				'hold_expires_utc' => $expiry,
				'active_members'   => $active,
				'all_members'      => array_values( array_unique( array_merge( $previous['all_members'] ?? array(), array_keys( $members ) ) ) ),
			);
			$revision = CSF_Scheduling_Calendar::document( 'details', $body['uuid'], $body['uuid'], $document, $body['expected'] );
			CSF_Scheduling_Calendar::document(
				'history',
				wp_generate_uuid4(),
				$body['uuid'],
				array(
					'kind'   => 'family_visit_saved',
					'before' => $previous,
					'after'  => $document,
				),
				0
			);
			return array(
				'uuid'            => $body['uuid'],
				'entity_revision' => $revision,
				'items'           => $results,
				'plan'            => $plan,
			);
		} finally {
			self::$current = null;
			self::$plans   = array();
		}
	}
}

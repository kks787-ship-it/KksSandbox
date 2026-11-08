<?php
/** Explicit trim of one optional resource buffer; mandatory constraints still apply.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
/** Coordinate this private scheduling operation through existing access and transaction boundaries. */
class CSF_Scheduling_Buffer_Override {
	/** Context for the active transaction only.
	 *
	 * @var array|string|null
	 */
	private static $current = null;
	/** Return context available only inside the active transaction.
	 *
	 * @param string $uuid Stable record UUID.
	 */
	public static function context( $uuid ) {
		return self::$current && self::$current['uuid'] === $uuid && CSF_Scheduling_Store::in_calendar_transaction() ? self::$current['metadata'] : null;
	}
	/** Check current scope and permission for the proposed command.
	 *
	 * @param array $body Validated command body.
	 */
	public static function authorize( $body ) {
		if ( ! CSF_Scheduling_Access::can( 'buffer_override' ) || ! CSF_Scheduling_Access::can( 'appointment_confirm' ) || ! CSF_Scheduling_Audit::uuid( $body['uuid'] ?? null ) ) {
			return false; }
		$a = CSF_Scheduling_Calendar::find( 'appointments', $body['uuid'] );
		return $a && CSF_Scheduling_Permissions::appointment_visible( $a );
	}
	/** Only reduce optional minutes; never shorten teaching or mandatory travel.
	 *
	 * @param array|null $before Appointment snapshot at the corresponding event boundary.
	 * @param array      $body Validated command body.
	 * @param int        $start Teaching boundary as a Unix timestamp.
	 * @param int        $end Teaching boundary as a Unix timestamp.
	 * @param int        $mandatory Mandatory travel minutes that cannot be trimmed.
	 * @param int        $actor Approving user ID.
	 */
	public static function trim( $before, $body, $start, $end, $mandatory, $actor ) {
		CSF_Scheduling_Calendar::require( in_array( $body['segment'], array( 'pre', 'post' ), true ) && is_int( $body['minutes'] ) && $body['minutes'] >= 0 && $body['minutes'] <= 1440 );
		CSF_Scheduling_Calendar::label( $body['reason'] );
		$windows = $before['resource_reservations'] ?? array();
		$found   = false;
		foreach ( $windows as &$window ) {
			if ( $window['resource_uuid'] !== $body['resource_uuid'] ) {
				continue; }
			$field = 'pre' === $body['segment'] ? 'pre_minutes' : 'post_minutes';
			CSF_Scheduling_Calendar::require( $body['minutes'] < $window[ $field ] && $body['minutes'] >= $mandatory, 'buffer_mandatory' );
			$original         = $window;
			$window[ $field ] = $body['minutes'];
			if ( 'pre' === $body['segment'] ) {
				$window['start'] = $start - 60 * $body['minutes'];
			} else {
				$window['end'] = $end + 60 * $body['minutes']; }
			CSF_Scheduling_Calendar::require( $window['start'] <= $start && $window['end'] >= $end && $window['start'] >= $original['start'] && $window['end'] <= $original['end'], 'buffer_mandatory' );
			$record = array(
				'resource_uuid'        => $body['resource_uuid'],
				'segment'              => $body['segment'],
				'reason'               => $body['reason'],
				'approved_by'          => $actor,
				'approved_at_utc'      => CSF_Scheduling_Audit::now(),
				'appointment_revision' => $body['expected'],
				'before'               => $original,
				'after'                => $window,
			);
			$found  = true;
		}
		unset( $window );
		CSF_Scheduling_Calendar::require( $found, 'buffer_snapshot_required' );
		$metadata = array(
			'resource_reservations' => $windows,
			'buffer_overrides'      => array_merge( $before['buffer_overrides'] ?? array(), array( $record ) ),
		);
		CSF_Scheduling_Calendar::require( count( $metadata['buffer_overrides'] ) <= 40, 'capacity' );
		if ( ! empty( $before['visit_uuid'] ) ) {
			$metadata['visit_uuid'] = $before['visit_uuid']; }
		return $metadata;
	}
	/** Apply the reviewed operation inside the existing Store transaction.
	 *
	 * @param array $body Validated command body.
	 * @param array $settings Locked facility settings.
	 */
	public static function apply( $body, $settings ) {
		CSF_Scheduling_Calendar::require( null === self::$current && CSF_Scheduling_Store::in_calendar_transaction() && self::authorize( $body ), 'forbidden' );
		CSF_Scheduling_Calendar::keys( $body, array( 'uuid', 'expected', 'resource_uuid', 'segment', 'minutes', 'reason' ) );
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $body['resource_uuid'] ) );
		$a = CSF_Scheduling_Calendar::find( 'appointments', $body['uuid'] );
		CSF_Scheduling_Calendar::require( 'confirmed' === $a['state'] && is_int( $body['expected'] ) && $body['expected'] === (int) $a['revision'], 'stale_appointment' );
		CSF_Scheduling_Calendar::require( strtotime( $a['start_utc'] . ' UTC' ) > time(), 'buffer_future_only' );
		$before = json_decode( CSF_Scheduling_Calendar::find( 'details', $body['uuid'] )['body_json'], true );
		$p      = json_decode( $settings['policy_json'], true );
		$s      = $p['services'][ $before['service_type'] ] ?? null;
		CSF_Scheduling_Calendar::require( $s && ! CSF_Scheduling_Policy::missing( $p ), 'policy_incomplete' );
		$metadata = self::trim( $before, $body, strtotime( $before['start'] ), strtotime( $before['end'] ), $s['requires_travel'] ? $p['travel_buffer_minutes'] : 0, get_current_user_id() );
		$metadata['buffer_overrides'][ count( $metadata['buffer_overrides'] ) - 1 ]['facility_revision'] = (int) $settings['revision'];
		$wire = $before;
		unset( $wire['resource_reservations'], $wire['visit_uuid'], $wire['buffer_overrides'] );
		$wire['expected'] = $body['expected'];
		self::$current    = array(
			'uuid'     => $body['uuid'],
			'metadata' => $metadata,
		);
		try {
			// Reuse ALL original appointment rules, including current horse welfare,
			// occupancy, resource/facility hours, scope and confirmation authority.
			$result = CSF_Scheduling_Calendar::apply( 'appointment_saved', $wire, $settings );
			CSF_Scheduling_Calendar::document(
				'history',
				wp_generate_uuid4(),
				$body['uuid'],
				array(
					'kind'              => 'buffer_overridden',
					'override'          => end( $metadata['buffer_overrides'] ),
					'facility_revision' => (int) $settings['revision'],
				),
				0
			);
			return $result;
		} finally {
			self::$current = null; }
	}
}

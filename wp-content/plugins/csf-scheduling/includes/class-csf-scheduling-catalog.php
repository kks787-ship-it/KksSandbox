<?php
/** Stable service identities, friendly catalog labels and booked snapshots.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/** All mutations remain inside the existing policy/appointment transaction. */
class CSF_Scheduling_Catalog {
	const META  = array( 'label', 'category', 'sort_order', 'classification' );
	const RULES = array( 'enabled', 'capacity', 'duration_minutes', 'horse_source', 'requires_travel', 'required_resources', 'classification' );
	/** Owner classifications only; no accounting interpretation.
	 *
	 * @return array
	 */
	public static function classifications() {
		return array(
			'unspecified'            => 'Not classified',
			'working_student_unpaid' => 'Working Student — Unpaid',
			'working_student_paid'   => 'Working Student — Paid',
			'student_lesson'         => 'Student Lesson',
			'training_client'        => 'Training Client',
			'private_double'         => 'Private / Double',
			'haul_in_offsite'        => 'Haul-In / Off-Site',
			'other'                  => 'Other',
		);
	}
	/** Plain bounded metadata.
	 *
	 * @param mixed $value Value.
	 * @param int   $limit Byte limit.
	 * @param bool  $blank Allow blank.
	 * @return bool
	 */
	public static function text( $value, $limit, $blank = false ) {
		return is_string( $value ) && strlen( $value ) <= $limit && ( $blank || '' !== trim( $value ) ) && sanitize_text_field( $value ) === $value;
	}
	/** Optional metadata never rewrites legacy six-field definitions.
	 *
	 * @param array $s Definition.
	 * @return bool
	 */
	public static function valid( $s ) {
		return ( ! array_key_exists( 'label', $s ) || self::text( $s['label'], 160 ) ) && ( ! array_key_exists( 'category', $s ) || self::text( $s['category'], 80, true ) ) && ( ! array_key_exists( 'sort_order', $s ) || ( is_int( $s['sort_order'] ) && $s['sort_order'] >= 0 && $s['sort_order'] <= 9999 ) ) && ( ! array_key_exists( 'classification', $s ) || ( is_string( $s['classification'] ) && isset( self::classifications()[ $s['classification'] ] ) ) );
	}
	/** Legacy fallback is display only, never a new ID.
	 *
	 * @param string $id Stable ID.
	 * @param array  $s Definition.
	 * @return string
	 */
	public static function label( $id, $s ) {
		return $s['label'] ?? ucwords( str_replace( '_', ' ', $id ) );
	}
	/** Enabled choices in owner order.
	 *
	 * @param array $services Definitions.
	 * @return array
	 */
	public static function choices( $services ) {
		$rows = array();
		foreach ( $services as $id => $s ) {
			if ( $s['enabled'] ) {
				$rows[] = array(
					'id'       => $id,
					'label'    => self::label( $id, $s ),
					'category' => $s['category'] ?? '',
					'order'    => $s['sort_order'] ?? count( $rows ) * 10,
				);
			}
		}
		usort( $rows, static fn( $a, $b ) => $a['order'] <=> $b['order'] ?: strcasecmp( $a['category'], $b['category'] ) ?: strcasecmp( $a['label'], $b['label'] ) ); // phpcs:ignore Universal.Operators.DisallowShortTernary -- Comparator fallback, no truthy business defaults.
		return array_column( $rows, 'label', 'id' );
	}
	/** Unsaved owner-requested examples; operating values remain unknown.
	 *
	 * @return array
	 */
	public static function examples() {
		$out = array();
		foreach ( array(
			array( 'Working Student — Unpaid Lesson', 'Working Student', 'working_student_unpaid', null ),
			array( 'Working Student — Paid Lesson', 'Working Student', 'working_student_paid', null ),
			array( 'Student Lesson — 30 Minutes', 'Student', 'student_lesson', 30 ),
			array( 'Student Lesson — 60 Minutes', 'Student', 'student_lesson', 60 ),
			array( 'Training Client Lesson', 'Training Client', 'training_client', null ),
			array( 'Private / Double Lesson', 'Other', 'private_double', null ),
			array( 'Haul-In / Off-Site Lesson', 'Other', 'haul_in_offsite', null ),
		) as $row ) {
			$out[] = array(
				'label'            => $row[0],
				'category'         => $row[1],
				'classification'   => $row[2],
				'duration_minutes' => $row[3],
			);
		}
		return $out;
	}
	/** Scheduling rules, with absent legacy classification normalized for comparison.
	 *
	 * @param array $s Definition.
	 * @return array
	 */
	private static function rules( $s ) {
		$out                   = array_intersect_key( $s, array_flip( self::RULES ) );
		$out['classification'] = $s['classification'] ?? 'unspecified';
		if ( is_array( $out['required_resources'] ?? null ) ) {
			sort( $out['required_resources'] );
		}
		ksort( $out );
		return $out;
	}
	/** Bounded aggregate impact; no unrelated appointment identities exposed.
	 *
	 * @param array $old Current policy.
	 * @param array $proposed Proposed policy.
	 * @return array
	 */
	public static function impact( $old, $proposed ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Store::in_calendar_transaction() && ! CSF_Scheduling_Access::can( 'policy_manage' ) ) {
			return array(
				'blocked' => true,
				'changes' => array(),
			);
		}
		$t   = CSF_Scheduling_Schema::table( 'appointments' );
		$out = array(
			'blocked' => false,
			'changes' => array(),
		);
		foreach ( $old['services'] as $id => $s ) {
			$next = $proposed['services'][ $id ] ?? null;
			if ( $s === $next ) {
				continue;
			}
			$history = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$t` WHERE service_type=%s", $id ) );
			CSF_Scheduling_Calendar::require( null !== $history && '' === $wpdb->last_error, 'write_failed' );
			$future = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$t` WHERE service_type=%s AND state<>'canceled' AND end_utc>%s", $id, CSF_Scheduling_Audit::now() ) );
			CSF_Scheduling_Calendar::require( null !== $future && '' === $wpdb->last_error, 'write_failed' );
			$rules            = null === $next || self::rules( $s ) !== self::rules( $next );
			$blocked          = ( null === $next && (int) $history > 0 ) || ( $rules && (int) $future > 0 );
			$out['blocked']   = $out['blocked'] || $blocked;
			$out['changes'][] = array(
				'id'            => $id,
				'label'         => self::label( $id, $s ),
				'history'       => (int) $history,
				'future'        => (int) $future,
				'rules_changed' => $rules,
				'blocked'       => $blocked,
			);
		}
		return $out;
	}
	/** Same singleton lock and rollback boundary as policy replacement.
	 *
	 * @param array $old Current policy.
	 * @param array $proposed Proposed policy.
	 * @param array $settings Locked settings row.
	 */
	public static function policy_changed( $old, $proposed, $settings ) {
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Store::in_calendar_transaction() && CSF_Scheduling_Access::can( 'policy_manage' ), 'forbidden' );
		CSF_Scheduling_Calendar::require( ! self::impact( $old, $proposed )['blocked'], 'service_in_use' );
		foreach ( array_unique( array_merge( array_keys( $old['services'] ), array_keys( $proposed['services'] ) ) ) as $id ) {
			$before = $old['services'][ $id ] ?? null;
			$after  = $proposed['services'][ $id ] ?? null;
			if ( $before !== $after ) {
				CSF_Scheduling_Calendar::document(
					'history',
					wp_generate_uuid4(),
					$settings['uuid'],
					array(
						'kind'            => 'lesson_type_changed',
						'service_id'      => $id,
						'before'          => $before,
						'after'           => $after,
						'policy_revision' => (int) $settings['revision'] + 1,
					),
					0
				);
			}
		}
	}
	/** Immutable display/rule meaning at booking time.
	 *
	 * @param string $id Service ID.
	 * @param array  $s Definition.
	 * @return array
	 */
	public static function pack( $id, $s ) {
		return array(
			'service_id'     => $id,
			'label'          => self::label( $id, $s ),
			'category'       => $s['category'] ?? '',
			'classification' => $s['classification'] ?? 'unspecified',
			'service'        => $s,
		);
	}
	/** Protected lookup; legacy rows retain the definition before its first catalog change.
	 *
	 * @param string $uuid Appointment UUID.
	 * @param string $id Service ID.
	 * @param array  $p Reviewed policy.
	 * @return array|null
	 */
	public static function booked( $uuid, $id, $p ) {
		global $wpdb;
		$a = CSF_Scheduling_Calendar::find( 'appointments', $uuid );
		if ( ! $a || $a['service_type'] !== $id ) {
			return null;
		}
		$t   = CSF_Scheduling_Schema::table( 'history' );
		$raw = $wpdb->get_var( $wpdb->prepare( "SELECT body_json FROM `$t` WHERE entity_uuid=%s AND JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.service_snapshot.service_id'))=%s ORDER BY id DESC LIMIT 1", $uuid, $id ) );
		CSF_Scheduling_Calendar::require( '' === $wpdb->last_error, 'write_failed' );
		if ( $raw ) {
			return json_decode( $raw, true )['service_snapshot'];
		}
		$settings = CSF_Scheduling_Schema::settings();
		$raw      = $wpdb->get_var( $wpdb->prepare( "SELECT body_json FROM `$t` WHERE entity_uuid=%s AND JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.kind'))='lesson_type_changed' AND JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.service_id'))=%s AND JSON_TYPE(JSON_EXTRACT(body_json,'$.before'))='OBJECT' ORDER BY id ASC LIMIT 1", $settings['uuid'], $id ) );
		CSF_Scheduling_Calendar::require( '' === $wpdb->last_error, 'write_failed' );
		$prior = $raw ? json_decode( $raw, true )['before'] : null;
		return self::pack( $id, $prior ?? $p['services'][ $id ] ?? array() );
	}
	/** Carry the booked snapshot through title/time edits; explicit type changes use a new snapshot.
	 *
	 * @param array      $body Canonical appointment.
	 * @param array|null $before Previous details.
	 * @param array      $p Policy.
	 * @return array
	 */
	public static function for_save( $body, $before, $p ) {
		if ( $before && $before['service_type'] === $body['service_type'] ) {
			$snapshot = self::booked( $body['uuid'], $body['service_type'], $p );
			// A completed record must not be rescheduled under silently changed rules.
			CSF_Scheduling_Calendar::require( isset( $body['cancellation'] ) || self::rules( $snapshot['service'] ) === self::rules( $p['services'][ $body['service_type'] ] ), 'service_in_use' );
			return $snapshot;
		}
		return self::pack( $body['service_type'], $p['services'][ $body['service_type'] ] );
	}
}

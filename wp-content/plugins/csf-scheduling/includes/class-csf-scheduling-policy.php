<?php
/** Facility-defined rules; no customer-specific operational defaults.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Validates policy shape separately from completeness. */
class CSF_Scheduling_Policy {
	/** Unconfigured means unknown, never silently available.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'timezone'              => null,
			'service_hours'         => null,
			'services'              => array(),
			'workload'              => array(
				'daily_minutes'       => null,
				'consecutive_minutes' => null,
				'break_minutes'       => null,
			),
			'training_horse_cap'    => null,
			'academy'               => null,
			'preferred_offsite_day' => null,
			'travel_buffer_minutes' => null,
		);
	}

	/** Validate a complete replacement document without guessing missing values.
	 *
	 * @param mixed $input Proposed policy.
	 * @return array|WP_Error
	 */
	public static function validate( $input ) {
		if ( ! is_array( $input ) || array_diff( array_keys( $input ), array_keys( self::defaults() ) ) || array_diff( array_keys( self::defaults() ), array_keys( $input ) ) ) {
			return new WP_Error( 'policy_shape', 'Provide exactly the documented policy keys.' );
		}
		$p = $input;
		if ( null !== $p['timezone'] && ( ! is_string( $p['timezone'] ) || ! in_array( $p['timezone'], timezone_identifiers_list(), true ) ) ) {
			return new WP_Error( 'policy_timezone', 'Use an IANA timezone.' );
		}
		if ( null !== $p['service_hours'] ) {
			if ( ! is_array( $p['service_hours'] ) || 7 !== count( $p['service_hours'] ) ) {
				return new WP_Error( 'policy_hours', 'Specify all seven weekdays; an empty day is closed.' );
			}
			foreach ( range( 0, 6 ) as $day ) {
				if ( ! array_key_exists( $day, $p['service_hours'] ) || ! is_array( $p['service_hours'][ $day ] ) ) {
					return new WP_Error( 'policy_hours', 'Invalid weekday intervals.' );
				}
				$end = '';
				foreach ( $p['service_hours'][ $day ] as $range ) {
					if ( ! is_array( $range ) || array_keys( $range ) !== array( 0, 1 ) || ! is_string( $range[0] ) || ! is_string( $range[1] ) || ! preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $range[0] ) || ! preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $range[1] ) || $range[0] >= $range[1] || $range[0] < $end ) {
						return new WP_Error( 'policy_hours', 'Use ordered, non-overlapping same-day intervals.' );
					}
					$end = $range[1];
				}
			}
		}
		if ( ! is_array( $p['services'] ) || count( $p['services'] ) > 30 ) {
			return new WP_Error( 'policy_services', 'Invalid services.' );
		}
		foreach ( $p['services'] as $key => $service ) {
			if ( ! is_string( $key ) || ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/D', $key ) || ! is_array( $service ) || ! self::keys( array_diff_key( $service, array_flip( CSF_Scheduling_Catalog::META ) ), array( 'enabled', 'capacity', 'duration_minutes', 'horse_source', 'requires_travel', 'required_resources' ) ) || ! is_bool( $service['enabled'] ) || ! is_bool( $service['requires_travel'] ) || ! in_array( $service['horse_source'], array( null, 'school', 'client', 'either', 'none' ), true ) || ! CSF_Scheduling_Catalog::valid( $service ) || ! self::number( $service['capacity'], 1, 100 ) || ! self::number( $service['duration_minutes'], 1, 1440 ) ) {
				return new WP_Error( 'policy_services', 'Invalid service fields or bounds.' );
			}
			if ( $service['enabled'] && isset( $service['label'] ) && ( null === $service['horse_source'] || null === $service['capacity'] || null === $service['required_resources'] || null === $service['duration_minutes'] || $service['duration_minutes'] < 15 || 0 !== $service['duration_minutes'] % 15 ) ) {
				return new WP_Error( 'policy_services', 'Enabled lesson types need capacity, horse source, reviewed resources and a duration in 15-minute steps.' );
			}
			$required = $service['required_resources'];
			if ( null !== $required && ( ! is_array( $required ) || ! array_is_list( $required ) || count( array_filter( $required, 'is_string' ) ) !== count( $required ) || array_values( array_unique( $required ) ) !== $required || array_diff( $required, array( 'instructor', 'horse', 'arena', 'location' ) ) ) ) {
				return new WP_Error( 'policy_resources', 'Explicit resource kinds required.' );
			}
		}
		if ( ! is_array( $p['workload'] ) || ! self::keys( $p['workload'], array( 'daily_minutes', 'consecutive_minutes', 'break_minutes' ) ) ) {
			return new WP_Error( 'policy_workload', 'Specify the documented workload fields.' );
		}
		foreach ( $p['workload'] as $value ) {
			if ( ! self::number( $value, 0, 1440 ) ) {
				return new WP_Error( 'policy_workload', 'Invalid workload bounds.' );
			}
		}
		if ( ! self::number( $p['training_horse_cap'], 0, 10000 ) || ! self::number( $p['preferred_offsite_day'], 0, 6 ) || ! self::number( $p['travel_buffer_minutes'], 0, 1440 ) ) {
			return new WP_Error( 'policy_bounds', 'Invalid facility bounds.' );
		}
		if ( null !== $p['academy'] ) {
			$a = $p['academy'];
			if ( ! is_array( $a ) || ! isset( $a['enabled'] ) || ! is_bool( $a['enabled'] ) || ( ! $a['enabled'] && ! self::keys( $a, array( 'enabled' ) ) ) ) {
				return new WP_Error( 'policy_academy', 'Explicitly disable or fully configure Academy.' );
			}
			if ( $a['enabled'] ) {
				if ( ! self::keys( $a, array( 'enabled', 'weekday', 'start', 'end', 'season_start', 'season_end', 'capacity' ) ) || ! is_int( $a['weekday'] ) || $a['weekday'] < 0 || $a['weekday'] > 6 || ! is_string( $a['start'] ) || ! is_string( $a['end'] ) || ! preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $a['start'] ) || ! preg_match( '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $a['end'] ) || $a['start'] >= $a['end'] || ! self::date( $a['season_start'] ) || ! self::date( $a['season_end'] ) || $a['season_start'] > $a['season_end'] || ! is_int( $a['capacity'] ) || $a['capacity'] < 1 || $a['capacity'] > 100 ) {
					return new WP_Error( 'policy_academy', 'Invalid Academy season, hours or capacity.' );
				}
			}
		}
		return $p;
	}

	/** JSON object key order has no meaning.
	 *
	 * @param array $value Object.
	 * @param array $expected Expected keys.
	 * @return bool
	 */
	private static function keys( $value, $expected ) {
		$keys = array_keys( $value );
		sort( $keys );
		sort( $expected );
		return $keys === $expected;
	}

	/** Nullable bounded integer, never numeric-string coercion.
	 *
	 * @param mixed $n Value.
	 * @param int   $min Minimum.
	 * @param int   $max Maximum.
	 * @return bool
	 */
	private static function number( $n, $min, $max ) {
		return null === $n || ( is_int( $n ) && $n >= $min && $n <= $max );
	}

	/** Strict calendar date.
	 *
	 * @param mixed $date Date.
	 * @return bool
	 */
	private static function date( $date ) {
		if ( ! is_string( $date ) ) {
			return false;
		}
		$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}

	/** Required configuration gaps. Shape validation always precedes readiness.
	 *
	 * @param array $p Policy.
	 * @return array
	 */
	public static function missing( $p ) {
		if ( is_wp_error( self::validate( $p ) ) ) {
			return array( 'invalid_policy' );
		}
		$missing = array();
		foreach ( array( 'timezone', 'service_hours', 'training_horse_cap', 'academy' ) as $key ) {
			if ( null === $p[ $key ] ) {
				$missing[] = $key;
			}
		}
		$enabled = false;
		foreach ( $p['services'] as $key => $s ) {
			if ( ! $s['enabled'] ) {
				continue;
			}
			$enabled = true;
			if ( null === $s['horse_source'] || null === $s['capacity'] || null === $s['duration_minutes'] || null === $s['required_resources'] ) {
				$missing[] = 'service:' . $key;
			}
			if ( in_array( $s['horse_source'], array( 'school', 'either' ), true ) && in_array( null, $p['workload'], true ) ) {
				$missing[] = 'workload';
			}
			if ( $s['requires_travel'] && null === $p['travel_buffer_minutes'] ) {
				$missing[] = 'travel_buffer_minutes';
			}
		}
		if ( ! $enabled ) {
			$missing[] = 'enabled_services';
		}
		return array_values( array_unique( $missing ) );
	}
}

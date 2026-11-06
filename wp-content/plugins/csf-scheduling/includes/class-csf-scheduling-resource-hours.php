<?php
/** Weekly resource hours and bounded closures using the existing transaction.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/** No materialized recurrence, new tables, grants or independent writer. */
class CSF_Scheduling_Resource_Hours {
	const COMMANDS = array( 'resource_hours_saved', 'resource_closure_added', 'resource_closure_released' );
	const ERRORS   = array( 'resource_hours_invalid', 'resource_impact_limit' );
	/** Strict date without PHP normalization.
	 *
	 * @param mixed $day Local date.
	 * @return bool
	 */
	public static function date( $day ) {
		if ( ! is_string( $day ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $day ) ) {
			return false; }
		$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, new DateTimeZone( 'UTC' ) );
		return $d && $d->format( 'Y-m-d' ) === $day;
	}
	/** Only arena/instructor/location management; never release horse welfare.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @return bool
	 */
	public static function authorize( $kind, $body ) {
		if ( ! CSF_Scheduling_Access::can( 'availability_manage' ) || ! CSF_Scheduling_Access::can( 'resource_view' ) ) {
			return false; }
		if ( 'resource_closure_released' === $kind ) {
			$d = CSF_Scheduling_Calendar::find( 'restrictions', $body['uuid'] ?? '' );
			if ( ! $d ) {
				return false; }
			$v = json_decode( $d['body_json'], true );
			if ( 'resource_closure' !== ( $v['closure_kind'] ?? null ) ) {
				return false; }
			$id = $v['target_uuid'];
		} else {
			$id = 'resource_hours_saved' === $kind ? ( $body['uuid'] ?? '' ) : ( $body['data']['target_uuid'] ?? '' ); }
		if ( ! CSF_Scheduling_Audit::uuid( $id ) ) {
			return false; }
		$r = CSF_Scheduling_Calendar::find( 'resources', $id );
		return $r && in_array( $r['kind'], array( 'arena', 'instructor', 'location' ), true );
	}
	/** Validate weekly local boundaries; no hours inferred from legacy intervals.
	 *
	 * @param array $d Weekly definition.
	 * @param array $p Facility policy.
	 */
	public static function validate( $d, $p ) {
		CSF_Scheduling_Calendar::keys( $d, array( 'mode', 'timezone', 'from', 'periods' ) );
		CSF_Scheduling_Calendar::require( in_array( $d['mode'], array( 'legacy', 'inherit', 'custom' ), true ) && is_string( $d['timezone'] ) && $d['timezone'] === $p['timezone'] && self::date( $d['from'] ) && is_array( $d['periods'] ) && array_is_list( $d['periods'] ) && count( $d['periods'] ) <= 28, 'resource_hours_invalid' );
		CSF_Scheduling_Calendar::require( 'custom' === $d['mode'] || ! $d['periods'], 'resource_hours_invalid' );
		$days = array_fill( 0, 7, array() );
		foreach ( $d['periods'] as $period ) {
			CSF_Scheduling_Calendar::keys( $period, array( 'weekday', 'start', 'end' ) );
			CSF_Scheduling_Calendar::require( is_int( $period['weekday'] ) && $period['weekday'] >= 0 && $period['weekday'] <= 6 && is_string( $period['start'] ) && is_string( $period['end'] ) && preg_match( '/^(?:[01][0-9]|2[0-3]):(?:00|15|30|45)$/D', $period['start'] ) && preg_match( '/^(?:(?:[01][0-9]|2[0-3]):(?:00|15|30|45)|24:00)$/D', $period['end'] ) && $period['start'] < $period['end'], 'resource_hours_invalid' );
			$days[ $period['weekday'] ][] = array( $period['start'], $period['end'] );
		}
		foreach ( $days as $ranges ) {
			sort( $ranges );
			$end = '';
			foreach ( $ranges as $range ) {
				CSF_Scheduling_Calendar::require( $range[0] >= $end, 'resource_hours_invalid' );
				$end = $range[1]; }
		}
	}
	/** Conservative boundary: availability narrows folds, closures widen them.
	 * Missing closure boundaries protect the entire date; hours stay closed.
	 *
	 * @param string $day Date.
	 * @param string $start Clock.
	 * @param string $end Clock, 24:00 means next midnight.
	 * @param string $zone Stored zone.
	 * @param bool   $closure Protective interval.
	 * @return array|null
	 */
	public static function window( $day, $start, $end, $zone, $closure = false ) {
		$next = ( new DateTimeImmutable( $day, new DateTimeZone( $zone ) ) )->modify( '+1 day' )->format( 'Y-m-d' );
		$a    = CSF_Scheduling_Time::choices( $day . 'T' . $start, $zone );
		$z    = CSF_Scheduling_Time::choices( ( '24:00' === $end ? $next . 'T00:00' : $day . 'T' . $end ), $zone );
		if ( ! $a || ! $z ) {
			if ( ! $closure ) {
				return null; }
			$a = CSF_Scheduling_Time::choices( $day . 'T00:00', $zone );
			$z = CSF_Scheduling_Time::choices( $next . 'T00:00', $zone );
			// Midnight transitions use PHP's local-day boundary, conservatively.
			if ( ! $a ) {
				$a = array( ( new DateTimeImmutable( $day, new DateTimeZone( $zone ) ) )->format( 'Y-m-d\TH:iP' ) ); }
			if ( ! $z ) {
				$z = array( ( new DateTimeImmutable( $next, new DateTimeZone( $zone ) ) )->format( 'Y-m-d\TH:iP' ) ); }
		}
		$left  = $closure ? $a[0] : end( $a );
		$right = $closure ? end( $z ) : $z[0];
		return strtotime( $right ) > strtotime( $left ) ? array(
			'start' => $left,
			'end'   => $right,
		) : null;
	}
	/** Project recurring base for a bounded view; preserve old dates before effective date.
	 *
	 * @param array  $d Availability document.
	 * @param string $day First local day.
	 * @param int    $days At most eight view days.
	 * @param array  $p Current policy.
	 * @return array
	 */
	public static function project( $d, $day, $days, $p ) {
		$weekly = $d['weekly'] ?? null;
		if ( ! $weekly || 'legacy' === $weekly['mode'] ) {
			return $d['intervals']; }
		if ( $weekly['timezone'] !== $p['timezone'] || $days < 1 || $days > 8 ) {
			return array(); }
		$out   = array();
		$zone  = new DateTimeZone( $weekly['timezone'] );
		$first = new DateTimeImmutable( $day, $zone );
		$cut   = new DateTimeImmutable( $weekly['from'], $zone );
		foreach ( $d['intervals'] as $interval ) {
			$a = CSF_Scheduling_Time::parse( $interval['start'], $p['timezone'] );
			$z = CSF_Scheduling_Time::parse( $interval['end'], $p['timezone'] );
			if ( $a < $cut ) {
				$out[] = array(
					'start' => $interval['start'],
					'end'   => min( $z, $cut )->format( 'Y-m-d\TH:iP' ),
				); }
		}
		for ( $i = 0;$i < $days;++$i ) {
			$date = $first->modify( '+' . $i . ' days' );
			if ( $date < $cut ) {
				continue; }
			$ranges = 'inherit' === $weekly['mode'] ? ( $p['service_hours'][ (int) $date->format( 'w' ) ] ?? array() ) : array_map( static fn( $v ) => array( $v['start'], $v['end'] ), array_filter( $weekly['periods'], static fn( $v ) => $v['weekday'] === (int) $date->format( 'w' ) ) );
			foreach ( $ranges as $range ) {
				$w = self::window( $date->format( 'Y-m-d' ), $range[0], $range[1], $weekly['timezone'] );
				if ( $w ) {
					$out[] = $w; }
			}
		}
		return $out;
	}
	/** One authority for actual booking and visual resource candidates.
	 *
	 * @param array             $d Availability document.
	 * @param DateTimeImmutable $a Start.
	 * @param DateTimeImmutable $z End.
	 * @param array             $p Policy.
	 * @return bool
	 */
	public static function covers( $d, $a, $z, $p ) {
		if ( empty( $d['weekly'] ) || 'legacy' === $d['weekly']['mode'] ) {
			foreach ( $d['intervals'] as $range ) {
				if ( CSF_Scheduling_Time::parse( $range['start'], $p['timezone'] ) <= $a && CSF_Scheduling_Time::parse( $range['end'], $p['timezone'] ) >= $z ) {
					return true; }
			}
			return false;
		}
		if ( $z <= $a || $z->getTimestamp() - $a->getTimestamp() > 345600 ) {
			return false; }
		$local  = $a->setTimezone( new DateTimeZone( $p['timezone'] ) );
		$ranges = self::project( $d, $local->format( 'Y-m-d' ), 6, $p );
		usort( $ranges, static fn( $x, $y ) => strtotime( $x['start'] ) <=> strtotime( $y['start'] ) );
		$cursor = $a->getTimestamp();
		foreach ( $ranges as $range ) {
			$left  = strtotime( $range['start'] );
			$right = strtotime( $range['end'] );
			if ( $right <= $cursor ) {
				continue;
			}if ( $left > $cursor ) {
				return false;
			}$cursor = $right;
			if ( $cursor >= $z->getTimestamp() ) {
				return true; }
		}
		return false;
	}
	/** Expand only the dates being checked, including the previous full-day boundary.
	 *
	 * @param array             $d Restriction data.
	 * @param DateTimeImmutable $a Query start.
	 * @param DateTimeImmutable $z Query end.
	 * @return array
	 */
	public static function closure_windows( $d, $a, $z ) {
		if ( ! empty( $d['released'] ) ) {
			return array(); }
		$r = $d['recurrence'] ?? null;
		if ( ! $r ) {
			return array(
				array(
					'start' => $d['start'],
					'end'   => $d['end'],
				),
			); }
		$zone  = new DateTimeZone( $r['timezone'] );
		$first = $a->setTimezone( $zone )->setTime( 0, 0 )->modify( '-1 day' );
		$out   = array();
		for ( $i = 0;$i < 10;++$i ) {
			$day = $first->modify( '+' . $i . ' days' );
			if ( $day > $z ) {
				break;
			}$date = $day->format( 'Y-m-d' );
			if ( $date >= $r['from'] && $date <= $r['until'] && ! in_array( $date, $d['reopen_dates'] ?? array(), true ) && (int) $day->format( 'w' ) === $r['weekday'] ) {
				$w = self::window( $date, $r['start'], $r['end'], $r['timezone'], true );
				if ( $w ) {
					$out[] = $w; }
			}
		}
		return $out;
	}
	/** Protective intersection; release never creates new availability.
	 *
	 * @param array             $d Restriction.
	 * @param DateTimeImmutable $a Start.
	 * @param DateTimeImmutable $z End.
	 * @return bool
	 */
	public static function blocks( $d, $a, $z ) {
		foreach ( self::closure_windows( $d, $a, $z ) as $w ) {
			if ( strtotime( $w['start'] ) < $z->getTimestamp() && strtotime( $w['end'] ) > $a->getTimestamp() ) {
				return true; }
		}
		return false;
	}
	/** Preview only aggregate affected appointments, including real slot envelopes.
	 *
	 * @param string $id Resource UUID.
	 * @param array  $d Proposed document.
	 * @param array  $p Policy.
	 * @param bool   $hours Base-hours change.
	 * @return int
	 */
	private static function impact( $id, $d, $p, $hours ) {
		global $wpdb;
		$r       = CSF_Scheduling_Calendar::find( 'resources', $id );
		$a       = CSF_Scheduling_Schema::table( 'appointments' );
		$details = CSF_Scheduling_Schema::table( 'details' );
		$slots   = CSF_Scheduling_Schema::table( 'slots' );
		$rows    = $wpdb->get_results( $wpdb->prepare( "SELECT a.start_utc,a.end_utc,(SELECT MIN(s.start_utc) FROM `$slots` s WHERE s.appointment_id=a.id AND s.resource_id=%d) reserve_start,(SELECT MAX(s.end_utc) FROM `$slots` s WHERE s.appointment_id=a.id AND s.resource_id=%d) reserve_end FROM `$a` a JOIN `$details` d ON d.uuid=a.uuid WHERE a.state<>'canceled' AND a.end_utc>%s AND JSON_CONTAINS(d.body_json,JSON_QUOTE(%s),'$.resources') ORDER BY a.id LIMIT 2001", $r['id'], $r['id'], CSF_Scheduling_Audit::now(), $id ), ARRAY_A );
		CSF_Scheduling_Calendar::require( is_array( $rows ) && '' === $wpdb->last_error, 'write_failed' );
		CSF_Scheduling_Calendar::require( count( $rows ) <= 2000, 'resource_impact_limit' );
		$count = 0;
		foreach ( $rows as $row ) {
			$start  = new DateTimeImmutable( $row['reserve_start'] ?? $row['start_utc'], new DateTimeZone( 'UTC' ) );
			$end    = new DateTimeImmutable( $row['reserve_end'] ?? $row['end_utc'], new DateTimeZone( 'UTC' ) );
			$count += $hours ? ! self::covers( $d, $start, $end, $p ) : self::blocks( $d, $start, $end ); }
		return $count;
	}
	/** Audited writer, callable only within the existing singleton transaction.
	 *
	 * @param string $kind Command.
	 * @param array  $body Exact body.
	 * @param array  $settings Locked settings.
	 * @return array
	 */
	public static function apply( $kind, $body, $settings ) {
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Store::in_calendar_transaction() && self::authorize( $kind, $body ), 'forbidden' );
		CSF_Scheduling_Calendar::keys( $body, array( 'uuid', 'expected', 'data', 'impact_acknowledged' ) );
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Audit::uuid( $body['uuid'] ) && is_int( $body['expected'] ) && true === $body['impact_acknowledged'], 'resource_hours_invalid' );
		$p = json_decode( $settings['policy_json'], true );
		CSF_Scheduling_Calendar::require( is_string( $p['timezone'] ), 'policy_incomplete' );
		$d      = $body['data'];
		$impact = 0;
		if ( 'resource_hours_saved' === $kind ) {
			self::validate( $d, $p );
			$table          = 'availability';
			$old            = CSF_Scheduling_Calendar::find( $table, $body['uuid'] );
			$before         = $old ? json_decode( $old['body_json'], true ) : null;
			$next           = $before ?? array(
				'resource_uuid' => $body['uuid'],
				'intervals'     => array(),
			);
			$next['weekly'] = $d;
			$entity         = $body['uuid'];
			$impact         = self::impact( $entity, $next, $p, true );
		} elseif ( 'resource_closure_added' === $kind ) {
			CSF_Scheduling_Calendar::keys( $d, array( 'target_uuid', 'start', 'end', 'category', 'note', 'repeat_until' ) );
			CSF_Scheduling_Calendar::require( 0 === $body['expected'] && ! CSF_Scheduling_Calendar::find( 'restrictions', $body['uuid'] ) && in_array( $d['category'], array( 'unavailable', 'protected_training' ), true ) && CSF_Scheduling_Catalog::text( $d['note'], 200 ), 'resource_hours_invalid' );
			$a = CSF_Scheduling_Time::parse( $d['start'], $p['timezone'] );
			$z = CSF_Scheduling_Time::parse( $d['end'], $p['timezone'] );
			CSF_Scheduling_Calendar::require( $z > $a && $z->getTimestamp() - $a->getTimestamp() <= 31622400 && 0 === (int) $a->format( 'i' ) % 15 && 0 === (int) $z->format( 'i' ) % 15, 'resource_hours_invalid' );
			$next = array(
				'target_uuid'  => $d['target_uuid'],
				'start'        => $d['start'],
				'end'          => $d['end'],
				'reason'       => $d['category'],
				'note'         => $d['note'],
				'closure_kind' => 'resource_closure',
				'recurrence'   => null,
				'reopen_dates' => array(),
			);
			if ( null !== $d['repeat_until'] ) {
				$full = '00:00' === $a->format( 'H:i' ) && '00:00' === $z->format( 'H:i' ) && $z->format( 'Y-m-d' ) === $a->modify( '+1 day' )->format( 'Y-m-d' );
				CSF_Scheduling_Calendar::require( self::date( $d['repeat_until'] ) && $d['repeat_until'] >= $a->format( 'Y-m-d' ) && $d['repeat_until'] <= $a->modify( '+366 days' )->format( 'Y-m-d' ) && ( $full || $a->format( 'Y-m-d' ) === $z->format( 'Y-m-d' ) ), 'resource_hours_invalid' );
				$next['recurrence'] = array(
					'timezone' => $p['timezone'],
					'from'     => $a->format( 'Y-m-d' ),
					'until'    => $d['repeat_until'],
					'weekday'  => (int) $a->format( 'w' ),
					'start'    => $a->format( 'H:i' ),
					'end'      => $full ? '24:00' : $z->format( 'H:i' ),
				);
			}
			$table  = 'restrictions';
			$before = null;
			$entity = $d['target_uuid'];
			$impact = self::impact( $entity, $next, $p, false );
		} else {
			CSF_Scheduling_Calendar::keys( $d, array( 'note', 'date' ) );
			CSF_Scheduling_Calendar::require( CSF_Scheduling_Catalog::text( $d['note'], 200 ), 'resource_hours_invalid' );
			$table  = 'restrictions';
			$old    = CSF_Scheduling_Calendar::find( $table, $body['uuid'] );
			$before = json_decode( $old['body_json'], true );
			CSF_Scheduling_Calendar::require( empty( $before['released'] ), 'invalid_transition' );
			$next = $before;
			if ( null === $d['date'] ) {
				$next['released']       = true;
				$next['release_reason'] = $d['note']; } else {
				$r = $before['recurrence'] ?? null;
				CSF_Scheduling_Calendar::require( $r && self::date( $d['date'] ) && $d['date'] >= $r['from'] && $d['date'] <= $r['until'] && (int) ( new DateTimeImmutable( $d['date'] ) )->format( 'w' ) === $r['weekday'] && ! in_array( $d['date'], $before['reopen_dates'] ?? array(), true ), 'resource_hours_invalid' );
				$next['reopen_dates'][] = $d['date'];
				}
				$entity = $old['entity_uuid'];
		}
		$revision = CSF_Scheduling_Calendar::document( $table, $body['uuid'], $entity, $next, $body['expected'] );
		CSF_Scheduling_Calendar::document(
			'history',
			wp_generate_uuid4(),
			$body['uuid'],
			array(
				'kind'                           => $kind,
				'before'                         => $before,
				'after'                          => $next,
				'reviewed_affected_appointments' => $impact,
			),
			0
		);
		return array(
			'uuid'                  => $body['uuid'],
			'entity_revision'       => $revision,
			'affected_appointments' => $impact,
		);
	}
}

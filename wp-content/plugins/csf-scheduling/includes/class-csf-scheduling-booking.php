<?php
/** Routine booking is normalized into the existing appointment command.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Quarter-hour choices and elapsed-duration arithmetic; no alternative write service. */
class CSF_Scheduling_Booking {
	const ERRORS = array( 'lesson_service', 'lesson_grid', 'lesson_rider', 'lesson_horse', 'lesson_resource', 'lesson_start', 'hold_expiry', 'booking_busy' );

	/** Whether a stored appointment has one complete horse turn (or is new).
	 *
	 * @param array $b Appointment or unsent draft.
	 * @return bool
	 */
	public static function simple( $b ) {
		if ( ! empty( $b['_routine'] ) || ( empty( $b['expected'] ) && empty( $b['riders'] ) && empty( $b['rides'] ) ) ) {
			return true;
		}
		return 1 === count( $b['riders'] ?? array() ) && ( empty( $b['rides'] ) || ( 1 === count( $b['rides'] ) && $b['rides'][0]['rider'] === $b['riders'][0] && $b['rides'][0]['start'] === $b['start'] && $b['rides'][0]['end'] === $b['end'] ) );
	}

	/** Calculate the finish as elapsed minutes, including DST transitions.
	 *
	 * @param array $b Routine wire draft.
	 * @param array $p Current validated policy.
	 * @return array Existing canonical appointment shape.
	 * @throws RuntimeException Invalid selection or local time.
	 */
	public static function derive( $b, $p ) {
		$s = $p['services'][ $b['service_type'] ] ?? null;
		if ( ! $s || ! $s['enabled'] || ! is_int( $s['duration_minutes'] ) || $s['duration_minutes'] < 15 || 0 !== $s['duration_minutes'] % 15 ) {
			throw new RuntimeException( 'lesson_service' );
		}
		if ( ! is_string( $p['timezone'] ) ) {
			throw new RuntimeException( 'policy_incomplete' );
		}
		if ( empty( $b['start'] ) || ( is_array( $b['start'] ) && empty( $b['start']['local'] ) ) ) {
			throw new RuntimeException( 'lesson_start' );
		}
		$iso = CSF_Scheduling_Time::resolve( $b['start'], $p['timezone'] );
		$a   = CSF_Scheduling_Time::parse( $iso, $p['timezone'] );
		if ( 0 !== (int) $a->format( 'i' ) % 15 ) {
			throw new RuntimeException( 'lesson_grid' );
		}
		$z     = $a->setTimestamp( $a->getTimestamp() + 60 * $s['duration_minutes'] );
		$rider = $b['single_rider'] ?? null;
		$horse = $b['single_horse'] ?? null;
		if ( ! CSF_Scheduling_Audit::uuid( $rider ) ) {
			throw new RuntimeException( 'lesson_rider' );
		}
		if ( 'none' !== $s['horse_source'] && ! CSF_Scheduling_Audit::uuid( $horse ) ) {
			throw new RuntimeException( 'lesson_horse' );
		}
		if ( 'held' === $b['state'] && empty( $b['hold_expires'] ) ) {
			throw new RuntimeException( 'hold_expiry' );
		}
		$b['start']  = $iso;
		$b['end']    = $z->format( 'Y-m-d\TH:iP' );
		$b['riders'] = array( $rider );
		$b['rides']  = 'none' === $s['horse_source'] ? array() : array(
			array(
				'horse' => $horse,
				'rider' => $rider,
				'start' => $b['start'],
				'end'   => $b['end'],
			),
		);
		if ( 'held' !== $b['state'] ) {
			$b['hold_expires'] = null;
		}
		unset( $b['_routine'], $b['single_rider'], $b['single_horse'] );
		return $b;
	}

	/** Bounded advisory slot scan; every candidate uses the existing locked preview.
	 *
	 * @param array  $draft Routine wire shape.
	 * @param string $day Local date.
	 * @param int    $revision Facility revision.
	 * @param int    $offset Quarter-hour cursor; eight starts per bounded page.
	 * @return array
	 * @throws RuntimeException Invalid date/draft or changed review.
	 */
	public static function slots( $draft, $day, $revision, $offset = 0 ) {
		if ( ! is_int( $offset ) || $offset < 0 || $offset > 95 || ! CSF_Scheduling_Access::writer() || ! CSF_Scheduling_UI::editable( 'appointment_saved', $draft ) || empty( $draft['_routine'] ) || ! is_string( $day ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $day ) ) {
			throw new RuntimeException( 'invalid_body' );
		}
		$row = CSF_Scheduling_Schema::settings();
		if ( (int) $row['revision'] !== $revision ) {
			throw new RuntimeException( 'stale_revision' );
		}
		$p = json_decode( $row['policy_json'], true );
		if ( CSF_Scheduling_Policy::missing( $p ) ) {
			throw new RuntimeException( 'policy_incomplete' );
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, new DateTimeZone( 'UTC' ) );
		if ( ! $date || $date->format( 'Y-m-d' ) !== $day ) {
			throw new RuntimeException( 'invalid_time' );
		}
		$draft['title'] = '' === trim( $draft['title'] ) ? 'Availability check' : $draft['title'];
		$deadline       = microtime( true ) + 15;
		$out            = array();
		$processed      = 0;
		$next           = null;
		foreach ( range( $offset, 95 ) as $n ) {
			if ( microtime( true ) > $deadline ) {
				throw new RuntimeException( 'booking_busy' );
			}
			$local    = $day . 'T' . sprintf( '%02d:%02d', intdiv( $n, 4 ), 15 * ( $n % 4 ) );
			$clock    = substr( $local, -5 );
			$in_hours = false;
			foreach ( $p['service_hours'][ (int) $date->format( 'w' ) ] as $range ) {
				$in_hours = $in_hours || ( $clock >= $range[0] && $clock < $range[1] );
			}
			if ( ! $in_hours ) {
				continue;
			}
			if ( $processed >= 8 ) {
				$next = $n;
				break;
			}
			++$processed;
			$choices = CSF_Scheduling_Time::choices( $local, $p['timezone'] );
			if ( ! $choices ) {
				$out[] = array(
					'local'     => $local,
					'choice'    => '',
					'label'     => $clock,
					'available' => false,
					'reason'    => 'This time does not exist when clocks go forward.',
					'end'       => '',
				);
			}
			foreach ( $choices as $index => $iso ) {
				$wire          = $draft;
				$wire['start'] = array(
					'local'  => $local,
					'choice' => $iso,
				);
				$b             = CSF_Scheduling_UI::normalize( 'appointment_saved', $wire );
				$result        = CSF_Scheduling_Store::preview( $b, $revision, wp_generate_uuid4(), true );
				$label         = $clock . ( count( $choices ) > 1 ? ( 0 === $index ? ' — first occurrence' : ' — second occurrence' ) . ' (' . substr( $iso, -6 ) . ')' : '' );
				$out[]         = array(
					'local'     => $local,
					'choice'    => $iso,
					'label'     => $label,
					'available' => ! is_wp_error( $result ),
					'reason'    => is_wp_error( $result ) ? CSF_Scheduling_UI::message( $result ) : '',
					'end'       => $b['end'],
				);
			}
		}
		if ( (int) CSF_Scheduling_Schema::settings()['revision'] !== $revision ) {
			throw new RuntimeException( 'stale_revision' );
		}
		return array(
			'slots' => $out,
			'next'  => $next,
		);
	}
}

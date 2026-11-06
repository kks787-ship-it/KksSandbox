<?php
/** Authorized visual presentation of the existing calendar read model.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** No booking writes, alternate authorization or inferred recurring resource hours. */
class CSF_Scheduling_Planner {
	/** Enumerate real quarter-hour instants; DST days contain 92 or 100 slots.
	 *
	 * @param string $day Local date.
	 * @param string $zone Facility timezone.
	 * @return array
	 * @throws RuntimeException Invalid date.
	 */
	public static function ticks( $day, $zone ) {
		$a = DateTimeImmutable::createFromFormat( '!Y-m-d', $day, new DateTimeZone( $zone ) );
		if ( ! $a || $a->format( 'Y-m-d' ) !== $day ) {
			throw new RuntimeException( 'invalid_time' );
		}
		$end = $a->modify( '+1 day' )->getTimestamp();
		$out = array();
		if ( $end - $a->getTimestamp() > 90000 || $end <= $a->getTimestamp() ) {
			throw new RuntimeException( 'invalid_time' );
		}
		for ( $stamp = $a->getTimestamp(); $stamp < $end; $stamp += 900 ) {
			$d     = $a->setTimestamp( $stamp );
			$out[] = array(
				'stamp' => $stamp,
				'iso'   => $d->format( 'Y-m-d\TH:iP' ),
				'local' => $d->format( 'Y-m-d\TH:i' ),
				'label' => $d->format( 'g:i a T' ),
				'clock' => $d->format( 'H:i' ),
			);
		}
		return $out;
	}

	/** Read-only navigation preserves the resource filter.
	 *
	 * @param string $date Date.
	 * @param string $view Day/week.
	 * @param string $filter Resource UUID.
	 * @return string
	 */
	public static function url( $date, $view, $filter ) {
		return add_query_arg(
			array(
				'page'     => 'csf-scheduling',
				'date'     => $date,
				'view'     => $view,
				'resource' => $filter,
			),
			admin_url( 'admin.php' )
		);
	}

	/** Lane-specific candidate status; only preview can establish bookability.
	 *
	 * @param array  $tick Real quarter hour.
	 * @param int    $weekday Local weekday.
	 * @param string $lane_resource Lane UUID.
	 * @param array  $p Policy.
	 * @param array  $availability Authorized availability documents keyed by resource.
	 * @param array  $restrictions Authorized protective blocks.
	 * @param array  $events Visible lane appointments and actual travel envelopes.
	 * @return string Empty means candidate only.
	 */
	public static function status( $tick, $weekday, $lane_resource, $p, $availability, $restrictions, $events ) {
		$open      = false;
		$finish    = $tick['stamp'] + 900;
		$clock_end = ( new DateTimeImmutable( $tick['iso'] ) )->modify( '+15 minutes' )->format( 'H:i' );
		foreach ( $p['service_hours'][ $weekday ] ?? array() as $hours ) {
			$open = $open || ( $tick['clock'] >= $hours[0] && $tick['clock'] < $hours[1] && ( $clock_end <= $hours[1] || '00:00' === $clock_end ) );
		}
		if ( ! $open ) {
			return 'Facility closed';
		}
		if ( $lane_resource && ! ( array_key_exists( $lane_resource, $availability ) && null === $availability[ $lane_resource ] ) ) {
			$available = false;
			foreach ( $availability[ $lane_resource ] ?? array() as $interval ) {
				$available = $available || ( strtotime( $interval['start'] ) <= $tick['stamp'] && strtotime( $interval['end'] ) >= $finish );
			}
			if ( ! $available ) {
				return 'Resource unavailable';
			}
		}
		foreach ( $restrictions as $restriction ) {
			$d = $restriction['data'];
			if ( $lane_resource && $restriction['entity_uuid'] === $lane_resource && empty( $d['released'] ) && strtotime( $d['start'] ) < $finish && strtotime( $d['end'] ) > $tick['stamp'] ) {
				return 'Protective block: ' . str_replace( '_', ' ', $d['reason'] );
			}
		}
		foreach ( $events as $event ) {
			if ( $event['reserved'] && $event['left'] < $finish && $event['right'] > $tick['stamp'] ) {
				return 'Reserved';
			}
		}
		return '';
	}

	/** Render a grid from already scoped appointments and existing absolute availability.
	 *
	 * @param string $date First local day.
	 * @param string $view Day/week.
	 * @param string $filter Selected resource.
	 * @param array  $rows Authorized appointment read model.
	 * @param array  $details Authorized appointment details, keyed by UUID.
	 * @param array  $ctx Existing authorized UI choices.
	 * @param array  $p Reviewed facility policy.
	 */
	public static function render( $date, $view, $filter, $rows, $details, $ctx, $p ) {
		if ( ! CSF_Scheduling_Access::can() || is_wp_error( $rows ) || ! is_string( $p['timezone'] ) ) {
			return;
		}
		try {
			self::ticks( $date, $p['timezone'] );
		} catch ( Throwable $error ) {
			return;
		}
		$first        = new DateTimeImmutable( $date, new DateTimeZone( $p['timezone'] ) );
		$days         = 'week' === $view ? 7 : 1;
		$today        = wp_date( 'Y-m-d', null, new DateTimeZone( $p['timezone'] ) );
		$can_create   = CSF_Scheduling_Access::can( 'appointment_edit' );
		$lanes        = array();
		$availability = array();
		foreach ( CSF_Scheduling_Calendar::documents( 'availability' ) as $doc ) {
			$availability[ $doc['entity_uuid'] ] = isset( $doc['data']['weekly'] ) ? CSF_Scheduling_Resource_Hours::project( $doc['data'], $date, $days, $p ) : $doc['data']['intervals'];
		}
		$restrictions = CSF_Scheduling_Calendar::documents( 'restrictions' );
		$expanded     = array();
		foreach ( $restrictions as $restriction ) {
			if ( 'resource_closure' !== ( $restriction['data']['closure_kind'] ?? null ) ) {
				$expanded[] = $restriction;
				continue; }
			foreach ( CSF_Scheduling_Resource_Hours::closure_windows( $restriction['data'], $first, $first->modify( '+' . $days . ' days' ) ) as $window ) {
				$copy                  = $restriction;
				$copy['data']['start'] = $window['start'];
				$copy['data']['end']   = $window['end'];
				$expanded[]            = $copy; }
		}
		$restrictions = $expanded;
		$manageable   = array();
		if ( CSF_Scheduling_Access::can( 'availability_manage' ) && CSF_Scheduling_Access::can( 'resource_view' ) ) {
			foreach ( $ctx['resource_rows'] as $r ) {
				if ( in_array( $r['kind'], array( 'arena', 'instructor', 'location' ), true ) ) {
					$manageable[] = $r['uuid']; }
			}
		}
		foreach ( $ctx['resource_rows'] as $r ) {
			if ( ! (int) $r['active'] ) {
				$availability[ $r['uuid'] ] = array();
			}
		}
		foreach ( $ctx['horse_rows'] as $h ) {
			if ( ! $h['data']['resource_uuid'] ) {
				$availability[ $h['uuid'] ] = null; // Client horse: existing engine uses suitability/welfare, not a school-resource interval.
			}
		}
		if ( $filter ) {
			$lanes[ $filter ] = $ctx['resources'][ $filter ] ?? $ctx['horses'][ $filter ] ?? 'Selected resource';
		} elseif ( 1 === $days ) {
			foreach ( $ctx['resource_rows'] as $r ) {
				if ( 'instructor' === $r['kind'] && (int) $r['active'] && count( $lanes ) < 6 ) {
					$lanes[ $r['uuid'] ] = $r['label'];
				}
			}
		}
		if ( ! $lanes ) {
			$lanes[''] = 'All resources';
		} elseif ( ! $filter ) {
			foreach ( $rows as $row ) {
				if ( ! array_intersect( array_keys( $lanes ), $details[ $row['uuid'] ]['resources'] ) ) {
					$lanes[''] = 'Other resources';
				}
			}
		}
		$dst_week = false;
		for ( $i = 0; $i < $days; ++$i ) {
			$dst_week = $dst_week || 96 !== count( self::ticks( $first->modify( '+' . $i . ' days' )->format( 'Y-m-d' ), $p['timezone'] ) );
		}
		echo '<section id="csf-visual-planner" class="' . ( 7 === $days ? 'csf-planner-week' : 'csf-planner-day' ) . ( $dst_week ? ' csf-planner-dst' : '' ) . '" aria-labelledby="csf-planner-heading"><h2 id="csf-planner-heading">' . esc_html( 'week' === $view ? 'Week planner' : 'Day planner' ) . '</h2><p>15-minute blocks · ' . esc_html( $p['timezone'] ) . '. Open blocks are candidates; check the lesson, horse and resources before saving. Last refreshed: ' . esc_html( wp_date( 'g:i a T', null, new DateTimeZone( $p['timezone'] ) ) ) . '.</p><nav aria-label="Planner dates">';
		foreach ( array(
			'Previous' => $first->modify( '-' . $days . ' days' )->format( 'Y-m-d' ),
			'Today'    => $today,
			'Next'     => $first->modify( '+' . $days . ' days' )->format( 'Y-m-d' ),
		) as $label => $day ) {
			echo '<a class="button" href="' . esc_url( self::url( $day, $view, $filter ) ) . '">' . esc_html( $label ) . '</a> ';
		}
		echo '<a class="button" href="' . esc_url( self::url( $date, 'day', $filter ) ) . '">Day</a> <a class="button" href="' . esc_url( self::url( $date, 'week', $filter ) ) . '">Week</a> <a class="button" href="' . esc_url( self::url( $date, $view, $filter ) ) . '">Refresh</a></nav><p class="csf-planner-legend">Confirmed · Held · Requested (not reserved) · Canceled (history) · Closed/unavailable. Shaded travel is reserved only where configured. No additional preparation/wrap-up time is reserved.</p>';
		if ( $dst_week ) {
			echo '<p>Clock change in this view: each day has its own offset-labeled time ruler. Scroll sideways for later days if needed.</p>';
		}
		if ( 7 === $days ) {
			echo '<label class="csf-mobile-day">Day on small screens <select class="csf-planner-day-choice">';
			for ( $i = 0; $i < 7; ++$i ) {
				$d = $first->modify( '+' . $i . ' days' );
				echo '<option value="' . esc_attr( $d->format( 'Y-m-d' ) ) . '"' . selected( $d->format( 'Y-m-d' ), $today >= $date && $today <= $first->modify( '+6 days' )->format( 'Y-m-d' ) ? $today : $date, false ) . '>' . esc_html( $d->format( 'D M j' ) ) . '</option>';
			}
			echo '</select></label>';
		}
		if ( count( $lanes ) > 1 ) {
			echo '<label class="csf-mobile-day">Resource lane on small screens <select class="csf-planner-lane-choice">';
			foreach ( $lanes as $id => $label ) {
				echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</option>';
			}
			echo '</select></label>';
		}
		if ( $manageable ) {
			echo '<button type="button" class="button csf-resource-mode" aria-pressed="false">Manage resource availability</button><label><input type="checkbox" class="csf-resource-range"> Two-tap range selection</label><p class="csf-resource-mode-status" role="status">Use a specific instructor/arena lane or calendar resource filter. Booking mode is unchanged until you enable resource selection.</p>'; }
		echo '<div class="csf-planner-scroll" tabindex="0" role="region" aria-label="Scrollable visual schedule"><div class="csf-planner-columns" style="--columns:' . esc_attr( $days * count( $lanes ) ) . '">';
		for ( $day_index = 0; $day_index < $days; ++$day_index ) {
			$day   = $first->modify( '+' . $day_index . ' days' );
			$ticks = self::ticks( $day->format( 'Y-m-d' ), $p['timezone'] );
			foreach ( $lanes as $resource => $label ) {
				$events = array();
				foreach ( $rows as $row ) {
					$d       = $details[ $row['uuid'] ];
					$ids     = array_merge( $d['resources'], array_column( $d['rides'], 'horse' ) );
					$in_lane = $resource ? in_array( $resource, $ids, true ) : ( $filter || 1 === count( $lanes ) || ! array_intersect( array_keys( $lanes ), $d['resources'] ) );
					if ( ! $in_lane ) {
						continue;
					}
					$a      = strtotime( $row['start'] );
					$z      = strtotime( $row['end'] );
					$active = 'confirmed' === $row['state'] || ( 'held' === $row['state'] && ! $row['hold_expired'] );
					$travel = $active && ! empty( $p['services'][ $d['service_type'] ]['requires_travel'] ) ? 60 * (int) $p['travel_buffer_minutes'] : 0;
					if ( $a - $travel < end( $ticks )['stamp'] + 900 && $z + $travel > $ticks[0]['stamp'] ) {
						$events[] = array(
							'row'      => $row,
							'left'     => $a - $travel,
							'right'    => $z + $travel,
							'start'    => $a,
							'end'      => $z,
							'reserved' => $active,
						);
					}
				}
				// Explicit subcolumns prevent any overlapping visible blocks from covering each other.
				$ends = array();
				foreach ( $events as &$event ) {
					$column = 0;
					while ( isset( $ends[ $column ] ) && $ends[ $column ] > $event['left'] ) {
						++$column;
					}
					$event['column'] = $column;
					$ends[ $column ] = $event['right'];
				}
				unset( $event );
				$visual_columns = max( 1, count( $ends ) );
				$mapped         = $resource;
				foreach ( $ctx['horse_rows'] as $horse ) {
					if ( $horse['uuid'] === $resource && $horse['data']['resource_uuid'] ) {
						$mapped = $horse['data']['resource_uuid'];
					}
				}
				echo '<div class="csf-planner-column" data-lane="' . esc_attr( $resource ) . '" data-day="' . esc_attr( $day->format( 'Y-m-d' ) ) . '"><h3>' . esc_html( $day->format( 'D M j' ) . ( $today === $day->format( 'Y-m-d' ) ? ' · Today' : '' ) ) . '<small>' . esc_html( $label ) . '</small></h3><div class="csf-planner-track" style="--ticks:' . esc_attr( count( $ticks ) ) . '">';
				$lane_blocks = array_map(
					static function ( $r ) use ( $resource, $mapped ) {
						if ( $mapped !== $resource && $r['entity_uuid'] === $resource ) {
							$r['entity_uuid'] = $mapped;
						}
						return $r;
					},
					$restrictions
				);
				foreach ( $ticks as $index => $tick ) {

					$status = self::status( $tick, (int) $day->format( 'w' ), $mapped, $p, $availability, $lane_blocks, $events );

					echo '<div class="csf-planner-tick" style="--index:' . esc_attr( $index ) . '"><span>' . esc_html( $tick['label'] ) . '</span><button type="button" class="csf-planner-slot ' . ( $status ? 'csf-slot-closed' : 'csf-slot-candidate' ) . '" data-local="' . esc_attr( $tick['local'] ) . '" data-choice="' . esc_attr( $tick['iso'] ) . '" data-resource="' . esc_attr( $resource ) . '" aria-label="' . esc_attr( $day->format( 'D M j' ) . ', ' . $tick['label'] . ', ' . $label . ': ' . ( $status ? $status : 'Open candidate; create a lesson draft' ) ) . '"' . ( $status || ! $can_create ? ' disabled' : '' ) . '>' . esc_html( $status ? $status : '+' ) . '</button>';
					if ( in_array( $resource, $manageable, true ) ) {
						$ids   = array();
						$start = new DateTimeImmutable( $tick['iso'] );
						$end   = $start->setTimestamp( $start->getTimestamp() + 900 );
						foreach ( $lane_blocks as $block ) {
							if ( $block['entity_uuid'] === $resource && 'resource_closure' === ( $block['data']['closure_kind'] ?? null ) && empty( $block['data']['released'] ) && strtotime( $block['data']['start'] ) < $end->getTimestamp() && strtotime( $block['data']['end'] ) > $start->getTimestamp() ) {
								$ids[] = $block['uuid']; }
						}
						echo '<button type="button" class="csf-resource-slot" data-resource="' . esc_attr( $resource ) . '" data-start="' . esc_attr( $tick['iso'] ) . '" data-end="' . esc_attr( $end->format( 'Y-m-d\TH:iP' ) ) . '" data-closures="' . esc_attr( implode( ',', array_unique( $ids ) ) ) . '" aria-label="' . esc_attr( 'Manage ' . $label . ', ' . $day->format( 'D M j' ) . ' ' . $tick['label'] . ( $status ? ': ' . $status : ': candidate' ) ) . '">' . esc_html( $ids ? 'Review / reopen closure' : ( $status ? 'Close / review: ' . $status : 'Select closure period' ) ) . '</button>';
					}
					echo '</div>';
				}
				foreach ( $events as $event ) {
					$row   = $event['row'];
					$style = '--top:' . max( 0, ( $event['start'] - $ticks[0]['stamp'] ) / 900 ) . ';--length:' . max( 1, ( min( $event['end'], end( $ticks )['stamp'] + 900 ) - max( $event['start'], $ticks[0]['stamp'] ) ) / 900 ) . ';--lane:' . $event['column'] . ';--lanes:' . $visual_columns;
					if ( $event['left'] < $event['start'] || $event['right'] > $event['end'] ) {
						echo '<div class="csf-planner-travel" style="--top:' . esc_attr( max( 0, ( $event['left'] - $ticks[0]['stamp'] ) / 900 ) ) . ';--length:' . esc_attr( ( min( $event['right'], end( $ticks )['stamp'] + 900 ) - max( $event['left'], $ticks[0]['stamp'] ) ) / 900 ) . '">Travel reserved</div>';
					}
					$state = $row['state'] . ( $row['hold_expired'] ? ' · expired, not reserved' : ( 'requested' === $row['state'] ? ' · not reserved' : '' ) );
					echo '<button type="button" class="csf-planner-event csf-event-' . esc_attr( $row['state'] ) . ( $row['hold_expired'] ? ' csf-event-expired' : '' ) . '" style="' . esc_attr( $style ) . '" data-event="' . esc_attr( $row['uuid'] ) . '" aria-haspopup="dialog"><strong>' . esc_html( $row['title'] ) . '</strong><span>' . esc_html( $state . ' · ' . wp_date( 'g:i a T', $event['start'], new DateTimeZone( $p['timezone'] ) ) . '–' . wp_date( 'g:i a T', $event['end'], new DateTimeZone( $p['timezone'] ) ) ) . '</span><span>' . esc_html( implode( ', ', array_map( static fn( $id ) => $ctx['resources'][ $id ] ?? 'Resource details restricted', $details[ $row['uuid'] ]['resources'] ) ) ) . '</span>' . ( ! $row['rider_cleared'] ? '<span>⚠ Rider clearance incomplete</span>' : '' ) . ( $row['welfare_review'] ? '<span>⚠ Welfare review required</span>' : '' ) . '</button>';
				}
				echo '</div></div>';
			}
		}
		echo '</div></div><p class="csf-planner-feedback" role="status" aria-live="polite"></p><dialog id="csf-planner-dialog" aria-label="Appointment details"><button type="button" class="button csf-planner-close">Close appointment details</button><div class="csf-planner-dialog-body"></div></dialog><noscript>Use Appointment list and details below to review or edit a lesson.</noscript></section>';
	}
}

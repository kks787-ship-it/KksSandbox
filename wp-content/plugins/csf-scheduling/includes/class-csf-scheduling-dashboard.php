<?php
/** Private weekly read model. No frontend route, shared cache or booking writes.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
/** Coordinate this private scheduling operation through existing access and transaction boundaries. */
class CSF_Scheduling_Dashboard {
	/** Facility-local Monday anchor, including weeks crossing DST/year boundaries.
	 *
	 * @param DateTimeImmutable|null $now Reference instant, or the current time.
	 * @param string                 $timezone Facility IANA timezone.
	 */
	public static function monday( $now, $timezone ) {
		$local = $now->setTimezone( new DateTimeZone( $timezone ) );
		return $local->modify( '-' . ( (int) $local->format( 'N' ) - 1 ) . ' days' )->format( 'Y-m-d' );
	}
	/** Existing calendar reader is the sole appointment authority.
	 *
	 * @param DateTimeImmutable|null $now Reference instant, or the current time.
	 */
	public static function week( $now = null ) {
		if ( ! CSF_Scheduling_Access::can() ) {
			return new WP_Error( 'forbidden', 'Private calendar access required.' ); }
		$s = CSF_Scheduling_Schema::settings();
		$p = json_decode( $s['policy_json'], true );
		if ( ! is_string( $p['timezone'] ?? null ) || ! in_array( $p['timezone'], timezone_identifiers_list(), true ) ) {
			return new WP_Error( 'policy_incomplete', 'Set the facility timezone.' ); }
		$now    = $now ?? new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
		$monday = self::monday( $now, $p['timezone'] );
		$rows   = CSF_Scheduling_Calendar::view( $monday, 'week' );
		if ( is_wp_error( $rows ) ) {
			return $rows; }
		$days  = array();
		$first = new DateTimeImmutable( $monday, new DateTimeZone( $p['timezone'] ) );
		foreach ( range( 0, 6 ) as $day ) {
			$date          = $first->modify( '+' . $day . ' days' )->format( 'Y-m-d' );
			$days[ $date ] = array(); }
		foreach ( $rows as $row ) {
			$day = ( new DateTimeImmutable( $row['start'] ) )->setTimezone( new DateTimeZone( $p['timezone'] ) )->format( 'Y-m-d' );
			if ( ! isset( $days[ $day ] ) ) {
				continue; }
			$key                    = $row['visit_uuid'] ?? $row['uuid'];
			$days[ $day ][ $key ][] = $row;
		}
		return array(
			'timezone' => $p['timezone'],
			'monday'   => $monday,
			'today'    => $now->setTimezone( new DateTimeZone( $p['timezone'] ) )->format( 'Y-m-d' ),
			'days'     => $days,
		);
	}
	/** Styles are confined to the private dashboard, with no public enqueue.
	 *
	 * @param string $hook Current WordPress admin page hook.
	 */
	public static function assets( $hook ) {
		if ( 'index.php' === $hook && CSF_Scheduling_Access::can() ) {
			wp_enqueue_style( 'csf-scheduling-dashboard', plugins_url( '../assets/staff.css', __FILE__ ), array(), CSF_SCHEDULING_VERSION ); }
	}

	/** Widget registration is itself permission checked; render rechecks again. */
	public static function register() {
		if ( CSF_Scheduling_Access::can() ) {
			wp_add_dashboard_widget( 'csf_this_week', "THIS WEEK'S SCHEDULE", array( __CLASS__, 'render' ) ); }
	}
	/** Same private summary on the native dashboard and Digital Ranch landing view. */
	public static function render() {
		$week = self::week();
		if ( is_wp_error( $week ) ) {
			return; }
		nocache_headers();
		CSF_Scheduling_Notifications::render();
		$ctx  = CSF_Scheduling_UI::context();
		$zone = new DateTimeZone( $week['timezone'] );
		echo '<section class="csf-week-summary" aria-label="This week’s schedule"><h2>THIS WEEK\'S SCHEDULE</h2><p>' . esc_html( $week['timezone'] . ' · teaching times and actual saved resource reservations' ) . '</p><div class="csf-week-days">';
		foreach ( $week['days'] as $day => $groups ) {
			$today = $day === $week['today'];
			echo '<section' . ( $today ? ' aria-label="Today" class="csf-week-today"' : '' ) . '><h3><a href="' . esc_url(
				CSF_Scheduling_UX::url(
					'calendar',
					array(
						'date' => $day,
						'view' => 'day',
					)
				)
			) . '">' . esc_html( ( new DateTimeImmutable( $day, $zone ) )->format( 'D M j' ) . ( $today ? ' · TODAY' : '' ) ) . '</a></h3>';
			if ( ! $groups ) {
				echo '<p>No visible lessons.</p>'; }
			foreach ( $groups as $rows ) {
				echo '<div class="csf-week-visit">';
				if ( ! empty( $rows[0]['visit_uuid'] ) ) {
					echo '<p><strong>Family visit</strong> · individually itemized lessons</p>'; }
				echo '<ul>';
				foreach ( $rows as $row ) {
					// The scoped overview supplies identity; detail access is independently checked.
					$stored = CSF_Scheduling_Calendar::find( 'details', $row['uuid'] );
					if ( ! $stored ) {
						continue; }
					$d      = json_decode( $stored['body_json'], true );
					$labels = array();
					foreach ( $d['resources'] as $id ) {
						$labels[] = $ctx['resources'][ $id ] ?? 'Assigned resource'; }
					foreach ( $d['riders'] as $id ) {
						if ( isset( $ctx['riders'][ $id ] ) ) {
							$labels[] = $ctx['riders'][ $id ]; }
					}
					foreach ( $d['rides'] as $ride ) {
						if ( isset( $ctx['horses'][ $ride['horse'] ] ) ) {
							$labels[] = $ctx['horses'][ $ride['horse'] ]; }
					}
					$url = CSF_Scheduling_UX::url(
						'calendar',
						array(
							'date' => $day,
							'view' => 'day',
						)
					) . '#csf-event-' . $row['uuid'];
					echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $row['title'] ) . '</a> <strong>' . esc_html( strtoupper( $row['state'] ) . ( $row['hold_expired'] ? ' · EXPIRED, NOT RESERVED' : ( 'requested' === $row['state'] ? ' · NOT RESERVED' : '' ) ) ) . '</strong><br>' . esc_html( wp_date( 'H:i P', strtotime( $row['start'] ), $zone ) . '–' . wp_date( 'H:i P', strtotime( $row['end'] ), $zone ) . ' · ' . implode( ', ', array_unique( $labels ) ) );
					foreach ( $row['resource_reservations'] ?? array() as $window ) {
						echo '<br><small>' . esc_html( ( 'canceled' === $row['state'] ? 'Previous window: ' : ( 'confirmed' === $row['state'] || ( 'held' === $row['state'] && ! $row['hold_expired'] ) ? 'Reserved window: ' : 'Planned, not reserved: ' ) ) . ( $ctx['resources'][ $window['resource_uuid'] ] ?? 'Assigned resource' ) . ' ' . wp_date( 'H:i P', $window['start'], $zone ) . '–' . wp_date( 'H:i P', $window['end'], $zone ) . ' · preparation ' . $window['pre_minutes'] . ' min / wrap-up ' . $window['post_minutes'] . ' min' ) . '</small>';
					}
					CSF_Scheduling_Buffer_UI::marker( $row['buffer_overrides'] ?? array(), $ctx );
					if ( ! $row['rider_cleared'] ) {
						echo '<br><strong>⚠ PRE-RIDE BLOCK</strong>'; }
					if ( $row['welfare_review'] ) {
						echo '<br><strong>⚠ WELFARE REVIEW</strong>'; }
					echo '</li>';
				}
				echo '</ul></div>';
			}
			echo '</section>';
		}
		echo '</div></section>';
	}
}

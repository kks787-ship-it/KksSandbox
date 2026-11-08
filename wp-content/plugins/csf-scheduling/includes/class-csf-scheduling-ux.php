<?php
/** Calendar-first presentation over the existing authorized command services.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
/** Navigation and compact presentation only; no independent writer. */
class CSF_Scheduling_UX {
	/** Allowlisted read-only screen.
	 *
	 * @return string
	 */
	public static function screen() {
		$screen = isset( $_GET['screen'] ) && is_string( $_GET['screen'] ) ? sanitize_key( wp_unslash( $_GET['screen'] ) ) : 'calendar'; // phpcs:ignore WordPress.Security.NonceVerification -- Read-only navigation.
		return in_array( $screen, array( 'calendar', 'family', 'hours', 'types', 'settings', 'farm' ), true ) ? $screen : 'calendar';
	}
	/** Safe navigation URL.
	 *
	 * @param string $screen Screen.
	 * @param array  $nav View.
	 * @return string
	 */
	public static function url( $screen, $nav = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'page'   => 'csf-scheduling',
					'screen' => $screen,
				),
				$nav
			),
			admin_url( 'admin.php' )
		); }
	/** Separately navigable administrative screens.
	 *
	 * @param string $screen Current.
	 * @param string $date Date.
	 * @param string $view View.
	 * @param string $filter Resource.
	 */
	public static function navigation( $screen, $date, $view, $filter ) {
		echo '<nav aria-label="Digital Ranch sections">';
		foreach ( array(
			'calendar' => 'Calendar',
			'hours'    => 'Manage Hours',
			'types'    => 'Lesson Types',
			'settings' => 'Farm Settings',
			'farm'     => 'Riders, Horses & Resources',
		) as $key => $label ) {
			if ( in_array( $key, array( 'types', 'settings' ), true ) && ! CSF_Scheduling_Access::can( 'policy_manage' ) ) {
				continue; }
			if ( 'hours' === $key && ( ! CSF_Scheduling_Access::can( 'availability_manage' ) || ! CSF_Scheduling_Access::can( 'resource_view' ) ) ) {
				continue; }
			if ( 'farm' === $key && ! CSF_Scheduling_Access::writer() ) {
				continue; }
			echo '<a href="' . esc_url(
				self::url(
					$key,
					array(
						'date'     => $date,
						'view'     => $view,
						'resource' => $filter,
					)
				)
			) . '" ' . ( $screen === $key ? 'aria-current="page"' : '' ) . '>' . esc_html( $label ) . '</a>';
		}
		echo '</nav>';
	}
	/** Calendar actions with no automatic writes.
	 *
	 * @param string $date Date.
	 * @param string $view View.
	 * @param string $filter Resource.
	 */
	public static function actions( $date, $view, $filter ) {
		echo '<div class="csf-calendar-actions">';
		if ( CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
			echo '<a class="button" href="' . esc_url( self::url( 'family' ) ) . '">Book family visit</a>';
		}
		if ( CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
			echo '<a class="button button-primary csf-new-lesson" href="#csf-create">Schedule Lesson</a>'; }
		if ( CSF_Scheduling_Access::can( 'availability_manage' ) && CSF_Scheduling_Access::can( 'resource_view' ) ) {
			echo '<a class="button csf-block-time" href="#csf-block-panel">Block Time</a><a class="button" href="' . esc_url(
				self::url(
					'hours',
					array(
						'resource' => $filter,
						'date'     => $date,
					)
				)
			) . '">Manage Hours</a>';
		}
		$zone = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true )['timezone'] ?? 'UTC';
		$day  = CSF_Scheduling_Resource_Hours::date( $date ) ? new DateTimeImmutable( $date, new DateTimeZone( $zone ) ) : new DateTimeImmutable( 'today', new DateTimeZone( $zone ) );
		foreach ( array(
			'Previous' => $day->modify( 'week' === $view ? '-7 days' : '-1 day' )->format( 'Y-m-d' ),
			'Today'    => wp_date( 'Y-m-d', null, new DateTimeZone( $zone ) ),
			'Next'     => $day->modify( 'week' === $view ? '+7 days' : '+1 day' )->format( 'Y-m-d' ),
		) as $label => $target ) {
			echo '<a class="button" href="' . esc_url(
				self::url(
					'calendar',
					array(
						'date'     => $target,
						'view'     => $view,
						'resource' => $filter,
					)
				)
			) . '">' . esc_html( $label ) . '</a>'; }
		echo '</div><noscript><p>Lesson editing needs JavaScript. The calendar list and native hours/closure review forms remain available.</p></noscript>';
	}
	/** Dedicated screens check capabilities before rendering.
	 *
	 * @param string $screen Screen.
	 * @param array  $ctx Authorized choices.
	 * @param array  $p Policy.
	 * @param array  $snapshot Revision snapshot.
	 * @param string $date Date.
	 * @param string $view View.
	 * @param string $filter Resource.
	 */
	public static function screen_content( $screen, $ctx, $p, $snapshot, $date, $view, $filter ) {
		if ( 'family' === $screen ) {
			CSF_Scheduling_Family_UI::render( $ctx, $p, $snapshot );
			return;
		}
		if ( in_array( $screen, array( 'types', 'settings' ), true ) ) {
			if ( ! CSF_Scheduling_Access::can( 'policy_manage' ) ) {
				wp_die( 'Facility policy permission required.', '', array( 'response' => 403 ) ); }
			echo '<h2>' . esc_html( 'types' === $screen ? 'Manage Lesson Types' : 'Farm Settings' ) . '</h2>';
			CSF_Scheduling_Settings::render( $snapshot, 'types' === $screen );
			return;
		}
		if ( 'farm' === $screen ) {
			if ( ! CSF_Scheduling_Access::writer() ) {
					wp_die( 'Staff permission required.', '', array( 'response' => 403 ) );
			}CSF_Scheduling_UI::setup( $ctx );
			return; }
		if ( ! CSF_Scheduling_Access::can( 'availability_manage' ) || ! CSF_Scheduling_Access::can( 'resource_view' ) ) {
			wp_die( 'Resource availability permission required.', '', array( 'response' => 403 ) ); }
		$resources = array_values( array_filter( $ctx['resource_rows'], static fn( $r ) => in_array( $r['kind'], array( 'arena', 'instructor', 'location' ), true ) ) );
		if ( ! $resources ) {
			echo '<p>Add an arena or instructor in Riders, Horses & Resources first.</p>';
			return; }
		$chosen = array_values( array_filter( $resources, static fn( $r ) => $r['uuid'] === $filter ) );
		$chosen = $chosen ? $chosen[0] : $resources[0];
		echo '<h2>Manage Hours</h2><form method="get"><input type="hidden" name="page" value="csf-scheduling"><input type="hidden" name="screen" value="hours"><input type="hidden" name="date" value="' . esc_attr( $date ) . '"><label>Switch resource <select name="resource">';
		foreach ( $resources as $r ) {
			echo '<option value="' . esc_attr( $r['uuid'] ) . '" ' . selected( $chosen['uuid'], $r['uuid'], false ) . '>' . esc_html( $r['label'] ) . '</option>'; }
		echo '</select></label><button class="button">Show hours</button></form>';
		$ctx['resource_rows'] = array( $chosen );
		CSF_Scheduling_Resource_UI::render( $ctx, $p, $snapshot, $date, $view, $chosen['uuid'], 'hours' );
	}
	/** Compact closure shell; JavaScript progressively moves forms into a modal.
	 *
	 * @param array  $ctx Authorized choices.
	 * @param array  $p Policy.
	 * @param array  $snapshot Revision.
	 * @param string $date Date.
	 * @param string $view View.
	 * @param string $filter Resource.
	 * @param mixed  $rows Scoped view.
	 */
	public static function block_panel( $ctx, $p, $snapshot, $date, $view, $filter, $rows ) {
		if ( ! CSF_Scheduling_Access::can( 'availability_manage' ) || ! CSF_Scheduling_Access::can( 'resource_view' ) ) {
			return; }
		echo '<section id="csf-block-panel"><h2>Block Time</h2><label>Resource <select class="csf-block-resource">';
		foreach ( $ctx['resource_rows'] as $r ) {
			if ( in_array( $r['kind'], array( 'arena', 'instructor', 'location' ), true ) ) {
					echo '<option value="' . esc_attr( $r['uuid'] ) . '" ' . selected( $filter, $r['uuid'], false ) . '>' . esc_html( $r['label'] ) . '</option>'; }
		}
		echo '</select></label><p>Select a calendar block or range, or enter its local time here. Nothing changes until the reviewed save.</p>';
		$ctx['visible_appointments'] = is_array( $rows ) ? $rows : array();
		CSF_Scheduling_Resource_UI::render( $ctx, $p, $snapshot, $date, $view, $filter, 'block' );
		echo '</section>';
	}
	/** Compact named selectors retain the existing exact checkbox-backed resource list.
	 *
	 * @param array $ctx Authorized choices.
	 * @param array $selected Existing assignments, never silently reduced.
	 */
	public static function resource_choices( $ctx, $selected ) {
		echo '<div class="csf-resource-choices">';
		foreach ( array(
			'instructor' => 'Instructor',
			'arena'      => 'Arena',
			'location'   => 'Location',
		) as $kind => $label ) {
			$options = array_filter( $ctx['resource_rows'], static fn( $r ) => $r['kind'] === $kind );
			if ( ! $options ) {
					continue; }
			echo '<label>' . esc_html( $label ) . ' <select data-resource-kind="' . esc_attr( $kind ) . '"><option value="">Choose ' . esc_html( strtolower( $label ) ) . '</option>';
			foreach ( $options as $r ) {
				echo '<option value="' . esc_attr( $r['uuid'] ) . '" ' . selected( in_array( $r['uuid'], $selected, true ), true, false ) . ( $r['active'] ? '' : ' disabled' ) . '>' . esc_html( $r['label'] ) . '</option>'; }
			echo '</select></label>';
		}
		echo '<details class="csf-assignment-details"><summary>Additional resource assignments</summary>';
		CSF_Scheduling_UI::checks( 'resources', 'Assigned resources', $ctx['resources'], $selected );
		echo '</details></div>';
	}
}

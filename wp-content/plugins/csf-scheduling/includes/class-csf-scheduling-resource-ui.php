<?php
/** Native resource review forms; no public endpoint or automatic writes.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
/** Existing MFA/permission, nonce, signed review and command lock govern all writes. */
class CSF_Scheduling_Resource_UI {
	/** Exact review retained for retry.
	 *
	 * @var array|null
	 */
	private static $review = null;
	/** Stale proposals are comparison-only.
	 *
	 * @var bool
	 */
	private static $stale = false;
	/** Status only.
	 *
	 * @var string
	 */
	private static $notice = '';
	/** Bounded unsaved native fields.
	 *
	 * @var array
	 */
	private static $raw = array();
	/** Navigation fields.
	 *
	 * @var array
	 */
	private static $nav = array();
	/** Strict scalar, never lossy sanitization of review data.
	 *
	 * @param array  $input Input.
	 * @param string $key Field.
	 * @return string
	 */
	private static function value( $input, $key ) {
		$v = $input[ $key ] ?? '';
		CSF_Scheduling_Calendar::require( is_string( $v ) && strlen( $v ) <= 400, 'invalid_body' );
		return $v;
	}
	/** Current session and actor bind the exact command.
	 *
	 * @param array $r Review.
	 * @return string
	 */
	private static function signature( $r ) {
		return hash_hmac( 'sha256', wp_json_encode( CSF_Scheduling_Store::canonical( $r ) ) . ':' . get_current_user_id() . ':' . wp_get_session_token(), wp_salt( 'nonce' ) ); }
	/** Parse normal forms into one strict command.
	 *
	 * @param array $input Native fields.
	 * @param array $p Policy snapshot.
	 * @return array
	 */
	public static function parse( $input, $p ) {
		$kind     = self::value( $input, 'resource_kind' );
		$id       = self::value( $input, 'resource_uuid' );
		$expected = self::value( $input, 'resource_expected' );
		CSF_Scheduling_Calendar::require( in_array( $kind, CSF_Scheduling_Resource_Hours::COMMANDS, true ) && CSF_Scheduling_Audit::uuid( $id ) && preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $expected ), 'invalid_body' );
		if ( 'resource_hours_saved' === $kind ) {
			$d    = array(
				'mode'     => self::value( $input, 'mode' ),
				'timezone' => $p['timezone'],
				'from'     => self::value( $input, 'from' ),
				'periods'  => array(),
			);
			$rows = $input['hours'] ?? array();
			CSF_Scheduling_Calendar::require( is_array( $rows ) && count( $rows ) <= 56, 'invalid_body' );
			foreach ( $rows as $row ) {
				CSF_Scheduling_Calendar::require( is_array( $row ), 'invalid_body' );
				$day   = self::value( $row, 'weekday' );
				$start = self::value( $row, 'start' );
				$end   = self::value( $row, 'end' );
				CSF_Scheduling_Calendar::require( preg_match( '/^[0-6]$/D', $day ), 'invalid_body' );
				if ( 'custom' === $d['mode'] && ( '' !== $start || '' !== $end ) ) {
					$d['periods'][] = array(
						'weekday' => (int) $day,
						'start'   => $start,
						'end'     => '00:00' === $end ? '24:00' : $end,
					); }
			}
			CSF_Scheduling_Resource_Hours::validate( $d, $p );
		} elseif ( 'resource_closure_added' === $kind ) {
			$resolve = static fn( $name ) => CSF_Scheduling_Time::resolve(
				array(
					'local'  => self::value( $input, $name . '_local' ),
					'choice' => ( '' === self::value( $input, $name . '_choice' ) ? null : self::value( $input, $name . '_choice' ) ),
				),
				$p['timezone']
			);
			$repeat  = self::value( $input, 'repeat' );
			CSF_Scheduling_Calendar::require( in_array( $repeat, array( '', '1' ), true ), 'invalid_body' );
			$d = array(
				'target_uuid'  => self::value( $input, 'resource_target' ),
				'start'        => $resolve( 'start' ),
				'end'          => $resolve( 'end' ),
				'category'     => self::value( $input, 'category' ),
				'note'         => self::value( $input, 'note' ),
				'repeat_until' => '1' === $repeat ? self::value( $input, 'repeat_until' ) : null,
			);
		} else {
			$d = array(
				'note' => self::value( $input, 'note' ),
				'date' => ( '' === self::value( $input, 'only_date' ) ? null : self::value( $input, 'only_date' ) ),
			); }
		return array(
			'kind' => $kind,
			'body' => array(
				'uuid'                => $id,
				'expected'            => (int) $expected,
				'data'                => $d,
				'impact_acknowledged' => true,
			),
		);
	}
	/** Every POST rechecks permission/MFA and nonce before parsing.
	 *
	 * @throws RuntimeException Caught for malformed or stale forms.
	 */
	public static function post() {
		if ( ! isset( $_POST['csf_resource_action'] ) ) {
			return; }
		if ( ! CSF_Scheduling_Access::can( 'availability_manage' ) || ! CSF_Scheduling_Access::can( 'resource_view' ) ) {
			wp_die( 'Resource availability permission and verified MFA required.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'csf_resource_review' );
		try {
			$input  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact bounded typed parser and signed review below.
			$action = self::value( $input, 'csf_resource_action' );
			if ( 'review' === $action ) {
				self::$raw = array_filter( $input, 'is_string' );
				if ( isset( $input['hours'] ) && is_array( $input['hours'] ) && count( $input['hours'] ) <= 56 ) {
					self::$raw['hours'] = array_values( array_filter( $input['hours'], static fn( $v ) => is_array( $v ) && isset( $v['weekday'], $v['start'], $v['end'] ) && is_string( $v['weekday'] ) && is_string( $v['start'] ) && is_string( $v['end'] ) ) ); }
				$rev = self::value( $input, 'resource_revision' );
				$s   = CSF_Scheduling_Schema::settings();
				CSF_Scheduling_Calendar::require( preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $rev ) && (int) $s['revision'] === (int) $rev, 'stale_revision' );
				$r      = self::parse( $input, json_decode( $s['policy_json'], true ) ) + array(
					'revision' => (int) $rev,
					'command'  => wp_generate_uuid4(),
				);
				$result = CSF_Scheduling_Store::resource_preview( $r['kind'], $r['body'], $r['revision'], $r['command'] );
				if ( is_wp_error( $result ) ) {
					self::$notice = CSF_Scheduling_UI::message( $result );
					return; }
				$r['impact']  = $result['affected_appointments'];
				self::$review = $r;
				self::$notice = 'Resource change checked. Nothing has been saved.';
				return;
			}
			CSF_Scheduling_Calendar::require( 'apply' === $action && '1' === self::value( $input, 'resource_reviewed' ), 'invalid_body' );
			$raw = $input['resource_review'] ?? null;
			CSF_Scheduling_Calendar::require( is_string( $raw ) && strlen( $raw ) <= 65536, 'invalid_body' );
			$r = json_decode( $raw, true );
			CSF_Scheduling_Calendar::keys( $r, array( 'kind', 'body', 'revision', 'command', 'impact' ) );
			CSF_Scheduling_Calendar::require( is_int( $r['revision'] ) && is_int( $r['impact'] ) && in_array( $r['kind'], CSF_Scheduling_Resource_Hours::COMMANDS, true ) && hash_equals( self::signature( $r ), self::value( $input, 'resource_signature' ) ), 'invalid_body' );
			$result = CSF_Scheduling_Store::calendar( $r['kind'], $r['body'], $r['revision'], $r['command'] );
			if ( is_wp_error( $result ) ) {
				self::$notice = CSF_Scheduling_UI::message( $result );
				self::$review = $r;
				self::$stale  = 'stale_revision' === ( $result->get_error_data()['reason'] ?? '' );
				if ( 'stale_revision' !== ( $result->get_error_data()['reason'] ?? '' ) ) {
					self::$review  = $r;
					self::$notice .= ' Result not confirmed: retry this exact review. If your session changes, inspect current resource history before starting another change.'; }
			} else {
				self::$notice = 'Resource change committed and audited. Existing appointments were not canceled or reassigned.'; }
		} catch ( RuntimeException $e ) {
			self::$notice = CSF_Scheduling_UI::message( new WP_Error( $e->getMessage(), 'Resource change rejected.' ) ); }
	}
	/** Native labeled field.
	 *
	 * @param string $name Name.
	 * @param string $label Label.
	 * @param mixed  $value Scalar.
	 * @param string $type Type.
	 * @param string $extra Static attributes.
	 */
	private static function field( $name, $label, $value = '', $type = 'text', $extra = '' ) {
		echo '<label>' . esc_html( $label ) . ' <input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '" ' . $extra . '></label>'; } // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static attributes at reviewed call sites.
	/** Bound review envelope, no raw command editor.
	 *
	 * @param string $kind Kind.
	 * @param string $uuid Entity.
	 * @param int    $expected Entity revision.
	 * @param int    $revision Global revision.
	 * @param string $target_resource Target for native prefill.
	 */
	private static function begin( $kind, $uuid, $expected, $revision, $target_resource ) {
		echo '<form method="post" class="csf-resource-draft" data-kind="' . esc_attr( $kind ) . '">';
		wp_nonce_field( 'csf_resource_review' );
		foreach ( array(
			'csf_resource_action' => 'review',
			'resource_kind'       => $kind,
			'resource_uuid'       => $uuid,
			'resource_expected'   => $expected,
			'resource_revision'   => $revision,
			'resource_target'     => $target_resource,
		) + self::$nav as $key => $value ) {
			echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
	}
	/** Local-time entry with explicit fold choices; ordinary submit also works without JS.
	 *
	 * @param string $name Field.
	 * @param string $label Label.
	 * @param string $local Local string.
	 * @param string $choice Explicit occurrence.
	 * @param string $zone Facility zone.
	 */
	private static function time( $name, $label, $local, $choice, $zone ) {
		echo '<fieldset class="csf-resource-time"><legend>' . esc_html( $label ) . '</legend>';
		self::field( $name . '_local', 'Local date and time', $local, 'datetime-local', 'step="900" required' );
		$choices = array();
		try {
			if ( $local ) {
				$choices = CSF_Scheduling_Time::choices( $local, $zone );
			}
		} catch ( RuntimeException $e ) {
			$choices = array(); }
		echo '<label>Clock-change occurrence <select name="' . esc_attr( $name . '_choice' ) . '"><option value="">Use unique local time, or choose an occurrence</option>';
		foreach ( $choices as $i => $iso ) {
			echo '<option value="' . esc_attr( $iso ) . '" ' . selected( $iso, $choice, false ) . '>' . esc_html( ( $i ? 'Second' : 'First' ) . ' occurrence · ' . substr( $iso, -6 ) ) . '</option>'; }
		echo '</select></label><span class="csf-resource-time-status" role="status"></span></fieldset>';
	}
	/** Resource editor with reviewed hours, closure spans and non-destructive reopening.
	 *
	 * @param array  $ctx Authorized resource labels.
	 * @param array  $p Policy snapshot.
	 * @param array  $snapshot Settings/revision snapshot.
	 * @param string $date View date.
	 * @param string $view Day/week.
	 * @param string $filter Selected resource.
	 */
	public static function render( $ctx, $p, $snapshot, $date, $view, $filter ) {
		if ( ! CSF_Scheduling_Access::can( 'availability_manage' ) || ! CSF_Scheduling_Access::can( 'resource_view' ) ) {
			return; }
		if ( ! CSF_Scheduling_Resource_Hours::date( $date ) ) {
			$date = wp_date( 'Y-m-d', null, new DateTimeZone( $p['timezone'] ?? 'UTC' ) ); }
		self::$nav = array(
			'calendar_date'     => $date,
			'calendar_view'     => $view,
			'calendar_resource' => $filter,
		);
		$days      = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
		echo '<section id="csf-resource-management"><h2>Resource hours and closures</h2><p>Weekly hours apply only after review. Existing dated availability is preserved. Facility hours and every other protective restriction still apply. Green blocks are candidates, not reservations. Select a resource lane/filter, enable Manage resource availability, then tap one square or two range endpoints. Accessible date/time forms work without grid selection.</p><p>Weekly missing-hour boundaries stay closed; repeated-hour openings use the later start/earlier finish. Protective closures cover both clock occurrences, or the whole date when a recurring boundary is missing. Existing appointments are never moved or canceled automatically.</p>';
		if ( self::$notice ) {
			echo '<p role="status">' . esc_html( self::$notice ) . '</p>'; }
		if ( self::$review ) {
			$r      = self::$review;
			$d      = $r['body']['data'];
			$target = 'resource_hours_saved' === $r['kind'] ? $r['body']['uuid'] : ( $d['target_uuid'] ?? '' );
			$row    = $target ? CSF_Scheduling_Calendar::find( 'resources', $target ) : null;
			echo '<div class="csf-resource-review"><h3>Review resource change</h3><p>' . esc_html( str_replace( '_', ' ', $r['kind'] ) . ( $row ? ' · ' . $row['label'] : '' ) ) . '</p>';
			if ( 'resource_hours_saved' === $r['kind'] ) {
				echo '<p>' . esc_html( 'Base: ' . $d['mode'] . ' · applies from ' . $d['from'] . ' · timezone ' . $d['timezone'] . '. Legacy dated entries remain stored.' ) . '</p><ul>';
				foreach ( $d['periods'] as $period ) {
					echo '<li>' . esc_html( $days[ $period['weekday'] ] . ' ' . $period['start'] . '–' . $period['end'] ) . '</li>';
				}echo '</ul>';
				if ( 'custom' === $d['mode'] && ! $d['periods'] ) {
					echo '<p>All custom weekdays are closed.</p>'; }
			} elseif ( 'resource_closure_added' === $r['kind'] ) {
				echo '<p>' . esc_html( CSF_Scheduling_UI::display_time( $d['start'] ) . ' – ' . CSF_Scheduling_UI::display_time( $d['end'] ) . ' · ' . $d['category'] . ' · ' . $d['note'] . ( $d['repeat_until'] ? ' · repeats weekly through ' . $d['repeat_until'] : ' · dated closure only' ) ) . '</p>';
			} else {
				echo '<p>' . esc_html( $d['date'] ? 'Reopen only ' . $d['date'] : 'Release this entire closure/series' ) . '. ' . esc_html( $d['note'] ) . '</p><p>Other closures, welfare/rest and base hours remain in force.</p>'; }
			echo '<p><strong>Future appointments requiring review: ' . esc_html( $r['impact'] ) . '</strong>. Includes recorded reserved travel/envelopes and requested lesson intervals. Existing bookings are unchanged.</p>';
			if ( ! self::$stale ) {
				echo '<form method="post" class="csf-resource-apply">';
				wp_nonce_field( 'csf_resource_review' );
				foreach ( array(
					'csf_resource_action' => 'apply',
					'resource_review'     => wp_json_encode( $r ),
					'resource_signature'  => self::signature( $r ),
				) + self::$nav as $key => $value ) {
					echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
				echo '<label><input type="checkbox" name="resource_reviewed" value="1" required> I reviewed these exact hours/closures and the affected appointment count. I will resolve any flagged bookings.</label><button class="button button-primary">Apply reviewed resource change</button></form>';
			} else {
				echo '<p role="alert">This earlier proposal is retained for comparison only. Review the current hours below and submit a new review before applying.</p>';
			}
			echo '</div>';
		}
		$restrictions = CSF_Scheduling_Calendar::documents( 'restrictions' );
		foreach ( $ctx['resource_rows'] as $target_resource ) {
			if ( ! in_array( $target_resource['kind'], array( 'arena', 'instructor', 'location' ), true ) ) {
				continue; }
			$id        = $target_resource['uuid'];
			$doc       = CSF_Scheduling_Calendar::find( 'availability', $id );
			$available = $doc ? json_decode( $doc['body_json'], true ) : array( 'intervals' => array() );
			$w         = $available['weekly'] ?? array(
				'mode'     => $doc ? 'legacy' : 'inherit',
				'timezone' => $p['timezone'],
				'from'     => $date,
				'periods'  => array(),
			);
			$own       = ( self::$raw['resource_target'] ?? null ) === $id;
			$raw       = $own ? self::$raw : array();
			$is_hours  = 'resource_hours_saved' === ( $raw['resource_kind'] ?? null );
			echo '<details class="csf-resource-panel" data-resource="' . esc_attr( $id ) . '"><summary>Hours and closures: ' . esc_html( $target_resource['label'] ) . '</summary><p>' . esc_html( 'Current base: ' . ( $available['weekly']['mode'] ?? 'legacy dated availability' ) . ' · ' . count( $available['intervals'] ) . ' preserved dated entries' ) . '</p>';
			if ( isset( $available['weekly'] ) && $w['timezone'] !== $p['timezone'] ) {
				echo '<p role="alert">Facility timezone changed. Weekly hours remain closed until you explicitly review them in the current timezone.</p>'; }
			self::begin( 'resource_hours_saved', $id, $doc ? (int) $doc['revision'] : 0, (int) $snapshot['revision'], $id );
			echo '<h3>Regular weekly hours</h3><label>Resource base <select name="mode">';
			foreach ( array(
				'legacy'  => 'Keep using preserved dated availability',
				'inherit' => 'Inherit facility opening hours',
				'custom'  => 'Custom weekly hours (within facility hours)',
			) as $mode => $label ) {
				echo '<option value="' . esc_attr( $mode ) . '" ' . selected( $is_hours ? self::value( $raw, 'mode' ) : $w['mode'], $mode, false ) . '>' . esc_html( $label ) . '</option>';
			}echo '</select></label>';
			self::field( 'from', 'Applies from (local date)', $is_hours ? self::value( $raw, 'from' ) : $w['from'], 'date', 'required' );
			echo '<p>Unlisted weekdays are closed in Custom mode. Split overnight hours across days. Close 00:00 means midnight at the end of that day. Timezone: ' . esc_html( $p['timezone'] ?? 'not configured' ) . '.</p>';
			$index = 0;foreach ( array( 1, 2, 3, 4, 5, 6, 0 ) as $day ) {
				$periods   = $is_hours && isset( $raw['hours'] ) ? array_values( array_filter( $raw['hours'], static fn( $v ) => $v['weekday'] === (string) $day && ( '' !== $v['start'] || '' !== $v['end'] ) ) ) : array_values( array_filter( $w['periods'], static fn( $v ) => $v['weekday'] === $day ) );
				$periods[] = array(
					'start' => '',
					'end'   => '',
				);
				$periods[] = array(
					'start' => '',
					'end'   => '',
				);
				echo '<fieldset><legend>' . esc_html( $days[ $day ] ) . '</legend>';
				foreach ( $periods as $period ) {
					echo '<input type="hidden" name="hours[' . esc_attr( $index ) . '][weekday]" value="' . esc_attr( $day ) . '">';
					self::field( 'hours[' . $index . '][start]', 'Open', $period['start'], 'time', 'step="900"' );
					self::field( 'hours[' . $index . '][end]', 'Close', '24:00' === $period['end'] ? '00:00' : $period['end'], 'time', 'step="900"' );
					++$index;
				}echo '</fieldset>';
			}
			echo '<button class="button">Review weekly hours</button></form><h3>Close a period or day</h3><label>Day to close <input class="csf-resource-day" type="date" value="' . esc_attr( $date ) . '"></label><button type="button" class="button csf-close-resource-day" data-resource="' . esc_attr( $id ) . '">Close this day</button>';
			self::begin( 'resource_closure_added', wp_generate_uuid4(), 0, (int) $snapshot['revision'], $id );
			$draft_closure = 'resource_closure_added' === ( $raw['resource_kind'] ?? null );
			foreach ( array(
				'start' => 'Closure starts',
				'end'   => 'Closure ends',
			) as $key => $label ) {
				self::time( $key, $label, $draft_closure ? self::value( $raw, $key . '_local' ) : '', $draft_closure ? self::value( $raw, $key . '_choice' ) : '', $p['timezone'] ); }
			echo '<label>Category <select name="category"><option value="unavailable">Unavailable / maintenance</option><option value="protected_training" ' . selected( $draft_closure ? ( $raw['category'] ?? '' ) : '', 'protected_training', false ) . '>Protected training time</option></select></label>';
			self::field( 'note', 'Reason (no medical or contact details)', $draft_closure ? self::value( $raw, 'note' ) : '', 'text', 'maxlength="200" required' );
			self::field( 'repeat', 'Repeat weekly', '1', 'checkbox', $draft_closure && '1' === ( $raw['repeat'] ?? '' ) ? 'checked' : '' );
			self::field( 'repeat_until', 'Repeat through (maximum one year)', $draft_closure ? self::value( $raw, 'repeat_until' ) : '', 'date' );
			echo '<p class="csf-resource-selection" role="status"></p><button class="button">Review closure</button></form><h3>Recorded closures</h3>';
			foreach ( $restrictions as $record ) {
				$d = $record['data'];
				if ( $record['entity_uuid'] !== $id || 'resource_closure' !== ( $d['closure_kind'] ?? null ) || ! empty( $d['released'] ) ) {
					continue; }
				echo '<details class="csf-resource-closure" data-closure="' . esc_attr( $record['uuid'] ) . '"><summary>' . esc_html( $d['note'] ) . '</summary><p>' . esc_html( CSF_Scheduling_UI::display_time( $d['start'] ) . ' – ' . CSF_Scheduling_UI::display_time( $d['end'] ) ) . '</p>';
				if ( $d['recurrence'] ) {
					$series = $d['recurrence'];
					echo '<p>' . esc_html( $days[ $series['weekday'] ] . ' weekly ' . $series['start'] . '–' . $series['end'] . ' ' . $series['timezone'] . ' through ' . $series['until'] . '; reopened dates: ' . implode( ', ', $d['reopen_dates'] ?? array() ) ) . '</p>'; }
				self::begin( 'resource_closure_released', $record['uuid'], (int) $record['revision'], (int) $snapshot['revision'], $id );
				self::field( 'note', 'Reason for reopening', '', 'text', 'required maxlength="200"' );
				echo '<input type="hidden" name="only_date" value=""><button class="button">Review reopening entire closure / series</button></form>';
				if ( $d['recurrence'] ) {
					self::begin( 'resource_closure_released', $record['uuid'], (int) $record['revision'], (int) $snapshot['revision'], $id );
					self::field( 'only_date', 'Reopen only this date', $date, 'date', 'required' );
					self::field( 'note', 'Reason for this exception', '', 'text', 'required maxlength="200"' );
					echo '<button class="button">Review reopening this occurrence</button></form>'; }
				echo '</details>';
			}
			echo '</details>';
		}
		echo '</section>';
	}
}

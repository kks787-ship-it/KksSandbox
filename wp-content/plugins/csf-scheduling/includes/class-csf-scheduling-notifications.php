<?php
/** Transactional in-app notices and held transport envelopes; never sends mail.
 * @package CSF_Scheduling
 */
defined( 'ABSPATH' ) || exit;
class CSF_Scheduling_Notifications {
	/** Only actual confirmed-state changes create notices; untouched siblings do not. */
	public static function change( $before, $after ) {
		if ( 'canceled' === ( $after['state'] ?? null ) ) { return 'confirmed' === ( $before['state'] ?? null ) ? 'CANCELED' : null; }
		if ( 'confirmed' !== ( $after['state'] ?? null ) ) { return null; }
		if ( 'confirmed' !== ( $before['state'] ?? null ) ) { return 'NEW'; }
		foreach ( array( 'title', 'service_type', 'start', 'end', 'resources', 'riders', 'rides', 'resource_reservations' ) as $key ) {
			if ( ( $before[$key] ?? null ) !== ( $after[$key] ?? null ) ) { return 'RESCHEDULED'; }
		}
		return null;
	}

	/** Moving an imminent lesson away still counts as an urgent change. */
	public static function urgent( $before, $after, $now ) {
		foreach ( array( $before['start'] ?? null, $after['start'] ?? null ) as $start ) {
			if ( is_string( $start ) && strtotime( $start ) >= $now && strtotime( $start ) <= $now + 86400 ) { return true; }
		}
		return false;
	}

	/** Effective staff coordination rights or explicit instructor assignment. */
	public static function recipients( $state, $changes ) {
		$out = array();
		foreach ( $state['members'] as $id => $member ) {
			$rights = CSF_Scheduling_Permissions::effective( $state, (int) $id );
			$oversight = ! empty( $rights['calendar_all'] ) && ( ! empty( $rights['appointment_edit'] ) || ! empty( $rights['appointment_confirm'] ) || ! empty( $rights['appointment_cancel'] ) );
			$items = array();
			foreach ( $changes as $change ) {
				if ( $oversight || ( ! empty( $rights['calendar_assigned'] ) && array_intersect( $member['resources'], array_merge( $change['instructors'], $change['previous_instructors'] ?? array() ) ) ) ) { $items[] = $change['uuid']; }
			}
			if ( $items ) { $out[(int) $id] = $items; }
		}
		return $out;
	}
	/** Record assignment provenance for a minimal historical/departure projection. */
	public static function audience( $state, $changes ) {
		$items = self::recipients( $state, $changes ); $assigned = array(); $departures = array();
		foreach ( $items as $id => $uuids ) {
			$rights = CSF_Scheduling_Permissions::effective( $state, (int) $id ); $member = $state['members'][$id];
			$oversight = ! empty( $rights['calendar_all'] ) && ( ! empty( $rights['appointment_edit'] ) || ! empty( $rights['appointment_confirm'] ) || ! empty( $rights['appointment_cancel'] ) );
			foreach ( $changes as $c ) {
				$old = array_intersect( $member['resources'], $c['previous_instructors'] ?? array() ); $new = array_intersect( $member['resources'], $c['instructors'] );
				if ( ! empty( $rights['calendar_assigned'] ) && ( $old || $new ) ) { $assigned[$id][] = $c['uuid']; if ( $old && ! $new && ! $oversight ) { $departures[$id][] = $c['uuid']; } }
			}
		}
		return array( 'items' => $items, 'assigned' => $assigned, 'departures' => $departures );
	}
	/** Frozen event-time projection; no new private details for a former instructor. */
	public static function project( $change, $departure, $current_visible, $historical_allowed ) {
		if ( ! $current_visible && ! $historical_allowed ) { return null; }
		$time = $departure ? ( $change['before_time'] ?? null ) : ( $change['after_time'] ?? null );
		return array( 'uuid' => $change['uuid'], 'kind' => $departure ? 'REMOVED_FROM_ASSIGNMENT' : $change['kind'], 'urgent' => $departure ? ! empty( $change['before_urgent'] ) : $change['urgent'], 'start' => $time['start'] ?? null, 'end' => $time['end'] ?? null, 'restricted' => $departure || ! $current_visible );
	}

	/** Navigation uses the currently authorized appointment, never the frozen display date. */
	public static function link_date( $projected, $appointment, $timezone ) {
		if ( ! $projected || $projected['restricted'] || ! $appointment ) { return null; }
		return ( new DateTimeImmutable( $appointment['start_utc'], new DateTimeZone( 'UTC' ) ) )->setTimezone( new DateTimeZone( $timezone ) )->format( 'Y-m-d' );
	}

	/** Called once inside Store's non-preview transaction, before COMMIT. */
	public static function record( $kind, $result, $command ) {
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		$items = 'family_visit_saved' === $kind ? $result['items'] : array( $result );
		$changes = array();
		foreach ( $items as $item ) {
			if ( empty( $item['notice_kind'] ) ) { continue; }
			$d = json_decode( CSF_Scheduling_Calendar::find( 'details', $item['uuid'] )['body_json'], true );
			$instructors = array();
			foreach ( $d['resources'] as $uuid ) { $r = CSF_Scheduling_Calendar::find( 'resources', $uuid ); if ( $r && 'instructor' === $r['kind'] ) { $instructors[] = $uuid; } }
			$before = $item['notice_before'] ?? null; $previous_instructors = array();
			if ( 'confirmed' === ( $before['state'] ?? null ) ) { foreach ( $before['resources'] as $uuid ) { $r = CSF_Scheduling_Calendar::find( 'resources', $uuid ); if ( $r && 'instructor' === $r['kind'] ) { $previous_instructors[] = $uuid; } } }
			$changes[] = array( 'before_urgent' => self::urgent( $before ?? array(), array(), time() ), 'before_time' => $before ? array_intersect_key( $before, array_flip( array( 'start', 'end' ) ) ) : null, 'after_time' => array_intersect_key( $d, array_flip( array( 'start', 'end' ) ) ), 'previous_instructors' => $previous_instructors, 'uuid' => $item['uuid'], 'kind' => $item['notice_kind'], 'instructors' => $instructors, 'urgent' => ! empty( $item['notice_urgent'] ) );
		}
		if ( ! $changes ) { return; }
		$state = CSF_Scheduling_Permissions::state();
		CSF_Scheduling_Calendar::require( is_array( $state ), 'forbidden' );
		$audience = self::audience( $state, $changes ); $recipients = $audience['items'];
		// Command UUID is the event idempotency key. Store replay returns before this hook.
		CSF_Scheduling_Calendar::document( 'details', $command, $result['uuid'], array(
			'record_type' => 'booking_notice', 'source_command' => $command, 'entity_uuid' => $result['uuid'],
			'changes' => $changes, 'recipient_ids' => array_map( 'intval', array_keys( $recipients ) ), 'recipient_items' => $recipients,
			'recipient_assignments' => $audience['assigned'], 'recipient_departures' => $audience['departures'],
			'email_state' => 'held_no_delivery_authorization', 'push_state' => 'not_configured',
		), 0 );
	}
	/** Current permission/scope and original recipient snapshot both apply. */
	private static function visible( $row ) {
		if ( ! $row || ! CSF_Scheduling_Access::can() ) { return null; }
		$d = json_decode( $row['body_json'], true ); $actor = get_current_user_id();
		if ( 'booking_notice' !== ( $d['record_type'] ?? null ) || ! in_array( $actor, $d['recipient_ids'], true ) ) { return null; }
		$changes = array();
		foreach ( $d['changes'] as $change ) {
			if ( ! in_array( $change['uuid'], $d['recipient_items'][$actor] ?? array(), true ) ) { continue; }
			$a = CSF_Scheduling_Calendar::find( 'appointments', $change['uuid'] );
			$departure = in_array( $change['uuid'], $d['recipient_departures'][$actor] ?? array(), true );
			$historical = CSF_Scheduling_Access::can( 'calendar_assigned' ) && in_array( $change['uuid'], $d['recipient_assignments'][$actor] ?? array(), true );
			$projected = self::project( $change, $departure, $a && CSF_Scheduling_Permissions::appointment_visible( $a ), $historical );
			if ( $projected ) {
				$p = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
				$projected['link_date'] = self::link_date( $projected, $a, $p['timezone'] );
				$changes[] = $projected;
			}
		}
		return $changes ? array( 'uuid' => $row['uuid'], 'created_at_utc' => $row['created_at_utc'], 'changes' => $changes ) : null;
	}
	public static function event( $uuid ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Audit::uuid( $uuid ) || ! CSF_Scheduling_Access::can() ) { return null; }
		$t = CSF_Scheduling_Calendar::table( 'details' ); $r = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$t` WHERE uuid=%s", $uuid ), ARRAY_A );
		CSF_Scheduling_Calendar::require( ! $wpdb->last_error, 'write_failed' );
		return self::visible( $r );
	}
	public static function authorize( $body ) { return is_array( $body ) && null !== self::event( $body['uuid'] ?? null ); }
	private static function acknowledged( $uuid ) {
		global $wpdb; $t = CSF_Scheduling_Calendar::table( 'history' );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$t` WHERE entity_uuid=%s AND JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.kind'))='notice_acknowledged' AND created_by=%d LIMIT 1", $uuid, get_current_user_id() ) );
		CSF_Scheduling_Calendar::require( ! $wpdb->last_error, 'write_failed' ); return (bool) $id;
	}
	/** An acknowledgement writes only that actor's history, never appointment state. */
	public static function acknowledge( $body ) {
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Store::in_calendar_transaction() && self::authorize( $body ), 'forbidden' );
		CSF_Scheduling_Calendar::keys( $body, array( 'uuid' ) );
		if ( ! self::acknowledged( $body['uuid'] ) ) { CSF_Scheduling_Calendar::document( 'history', wp_generate_uuid4(), $body['uuid'], array( 'kind' => 'notice_acknowledged', 'event_uuid' => $body['uuid'] ), 0 ); }
		return array( 'uuid' => $body['uuid'], 'acknowledged' => true );
	}
	/** Bounded history with a stable id cursor, never a shared cache. */
	public static function page( $before = 0, $history = false ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Access::can() ) { return array( 'items' => array(), 'next' => null ); }
		CSF_Scheduling_Calendar::require( is_int( $before ) && $before >= 0 && is_bool( $history ) );
		$t = CSF_Scheduling_Calendar::table( 'details' );
		$h = CSF_Scheduling_Calendar::table( 'history' );
		$unread = $history ? '' : $wpdb->prepare( " AND NOT EXISTS (SELECT 1 FROM `$h` h WHERE h.entity_uuid=`$t`.uuid AND h.created_by=%d AND JSON_UNQUOTE(JSON_EXTRACT(h.body_json,'$.kind'))='notice_acknowledged')", get_current_user_id() );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$t` WHERE JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.record_type'))='booking_notice' AND JSON_CONTAINS(body_json,%s,'$.recipient_ids') AND (%d=0 OR id<%d) $unread ORDER BY id DESC LIMIT 51", wp_json_encode( get_current_user_id() ), $before, $before ), ARRAY_A );
		CSF_Scheduling_Calendar::require( is_array( $rows ) && ! $wpdb->last_error, 'write_failed' );
		$more = count( $rows ) > 50; $rows = array_slice( $rows, 0, 50 ); $out = array();
		foreach ( $rows as $r ) { $item = self::visible( $r ); if ( $item ) { $item['acknowledged'] = self::acknowledged( $r['uuid'] ); $out[] = $item; } }
		return array( 'items' => $out, 'next' => $more ? (int) end( $rows )['id'] : null );
	}
	public static function post() {
		if ( ! isset( $_POST['csf_notice_ack'] ) ) { return; }
		if ( ! CSF_Scheduling_Access::can() ) { wp_die( 'Private calendar permission required.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'csf_notice_ack' );
		$uuid = isset( $_POST['notice_uuid'] ) && is_string( $_POST['notice_uuid'] ) ? wp_unslash( $_POST['notice_uuid'] ) : '';
		$command = isset( $_POST['notice_command'] ) && is_string( $_POST['notice_command'] ) ? wp_unslash( $_POST['notice_command'] ) : '';
		$revision = isset( $_POST['notice_revision'] ) && is_string( $_POST['notice_revision'] ) ? wp_unslash( $_POST['notice_revision'] ) : '';
		if ( ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $revision ) ) { return; }
		$r = CSF_Scheduling_Store::calendar( 'notice_acknowledged', array( 'uuid' => $uuid ), (int) $revision, $command );
		if ( is_wp_error( $r ) ) { add_action( 'admin_notices', static function() { echo '<div class="notice notice-warning"><p>Acknowledgement was not confirmed. Refresh and retry the visible notice; booking state was not changed.</p></div>'; } ); }
	}
	public static function render() {
		if ( ! CSF_Scheduling_Access::can() ) { return; }
		try {
			$cursor = isset( $_GET['notice_before'] ) && is_string( $_GET['notice_before'] ) && preg_match( '/^[0-9]{1,12}$/D', $_GET['notice_before'] ) ? (int) $_GET['notice_before'] : 0; // Scoped read-only cursor.
			$history = isset( $_GET['notice_history'] ) && '1' === $_GET['notice_history'];
			$page = self::page( $cursor, $history ); $revision = (int) CSF_Scheduling_Schema::settings()['revision'];
			echo '<section aria-label="Booking notices"><a href="' . esc_url( CSF_Scheduling_UX::url( 'calendar', array( 'notice_history' => $history ? '0' : '1' ) ) ) . '">' . esc_html( $history ? 'Unread booking notices' : 'Booking notice history' ) . '</a>';
			foreach ( $page['items'] as $item ) {
				$new = array_filter( $item['changes'], static fn( $c ) => 'NEW' === $c['kind'] );
				$heading = $new ? 'NEW LESSON SCHEDULED' : 'LESSON CHANGED';
				if ( array_filter( $item['changes'], static fn( $c ) => $c['urgent'] ) ) { $heading = 'URGENT CHANGE'; }
				echo '<article class="csf-booking-notice"' . ( $item['acknowledged'] ? '' : ' role="status"' ) . '><' . ( $item['acknowledged'] ? 'h3' : 'h2' ) . '>' . esc_html( $heading . ( $item['acknowledged'] ? ' · acknowledged' : '' ) ) . '</' . ( $item['acknowledged'] ? 'h3' : 'h2' ) . '><ul>';
				foreach ( $item['changes'] as $change ) {
					$text = $change['kind'] . ' · ' . ( $change['start'] ? substr( $change['start'], 0, 16 ) . ' ' . substr( $change['start'], -6 ) : 'Event-time date unavailable in legacy notice' );
					if ( $change['restricted'] ) { echo '<li>' . esc_html( $text . ' · historical assignment only; current private lesson details unavailable' ) . '</li>'; }
					else { echo '<li><a href="' . esc_url( CSF_Scheduling_UX::url( 'calendar', $change['link_date'] ? array( 'date' => $change['link_date'] ) : array() ) . '#csf-event-' . $change['uuid'] ) . '">' . esc_html( $text . ' · open currently permitted lesson details' ) . '</a></li>'; }
				}
				echo '</ul>';
				if ( ! $item['acknowledged'] ) {
					echo '<form method="post">'; wp_nonce_field( 'csf_notice_ack' );
					foreach ( array( 'notice_uuid' => $item['uuid'], 'notice_command' => wp_generate_uuid4(), 'notice_revision' => $revision ) as $key => $value ) { echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
					echo '<button class="button" name="csf_notice_ack" value="1">Acknowledge</button></form>';
				}
				echo '</article>';
			}
			if ( $page['next'] ) { echo '<a href="' . esc_url( CSF_Scheduling_UX::url( 'calendar', array( 'notice_before' => $page['next'], 'notice_history' => $history ? '1' : '0' ) ) ) . '">Older booking notices</a>'; }
			echo '</section>';
		} catch ( Throwable $e ) { echo '<p>Booking notices are temporarily unavailable. Review the calendar directly.</p>'; }
	}
}

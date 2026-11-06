<?php
/** Private wp-admin staff calendar; no public endpoints.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Server-rendered, keyboard-accessible calendar and manual staff commands. */
class CSF_Scheduling_Admin {
	/** Private request state.
	 *
	 * @var array|WP_Error|null Command outcome. */
	private static $result = null;
	/** Original command retained for exact replay after an uncertain outcome.
	 *
	 * @var array|null
	 */
	private static $retry = null;
	/** Original unsent fields, retained only within this authorized response.
	 *
	 * @var array|null
	 */
	private static $draft = null;

	/** Register private page. */
	public static function menu() {
		if ( ! CSF_Scheduling_Access::can() && ! CSF_Scheduling_Access::writer() && ! CSF_Scheduling_Access::can( 'team_view' ) ) {
			return; }
		add_menu_page( 'Digital Ranch Calendar', 'Digital Ranch', 'read', 'csf-scheduling', array( __CLASS__, 'render' ), 'dashicons-calendar-alt' );
	}

	/** Authenticated nonce-checked command handler. No automatic mutations on GET.
	 *
	 * @throws RuntimeException Caught locally for malformed exact-retry fields.
	 */
	public static function post() {
		if ( isset( $_POST['csf_calendar_view'] ) ) {
			if ( ! CSF_Scheduling_Access::can() ) {
				wp_die( 'Private scheduling access required.', '', array( 'response' => 403 ) ); }
			check_admin_referer( 'csf_calendar_view' );
		}
		if ( ! isset( $_POST['csf_calendar_command'] ) ) {
			return; }
		if ( ! CSF_Scheduling_Access::writer() ) {
			wp_die( 'Private scheduling access required.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'csf_calendar_write' );
		$raw     = isset( $_POST['body'] ) && is_string( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : '';// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict service validation, no lossy sanitization of JSON.
		$kind    = isset( $_POST['csf_calendar_command'] ) && is_string( $_POST['csf_calendar_command'] ) ? sanitize_key( wp_unslash( $_POST['csf_calendar_command'] ) ) : '';
		$command = isset( $_POST['command_id'] ) && is_string( $_POST['command_id'] ) ? sanitize_text_field( wp_unslash( $_POST['command_id'] ) ) : '';
		$rev     = isset( $_POST['facility_revision'] ) && is_string( $_POST['facility_revision'] ) ? sanitize_text_field( wp_unslash( $_POST['facility_revision'] ) ) : '';
		$body    = strlen( $raw ) <= 65536 ? json_decode( $raw, true ) : null;
		if ( ! is_array( $body ) || ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $rev ) ) {
			self::$result = new WP_Error( 'invalid_body', 'Invalid command body or revision.' );
			return; }
		self::$draft = array(
			'kind' => $kind,
			'body' => $body,
		);
		try {
			if ( isset( $_POST['csf_exact_retry'] ) && '1' === $_POST['csf_exact_retry'] ) {
				if ( ! CSF_Scheduling_UI::editable( $kind, $body ) ) {
					throw new RuntimeException( 'invalid_body' ); }
			} else {
				$body = CSF_Scheduling_UI::normalize( $kind, $body ); }
		} catch ( Throwable $error ) {
			$code = in_array( $error->getMessage(), array_merge( CSF_Scheduling_Booking::ERRORS, array( 'ambiguous_time', 'nonexistent_time', 'invalid_time', 'policy_incomplete', 'stale_revision' ) ), true ) ? $error->getMessage() : 'invalid_body';
			if ( 'invalid_body' === $code ) {
				self::$draft = null; }
			self::$result = new WP_Error( $code, 'Invalid scheduling fields.', $error instanceof CSF_Scheduling_Conflict ? array( 'detail' => $error->resource_label ) : null );
			return;
		}
			self::$retry = array(
				'kind'     => $kind,
				'body'     => $body,
				'revision' => $rev,
				'command'  => $command,
			);
			if ( 'resource_created' === $kind ) {
				self::$result = CSF_Scheduling_Store::resource( $body, (int) $rev, $command );
			} else {
				self::$result = CSF_Scheduling_Store::calendar( $kind, $body, (int) $rev, $command );
			}
	}

	/** Staff-only local time and transaction preview tools. No nopriv handler.
	 *
	 * @throws RuntimeException Caught and converted to fixed validation errors.
	 */
	public static function ajax() {
		if ( ! CSF_Scheduling_Access::writer() ) {
			wp_send_json_error( array( 'message' => 'Private scheduling access required.' ), 403 ); }
		check_ajax_referer( 'csf_calendar_write', '_wpnonce' );
		nocache_headers();
		try {
			$op = isset( $_POST['op'] ) && is_string( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';
			if ( 'times' === $op ) {
				$local = isset( $_POST['local'] ) && is_string( $_POST['local'] ) ? sanitize_text_field( wp_unslash( $_POST['local'] ) ) : '';
				$p     = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
				wp_send_json_success( array( 'choices' => CSF_Scheduling_Time::choices( $local, $p['timezone'] ) ) );
			}
			if ( ! in_array( $op, array( 'preview', 'booking_slots' ), true ) ) {
				throw new RuntimeException( 'invalid_body' ); }
			$raw  = isset( $_POST['body'] ) && is_string( $_POST['body'] ) ? wp_unslash( $_POST['body'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict typed service validation.
			$body = strlen( $raw ) <= 65536 ? json_decode( $raw, true ) : null;
			if ( ! is_array( $body ) ) {
				throw new RuntimeException( 'invalid_body' ); }
			$rev = isset( $_POST['facility_revision'] ) && is_string( $_POST['facility_revision'] ) ? sanitize_text_field( wp_unslash( $_POST['facility_revision'] ) ) : '';
			$id  = isset( $_POST['command_id'] ) && is_string( $_POST['command_id'] ) ? sanitize_text_field( wp_unslash( $_POST['command_id'] ) ) : '';
			if ( ! preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $rev ) ) {
				throw new RuntimeException( 'invalid_body' ); }
			if ( 'booking_slots' === $op ) {
				$day = isset( $_POST['day'] ) && is_string( $_POST['day'] ) ? sanitize_text_field( wp_unslash( $_POST['day'] ) ) : '';

				$offset = isset( $_POST['offset'] ) && is_string( $_POST['offset'] ) ? sanitize_text_field( wp_unslash( $_POST['offset'] ) ) : '0';
				if ( ! preg_match( '/^(0|[1-9][0-9]?)$/D', $offset ) || (int) $offset > 95 ) {
					throw new RuntimeException( 'invalid_body' );
				}
				wp_send_json_success( CSF_Scheduling_Booking::slots( $body, $day, (int) $rev, (int) $offset ) );
			}
			$routine = ! empty( $body['_routine'] );
			$body    = CSF_Scheduling_UI::normalize( 'appointment_saved', $body );
			$result  = CSF_Scheduling_Store::preview( $body, (int) $rev, $id, $routine );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => CSF_Scheduling_UI::message( $result ) ) ); }

			$p      = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
			$totals = array();
			$buffer = $p['services'][ $body['service_type'] ]['requires_travel'] ? $p['travel_buffer_minutes'] : 0;
			foreach ( $body['rides'] as $ride ) {
				$horse           = json_decode( CSF_Scheduling_Calendar::find( 'horses', $ride['horse'] )['body_json'], true );
				$name            = $horse['label'];
				$totals[ $name ] = ( $totals[ $name ] ?? 0 ) + ( strtotime( $ride['end'] ) - strtotime( $ride['start'] ) ) / 60 + 2 * $buffer;
			}
			$impact = array( 'Reserved interval: ' . wp_date( 'M j, g:i a T', strtotime( $body['start'] ) - 60 * $buffer, new DateTimeZone( $p['timezone'] ) ) . ' – ' . wp_date( 'M j, g:i a T', strtotime( $body['end'] ) + 60 * $buffer, new DateTimeZone( $p['timezone'] ) ) . '. Teaching duration excludes configured travel. No additional preparation/wrap-up time is reserved.' );
			foreach ( $totals as $name => $minutes ) {
				$impact[] = $name . ': ' . $minutes . ' reserved minutes, including configured travel.'; }
			wp_send_json_success(
				array(
					'message' => ! empty( $result['preview'] ) ? 'Schedule checked. No reservation has been made. Saving checks it again.' : 'This command was already saved. Saving again replays its original result; check the current calendar.',
					'times'   => array( $body['start'], $body['end'] ),
					'impact'  => implode( ' ', $impact ),
				)
			);
		} catch ( Throwable $error ) {
			$code = in_array( $error->getMessage(), array_merge( CSF_Scheduling_Booking::ERRORS, array( 'ambiguous_time', 'nonexistent_time', 'invalid_time', 'policy_incomplete', 'stale_revision' ) ), true ) ? $error->getMessage() : 'invalid_body';
			wp_send_json_error( array( 'message' => CSF_Scheduling_UI::message( new WP_Error( $code, 'Invalid scheduling fields.', $error instanceof CSF_Scheduling_Conflict ? array( 'detail' => $error->resource_label ) : null ) ) ) );
		}
	}

	/** Private page, with no raw structured editor. */
	public static function render() {
		CSF_Scheduling_UI::render( self::$result, self::$retry, self::$draft ); }
}

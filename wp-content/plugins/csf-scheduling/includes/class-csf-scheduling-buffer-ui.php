<?php
/** Signed native review for explicitly granted optional-buffer exceptions.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
/** Coordinate this private scheduling operation through existing access and transaction boundaries. */
class CSF_Scheduling_Buffer_UI {
	/** Exact signed proposal awaiting confirmation.
	 *
	 * @var array|null
	 */
	private static $review = null;
	/** Current request outcome text.
	 *
	 * @var string
	 */
	private static $notice = '';
	/** Whether this request received commit acknowledgement.
	 *
	 * @var bool
	 */
	private static $committed = false;
	/** Bind the exact reviewed payload to this actor and session.
	 *
	 * @param array $r Exact reviewed proposal.
	 */
	private static function signature( $r ) {
		return hash_hmac( 'sha256', wp_json_encode( CSF_Scheduling_Store::canonical( $r ) ) . ':' . get_current_user_id() . ':' . wp_get_session_token(), wp_salt( 'nonce' ) ); }
	/** Read a bounded scalar form field.
	 *
	 * @param array  $input Submitted native fields.
	 * @param string $key Field key.
	 */
	private static function scalar( $input, $key ) {
		$v = $input[ $key ] ?? '';
		CSF_Scheduling_Calendar::require( is_string( $v ) && strlen( $v ) <= 400 );
		return $v; }
	/** Process the nonce-protected submitted command. */
	public static function post() {
		if ( ! isset( $_POST['csf_buffer_action'] ) ) {
			return; }
		if ( ! CSF_Scheduling_Access::can( 'buffer_override' ) || ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) {
			wp_die( 'Explicit buffer exception permission and verified MFA required.', '', array( 'response' => 403 ) ); }
		check_admin_referer( 'csf_buffer_review' );
		try {
			$input  = wp_unslash( $_POST );
			$action = self::scalar( $input, 'csf_buffer_action' );
			if ( 'review' === $action ) {
				foreach ( array( 'expected', 'minutes', 'revision' ) as $key ) {
					CSF_Scheduling_Calendar::require( preg_match( '/^(0|[1-9][0-9]{0,9})$/D', self::scalar( $input, $key ) ) ); }
				$b = array(
					'uuid'          => self::scalar( $input, 'uuid' ),
					'expected'      => (int) $input['expected'],
					'resource_uuid' => self::scalar( $input, 'resource_uuid' ),
					'segment'       => self::scalar( $input, 'segment' ),
					'minutes'       => (int) $input['minutes'],
					'reason'        => self::scalar( $input, 'reason' ),
				);
				CSF_Scheduling_Calendar::require( CSF_Scheduling_Buffer_Override::authorize( $b ), 'forbidden' );
				$d      = json_decode( CSF_Scheduling_Calendar::find( 'details', $b['uuid'] )['body_json'], true );
				$r      = array(
					'body'     => $b,
					'revision' => (int) $input['revision'],
					'command'  => wp_generate_uuid4(),
					'before'   => $d['resource_reservations'] ?? array(),
				);
				$result = CSF_Scheduling_Store::buffer_preview( $b, $r['revision'], $r['command'] );
				if ( is_wp_error( $result ) ) {
					self::$notice = CSF_Scheduling_UI::message( $result );
					return; }
				$r['after']   = $result['resource_reservations'];
				self::$review = $r;
				self::$notice = 'Exception checked. Nothing has changed; review the exact segment and reason before approving.';
				return;
			}
			CSF_Scheduling_Calendar::require( 'apply' === $action );
			$raw = $input['buffer_review'] ?? null;
			CSF_Scheduling_Calendar::require( is_string( $raw ) && strlen( $raw ) <= 65536 );
			$r = json_decode( $raw, true );
			CSF_Scheduling_Calendar::keys( $r, array( 'body', 'revision', 'command', 'before', 'after' ) );
			CSF_Scheduling_Calendar::require( hash_equals( self::signature( $r ), self::scalar( $input, 'buffer_signature' ) ) );
			self::$review = $r;
			$result       = CSF_Scheduling_Store::calendar( 'buffer_overridden', $r['body'], $r['revision'], $r['command'] );
			if ( is_wp_error( $result ) ) {
				self::$notice = CSF_Scheduling_UI::message( $result ) . ' No successful change is confirmed. Retry only this exact review or reload current records.';
				return; }
			self::$committed = true;
			self::$notice    = 'The reviewed buffer exception was committed and audited. Mandatory booking and welfare checks remain in force.';
		} catch ( Throwable $e ) {
			self::$notice = CSF_Scheduling_UI::message( new WP_Error( $e->getMessage(), 'Buffer exception rejected.' ) ); }
	}
	/** Render an escaped hidden form field.
	 *
	 * @param string $key Field key.
	 * @param mixed  $value Field value validated or escaped by the caller and renderer.
	 */
	private static function hidden( $key, $value ) {
		echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">'; }
	/** Render the exact signed proposal for confirmation. */
	public static function review() {
		if ( ! self::$notice && ! self::$review ) {
			return; }
		echo '<section aria-label="Buffer exception review"><p role="status">' . esc_html( self::$notice ) . '</p>';
		if ( self::$review ) {
			$r        = self::$review;
			$b        = $r['body'];
			$resource = CSF_Scheduling_Calendar::find( 'resources', $b['resource_uuid'] );
			echo '<h2>' . esc_html( self::$committed ? '⚠ BUFFER OVERRIDE' : 'Review optional buffer exception' ) . '</h2><p>' . esc_html( ( $resource['label'] ?? 'Assigned resource' ) . ' · ' . $b['segment'] . ' segment reduced to ' . $b['minutes'] . ' minutes · reason: ' . $b['reason'] ) . '</p>';
			foreach ( array(
				'before' => 'Before',
				'after'  => 'After approval',
			) as $key => $label ) {
				foreach ( $r[ $key ] as $window ) {
					if ( $window['resource_uuid'] === $b['resource_uuid'] ) {
						echo '<p>' . esc_html( $label . ': ' . gmdate( 'Y-m-d H:i', $window['start'] ) . '–' . gmdate( 'Y-m-d H:i', $window['end'] ) . ' UTC' ) . '</p>'; }
				}
			}
			if ( ! self::$committed ) {
				echo '<form method="post">';
				wp_nonce_field( 'csf_buffer_review' );
				self::hidden( 'buffer_review', wp_json_encode( $r ) );
				self::hidden( 'buffer_signature', self::signature( $r ) );
				echo '<button class="button" name="csf_buffer_action" value="apply">Approve this buffer exception</button></form>'; }
		}
		echo '</section>';
	}
	/** Render the permitted buffer exception form for this appointment.
	 *
	 * @param array $row Stored database row.
	 * @param array $d Saved appointment detail.
	 * @param array $ctx Current scoped presentation context.
	 */
	public static function form( $row, $d, $ctx ) {
		if ( 'confirmed' !== $row['state'] || empty( $d['resource_reservations'] ) || ! CSF_Scheduling_Access::can( 'buffer_override' ) || ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) {
			return; }
		echo '<details><summary>Review an optional buffer exception</summary><p>Normal family sharing needs no exception. This can reduce only one optional segment; travel, teaching, horse recovery and current restrictions remain enforced. Ordinary rescheduling restores default buffers and requires a new exception review.</p><form method="post">';
		wp_nonce_field( 'csf_buffer_review' );
		foreach ( array(
			'uuid'     => $row['uuid'],
			'expected' => $row['revision'],
			'revision' => CSF_Scheduling_Schema::settings()['revision'],
		) as $key => $value ) {
			self::hidden( $key, $value ); }
		echo '<label>Resource <select name="resource_uuid">';
		foreach ( $d['resource_reservations'] as $w ) {
			echo '<option value="' . esc_attr( $w['resource_uuid'] ) . '">' . esc_html( ( $ctx['resources'][ $w['resource_uuid'] ] ?? 'Assigned resource' ) . ' · pre ' . $w['pre_minutes'] . ' / post ' . $w['post_minutes'] . ' min' ) . '</option>'; }
		echo '</select></label> <label>Segment <select name="segment"><option value="pre">Preparation before</option><option value="post">Wrap-up after</option></select></label> <label>Remaining minutes <input type="number" name="minutes" min="0" max="1440" value="0" required></label> <label>Reason (no private health/contact details) <input name="reason" maxlength="200" required></label> <button class="button" name="csf_buffer_action" value="review">Review exception</button></form></details>';
	}
	/** Render approved exception markers without changing reservations.
	 *
	 * @param array $overrides Saved approval metadata.
	 * @param array $ctx Current scoped presentation context.
	 */
	public static function marker( $overrides, $ctx ) {
		if ( ! $overrides ) {
			return; }
		echo '<aside class="csf-buffer-override" aria-label="Approved buffer override"><strong>⚠ BUFFER OVERRIDE</strong><ul>';
		foreach ( $overrides as $o ) {
			echo '<li>' . esc_html( ( $ctx['resources'][ $o['resource_uuid'] ] ?? 'Assigned resource' ) . ' · ' . $o['segment'] . ' · approver #' . $o['approved_by'] . ' · ' . $o['reason'] . ' · before ' . gmdate( 'Y-m-d H:i', $o['before']['start'] ) . '–' . gmdate( 'H:i', $o['before']['end'] ) . ' UTC; after ' . gmdate( 'Y-m-d H:i', $o['after']['start'] ) . '–' . gmdate( 'H:i', $o['after']['end'] ) . ' UTC' ) . '</li>'; }
		echo '</ul><p>This exception does not waive horse welfare or mandatory travel.</p></aside>';
	}
}

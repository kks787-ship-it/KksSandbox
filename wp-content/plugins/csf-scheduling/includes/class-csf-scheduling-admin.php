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

	/** Register private page. */
	public static function menu() {
		add_menu_page( 'Digital Ranch Calendar', 'Digital Ranch', 'csf_schedule_view', 'csf-scheduling', array( __CLASS__, 'render' ), 'dashicons-calendar-alt' );
	}

	/** Authenticated nonce-checked command handler. No automatic mutations on GET. */
	public static function post() {
		if ( ! isset( $_POST['csf_calendar_command'] ) ) {
			return; }
		if ( ! CSF_Scheduling_Access::can( 'csf_schedule_manage' ) ) {
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

	/** Render private overview and progressive enhancement forms. */
	public static function render() {
		if ( ! CSF_Scheduling_Access::can() ) {
			wp_die( 'Private scheduling access required.', '', array( 'response' => 403 ) ); }
		nocache_headers();
		$p      = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
		$date   = isset( $_GET['date'] ) && is_string( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : wp_date( 'Y-m-d', null, new DateTimeZone( $p['timezone'] ?? 'UTC' ) );// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation.
		$view   = isset( $_GET['view'] ) && 'week' === $_GET['view'] ? 'week' : 'day';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$filter = isset( $_GET['resource'] ) && is_string( $_GET['resource'] ) ? sanitize_text_field( wp_unslash( $_GET['resource'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rows   = CSF_Scheduling_Calendar::view( $date, $view, $filter );
		global $wpdb;
		$t         = CSF_Scheduling_Schema::table( 'resources' );
		$resources = $wpdb->get_results( "SELECT uuid,label,kind FROM `$t` WHERE active=1 ORDER BY kind,label", ARRAY_A );// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$riders    = CSF_Scheduling_Calendar::documents( 'riders' );
		$horses    = CSF_Scheduling_Calendar::documents( 'horses' );
		echo '<div class="wrap csf-calendar"><h1>Digital Ranch Staff Calendar</h1><p>Private calendar · ' . esc_html( $p['timezone'] ?? 'Timezone not configured' ) . ' · Confirmation reserves time; rider clearance is a separate pre-ride check.</p>';
		if ( self::$result ) {
			if ( is_wp_error( self::$result ) ) {
				$reason = self::$result->get_error_data()['reason'] ?? self::$result->get_error_code();
				echo '<div role="alert" class="notice notice-error"><p>Command not acknowledged: ' . esc_html( $reason ) . '. Keep the same command ID when retrying an uncertain result. Refresh before correcting a stale revision.</p></div>';
				if ( self::$retry ) {
					echo '<details><summary>Retry the original command</summary><p>Use this for an uncertain commit only. A stale revision needs a refreshed edit. Command ID: ' . esc_html( self::$retry['command'] ) . '</p><form method="post">';
					wp_nonce_field( 'csf_calendar_write' );
					$fields = array(
						'csf_calendar_command' => self::$retry['kind'],
						'body'                 => wp_json_encode( self::$retry['body'] ),
						'facility_revision'    => self::$retry['revision'],
						'command_id'           => self::$retry['command'],
					);
					foreach ( $fields as $name => $value ) {
						echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; }
					echo '<button class="button">Retry exact command</button></form></details>';
				}
			} else {
				echo '<div role="status" class="notice notice-success"><p>Command committed.</p></div>'; }
		}
		?>
		<noscript><p>JavaScript is required for routine appointment entry. Calendar viewing remains available.</p></noscript>
		<style>.csf-calendar form{margin:1rem 0}.csf-calendar label{display:block;margin:.6rem 0}.csf-calendar input,.csf-calendar select,.csf-calendar textarea{max-width:100%;box-sizing:border-box}.csf-calendar textarea{width:100%;font-family:monospace}.csf-calendar .appointments{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:1rem}.csf-calendar article{background:white;border:1px solid #777;padding:1rem;overflow-wrap:anywhere}.csf-calendar .blocked{border-left:5px solid #a00}.csf-calendar .safe{border-left:5px solid #276039}</style>
		<form method="get"><input type="hidden" name="page" value="csf-scheduling"><label>Date <input type="date" name="date" required value="<?php echo esc_attr( $date ); ?>"></label><label>View <select name="view"><option value="day">Day</option><option value="week" <?php selected( $view, 'week' ); ?>>Week (7 days)</option></select></label><label>Instructor, arena or horse <select name="resource"><option value="">All</option>
		<?php
		foreach ( $resources as $r ) {
			if ( 'horse' === $r['kind'] ) {
				continue; }
			?>
			<option value="<?php echo esc_attr( $r['uuid'] ); ?>" <?php selected( $filter, $r['uuid'] ); ?>><?php echo esc_html( $r['kind'] . ': ' . $r['label'] ); ?></option>
			<?php
		} foreach ( $horses as $h ) {
			?>
	<option value="<?php echo esc_attr( $h['uuid'] ); ?>" <?php selected( $filter, $h['uuid'] ); ?>><?php echo esc_html( 'horse: ' . $h['data']['label'] ); ?></option><?php } ?></select></label><button class="button">Show calendar</button></form>
		<?php
		if ( is_wp_error( $rows ) ) {
			echo '<p role="alert">Calendar blocked: configure facility policies and timezone before scheduling.</p>'; } elseif ( ! $rows ) {
			echo '<p>No appointments in this view.</p>'; }
			echo '<div class="appointments">';
			foreach ( is_array( $rows ) ? $rows : array() as $row ) {
				echo '<article class="' . ( $row['rider_cleared'] && ! $row['welfare_review'] ? 'safe' : 'blocked' ) . '"><h2>' . esc_html( $row['title'] ) . '</h2><p>' . esc_html( $row['start'] . ' – ' . $row['end'] ) . '</p><p>Status: <strong>' . esc_html( $row['state'] ) . ( $row['hold_expired'] ? ' (expired; not reserved)' : '' ) . '</strong></p><p>' . ( $row['rider_cleared'] ? 'Rider evidence verified' : 'PRE-RIDE BLOCK: rider evidence incomplete' ) . '</p><p>' . ( $row['welfare_review'] ? 'WELFARE BLOCK: staff review required' : 'No recorded welfare restriction' ) . '</p>';
				if ( CSF_Scheduling_Access::can( 'csf_schedule_manage' ) ) {
					$d = json_decode( CSF_Scheduling_Calendar::find( 'details', $row['uuid'] )['body_json'], true );
					if ( 'canceled' !== $row['state'] ) {
						$d['expected'] = $row['revision'];
						echo '<details><summary>Edit or confirm appointment</summary><p>Change state to confirmed only after direct agreement. Keep explicit time offsets.</p>';
						self::appointment_form( $d, 'Save appointment', $resources, $riders, $horses, $p );
						echo '</details>';
						$now = ( new DateTimeImmutable( 'now', new DateTimeZone( $p['timezone'] ) ) )->format( 'Y-m-d\TH:iP' );
						echo '<details><summary>Cancel appointment</summary><p>Record initiating party, actual notice, effective time and reason. No fees are calculated.</p>';
						self::cancel_form(
							array(
								'uuid'      => $row['uuid'],
								'expected'  => $row['revision'],
								'initiator' => 'staff',
								'reason'    => '',
								'notice'    => $now,
								'effective' => $now,
							)
						);
						echo '</details>';
					}
				}
				if ( CSF_Scheduling_Access::can( 'csf_schedule_audit' ) ) {
					$history = array_values( array_filter( CSF_Scheduling_Calendar::documents( 'history' ), static fn( $event ) => $event['entity_uuid'] === $row['uuid'] ) );
					echo '<details><summary>Appointment history and cancellation record</summary><pre>' . esc_html( wp_json_encode( $history, JSON_PRETTY_PRINT ) ) . '</pre></details>';
				}
				echo '</article>';
			}
			echo '</div>';
			if ( CSF_Scheduling_Access::can( 'csf_schedule_manage' ) ) {
				$uuid = wp_generate_uuid4();
				echo '<h2>Create manual appointment</h2><p>Choose a service, time and staff resources. All times require the facility’s explicit UTC offset, for example 2026-11-02T09:00-08:00. Use a separate appointment for each service interval.</p>';
				self::appointment_form(
					array(
						'uuid'         => $uuid,
						'expected'     => 0,
						'title'        => '',
						'service_type' => '',
						'state'        => 'requested',
						'start'        => '',
						'end'          => '',
						'hold_expires' => null,
						'resources'    => array(),
						'riders'       => array(),
						'rides'        => array(),
					),
					'Create appointment',
					$resources,
					$riders,
					$horses,
					$p,
					'csf-new-appointment'
				);
				echo '<details><summary>Roster, availability and welfare administration</summary><p>These staff-only records are evidence flags, not signed agreements. Do not enter medical or emergency contact details here. Availability intervals are explicit; missing availability blocks booking. Group lessons use the reviewed appointment JSON with multiple riders and discrete, non-overlapping horse ride slots.</p>';
				self::form(
					'resource_created',
					array(
						'uuid'  => wp_generate_uuid4(),
						'kind'  => 'instructor',
						'label' => '',
					),
					'Create resource'
				);
				self::form(
					'rider_saved',
					array(
						'uuid'     => wp_generate_uuid4(),
						'expected' => 0,
						'data'     => array(
							'label'              => '',
							'adult'              => false,
							'release_verified'   => false,
							'guardian_verified'  => false,
							'emergency_verified' => false,
						),
					),
					'Create rider'
				);
				self::form(
					'horse_saved',
					array(
						'uuid'     => wp_generate_uuid4(),
						'expected' => 0,
						'data'     => array(
							'label'             => '',
							'source'            => 'client',
							'resource_uuid'     => null,
							'suitable_services' => array(),
							'suitable_riders'   => array(),
							'training_enrolled' => false,
						),
					),
					'Create horse'
				);
				self::form(
					'availability_saved',
					array(
						'uuid'     => '',
						'expected' => 0,
						'data'     => array(
							'resource_uuid' => '',
							'intervals'     => array(
								array(
									'start' => '',
									'end'   => '',
								),
							),
						),
					),
					'Save resource availability'
				);
				self::form(
					'restriction_added',
					array(
						'uuid'     => wp_generate_uuid4(),
						'expected' => 0,
						'data'     => array(
							'target_uuid' => '',
							'start'       => '',
							'end'         => '',
							'reason'      => 'welfare',
						),
					),
					'Add protective block'
				);
				foreach ( array(
					'riders'       => 'rider_saved',
					'horses'       => 'horse_saved',
					'availability' => 'availability_saved',
				) as $table => $kind ) {
					foreach ( CSF_Scheduling_Calendar::documents( $table ) as $doc ) {
						echo '<details><summary>' . esc_html( $table . ': ' . ( $doc['data']['label'] ?? $doc['entity_uuid'] ) ) . '</summary>';
						self::form(
							$kind,
							array(
								'uuid'     => $doc['uuid'],
								'expected' => (int) $doc['revision'],
								'data'     => $doc['data'],
							),
							'Save revision'
						);
						echo '</details>'; }
				}
				foreach ( CSF_Scheduling_Calendar::documents( 'restrictions' ) as $doc ) {
					if ( empty( $doc['data']['released'] ) ) {
						echo '<details><summary>Release protective block: ' . esc_html( $doc['uuid'] ) . '</summary><pre>' . esc_html( wp_json_encode( $doc['data'], JSON_PRETTY_PRINT ) ) . '</pre>';
						self::form(
							'restriction_released',
							array(
								'uuid'     => $doc['uuid'],
								'expected' => (int) $doc['revision'],
								'reason'   => '',
							),
							'Record block release'
						);
						echo '</details>';
					}
				}
				echo '<h3>Resource identifiers</h3><pre>' . esc_html( wp_json_encode( $resources, JSON_PRETTY_PRINT ) ) . '</pre></details>';
			}
			?>
<script>document.querySelectorAll('form[data-appointment]').forEach(function(f){f.addEventListener('submit',function(){const v=k=>f.querySelector('[data-field="'+k+'"]').value,b=JSON.parse(f.elements.body.value);Object.assign(b,{title:v('title'),service_type:v('service_type'),state:v('state'),start:v('start'),end:v('end'),hold_expires:v('state')==='held'?v('hold_expires'):null,resources:Array.from(f.querySelector('[data-field="resources"]').selectedOptions,o=>o.value),riders:v('rider')?[v('rider')]:[],rides:v('horse')?[{horse:v('horse'),rider:v('rider')||null,start:v('start'),end:v('end')}]:[]});f.elements.body.value=JSON.stringify(b);});});document.querySelectorAll('form[data-cancellation]').forEach(function(f){f.addEventListener('submit',function(){const b=JSON.parse(f.elements.body.value);f.querySelectorAll('[data-field]').forEach(el=>b[el.dataset.field]=el.value);f.elements.body.value=JSON.stringify(b);});});</script>
		<?php
		echo '</div>';
	}

	/** Ordinary fields for routine appointments; advanced groups keep their full slot document.
	 *
	 * @param array  $body Appointment.
	 * @param string $button Action.
	 * @param array  $resources Resources.
	 * @param array  $riders Roster.
	 * @param array  $horses Horses.
	 * @param array  $policy Policy.
	 * @param string $id Form ID.
	 */
	private static function appointment_form( $body, $button, $resources, $riders, $horses, $policy, $id = '' ) {
		if ( count( $body['riders'] ) > 1 || count( $body['rides'] ) > 1 || ( isset( $body['rides'][0] ) && ( $body['rides'][0]['start'] !== $body['start'] || $body['rides'][0]['end'] !== $body['end'] ) ) ) {
			echo '<p>Advanced group rotation editor: preserve all rider and horse slots.</p>';
			self::form( 'appointment_saved', $body, $button );
			return;
		}
		echo '<form method="post" data-appointment="true" id="' . esc_attr( $id ) . '">';
		wp_nonce_field( 'csf_calendar_write' );
		echo '<input type="hidden" name="csf_calendar_command" value="appointment_saved"><input type="hidden" name="facility_revision" value="' . esc_attr( CSF_Scheduling_Schema::settings()['revision'] ) . '"><input type="hidden" name="body" value="' . esc_attr( wp_json_encode( $body ) ) . '"><input type="hidden" name="command_id" value="' . esc_attr( wp_generate_uuid4() ) . '">';
		foreach ( array(
			'title'        => 'Appointment title',
			'start'        => 'Start (with UTC offset)',
			'end'          => 'End (with UTC offset)',
			'hold_expires' => 'Hold expiry (held only)',
		) as $key => $label ) {
			echo '<label>' . esc_html( $label ) . ' <input data-field="' . esc_attr( $key ) . '" value="' . esc_attr( $body[ $key ] ?? '' ) . '" ' . ( 'hold_expires' !== $key ? 'required' : '' ) . '></label>';
		}
		echo '<label>Service <select data-field="service_type">';
		foreach ( $policy['services'] as $key => $service ) {
			if ( $service['enabled'] ) {
				echo '<option value="' . esc_attr( $key ) . '" ' . selected( $body['service_type'], $key, false ) . '>' . esc_html( $key ) . '</option>'; }
		}
		echo '</select></label><label>State <select data-field="state">';
		foreach ( array( 'requested', 'held', 'confirmed' ) as $state ) {
			if ( 'confirmed' !== $state || CSF_Scheduling_Access::can( 'csf_schedule_confirm' ) ) {
				echo '<option ' . selected( $body['state'], $state, false ) . '>' . esc_html( $state ) . '</option>'; }
		}
		echo '</select></label><label>Resources (select instructor and arena/location as required) <select data-field="resources" multiple size="5">';
		foreach ( $resources as $r ) {
			if ( 'horse' !== $r['kind'] ) {
				echo '<option value="' . esc_attr( $r['uuid'] ) . '" ' . selected( in_array( $r['uuid'], $body['resources'], true ), true, false ) . '>' . esc_html( $r['kind'] . ': ' . $r['label'] ) . '</option>'; }
		}
		echo '</select></label><label>Rider <select data-field="rider"><option value="">No rider (training/protected service)</option>';
		foreach ( $riders as $r ) {
			echo '<option value="' . esc_attr( $r['uuid'] ) . '" ' . selected( $body['riders'][0] ?? '', $r['uuid'], false ) . '>' . esc_html( $r['data']['label'] ) . '</option>'; }
		echo '</select></label><label>Horse <select data-field="horse"><option value="">No horse</option>';
		foreach ( $horses as $h ) {
			echo '<option value="' . esc_attr( $h['uuid'] ) . '" ' . selected( $body['rides'][0]['horse'] ?? '', $h['uuid'], false ) . '>' . esc_html( $h['data']['source'] . ': ' . $h['data']['label'] ) . '</option>'; }
		echo '</select></label><p>Confirmation follows direct agreement. It does not clear a rider to participate.</p><button class="button button-primary">' . esc_html( $button ) . '</button></form>';
	}


	/** Human-readable cancellation fields; no financial side effects.
	 *
	 * @param array $body Cancellation.
	 */
	private static function cancel_form( $body ) {
		echo '<form method="post" data-cancellation="true">';
		wp_nonce_field( 'csf_calendar_write' );
		echo '<input type="hidden" name="csf_calendar_command" value="appointment_canceled"><input type="hidden" name="facility_revision" value="' . esc_attr( CSF_Scheduling_Schema::settings()['revision'] ) . '"><input type="hidden" name="body" value="' . esc_attr( wp_json_encode( $body ) ) . '"><input type="hidden" name="command_id" value="' . esc_attr( wp_generate_uuid4() ) . '"><label>Initiating party <select data-field="initiator">';
		foreach ( array( 'client', 'staff', 'facility', 'weather', 'horse_welfare' ) as $party ) {
			echo '<option ' . selected( $body['initiator'], $party, false ) . '>' . esc_html( $party ) . '</option>'; }
		echo '</select></label>';
		foreach ( array(
			'reason'    => 'Reason (no medical details)',
			'notice'    => 'Actual notice received (with UTC offset)',
			'effective' => 'Cancellation effective time (with UTC offset)',
		) as $key => $label ) {
			echo '<label>' . esc_html( $label ) . ' <input data-field="' . esc_attr( $key ) . '" value="' . esc_attr( $body[ $key ] ) . '" required></label>'; }
		echo '<button class="button">Record cancellation</button></form>';
	}


	/** Nonce and actor-bound idempotency ID, with reviewable editable JSON fallback.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @param string $label Button label.
	 * @param string $id Form ID.
	 */
	private static function form( $kind, $body, $label, $id = '' ) {
		echo '<form method="post" id="' . esc_attr( $id ) . '">';
		wp_nonce_field( 'csf_calendar_write' );
		echo '<input type="hidden" name="csf_calendar_command" value="' . esc_attr( $kind ) . '"><input type="hidden" name="facility_revision" value="' . esc_attr( CSF_Scheduling_Schema::settings()['revision'] ) . '"><label>' . esc_html( $label ) . ' — review fields<textarea name="body" rows="10" required>' . esc_textarea( wp_json_encode( $body, JSON_PRETTY_PRINT ) ) . '</textarea></label><label>Command ID (retain for uncertain-result retry) <input name="command_id" size="38" required value="' . esc_attr( wp_generate_uuid4() ) . '"></label><button class="button button-primary">' . esc_html( $label ) . '</button></form>';
	}
}

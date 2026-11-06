<?php
/** Staff forms over the existing command service.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// Authoritative staff labels use only checked table identifiers and bypass caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/** Private presentation and local-time normalization. */
class CSF_Scheduling_UI {
	/** Navigation stays in authorized POST bodies, never private record URLs.
	 *
	 * @var array
	 */
	private static $navigation = array();
	/** One presentation snapshot binds timezone, policy and review revision together.
	 *
	 * @var array|null
	 */
	private static $reviewed = null;

	/** Preserve the current date and private filter across save/preview responses. */
	private static function navigation_fields() {
		foreach ( self::$navigation as $name => $value ) {
			echo '<input type="hidden" name="calendar_' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; }
	}

	/** Presentation shape validation prevents malformed drafts being rendered.
	 * Business rules still belong exclusively to the existing command service.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @return bool
	 */
	public static function editable( $kind, $body ) {
		if ( 'appointment_saved' === $kind && array_key_exists( '_routine', $body ) ) {
			if ( true !== $body['_routine'] || ! array_key_exists( 'single_rider', $body ) || ! array_key_exists( 'single_horse', $body ) || ! self::shape( $body['single_rider'], 'id' ) || ! self::shape( $body['single_horse'], 'id' ) ) {
				return false;
			}
			unset( $body['_routine'], $body['single_rider'], $body['single_horse'] );
		}
		$common  = array(
			'uuid'     => 'id',
			'expected' => 'revision',
		);
		$schemas = array(
			'appointment_saved'    => $common + array(
				'title'        => 'text',
				'service_type' => 'text',
				'state'        => 'text',
				'start'        => 'time',
				'end'          => 'time',
				'hold_expires' => 'time',
				'resources'    => 'ids',
				'riders'       => 'ids',
				'rides'        => array(
					array(
						'horse' => 'id',
						'rider' => 'id',
						'start' => 'time',
						'end'   => 'time',
					),
				),
			),
			'appointment_canceled' => $common + array(
				'initiator' => 'text',
				'reason'    => 'text',
				'notice'    => 'time',
				'effective' => 'time',
			),
			'resource_created'     => array(
				'uuid'  => 'id',
				'kind'  => 'text',
				'label' => 'text',
			),
			'resource_saved'       => $common + array(
				'data' => array(
					'label'  => 'text',
					'active' => 'bool',
				),
			),
			'rider_saved'          => $common + array(
				'data' => array(
					'label'              => 'text',
					'adult'              => 'bool',
					'release_verified'   => 'bool',
					'guardian_verified'  => 'bool',
					'emergency_verified' => 'bool',
				),
			),
			'horse_saved'          => $common + array(
				'data' => array(
					'label'             => 'text',
					'source'            => 'text',
					'resource_uuid'     => 'id',
					'suitable_services' => 'texts',
					'suitable_riders'   => 'ids',
					'training_enrolled' => 'bool',
				),
			),
			'availability_saved'   => $common + array(
				'data' => array(
					'resource_uuid' => 'id',
					'intervals'     => array(
						array(
							'start' => 'time',
							'end'   => 'time',
						),
					),
				),
			),
			'restriction_added'    => $common + array(
				'data' => array(
					'target_uuid' => 'id',
					'reason'      => 'text',
					'start'       => 'time',
					'end'         => 'time',
				),
			),
			'restriction_released' => $common + array( 'reason' => 'text' ),
		);
			return isset( $schemas[ $kind ] ) && self::shape( $body, $schemas[ $kind ] );
	}

	/** Recursive bounded field types, including local time pairs.
	 *
	 * @param mixed $value Field.
	 * @param mixed $schema Shape.
	 * @return bool
	 */
	private static function shape( $value, $schema ) {
		if ( is_array( $schema ) ) {
			if ( ! is_array( $value ) || count( $value ) > 100 ) {
				return false; }
			if ( array_is_list( $schema ) ) {
				return array_is_list( $value ) && ! array_filter( $value, static fn( $v ) => ! self::shape( $v, $schema[0] ) ); }
			if ( array_diff( array_keys( $value ), array_keys( $schema ) ) || array_diff( array_keys( $schema ), array_keys( $value ) ) ) {
				return false; }
			foreach ( $schema as $key => $type ) {
				if ( ! self::shape( $value[ $key ], $type ) ) {
					return false;
				}
			} return true;
		}
		if ( 'bool' === $schema ) {
			return is_bool( $value ); }
		if ( 'revision' === $schema ) {
			return is_int( $value ) && $value >= 0; }
		if ( 'id' === $schema ) {
			return null === $value || '' === $value || CSF_Scheduling_Audit::uuid( $value ); }
		if ( 'text' === $schema ) {
			return is_string( $value ); }
		if ( 'ids' === $schema || 'texts' === $schema ) {
			return is_array( $value ) && array_is_list( $value ) && count( $value ) <= 100 && ! array_filter( $value, static fn( $v ) => ! self::shape( $v, 'ids' === $schema ? 'id' : 'text' ) ); }
		if ( 'time' === $schema ) {
			return null === $value || is_string( $value ) || ( is_array( $value ) && self::shape(
				$value,
				array(
					'local'  => 'text',
					'choice' => 'id_or_text',
				)
			) ); }
		return 'id_or_text' === $schema && ( null === $value || is_string( $value ) );
	}

	/** Resolve all documented temporal fields on the server.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @return array
	 * @throws RuntimeException Invalid presentation shape or local time.
	 */
	public static function normalize( $kind, $body ) {
		if ( ! self::editable( $kind, $body ) ) {
			throw new RuntimeException( 'invalid_body' ); }
		$p = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
		if ( 'appointment_saved' === $kind && ! empty( $body['_routine'] ) ) {
			$body = CSF_Scheduling_Booking::derive( $body, $p );
		}
		$convert = static function ( &$record ) use ( $p ) {
			foreach ( array( 'start', 'end', 'hold_expires', 'notice', 'effective' ) as $key ) {
				if ( isset( $record[ $key ] ) && '' !== $record[ $key ] ) {
					$record[ $key ] = CSF_Scheduling_Time::resolve( $record[ $key ], $p['timezone'] ); }
			}
		};
		$convert( $body );
		foreach ( $body['rides'] ?? array() as $index => $ride ) {
			$convert( $ride );
			$body['rides'][ $index ] = $ride; }
		if ( isset( $body['data'] ) ) {
			$convert( $body['data'] );
			foreach ( $body['data']['intervals'] ?? array() as $index => $interval ) {
				$convert( $interval );
				$body['data']['intervals'][ $index ] = $interval; }
		}
		return $body;
	}

	/** Human-readable fixed error messages.
	 *
	 * @param WP_Error $error Error.
	 * @return string
	 */
	public static function message( $error ) {
		$reason   = $error->get_error_data()['reason'] ?? $error->get_error_code();
		$messages = array(
			'resource_conflict'  => 'Already reserved. Change the horse, instructor, arena or time, then preview again.',
			'rider_conflict'     => 'This rider already has an appointment. Choose another time.',
			'outside_hours'      => 'This time is outside facility hours or a resource’s recorded availability. Check availability before continuing.',
			'horse_unsafe'       => 'The horse or its resource may be inactive, or suitability is missing. Review the horse’s resource, approved riders and services.',
			'welfare_conflict'   => 'A protective block, workload limit or required horse break prevents this schedule. Review welfare and ride times.',
			'capacity'           => 'The service capacity or training enrollment limit is exceeded. Reduce the roster or review the approved policy.',
			'policy_incomplete'  => 'Facility policy is incomplete. Ask the pilot operator to finish approved settings before scheduling.',
			'stale_revision'     => 'Someone changed the calendar. Your draft is retained. Review the latest calendar, then preview the draft again.',
			'stale_appointment'  => 'This record changed. Your draft is retained; compare it with the current record before making a new edit.',
			'commit_unknown'     => 'The save result is uncertain. Do not create another appointment. Retry the original command below to recover its result.',
			'ambiguous_time'     => 'This time occurs twice when clocks go back. Choose the first or second occurrence.',
			'nonexistent_time'   => 'This local time does not exist when clocks go forward. Choose another time.',
			'booking_busy'       => 'The slot check took too long. Keep your draft and check starts again.',
			'lesson_service'     => 'Choose an enabled lesson type with a duration in 15-minute increments.',
			'lesson_grid'        => 'Choose a start on the 15-minute grid.',
			'lesson_start'       => 'Choose a date and an available start slot.',
			'lesson_rider'       => 'Choose the rider for this lesson.',
			'lesson_horse'       => 'Choose a suitable horse, or use Advanced rotation for multiple horse turns.',
			'lesson_resource'    => 'Choose every required instructor, arena or location before selecting a start.',
			'hold_expiry'        => 'For a held appointment, choose its actual expiry before the lesson and within 24 hours.',
			'service_duration'   => 'The finish must match the selected lesson type duration. Recheck the service and calculated finish.',
			'invalid_time'       => 'Check local dates, service duration, hold expiry and cancellation notice. Times must be valid in the facility timezone.',
			'forbidden'          => 'Your scheduling permission does not allow this action.',
			'invalid_transition' => 'This appointment cannot change to that state. Canceled appointments stay in history.',
		);
		$text     = $messages[ $reason ] ?? 'The command was not accepted. Review required fields and refresh before trying again.';
		$detail   = $error->get_error_data()['detail'] ?? null;
		return $detail ? $detail . ': ' . $text : $text;
	}

	/** Plain input.
	 *
	 * @param string $key Field.
	 * @param string $label Label.
	 * @param mixed  $value Value.
	 * @param string $type Type.
	 * @param bool   $locked Preserve a field the actor cannot change.
	 */
	public static function input( $key, $label, $value = '', $type = 'text', $locked = false ) {
		echo '<label>' . esc_html( $label ) . ' <input data-field="' . esc_attr( $key ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ?? '' ) . '"' . ( $locked ? ( 'checkbox' === $type ? ' disabled' : ' readonly' ) : '' ) . ( 'checkbox' === $type ? checked( (bool) $value, true, false ) : ' required' ) . '></label>';
	}

	/** Present an existing instant in the current facility zone without moving it.
	 *
	 * @param mixed $value Original time or unsent wall-time pair.
	 * @return mixed
	 */
	public static function local_value( $value ) {
		if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}[+-]\d{2}:\d{2}$/D', $value ) ) {
			return $value; }
		$p = json_decode( ( self::$reviewed ?? CSF_Scheduling_Schema::settings() )['policy_json'], true );
		if ( ! is_string( $p['timezone'] ) || ! in_array( $p['timezone'], timezone_identifiers_list(), true ) ) {
			return $value; }
		$d = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:iP', $value );
		return $d ? $d->setTimezone( new DateTimeZone( $p['timezone'] ) )->format( 'Y-m-d\TH:iP' ) : $value;
	}

	/** Local wall time with explicit fold choice supplied by the server.
	 *
	 * @param string $key Field.
	 * @param string $label Label.
	 * @param mixed  $value Original ISO or unsent local pair.
	 * @param bool   $optional Optional empty input.
	 */
	public static function time( $key, $label, $value = '', $optional = false ) {
		$value  = self::local_value( $value );
		$local  = is_array( $value ) ? ( $value['local'] ?? '' ) : substr( $value ?? '', 0, 16 );
		$choice = is_array( $value ) ? ( $value['choice'] ?? '' ) : ( $value ?? '' );
		echo '<div class="csf-time"><label>' . esc_html( $label ) . ' <input type="datetime-local" data-time="' . esc_attr( $key ) . '" value="' . esc_attr( $local ) . '"' . ( $optional ? '' : ' required' ) . '></label><label class="csf-fold" hidden>Clock-change choice for ' . esc_html( $label ) . ' <select data-fold="' . esc_attr( $key ) . '"><option value="' . esc_attr( $choice ) . '">' . ( $choice ? esc_html( 'Recorded time · ' . substr( $choice, -6 ) ) : 'Automatic for ordinary times; choose if repeated' ) . '</option></select></label><span class="csf-time-status" aria-live="polite"></span></div>';
	}

	/** Single choice.
	 *
	 * @param string $key Key.
	 * @param string $label Label.
	 * @param array  $options Value/label map.
	 * @param mixed  $value Selected.
	 */
	public static function select( $key, $label, $options, $value = '' ) {
		echo '<label>' . esc_html( $label ) . ' <select data-field="' . esc_attr( $key ) . '">';
		foreach ( $options as $id => $text ) {
			echo '<option value="' . esc_attr( $id ) . '" ' . selected( $value ?? '', $id, false ) . '>' . esc_html( $text ) . '</option>'; }
		echo '</select></label>';
	}

	/** Touch-friendly multiple choice without modifier keys.
	 *
	 * @param string $key Field.
	 * @param string $label Group label.
	 * @param array  $options Value/label map.
	 * @param array  $values Selected values.
	 */
	public static function checks( $key, $label, $options, $values ) {
		echo '<fieldset data-array="' . esc_attr( $key ) . '"><legend>' . esc_html( $label ) . '</legend>';
		foreach ( $options as $id => $text ) {
			echo '<label><input type="checkbox" value="' . esc_attr( $id ) . '" ' . checked( in_array( $id, $values, true ), true, false ) . '> ' . esc_html( $text ) . '</label>'; }
		if ( ! $options ) {
			echo '<p>No records yet. Add them in Farm setup.</p>'; }
		echo '</fieldset>';
	}

	/** Hidden command envelope, never an editable structured-data field.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @param string $id Form ID.
	 * @param bool   $routine Routine entry instead of rotation editor.
	 */
	public static function begin( $kind, $body, $id = '', $routine = false ) {
		echo '<form method="post" class="' . ( $routine ? 'csf-routine-editor' : 'csf-editor' ) . '" data-kind="' . esc_attr( $kind ) . '" id="' . esc_attr( $id ) . '">';
		wp_nonce_field( 'csf_calendar_write' );
		self::navigation_fields();
		echo '<input type="hidden" name="csf_calendar_command" value="' . esc_attr( $kind ) . '"><input type="hidden" name="body" value="' . esc_attr( wp_json_encode( $body ) ) . '"><input type="hidden" name="facility_revision" value="' . esc_attr( ( self::$reviewed ?? CSF_Scheduling_Schema::settings() )['revision'] ) . '"><input type="hidden" name="command_id" value="' . esc_attr( wp_generate_uuid4() ) . '">';
	}

	/** End a form, appointment saving requires a successful preview.
	 *
	 * @param string $label Button.
	 * @param bool   $appointment Preview first.
	 */
	public static function end( $label, $appointment = false ) {
		if ( $appointment ) {
			echo '<button type="button" class="button csf-preview">Check schedule before saving</button>'; }
		echo '<p class="csf-feedback" role="status" aria-live="polite"></p><button class="button button-primary csf-save"' . ( $appointment ? ' disabled' : '' ) . '>' . esc_html( $label ) . '</button></form>';
	}

	/** Appointment with unrestricted number of explicit riders/ride rows (service bounds apply).
	 *
	 * @param array  $body Original body.
	 * @param array  $ctx Private labels and policy.
	 * @param string $id ID.
	 */
	public static function appointment_advanced( $body, $ctx, $id = '' ) {
		if ( ( ! CSF_Scheduling_Access::can( 'appointment_edit' ) && ( ! $body['expected'] || ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) ) || ( 'confirmed' === ( $body['state'] ?? '' ) && ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) ) {
			return; }
		self::begin( 'appointment_saved', $body, $id );
		self::input( 'title', 'Appointment title', $body['title'] );
		self::select( 'service_type', 'Service', $ctx['services'], $body['service_type'] );
		$states = array(
			'requested' => 'requested',
			'held'      => 'held',
			'confirmed' => 'confirmed',
		);
		if ( ! CSF_Scheduling_Access::can( 'csf_schedule_confirm' ) && 'confirmed' !== $body['state'] ) {
			unset( $states['confirmed'] ); }
		if ( ! CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
			unset( $states['requested'], $states['held'] );}
		self::select( 'state', 'State', $states, $body['state'] );
		self::time( 'start', 'Appointment start', $body['start'] );
		self::time( 'end', 'Appointment end', $body['end'] );
		self::time( 'hold_expires', 'Hold expires (held only)', $body['hold_expires'], true );
		if ( $body['expected'] ) {
			echo '<button type="button" class="button csf-shift">Move end and all turns to match the new start</button>'; }
		self::checks( 'resources', 'Instructor, arena and location', $ctx['resources'], $body['resources'] );
		self::checks( 'riders', 'Riders attending', $ctx['riders'], $body['riders'] );
		self::checks( 'pool', 'Approved horse pool for these ride rows', $ctx['horses'], array_values( array_unique( array_column( $body['rides'], 'horse' ) ) ) );
		echo '<p>Choose a horse pool, then add one row for each rider’s turn. Unchecked horses cannot be assigned. Suitability and rest are checked by the server.</p><div class="csf-rows" data-rows="rides">';
		foreach ( $body['rides'] as $ride ) {
			self::ride( $ride, $ctx ); }
		echo '</div><template class="csf-row-template">';
		self::ride(
			array(
				'rider' => null,
				'horse' => '',
				'start' => '',
				'end'   => '',
			),
			$ctx
		);
		echo '</template>';
		echo '<label>Recovery minutes before the next added turn <input type="number" data-recovery value="0" min="0" max="1440"></label><button type="button" class="button csf-add-row">Add rider / horse turn</button><p class="csf-impact" aria-live="polite"></p><p>Confirmation reserves time after direct agreement. PRE-RIDE BLOCK remains until rider evidence is verified. A preview does not reserve resources.</p>';
		self::end( $body['expected'] ? 'Save appointment' : 'Create appointment', true );
	}

	/** One explicit ride row.
	 *
	 * @param array $ride Ride.
	 * @param array $ctx Labels.
	 */
	public static function ride( $ride, $ctx ) {
		echo '<fieldset class="csf-row"><legend>Horse turn</legend>';
		self::select( 'rider', 'Rider for this turn', array( '' => 'No rider (horse training)' ) + $ctx['riders'], $ride['rider'] );
		self::select( 'horse', 'Horse for this turn', array( '' => 'Choose a horse from the pool' ) + $ctx['horses'], $ride['horse'] );
		self::time( 'start', 'Turn start', $ride['start'] );
		self::time( 'end', 'Turn end', $ride['end'] );
		echo '<button type="button" class="button csf-remove-row">Remove this turn</button></fieldset>';
	}

	/** Default lesson entry, preserving the advanced editor for group/short turns.
	 *
	 * @param array  $body Appointment.
	 * @param array  $ctx Visible choices.
	 * @param string $id Form identity.
	 */
	public static function appointment( $body, $ctx, $id = '' ) {
		if ( ! CSF_Scheduling_Booking::simple( $body ) ) {
			self::appointment_advanced( $body, $ctx, $id );
			return;
		}
		if ( ! CSF_Scheduling_Access::can( 'appointment_edit' ) || ( 'confirmed' === $body['state'] && ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) ) {
			self::appointment_advanced( $body, $ctx, $id );
			return;
		}
		$wire                 = $body;
		$wire['_routine']     = true;
		$wire['single_rider'] = $body['single_rider'] ?? ( $body['riders'][0] ?? '' );
		$wire['single_horse'] = $body['single_horse'] ?? ( $body['rides'][0]['horse'] ?? '' );
		self::begin( 'appointment_saved', $wire, $id, true );
		self::input( 'title', 'Appointment title', $body['title'] );
		self::select( 'service_type', 'Lesson type', $ctx['services'], $body['service_type'] );

		$states = array(
			'requested' => 'Requested — no time reserved',
			'held'      => 'Held — temporary reservation',
		);
		if ( CSF_Scheduling_Access::can( 'appointment_confirm' ) ) {
			$states['confirmed'] = 'Confirmed';
		}
		self::select( 'state', 'Booking state', $states, $body['state'] );
		$start  = self::local_value( $body['start'] );
		$local  = is_array( $start ) ? ( $start['local'] ?? '' ) : substr( $start ?? '', 0, 16 );
		$choice = is_array( $start ) ? ( $start['choice'] ?? '' ) : ( $start ?? '' );
		echo '<label>Lesson date <input type="date" class="csf-lesson-date" value="' . esc_attr( $local ? substr( $local, 0, 10 ) : ( self::$navigation['date'] ?? '' ) ) . '" required></label>';
		self::select( 'single_rider', 'Rider', array( '' => 'Choose rider' ) + $ctx['riders'], $wire['single_rider'] );
		echo '<div class="csf-routine-horse">';
		self::select( 'single_horse', 'Horse', array( '' => 'Choose horse' ) + $ctx['horses'], $wire['single_horse'] );
		echo '</div>';
		self::checks( 'resources', 'Instructor, arena and location', $ctx['resources'], $body['resources'] );
		echo '<button type="button" class="button csf-load-slots">Show 15-minute start slots</button><button type="button" class="button csf-more-slots" hidden>Show later starts</button><label>Available start <select class="csf-start-slot" required><option value="">Check the selected date and resources first</option></select></label><input type="hidden" class="csf-routine-start" value="' . esc_attr( $local ) . '" data-choice="' . esc_attr( $choice ) . '"><p class="csf-routine-summary" role="status" aria-live="polite">Choose a start to calculate the finish. Long lessons may require shorter turns in Advanced rotation.</p><details class="csf-slot-reasons"><summary>Why other starts are unavailable</summary><ul></ul></details><div class="csf-routine-hold"' . ( 'held' === $body['state'] ? '' : ' hidden' ) . '>';
		self::time( 'hold_expires', 'Hold expires', $body['hold_expires'], true );
		echo '</div>';
		self::end( $body['expected'] ? 'Save appointment' : 'Create appointment', true );
		echo '<details class="csf-advanced-rotation"><summary>Advanced rotation — group lessons or shorter horse turns</summary><p>Use explicit turns for multiple riders/horses or when one horse cannot safely work for the full lesson. Ordinary finish times are calculated above.</p>';
		unset( $body['_routine'], $body['single_rider'], $body['single_horse'] );
		self::appointment_advanced( $body, $ctx, $id ? $id . '-advanced' : '' );
		echo '</details>';
	}

	/** Cancellation is an ordinary form with local times.
	 *
	 * @param array $body Cancellation.
	 */
	public static function cancel( $body ) {
		if ( ! CSF_Scheduling_Access::can( 'appointment_cancel' ) ) {
			return; }
		self::begin( 'appointment_canceled', $body );
		self::select(
			'initiator',
			'Who initiated cancellation?',
			array(
				'client'        => 'Client',
				'staff'         => 'Staff',
				'facility'      => 'Facility',
				'weather'       => 'Weather',
				'horse_welfare' => 'Horse welfare',
			),
			$body['initiator']
		);
		self::input( 'reason', 'Cancellation reason (no medical details)', $body['reason'] );
		self::time( 'notice', 'Actual notice received', $body['notice'] );
		self::time( 'effective', 'Cancellation effective time', $body['effective'] );
		self::end( 'Record cancellation' );
	}

	/** Roster, suitability and resource availability forms.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @param array  $ctx Choices.
	 */
	public static function setup_form( $kind, $body, $ctx ) {
		$cap = array(
			'resource_created'     => 'resource_manage',
			'resource_saved'       => 'resource_manage',
			'rider_saved'          => CSF_Scheduling_Access::can( 'rider_edit' ) ? 'rider_edit' : 'rider_clearance',
			'horse_saved'          => CSF_Scheduling_Access::can( 'suitability_manage' ) ? 'suitability_manage' : 'resource_manage',
			'availability_saved'   => 'availability_manage',
			'restriction_added'    => 'welfare_hold',
			'restriction_released' => 'welfare_release',
		)[ $kind ] ?? null;
		if ( ! $cap || ! CSF_Scheduling_Access::can( $cap ) ) {
			return; }

		self::begin( $kind, $body );
		$d = $body['data'] ?? $body;
		if ( in_array( $kind, array( 'resource_created', 'resource_saved', 'rider_saved', 'horse_saved' ), true ) ) {
			self::input( 'label', 'Name', $d['label'], 'text', 'rider_saved' === $kind ? ! CSF_Scheduling_Access::can( 'rider_edit' ) : ( 'horse_saved' === $kind && ! CSF_Scheduling_Access::can( 'resource_manage' ) ) ); }
		if ( 'resource_created' === $kind ) {
			self::select(
				'kind',
				'Resource type',
				array(
					'instructor' => 'Instructor',
					'arena'      => 'Arena',
					'location'   => 'Off-site location',
					'horse'      => 'School horse availability resource',
				),
				$d['kind']
			); }
		if ( 'resource_saved' === $kind ) {
			self::input( 'active', 'Active for scheduling', $d['active'], 'checkbox' ); }
		if ( 'rider_saved' === $kind ) {
			foreach ( array(
				'adult'              => 'Adult rider',
				'release_verified'   => 'Release evidence checked by staff',
				'guardian_verified'  => 'Guardian permission checked by staff',
				'emergency_verified' => 'Emergency contact evidence checked by staff',
			) as $key => $label ) {
				if ( CSF_Scheduling_Access::can( 'rider_clearance' ) ) {
					self::input( $key, $label, $d[ $key ], 'checkbox' );
				} else {
					echo '<p>' . esc_html( $label . ': ' . ( $d[ $key ] ? 'Yes' : 'No' ) . ' (verification permission required)' ) . '</p>'; }
			}
		}
		if ( 'horse_saved' === $kind ) {
			if ( ! $body['expected'] ) {
				self::select(
					'source',
					'Horse ownership',
					array(
						'client' => 'Client-owned',
						'school' => 'School horse',
					),
					$d['source']
				);
				self::select( 'resource_uuid', 'School horse availability resource (school horses only)', array( '' => 'No school resource for client-owned horse' ) + $ctx['school_resources'], $d['resource_uuid'] ); } else {
				echo '<p>' . esc_html( ucfirst( $d['source'] ) . ' horse · identity retained' ) . '</p>'; }
				self::checks( 'suitable_services', 'Approved services', $ctx['services'], $d['suitable_services'] );
				self::checks( 'suitable_riders', 'Approved riders', $ctx['riders'], $d['suitable_riders'] );
				self::input( 'training_enrolled', 'Enrolled in full training (counts toward facility cap)', $d['training_enrolled'], 'checkbox', ! CSF_Scheduling_Access::can( 'resource_manage' ) );
		}
		if ( 'availability_saved' === $kind ) {
			echo '<p>No intervals means unavailable. Add explicit dates that cover the appointment and any travel buffers.</p><div class="csf-rows" data-rows="intervals">';
			foreach ( $d['intervals'] as $interval ) {
				self::interval( $interval ); }
			echo '</div><template class="csf-row-template">';
			self::interval(
				array(
					'start' => '',
					'end'   => '',
				)
			);
			echo '</template><button type="button" class="button csf-add-row">Add available interval</button>';
		}
		if ( 'restriction_added' === $kind ) {
			self::select( 'target_uuid', 'Horse or resource to protect', array( '' => 'Choose horse or resource' ) + $ctx['targets'], $d['target_uuid'] );
			self::select(
				'reason',
				'Protective block',
				array(
					'welfare'            => 'Horse welfare concern',
					'rest'               => 'Rest / recovery',
					'unavailable'        => 'Unavailable',
					'protected_training' => 'Protected schooling time',
				),
				$d['reason']
			);
			self::time( 'start', 'Block starts', $d['start'] );
			self::time( 'end', 'Block ends', $d['end'] );
		}
		if ( 'restriction_released' === $kind ) {
			self::input( 'reason', 'Reason staff released the block', $body['reason'] ); }
		self::end( 'Save ' . str_replace( array( '_saved', '_created', '_added', '_released', '_' ), array( '', '', '', ' release', ' ' ), $kind ) );
	}

	/** One availability interval.
	 *
	 * @param array $interval Start/end.
	 */
	public static function interval( $interval ) {
		echo '<fieldset class="csf-row"><legend>Available interval</legend>';
		self::time( 'start', 'Available from', $interval['start'] );
		self::time( 'end', 'Available until', $interval['end'] );
		echo '<button type="button" class="button csf-remove-row">Remove interval</button></fieldset>';
	}

	/** Assets only on the private admin page.
	 *
	 * @param string $hook Admin screen.
	 */
	public static function assets( $hook ) {
		if ( 'toplevel_page_csf-scheduling' !== $hook || ! CSF_Scheduling_Access::can() ) {
			return; }
		wp_enqueue_style( 'csf-scheduling-staff', plugins_url( '../assets/staff.css', __FILE__ ), array(), CSF_SCHEDULING_VERSION );
		wp_enqueue_script( 'csf-scheduling-staff', plugins_url( '../assets/staff.js', __FILE__ ), array(), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_script( 'csf-scheduling-routine', plugins_url( '../assets/routine.js', __FILE__ ), array( 'csf-scheduling-staff' ), CSF_SCHEDULING_VERSION, true );
		$p = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
		wp_add_inline_script(
			'csf-scheduling-staff',
			'window.CSFStaff=' . wp_json_encode(
				array(
					'url'      => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'csf_calendar_write' ),
					'services' => $p['services'],
					'workload' => $p['workload'],
					'timezone' => $p['timezone'],
				),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			) . ';',
			'before'
		);
	}

	/** Private context; generic administrators cannot call the page without staff authorization.
	 *
	 * @param array|null $policy Reviewed presentation policy.
	 * @return array
	 */
	public static function context( $policy = null ) {
		if ( ! CSF_Scheduling_Access::can() && ! CSF_Scheduling_Access::writer() ) {
			return array(); }
		global $wpdb;
		$t   = CSF_Scheduling_Schema::table( 'resources' );
		$ctx = array(
			'resource_rows'    => CSF_Scheduling_Access::can( 'resource_view' ) ? $wpdb->get_results( "SELECT * FROM `$t` ORDER BY kind,label", ARRAY_A ) : array(),
			'rider_rows'       => CSF_Scheduling_Calendar::documents( 'riders' ),
			'horse_rows'       => CSF_Scheduling_Calendar::documents( 'horses' ),
			'services'         => array(),
			'resources'        => array(),
			'riders'           => array(),
			'horses'           => array(),
			'targets'          => array(),
			'school_resources' => array(),
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$p   = $policy ?? json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
		foreach ( $p['services'] as $key => $service ) {
			if ( $service['enabled'] ) {
				$ctx['services'][ $key ] = ucwords( str_replace( '_', ' ', $key ) ); }
		}
		foreach ( $ctx['resource_rows'] as $r ) {
			$ctx['targets'][ $r['uuid'] ] = $r['kind'] . ': ' . $r['label'];
			if ( 'horse' === $r['kind'] ) {
				$ctx['school_resources'][ $r['uuid'] ] = $r['label']; } else {
				$ctx['resources'][ $r['uuid'] ] = $r['kind'] . ': ' . $r['label'] . ( $r['active'] ? '' : ' (inactive)' ); }
		}
		foreach ( $ctx['rider_rows'] as $r ) {
			$ctx['riders'][ $r['uuid'] ] = $r['data']['label']; }
		foreach ( $ctx['horse_rows'] as $h ) {
			$ctx['horses'][ $h['uuid'] ] = $h['data']['label'] . ' (' . $h['data']['source'] . ')'; }
		$ctx['targets'] += $ctx['horses'];
		return $ctx;
	}

	/** Format original local time without requiring offsets from the user.
	 *
	 * @param mixed $value Time.
	 * @return string
	 */
	public static function display_time( $value ) {
		$value = self::local_value( $value );
		if ( is_array( $value ) ) {
			return str_replace( 'T', ' ', $value['local'] ?? '' ); }
		return str_replace( 'T', ' ', substr( $value ?? '', 0, 16 ) );
	}

	/** Complete staff page.
	 *
	 * @param array|WP_Error|null $result Outcome.
	 * @param array|null          $retry Exact original command.
	 * @param array|null          $draft Unsent edits.
	 */
	public static function render( $result, $retry, $draft ) {
		if ( ! CSF_Scheduling_Access::can() && ! CSF_Scheduling_Access::writer() ) {
			wp_die( 'Private scheduling access required.', '', array( 'response' => 403 ) ); }
		self::$reviewed = CSF_Scheduling_Schema::settings();
		try {
			self::page( $result, $retry, $draft );
		} finally {
			self::$reviewed = null; }
	}

	/** Render against a single presentation policy/revision snapshot.
	 *
	 * @param array|WP_Error|null $result Outcome.
	 * @param array|null          $retry Original command.
	 * @param array|null          $draft Unsent edits.
	 */
	private static function page( $result, $retry, $draft ) {
		if ( ! CSF_Scheduling_Access::can() && ! CSF_Scheduling_Access::writer() ) {
			wp_die( 'Private scheduling access required.', '', array( 'response' => 403 ) ); }
		if ( isset( $_POST['csf_calendar_view'] ) ) {
			check_admin_referer( 'csf_calendar_view' ); }
		nocache_headers();
		$p   = json_decode( self::$reviewed['policy_json'], true );
		$ctx = self::context( $p );
		wp_add_inline_script(
			'csf-scheduling-staff',
			'Object.assign(window.CSFStaff||{},' . wp_json_encode(
				array(
					'services' => $p['services'],
					'workload' => $p['workload'],
					'timezone' => $p['timezone'],
				),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			) . ');',
			'before'
		);
		$nav = isset( $_POST['csf_calendar_view'] ) ? $_POST : $_GET;
		if ( isset( $_POST['calendar_date'] ) ) {
			check_admin_referer( 'csf_calendar_write' );
			$nav = array(
				'date'     => isset( $_POST['calendar_date'] ) && is_string( $_POST['calendar_date'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_date'] ) ) : '',
				'view'     => isset( $_POST['calendar_view'] ) && is_string( $_POST['calendar_view'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_view'] ) ) : '',
				'resource' => isset( $_POST['calendar_resource'] ) && is_string( $_POST['calendar_resource'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_resource'] ) ) : '',
			); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Typed and sanitized below.
		} // phpcs:ignore WordPress.Security.NonceVerification -- Controller verifies navigation POST, GET is read-only.
		$date             = isset( $nav['date'] ) && is_string( $nav['date'] ) ? sanitize_text_field( wp_unslash( $nav['date'] ) ) : wp_date( 'Y-m-d', null, new DateTimeZone( $p['timezone'] ?? 'UTC' ) );
		$view             = isset( $nav['view'] ) && 'week' === $nav['view'] ? 'week' : 'day';
		$filter           = ( isset( $_POST['csf_calendar_view'] ) || isset( $_POST['calendar_date'] ) ) && isset( $nav['resource'] ) && is_string( $nav['resource'] ) ? sanitize_text_field( wp_unslash( $nav['resource'] ) ) : '';
		self::$navigation = array(
			'date'     => $date,
			'view'     => $view,
			'resource' => $filter,
		);
		$rows             = CSF_Scheduling_Calendar::view( $date, $view, $filter );
		echo '<div class="wrap csf-calendar"><h1>Digital Ranch Staff Calendar</h1><p>All times: <strong>' . esc_html( $p['timezone'] ?? 'Timezone not configured' ) . '</strong>. Confirmation reserves time; rider permission is checked separately.</p><nav aria-label="Calendar sections"><a href="#csf-calendar-list">Appointments</a> · <a href="#csf-create">New appointment</a> · <a href="#csf-farm-setup">Farm setup and horse availability</a>' . ( CSF_Scheduling_Access::can( 'policy_manage' ) ? ' · <a href="#csf-facility-settings">Facility settings</a>' : '' ) . '</nav><noscript><p>Appointment editing requires JavaScript. Viewing remains available.</p></noscript>';
		CSF_Scheduling_Settings::render( self::$reviewed );
		if ( $result ) {
			if ( is_wp_error( $result ) ) {
				echo '<div class="notice notice-error" role="alert"><p>' . esc_html( self::message( $result ) ) . '</p></div>'; } else {
				echo '<div class="notice notice-success" role="status"><p>Command committed.</p></div>'; }
		}
		if ( is_wp_error( $result ) && $retry ) {
			echo '<details><summary>Retry the original command if the save result is uncertain</summary><form method="post">';
			wp_nonce_field( 'csf_calendar_write' );
			self::navigation_fields();
			foreach ( array(
				'csf_exact_retry'      => '1',
				'csf_calendar_command' => $retry['kind'],
				'body'                 => wp_json_encode( $retry['body'] ),
				'facility_revision'    => $retry['revision'],
				'command_id'           => $retry['command'],
			) as $name => $value ) {
				echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">'; }
			echo '<button class="button">Retry exact command</button></form></details>';
		}
		echo '<form method="post" class="csf-navigation"><input type="hidden" name="csf_calendar_view" value="1">';
		wp_nonce_field( 'csf_calendar_view' );
		echo '<label>Date <input type="date" name="date" value="' . esc_attr( $date ) . '" required></label><label>View <select name="view"><option value="day">Day</option><option value="week" ' . selected( $view, 'week', false ) . '>Week (7 days)</option></select></label><label>Instructor, arena or horse <select name="resource"><option value="">All</option>';
		foreach ( $ctx['resources'] + $ctx['horses'] as $uuid => $label ) {
			echo '<option value="' . esc_attr( $uuid ) . '" ' . selected( $filter, $uuid, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></label><button class="button">Show calendar</button></form><div id="csf-calendar-list" class="appointments">';
		if ( is_wp_error( $rows ) ) {
			echo '<p role="alert">Calendar blocked: configure facility policies and timezone before scheduling.</p>'; } elseif ( ! $rows ) {
			echo '<p>No appointments in this view.</p>'; }
			foreach ( is_array( $rows ) ? $rows : array() as $row ) {
				$d = json_decode( CSF_Scheduling_Calendar::find( 'details', $row['uuid'] )['body_json'], true );
				echo '<article><h2>' . esc_html( $row['title'] ) . '</h2><p>' . esc_html( self::display_time( $row['start'] ) . ' – ' . self::display_time( $row['end'] ) ) . '</p><p>Status: <strong>' . esc_html( $row['state'] ) . ( $row['hold_expired'] ? ' (expired; not reserved)' : '' ) . '</strong></p><p class="' . ( $row['rider_cleared'] ? 'csf-clear' : 'csf-blocked' ) . '">' . ( $row['rider_cleared'] ? ( $d['riders'] ? 'Rider evidence verified' : 'No rider participation recorded' ) : 'PRE-RIDE BLOCK: rider evidence incomplete' ) . '</p><p class="' . ( $row['welfare_review'] ? 'csf-blocked' : '' ) . '">' . ( $row['welfare_review'] ? 'WELFARE BLOCK: staff review required' : 'No recorded welfare restriction' ) . '</p>';
				echo '<p>' . esc_html( implode( ', ', array_map( static fn( $id ) => $ctx['resources'][ $id ] ?? 'Unavailable resource', $d['resources'] ) ) ) . '</p><ul class="csf-rotation">';
				foreach ( $d['rides'] as $ride ) {
					echo '<li>' . esc_html( ( $ctx['riders'][ $ride['rider'] ?? '' ] ?? 'Rider details restricted' ) . ' · ' . ( $ctx['horses'][ $ride['horse'] ] ?? 'Unknown horse' ) . ' · ' . self::display_time( $ride['start'] ) . '–' . self::display_time( $ride['end'] ) ) . '</li>'; }
				echo '</ul>';
				if ( CSF_Scheduling_Access::writer() && 'canceled' !== $row['state'] ) {
					$d['expected'] = $row['revision'];
					echo '<details><summary>Edit or confirm appointment</summary>';
					self::appointment( $d, $ctx );
					echo '</details><details><summary>Cancel appointment</summary>';
					$now = wp_date( 'Y-m-d\TH:iP', null, new DateTimeZone( $p['timezone'] ?? 'UTC' ) );
					self::cancel(
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
				if ( CSF_Scheduling_Access::can( 'csf_schedule_audit' ) ) {
					echo '<details><summary>Appointment history and cancellation record</summary><ul>';
					foreach ( CSF_Scheduling_Calendar::documents( 'history' ) as $event ) {
						if ( $event['entity_uuid'] === $row['uuid'] ) {
							echo '<li>' . esc_html( str_replace( '_', ' ', $event['data']['kind'] ) . ' · ' . $event['created_at_utc'] . ' UTC · staff #' . $event['created_by'] . ( isset( $event['data']['after']['cancellation'] ) ? ' · ' . $event['data']['after']['cancellation']['reason'] : '' ) ) . '</li>'; }
					}
					echo '</ul></details>';
				}
				echo '</article>';
			}
			echo '</div>';
			if ( CSF_Scheduling_Access::writer() ) {
				if ( $draft && is_wp_error( $result ) && 'commit_unknown' !== ( $result->get_error_data()['reason'] ?? '' ) ) {
					echo '<section><h2>Your unsaved draft</h2><p>Compare with current records before previewing. A changed record still requires a fresh edit; it will not be overwritten.</p>';
					if ( 'appointment_saved' === $draft['kind'] ) {
						self::appointment( $draft['body'], $ctx, 'csf-unsent-draft' ); } elseif ( 'appointment_canceled' === $draft['kind'] ) {
										self::cancel( $draft['body'] ); } else {
							self::setup_form( $draft['kind'], $draft['body'], $ctx );
										} echo '</section>';
				}
				if ( CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
					echo '<section id="csf-create"><h2>Create manual appointment</h2>'; }
				self::appointment(
					array(
						'uuid'         => wp_generate_uuid4(),
						'expected'     => 0,
						'title'        => '',
						'service_type' => array_key_first( $ctx['services'] ) ?? '',
						'state'        => 'requested',
						'start'        => '',
						'end'          => '',
						'hold_expires' => null,
						'resources'    => array(),
						'riders'       => array(),
						'rides'        => array(),
					),
					$ctx,
					'csf-new-appointment'
				);
				if ( CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
					echo '</section>'; }
				self::setup( $ctx );
			}
			echo '</div>';
	}

	/** Farm setup remains native fields, with no sample records added automatically.
	 *
	 * @param array $ctx Private labels.
	 */
	public static function setup( $ctx ) {
		echo '<section id="csf-farm-setup"><h2>Farm setup and horse availability</h2><p>Use approved names and evidence only. Do not enter medical documents or contact details. Staff evidence flags are not signed agreements. Missing suitability or availability blocks booking.</p>';
		self::details_form(
			'Add instructor, arena, location or school horse resource',
			'resource_created',
			array(
				'uuid'  => wp_generate_uuid4(),
				'kind'  => 'instructor',
				'label' => '',
			),
			$ctx
		);
		foreach ( $ctx['resource_rows'] as $r ) {
			self::details_form(
				'Resource: ' . $r['label'],
				'resource_saved',
				array(
					'uuid'     => $r['uuid'],
					'expected' => (int) $r['revision'],
					'data'     => array(
						'label'  => $r['label'],
						'active' => (bool) $r['active'],
					),
				),
				$ctx
			);
			$doc  = CSF_Scheduling_Calendar::find( 'availability', $r['uuid'] );
			$data = $doc ? json_decode( $doc['body_json'], true ) : array(
				'resource_uuid' => $r['uuid'],
				'intervals'     => array(),
			);
			echo '<details><summary>Availability: ' . esc_html( $r['label'] ) . ( $data['intervals'] ? ' · intervals recorded' : ' · unavailable until dates are added' ) . '</summary><ul>';
			foreach ( $data['intervals'] as $interval ) {
				echo '<li>' . esc_html( self::display_time( $interval['start'] ) . ' – ' . self::display_time( $interval['end'] ) ) . '</li>';
			} echo '</ul>';
			self::setup_form(
				'availability_saved',
				array(
					'uuid'     => $r['uuid'],
					'expected' => $doc ? (int) $doc['revision'] : 0,
					'data'     => $data,
				),
				$ctx
			);
			echo '</details>';
		}
		foreach ( array(
			'rider_saved' => array(
				'label'              => '',
				'adult'              => false,
				'release_verified'   => false,
				'guardian_verified'  => false,
				'emergency_verified' => false,
			),
			'horse_saved' => array(
				'label'             => '',
				'source'            => 'client',
				'resource_uuid'     => null,
				'suitable_services' => array(),
				'suitable_riders'   => array(),
				'training_enrolled' => false,
			),
		) as $kind => $data ) {
			self::details_form(
				'Add ' . ( 'rider_saved' === $kind ? 'rider' : 'horse' ),
				$kind,
				array(
					'uuid'     => wp_generate_uuid4(),
					'expected' => 0,
					'data'     => $data,
				),
				$ctx
			); }
		foreach ( array(
			'rider_rows' => 'rider_saved',
			'horse_rows' => 'horse_saved',
		) as $key => $kind ) {
			foreach ( $ctx[ $key ] as $doc ) {
				self::details_form(
					( 'rider_rows' === $key ? 'Rider: ' : 'Horse: ' ) . $doc['data']['label'],
					$kind,
					array(
						'uuid'     => $doc['uuid'],
						'expected' => (int) $doc['revision'],
						'data'     => $doc['data'],
					),
					$ctx
				); }
		}
		self::details_form(
			'Add protective block / mark horse unavailable',
			'restriction_added',
			array(
				'uuid'     => wp_generate_uuid4(),
				'expected' => 0,
				'data'     => array(
					'target_uuid' => '',
					'reason'      => 'welfare',
					'start'       => '',
					'end'         => '',
				),
			),
			$ctx
		);
		foreach ( CSF_Scheduling_Calendar::documents( 'restrictions' ) as $doc ) {
			$d = $doc['data'];
			if ( ! empty( $d['released'] ) ) {
				continue; }
			echo '<details><summary>Protective block: ' . esc_html( $ctx['targets'][ $d['target_uuid'] ] ?? 'Resource' ) . '</summary><p>' . esc_html( $d['reason'] . ' · ' . self::display_time( $d['start'] ) . ' – ' . self::display_time( $d['end'] ) ) . '</p>';
			self::setup_form(
				'restriction_released',
				array(
					'uuid'     => $doc['uuid'],
					'expected' => (int) $doc['revision'],
					'reason'   => '',
				),
				$ctx
			);
			echo '</details>';
		}
		echo '</section>';
	}

	/** Foldable setup form.
	 *
	 * @param string $label Label.
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @param array  $ctx Labels.
	 */
	public static function details_form( $label, $kind, $body, $ctx ) {
		echo '<details><summary>' . esc_html( $label ) . '</summary>';
		self::setup_form( $kind, $body, $ctx );
		echo '</details>';
	}
}

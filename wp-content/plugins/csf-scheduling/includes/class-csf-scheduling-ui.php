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
		if ( 'horse_saved' === $kind && isset( $body['data'] ) && is_array( $body['data'] ) ) {
			$body['data'] += array( 'any_rider' => false );
		}
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
			'horse_deleted'        => $common + array(
				'reason'        => 'text',
				'confirm_label' => 'text',
				'irreversible'  => 'bool',
			),
			'horse_status_saved'   => $common + array(
				'archived'      => 'bool',
				'reason'        => 'text',
				'confirm_label' => 'text',
			),
			'horse_saved'          => $common + array(
				'data' => array(
					'label'             => 'text',
					'source'            => 'text',
					'resource_uuid'     => 'id',
					'suitable_services' => 'texts',
					'suitable_riders'   => 'ids',
					'any_rider'         => 'bool',
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
			'buffer_future_only'       => 'Buffer exceptions must be reviewed before teaching starts; historical reservations cannot be waived retroactively.',
			'buffer_mandatory'         => 'Only optional preparation or wrap-up can be reduced. Teaching, mandatory travel, occupied time and horse welfare remain protected.',
			'buffer_snapshot_required' => 'Review and save this lesson with an explicit buffer snapshot before requesting an exception.',
			'visit_required'           => 'Open the family visit to review all surviving lessons and recalculated buffers before saving or canceling.',
			'visit_state'              => 'Every active lesson in a family visit must have the same state and, when held, the same expiry. Review all lessons together.',
			'visit_members_required'   => 'Include every active lesson in this family review; cancel a lesson explicitly rather than omitting it.',
			'resource_conflict'        => 'Already reserved. Change the horse, instructor, arena or time, then preview again.',
			'rider_conflict'           => 'This rider already has an appointment. Choose another time.',
			'outside_hours'            => 'This time is outside facility hours or a resource’s recorded availability. Check availability before continuing.',
			'resource_hours_invalid'   => 'Review the resource date, 15-minute weekly ranges, timezone, reason and repeat end date. Weekly ranges must not overlap; overnight ranges must be split across days.',
			'closure_edit_future_only' => 'Choose a future start time. For a closure that has started, choose tomorrow or a later date so earlier occurrences remain unchanged.',
			'resource_impact_limit'    => 'More than 2,000 future appointments need review. Ask the operator to narrow the resource change before saving.',
			'service_in_use'           => 'This lesson type has protected booking history. Reconcile future bookings or choose a new lesson type before changing its rules.',
			'horse_mapping_in_use'     => 'This horse or its old/new resource still has an ongoing or future appointment, reservation, or unreleased protective block. Reconcile those records through their normal reviewed controls before changing classification. History must not be deleted.',
			'horse_deleted'            => 'This horse UUID was deleted as an unused setup mistake and cannot be recreated. Its audit history is retained.',
			'horse_delete_in_use'      => 'This horse has booking, reservation, protection or lifecycle dependencies. Delete is blocked; use Archive and retain its history.',
			'horse_archived'           => 'This horse is archived and cannot be booked. An authorized manager must review and restore it first.',
			'horse_future_bookings'    => 'This horse has future requests, holds or confirmed lessons. Review and reconcile those appointments before changing archive status. Nothing was canceled or reassigned.',
			'horse_unsafe'             => 'The horse or its resource may be inactive, or suitability is missing. Review the horse’s resource, approved riders and services.',
			'welfare_conflict'         => 'A protective block, workload limit or required horse break prevents this schedule. Review welfare and ride times.',
			'capacity'                 => 'The service capacity or training enrollment limit is exceeded. Reduce the roster or review the approved policy.',
			'policy_incomplete'        => 'Facility policy is incomplete. Ask the pilot operator to finish approved settings before scheduling.',
			'stale_revision'           => 'Someone changed the calendar. Your draft is retained. Review the latest calendar, then preview the draft again.',
			'stale_appointment'        => 'This record changed. Your draft is retained; compare it with the current record before making a new edit.',
			'commit_unknown'           => 'The save result is uncertain. Do not create another appointment. Retry the original command below to recover its result.',
			'ambiguous_time'           => 'This time occurs twice when clocks go back. Choose the first or second occurrence.',
			'nonexistent_time'         => 'This local time does not exist when clocks go forward. Choose another time.',
			'booking_busy'             => 'The slot check took too long. Keep your draft and check starts again.',
			'lesson_service'           => 'Choose an enabled lesson type with a duration in 15-minute increments.',
			'lesson_grid'              => 'Choose a start on the 15-minute grid.',
			'lesson_start'             => 'Choose a date and an available start slot.',
			'lesson_rider'             => 'Choose the rider for this lesson.',
			'lesson_horse'             => 'Choose a suitable horse, or use Advanced rotation for multiple horse turns.',
			'lesson_resource'          => 'Choose every required instructor, arena or location before selecting a start.',
			'hold_expiry'              => 'For a held appointment, choose its actual expiry before the lesson and within 24 hours.',
			'service_duration'         => 'The finish must match the selected lesson type duration. Recheck the service and calculated finish.',
			'invalid_time'             => 'Check local dates, service duration, hold expiry and cancellation notice. Times must be valid in the facility timezone.',
			'forbidden'                => 'Your scheduling permission does not allow this action.',
			'invalid_transition'       => 'This appointment cannot change to that state. Canceled appointments stay in history.',
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
		if ( $body['expected'] && ! isset( $ctx['services'][ $body['service_type'] ] ) ) {
			$p                                        = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
			$ctx['services'][ $body['service_type'] ] = CSF_Scheduling_Catalog::label( $body['service_type'], $p['services'][ $body['service_type'] ] ?? array() ) . ' (disabled; select an enabled type to reschedule)';
		}
		if ( ( ! CSF_Scheduling_Access::can( 'appointment_edit' ) && ( ! $body['expected'] || ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) ) || ( 'confirmed' === ( $body['state'] ?? '' ) && ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) ) {
			return; }
		self::begin( 'appointment_saved', $body, $id );
		echo '<details class="csf-lesson-options"><summary>More options</summary>';
		echo '<label>Custom lesson title (optional) <input data-field="title" type="text" value="' . esc_attr( $body['title'] ) . '" maxlength="180"></label>';
		echo '</details>';
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
		echo '<div class="csf-advanced-hold"' . ( 'held' === $body['state'] ? '' : ' hidden' ) . '>';
		self::time( 'hold_expires', 'Hold expires (held only)', $body['hold_expires'], true );
		echo '</div>';
		if ( $body['expected'] ) {
			echo '<button type="button" class="button csf-shift">Move end and all turns to match the new start</button>'; }
		CSF_Scheduling_UX::resource_choices( $ctx, $body['resources'] );
		self::checks( 'riders', 'Riders attending', $ctx['riders'], $body['riders'] );
		self::checks( 'pool', 'Approved horse pool for these ride rows', $ctx['active_horses'], array_values( array_unique( array_column( $body['rides'], 'horse' ) ) ) );
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
		self::end( $body['expected'] ? 'Save lesson' : 'Schedule lesson', true );
	}

	/** One explicit ride row.
	 *
	 * @param array $ride Ride.
	 * @param array $ctx Labels.
	 */
	public static function ride( $ride, $ctx ) {
		echo '<fieldset class="csf-row"><legend>Horse turn</legend>';
		self::select( 'rider', 'Rider for this turn', array( '' => 'No rider (horse training)' ) + $ctx['riders'], $ride['rider'] );
		self::select( 'horse', 'Horse for this turn', array( '' => 'Choose a horse from the pool' ) + $ctx['active_horses'], $ride['horse'] );
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
		if ( ! empty( $body['buffer_overrides'] ) ) {
			echo '<p>Saving this lesson restores standard buffers. Any new exception needs a separate review; prior approvals remain in history.</p>'; }
		unset( $body['resource_reservations'], $body['buffer_overrides'] ); // Saved evidence is never accepted as client authority.

		if ( $body['expected'] && ! isset( $ctx['services'][ $body['service_type'] ] ) ) {
			$p                                        = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
			$ctx['services'][ $body['service_type'] ] = CSF_Scheduling_Catalog::label( $body['service_type'], $p['services'][ $body['service_type'] ] ?? array() ) . ' (disabled; select an enabled type to reschedule)';
		}
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
		self::select( 'service_type', 'Lesson type', $ctx['services'], $body['service_type'] );

		$states = array(
			'requested' => 'Requested — no time reserved',
			'held'      => 'Held — temporary reservation',
		);
		if ( CSF_Scheduling_Access::can( 'appointment_confirm' ) ) {
			$states['confirmed'] = 'Confirmed — reserves time';
		}
		self::select( 'state', 'Booking state', $states, $body['state'] );
		echo '<p class="csf-reservation-state" role="status"></p>';
		if ( CSF_Scheduling_Access::can( 'policy_manage' ) ) {
			$policy  = json_decode( ( self::$reviewed ?? CSF_Scheduling_Schema::settings() )['policy_json'], true );
			$student = array();
			foreach ( $policy['services'] as $key => $type ) {
				$name = strtolower( preg_replace( '/[^a-zA-Z0-9]/', '', CSF_Scheduling_Catalog::label( $key, $type ) ) );
				foreach ( array( 30, 60 ) as $minutes ) {
					if ( $type['enabled'] && $type['duration_minutes'] === $minutes && 'studentlesson' . $minutes . 'minutes' === $name ) {
						$student[ $minutes ] = true; }
				}
			}
			echo '<p class="csf-lesson-setup-help">' . ( count( $student ) < 2 ? 'Student 30/60 choices are not yet saved and enabled. ' : '' ) . '<a href="' . esc_url( CSF_Scheduling_UX::url( 'types' ) ) . '">Manage Lesson Types</a></p>';
		} elseif ( ! $ctx['services'] ) {
			echo '<p>No enabled lesson types. Ask a farm manager to set them up before booking.</p>';
		}

		$start  = self::local_value( $body['start'] );
		$local  = is_array( $start ) ? ( $start['local'] ?? '' ) : substr( $start ?? '', 0, 16 );
		$choice = is_array( $start ) ? ( $start['choice'] ?? '' ) : ( $start ?? '' );
		echo '<label>Lesson date <input type="date" class="csf-lesson-date" value="' . esc_attr( $local ? substr( $local, 0, 10 ) : ( self::$navigation['date'] ?? '' ) ) . '" required></label>';
		self::select( 'single_rider', 'Rider', array( '' => 'Choose rider' ) + $ctx['riders'], $wire['single_rider'] );
		echo '<div class="csf-routine-horse">';
		self::select( 'single_horse', 'Horse', array( '' => 'Choose horse' ) + $ctx['active_horses'], $wire['single_horse'] );
		echo '</div>';
		CSF_Scheduling_UX::resource_choices( $ctx, $body['resources'] );
		echo '<label>Start time <input type="time" class="csf-lesson-clock" step="900" value="' . esc_attr( $local ? substr( $local, 11, 5 ) : '' ) . '"></label><details class="csf-time-alternatives"><summary>Change time / See alternatives</summary><button type="button" class="button csf-load-slots">Show 15-minute start slots</button><button type="button" class="button csf-more-slots" hidden>Show later starts</button><label>Available start <select class="csf-start-slot" required><option value="">Check the selected date and resources first</option></select></label><input type="hidden" class="csf-routine-start" value="' . esc_attr( $local ) . '" data-choice="' . esc_attr( $choice ) . '"></details><p class="csf-routine-summary" role="status" aria-live="polite">Choose a start to calculate the finish. Long lessons may require shorter turns in Advanced rotation.</p><details class="csf-slot-reasons"><summary>Why other starts are unavailable</summary><ul></ul></details><div class="csf-routine-hold"' . ( 'held' === $body['state'] ? '' : ' hidden' ) . '>';
		self::time( 'hold_expires', 'Hold expires', $body['hold_expires'], true );
		echo '</div>';
		echo '<details class="csf-lesson-options"><summary>More options</summary>';
		echo '<label>Custom lesson title (optional) <input data-field="title" type="text" value="' . esc_attr( $body['title'] ) . '" maxlength="180"></label>';
		echo '</details>';
		self::end( $body['expected'] ? 'Save lesson' : 'Schedule lesson', true );
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
			'horse_status_saved'   => 'resource_manage',
			'horse_deleted'        => 'resource_manage',
			'horse_saved'          => CSF_Scheduling_Access::can( 'suitability_manage' ) ? 'suitability_manage' : 'resource_manage',
			'availability_saved'   => 'availability_manage',
			'restriction_added'    => 'welfare_hold',
			'restriction_released' => 'welfare_release',
		)[ $kind ] ?? null;
		if ( ! $cap || ! CSF_Scheduling_Access::can( $cap ) ) {
			return; }

		if ( 'horse_saved' === $kind ) {
			$body['data'] += array( 'any_rider' => false );
			unset( $body['data']['archived'] ); // Status changes use their own reviewed command.
		}
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
			if ( CSF_Scheduling_Access::can( 'resource_manage' ) ) {
				self::select(
					'source',
					'Booking classification (not legal ownership)',
					array(
						'client' => 'Client-owned',
						'school' => 'School horse',
					),
					$d['source']
				);
				self::select( 'resource_uuid', 'School horse availability resource (school horses only)', array( '' => 'No school resource for client-owned horse' ) + $ctx['school_resources'], $d['resource_uuid'] ); } else {
				echo '<p>' . esc_html( ucfirst( $d['source'] ) . ' booking classification · resource-management permission required to change' ) . '</p>'; }
				if ( $body['expected'] ) {
					echo '<p>Review the booking classification and school resource above before saving. Past and canceled booking history remains unchanged. Ongoing/future lessons, remaining reservations and unreleased protective blocks on the horse or either resource must be reconciled first; this action never cancels or reassigns a lesson or removes a welfare restriction.</p>';
				}
				if ( $body['expected'] && CSF_Scheduling_Access::can( 'resource_manage' ) ) {
					echo '<p>Ongoing/future lessons you can review (limited to the first 201 records). Other protected dependencies may also prevent a correction.</p><ul>';
					try {
						foreach ( CSF_Scheduling_Calendar::horse_mapping_bookings( $body['uuid'] ) as $booking ) {
							$day = CSF_Scheduling_Resource_UI::stored_time( $booking['start'], json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true )['timezone'] )->format( 'Y-m-d' );
							echo '<li><a href="' . esc_url( CSF_Scheduling_UX::url( 'calendar', array( 'date' => $day ) ) ) . '">' . esc_html( self::display_time( $booking['start'] ) . ' · ' . $booking['state'] ) . '</a></li>';
						}
					} catch ( RuntimeException $e ) {
						echo '<li>Unable to load dependencies. Saving will recheck them under the booking lock.</li>';
					}
					echo '</ul>';
				}
				self::checks( 'suitable_services', 'Approved services', $ctx['all_services'] ?? $ctx['services'], $d['suitable_services'] );
				self::input( 'any_rider', 'Any rider — no individual rider list required', $d['any_rider'], 'checkbox', ! CSF_Scheduling_Access::can( 'suitability_manage' ) );
				echo '<p>Includes existing and future riders. Rider clearance, approved services, availability and horse welfare checks still apply. Turn this off to use only the selected riders below.</p>';
				self::checks( 'suitable_riders', 'Approved riders (used when Any rider is off)', $ctx['riders'], $d['suitable_riders'] );
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
		if ( 'horse_status_saved' === $kind ) {
			self::input( 'reason', 'Reason for this change (no medical details)', $body['reason'] );
			self::input( 'confirm_label', 'Type the current horse name to confirm', $body['confirm_label'] );
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
		wp_enqueue_script( 'csf-scheduling-resources', plugins_url( '../assets/resources.js', __FILE__ ), array( 'csf-scheduling-staff' ), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_script( 'csf-scheduling-catalog', plugins_url( '../assets/catalog.js', __FILE__ ), array(), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_script( 'csf-scheduling-routine', plugins_url( '../assets/routine.js', __FILE__ ), array( 'csf-scheduling-staff' ), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_style( 'csf-scheduling-planner', plugins_url( '../assets/planner.css', __FILE__ ), array( 'csf-scheduling-staff' ), CSF_SCHEDULING_VERSION );
		wp_enqueue_script( 'csf-scheduling-ux', plugins_url( '../assets/ux.js', __FILE__ ), array( 'csf-scheduling-planner', 'csf-scheduling-resources' ), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_script( 'csf-scheduling-directory', plugins_url( '../assets/directory.js', __FILE__ ), array( 'csf-scheduling-staff' ), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_style( 'csf-scheduling-directory', plugins_url( '../assets/directory.css', __FILE__ ), array( 'csf-scheduling-staff' ), CSF_SCHEDULING_VERSION );
		wp_enqueue_style( 'csf-scheduling-ux', plugins_url( '../assets/ux.css', __FILE__ ), array( 'csf-scheduling-planner' ), CSF_SCHEDULING_VERSION );
		wp_enqueue_script( 'csf-scheduling-planner', plugins_url( '../assets/planner.js', __FILE__ ), array( 'csf-scheduling-routine' ), CSF_SCHEDULING_VERSION, true );
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
		$t                   = CSF_Scheduling_Schema::table( 'resources' );
		$ctx                 = array(
			'resource_rows'    => CSF_Scheduling_Access::can( 'resource_view' ) ? $wpdb->get_results( "SELECT * FROM `$t` ORDER BY kind,label", ARRAY_A ) : array(),
			'rider_rows'       => CSF_Scheduling_Calendar::documents( 'riders' ),
			'horse_rows'       => CSF_Scheduling_Calendar::documents( 'horses' ),
			'services'         => array(),
			'resources'        => array(),
			'riders'           => array(),
			'horses'           => array(),
			'active_horses'    => array(),
			'targets'          => array(),
			'school_resources' => array(),
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$p                   = $policy ?? json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
		$ctx['services']     = CSF_Scheduling_Catalog::choices( $p['services'] );
		$ctx['all_services'] = array();
		foreach ( $p['services'] as $id => $service ) {
			$ctx['all_services'][ $id ] = CSF_Scheduling_Catalog::label( $id, $service ) . ( $service['enabled'] ? '' : ' (disabled)' );
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
			$ctx['horses'][ $h['uuid'] ] = $h['data']['label'] . ' (' . $h['data']['source'] . ')' . ( empty( $h['data']['archived'] ) ? '' : ' (archived)' );
			if ( empty( $h['data']['archived'] ) ) {
				$ctx['active_horses'][ $h['uuid'] ] = $ctx['horses'][ $h['uuid'] ];
			}
		}
		$ctx['targets'] += $ctx['horses'];
		return $ctx;
	}

	/** Explicit named archive/restore review; no mutations while rendering.
	 *
	 * @param array $horse Horse document.
	 */
	private static function horse_status_form( $horse ) {
		if ( ! CSF_Scheduling_Access::can( 'resource_manage' ) ) {
			return;
		}
		$archive = empty( $horse['data']['archived'] );
		$label   = $horse['data']['label'];
		echo '<details><summary>' . esc_html( ( $archive ? 'Archive horse: ' : 'Restore horse: ' ) . $label ) . '</summary>';
		echo '<p>' . esc_html( $archive ? 'Archive keeps this horse and its history, removes it from new booking choices and active training counts, and does not cancel any appointment. Future appointments must be reconciled first.' : 'Restore rechecks the resource, recorded suitability, active training cap and current protective blocks. Future bookings must still pass all normal availability and welfare checks.' ) . '</p>';
		self::begin(
			'horse_status_saved',
			array(
				'uuid'          => $horse['uuid'],
				'expected'      => (int) $horse['revision'],
				'archived'      => $archive,
				'reason'        => '',
				'confirm_label' => '',
			)
		);
		self::input( 'reason', 'Reason for this change (no medical details)' );
		self::input( 'confirm_label', 'Type the horse name to confirm: ' . $label );
		self::end( $archive ? 'Archive this horse' : 'Restore this horse' );
		echo '</details>';
		if ( $archive ) {
			echo '<details><summary>Delete mistaken unused entry: ' . esc_html( $label ) . '</summary><p>Irreversible removal from the horse roster. All appointment, resource, protective-block and history dependencies are checked under the booking lock. Existing audit/history and a UUID tombstone remain; the associated resource is not deleted. Used horses must be archived instead.</p>';
			self::begin(
				'horse_deleted',
				array(
					'uuid'          => $horse['uuid'],
					'expected'      => (int) $horse['revision'],
					'reason'        => '',
					'confirm_label' => '',
					'irreversible'  => false,
				)
			);
			self::input( 'reason', 'Why this entry was a setup mistake' );
			self::input( 'confirm_label', 'Type the exact horse name: ' . $label );
			self::input( 'irreversible', 'I understand this unused entry will be permanently removed; it cannot be restored through Archive / Restore', false, 'checkbox' );
			self::end( 'Permanently delete this unused entry' );
			echo '</details>';
		}
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
					'resourceKinds' => array_column( $ctx['resource_rows'], 'kind', 'uuid' ),
					'services'      => $p['services'],
					'workload'      => $p['workload'],
					'timezone'      => $p['timezone'],
				),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			) . ');',
			'before'
		);
		$nav = isset( $_POST['csf_calendar_view'] ) ? $_POST : $_GET;
		if ( isset( $_POST['calendar_date'] ) ) {
			check_admin_referer( isset( $_POST['csf_resource_action'] ) ? 'csf_resource_review' : 'csf_calendar_write' );
			$nav = array(
				'date'     => isset( $_POST['calendar_date'] ) && is_string( $_POST['calendar_date'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_date'] ) ) : '',
				'view'     => isset( $_POST['calendar_view'] ) && is_string( $_POST['calendar_view'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_view'] ) ) : '',
				'resource' => isset( $_POST['calendar_resource'] ) && is_string( $_POST['calendar_resource'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_resource'] ) ) : '',
			); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Typed and sanitized below.
		} // phpcs:ignore WordPress.Security.NonceVerification -- Controller verifies navigation POST, GET is read-only.
		$date   = isset( $nav['date'] ) && is_string( $nav['date'] ) ? sanitize_text_field( wp_unslash( $nav['date'] ) ) : wp_date( 'Y-m-d', null, new DateTimeZone( $p['timezone'] ?? 'UTC' ) );
		$view   = isset( $nav['view'] ) && 'week' === $nav['view'] ? 'week' : 'day';
		$filter = isset( $nav['resource'] ) && is_string( $nav['resource'] ) ? sanitize_text_field( wp_unslash( $nav['resource'] ) ) : '';
		if ( $filter && ! isset( $ctx['resources'][ $filter ] ) && ! isset( $ctx['horses'][ $filter ] ) ) {
			$filter = '';
		}
		if ( 'week' === $view && is_string( $p['timezone'] ) ) {
			try {
				CSF_Scheduling_Planner::ticks( $date, $p['timezone'] );
				$anchor = new DateTimeImmutable( $date, new DateTimeZone( $p['timezone'] ) );
				$date   = $anchor->modify( '-' . ( (int) $anchor->format( 'N' ) - 1 ) . ' days' )->format( 'Y-m-d' );
			} catch ( Throwable $error ) {
				$view = 'day'; // The existing read model returns a bounded invalid-date error below.
			}
		}
		self::$navigation = array(
			'date'     => $date,
			'view'     => $view,
			'resource' => $filter,
		);
		$rows             = CSF_Scheduling_Calendar::view( $date, $view, $filter );
		$screen           = CSF_Scheduling_UX::screen();
		echo '<div class="wrap csf-calendar" data-screen="' . esc_attr( $screen ) . '"><h1>Digital Ranch Calendar</h1>';
		CSF_Scheduling_UX::navigation( $screen, $date, $view, $filter );

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
		if ( 'calendar' !== $screen ) {
			CSF_Scheduling_UX::screen_content( $screen, $ctx, $p, self::$reviewed, $date, $view, $filter );
			echo '</div>';
			return;
		}
		echo '<p>All times: ' . esc_html( $p['timezone'] ?? 'Timezone not configured' ) . '. Confirmed reserves time; rider clearance is checked separately.</p>';
		CSF_Scheduling_Buffer_UI::review();
		CSF_Scheduling_Dashboard::render();
		CSF_Scheduling_UX::actions( $date, $view, $filter );
		echo '<form method="post" class="csf-navigation"><input type="hidden" name="csf_calendar_view" value="1">';
		wp_nonce_field( 'csf_calendar_view' );
		echo '<label>Date <input type="date" name="date" value="' . esc_attr( $date ) . '" required></label><label>View <select name="view"><option value="day">Day</option><option value="week" ' . selected( $view, 'week', false ) . '>Week (7 days)</option></select></label><label>Instructor, arena or horse <select name="resource"><option value="">All</option>';
		foreach ( $ctx['resources'] + $ctx['horses'] as $uuid => $label ) {
			echo '<option value="' . esc_attr( $uuid ) . '" ' . selected( $filter, $uuid, false ) . '>' . esc_html( $label ) . '</option>'; }
		echo '</select></label><button class="button">Show calendar</button></form>';
		$details = array();
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$details[ $row['uuid'] ] = json_decode( CSF_Scheduling_Calendar::find( 'details', $row['uuid'] )['body_json'], true );
		}
		CSF_Scheduling_Planner::render( $date, $view, $filter, $rows, $details, $ctx, $p );
		CSF_Scheduling_UX::block_panel( $ctx, $p, self::$reviewed, $date, $view, $filter, $rows );
		if ( 'family' === filter_input( INPUT_GET, 'tool', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) && CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
			echo '<section id="csf-family-panel">';
			CSF_Scheduling_Family_UI::render( $ctx, $p, self::$reviewed );
			echo '</section>';
		}
		echo '<details class="csf-planner-list"><summary>Appointment list and details</summary><div id="csf-calendar-list" class="appointments">';
		if ( is_wp_error( $rows ) ) {
			echo '<p role="alert">Calendar blocked: configure facility policies and timezone before scheduling.</p>'; } elseif ( ! $rows ) {
			echo '<p>No appointments in this view.</p>'; }
			foreach ( is_array( $rows ) ? $rows : array() as $row ) {
				$d      = $details[ $row['uuid'] ];
				$booked = CSF_Scheduling_Catalog::booked( $row['uuid'], $d['service_type'], $p );
				echo '<article id="csf-event-' . esc_attr( $row['uuid'] ) . '"><h2>' . esc_html( $row['title'] ) . '</h2><p>' . esc_html( self::display_time( $row['start'] ) . ' – ' . self::display_time( $row['end'] ) ) . '</p><p>Status: <strong>' . esc_html( $row['state'] ) . ( $row['hold_expired'] ? ' (expired; not reserved)' : '' ) . '</strong></p><p class="' . ( $row['rider_cleared'] ? 'csf-clear' : 'csf-blocked' ) . '">' . ( $row['rider_cleared'] ? ( $d['riders'] ? 'Rider evidence verified' : 'No rider participation recorded' ) : 'PRE-RIDE BLOCK: rider evidence incomplete' ) . '</p><p class="' . ( $row['welfare_review'] ? 'csf-blocked' : '' ) . '">' . ( $row['welfare_review'] ? 'WELFARE BLOCK: staff review required' : 'No recorded welfare restriction' ) . '</p>';
				echo '<p>Lesson type (as booked): ' . esc_html( $booked['label'] ?? 'Unavailable' ) . '</p>';
				echo '<p>' . esc_html( implode( ', ', array_map( static fn( $id ) => $ctx['resources'][ $id ] ?? 'Unavailable resource', $d['resources'] ) ) ) . '</p><ul class="csf-rotation">';
				foreach ( $d['rides'] as $ride ) {
					echo '<li>' . esc_html( ( $ctx['riders'][ $ride['rider'] ?? '' ] ?? 'Rider details restricted' ) . ' · ' . ( $ctx['horses'][ $ride['horse'] ] ?? 'Unknown horse' ) . ' · ' . self::display_time( $ride['start'] ) . '–' . self::display_time( $ride['end'] ) ) . '</li>'; }
				echo '</ul>';
				if ( 'canceled' !== $row['state'] && CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
					echo '<a class="button" href="' . esc_url(
						CSF_Scheduling_UX::url(
							'calendar',
							array(
								'tool' => 'family',
								'date' => $date,
								'view' => $view,
							) + ( empty( $d['visit_uuid'] ) ? array( 'from_lesson' => $row['uuid'] ) : array( 'visit' => $d['visit_uuid'] ) )
						)
					) . '">Book another lesson for this family</a>'; }
				if ( CSF_Scheduling_Access::can( 'appointment_edit' ) && CSF_Scheduling_Access::can( 'calendar_all' ) ) {
					$copy                 = $d;
					$copy['title']        = 'Copy of ' . $d['title'];
					$copy['single_rider'] = $d['riders'][0] ?? '';
					$copy['single_horse'] = $d['rides'][0]['horse'] ?? '';
					$copy['start']        = self::local_value( $d['start'] );
					$copy['end']          = self::local_value( $d['end'] );
					foreach ( $copy['rides'] as &$turn ) {
						$turn['start'] = self::local_value( $turn['start'] );
						$turn['end']   = self::local_value( $turn['end'] );
					}
					unset( $turn, $copy['uuid'], $copy['expected'], $copy['hold_expires'], $copy['visit_uuid'], $copy['resource_reservations'], $copy['buffer_overrides'] );
					echo '<button type="button" class="button csf-planner-duplicate" data-copy-kind="' . ( CSF_Scheduling_Booking::simple( $d ) ? 'routine' : 'advanced' ) . '" data-copy="' . esc_attr( wp_json_encode( $copy ) ) . '">Duplicate as a new requested lesson</button>';
				}
				if ( ! empty( $d['resource_reservations'] ) ) {
					if ( ! empty( $d['visit_uuid'] ) ) {
						echo '<p><a class="button" href="' . esc_url(
							CSF_Scheduling_UX::url(
								'calendar',
								array(
									'tool'  => 'family',
									'visit' => $d['visit_uuid'],
									'date'  => $date,
									'view'  => $view,
								)
							)
						) . '">Open family visit / edit or cancel together</a></p>'; }
					foreach ( $d['resource_reservations'] ?? array() as $window ) {
						echo '<p>' . esc_html( ( $ctx['resources'][ $window['resource_uuid'] ] ?? 'Assigned resource' ) . ' · ' . ( 'canceled' === $row['state'] ? 'Previous reservation: ' : ( 'confirmed' === $row['state'] || ( 'held' === $row['state'] && ! $row['hold_expired'] ) ? 'Reserved: ' : 'Planned, not reserved: ' ) ) . wp_date( 'Y-m-d H:i P', $window['start'], new DateTimeZone( $p['timezone'] ) ) . '–' . wp_date( 'H:i P', $window['end'], new DateTimeZone( $p['timezone'] ) ) . ' · preparation ' . $window['pre_minutes'] . ' min / wrap-up ' . $window['post_minutes'] . ' min' ) . '</p>';
					}
				}
				if ( CSF_Scheduling_Access::writer() && 'canceled' !== $row['state'] && empty( $d['visit_uuid'] ) ) {
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
				CSF_Scheduling_Buffer_UI::marker( $d['buffer_overrides'] ?? array(), $ctx );
				CSF_Scheduling_Buffer_UI::form( $row, $d, $ctx );
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
			echo '</div></details>';
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
					echo '<section id="csf-create"><h2>Schedule lesson</h2>'; }
				self::appointment(
					array(
						'uuid'         => wp_generate_uuid4(),
						'expected'     => 0,
						'title'        => '',
						'service_type' => array_key_first( $ctx['services'] ) ?? '',
						'state'        => CSF_Scheduling_Access::can( 'appointment_confirm' ) ? 'confirmed' : 'requested',
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
				// Farm setup is separately navigable; never emitted on the everyday calendar.
			}
			echo '</div>';
	}

	/** Farm setup remains native fields, with no sample records added automatically.
	 *
	 * @param array $ctx Private labels.
	 */
	public static function setup( $ctx ) {
		echo '<section id="csf-farm-setup"><h2>Riders, horses &amp; resources</h2><p class="csf-directory-intro">Find a record, then choose what you need to change. Booking checks and existing history stay protected.</p><p>Use approved names and evidence only. Do not enter medical documents or contact details. Staff evidence flags are not signed agreements. Missing suitability or availability blocks booking.</p>';
		self::directory_group( 'resources', 'Resources', 'Instructors, arenas, locations and school-horse resources. Edit a record or its dated availability together.' );
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
		echo '<div class="csf-directory-grid">';
		foreach ( $ctx['resource_rows'] as $r ) {
			self::directory_record( $r['label'], ucfirst( $r['kind'] ) . ( $r['active'] ? ' · Active' : ' · Inactive' ) );
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
			echo '<details><summary>Availability: ' . esc_html( $r['label'] ) . ( isset( $data['weekly'] ) && 'legacy' !== $data['weekly']['mode'] ? ' · weekly base configured; dated entries preserved' : ( $data['intervals'] ? ' · intervals recorded' : ' · unavailable until reviewed hours/dates are saved' ) ) . '</summary><ul>';
			foreach ( $data['intervals'] as $interval ) {
				echo '<li>' . esc_html( self::display_time( $interval['start'] ) . ' – ' . self::display_time( $interval['end'] ) ) . '</li>';
			} echo '</ul>';
			self::setup_form(
				'availability_saved',
				array(
					'uuid'     => $r['uuid'],
					'expected' => $doc ? (int) $doc['revision'] : 0,
					'data'     => array_intersect_key( $data, array_flip( array( 'resource_uuid', 'intervals' ) ) ),
				),
				$ctx
			);
			echo '</details></div></details>';
		}
		echo '</div></section>';
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
				'any_rider'         => false,
				'training_enrolled' => false,
			),
		) as $kind => $data ) {
			$is_rider = 'rider_saved' === $kind;
			self::directory_group( $is_rider ? 'riders' : 'horses', $is_rider ? 'Riders' : 'Horses', $is_rider ? 'Rider records and recorded pre-ride evidence. Evidence flags do not replace signed agreements.' : 'Classification, suitability and booking resources. Archive preserves history; it never cancels a lesson.' );
			self::details_form(
				'Add ' . ( $is_rider ? 'rider' : 'horse' ),
				$kind,
				array(
					'uuid'     => wp_generate_uuid4(),
					'expected' => 0,
					'data'     => $data,
				),
				$ctx
			);
			echo '<div class="csf-directory-grid">';
			foreach ( $ctx[ $is_rider ? 'rider_rows' : 'horse_rows' ] as $doc ) {
				$status = $is_rider ? ( $doc['data']['adult'] ? 'Adult rider' : 'Youth rider' ) : ( empty( $doc['data']['archived'] ) ? 'Active' : 'Archived' ) . ' · ' . ( 'school' === $doc['data']['source'] ? 'School horse' : 'Client horse' );
				self::directory_record( $doc['data']['label'], $status );
				self::details_form(
					$is_rider ? 'Edit rider & pre-ride evidence' : 'Edit horse & suitability',
					$kind,
					array(
						'uuid'     => $doc['uuid'],
						'expected' => (int) $doc['revision'],
						'data'     => $doc['data'],
					),
					$ctx
				);
				if ( ! $is_rider && CSF_Scheduling_Access::can( 'resource_manage' ) ) {
					echo '<details class="csf-record-lifecycle"><summary>Archive, restore or remove a mistake</summary><p>Use Archive for a horse with history. Permanent removal is only available for unused setup mistakes, after all dependency checks.</p>';
					self::horse_status_form( $doc );
					echo '</details>';
				}
				echo '</div></details>';
			}
			echo '</div></section>';
		}
		self::directory_group( 'blocks', 'Protective blocks', 'Keep a horse or resource unavailable. These protections remain independent of normal working hours and lesson buffers.' );
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
			if ( ! empty( $d['released'] ) || ! empty( $d['superseded_at'] ) ) {
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
		echo '</section></section>';
	}

	/** Directory section; fully readable without JavaScript.
	 *
	 * @param string $key Section key.
	 * @param string $title Heading.
	 * @param string $description Help.
	 */
	private static function directory_group( $key, $title, $description ) {
		echo '<section class="csf-directory-group" data-directory-group="' . esc_attr( $key ) . '" aria-labelledby="csf-directory-' . esc_attr( $key ) . '"><h3 id="csf-directory-' . esc_attr( $key ) . '">' . esc_html( $title ) . '</h3><p>' . esc_html( $description ) . '</p>';
	}

	/** One named record groups all its actions without granting new access.
	 *
	 * @param string $label Record label.
	 * @param string $status Descriptive status, not riding clearance.
	 */
	private static function directory_record( $label, $status ) {
		echo '<details class="csf-directory-record" data-directory-name="' . esc_attr( $label ) . '"><summary><span class="csf-record-name">' . esc_html( $label ) . '</span><span class="csf-record-status">' . esc_html( $status ) . '</span><span class="csf-record-open">Open record →</span></summary><div class="csf-record-body">';
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


<?php
/** Private appointment and roster service, called only within the foundation lock.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// Private authoritative records deliberately bypass the WordPress object cache.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/** One facility lock orders all configuration, roster and appointment mutations. */
class CSF_Scheduling_Calendar {
	/** Internal bounded visibility-checked read traversal.
	 *
	 * @var bool
	 */
	private static $reading = false;
	const COMMANDS          = array( 'email_sink_transition', 'horse_deleted', 'buffer_overridden', 'notice_acknowledged', 'family_visit_saved', 'resource_hours_saved', 'resource_closure_added', 'resource_closure_edited', 'resource_closure_released', 'resource_saved', 'rider_saved', 'horse_saved', 'horse_status_saved', 'availability_saved', 'restriction_added', 'restriction_released', 'appointment_saved', 'appointment_canceled' );
	const ERRORS            = array( 'horse_deleted', 'horse_delete_in_use', 'buffer_future_only', 'buffer_mandatory', 'buffer_snapshot_required', 'visit_required', 'visit_state', 'visit_members_required', 'forbidden', 'invalid_body', 'invalid_time', 'service_duration', 'lesson_resource', 'policy_incomplete', 'outside_hours', 'resource_conflict', 'rider_conflict', 'horse_unsafe', 'horse_mapping_in_use', 'horse_archived', 'horse_future_bookings', 'welfare_conflict', 'capacity', 'stale_appointment', 'invalid_transition', 'write_failed', 'not_found' );

	/** Fixed private table lookup.
	 *
	 * @param string $name Logical table.
	 * @return string
	 */
	public static function table( $name ) {
		return CSF_Scheduling_Schema::table( $name );
	}

	/** Fail closed with a fixed, non-private reason.
	 *
	 * @param bool   $ok Predicate.
	 * @param string $reason Code.
	 * @throws RuntimeException Failed predicate.
	 */
	public static function require( $ok, $reason = 'invalid_body' ) {
		if ( ! $ok ) {
			throw new RuntimeException( esc_html( $reason ) );
		}
	}

	/** Require exact keys rather than silently discarding misspelled fields.
	 *
	 * @param array $body Object.
	 * @param array $keys Keys.
	 */
	public static function keys( $body, $keys ) {
		self::require( is_array( $body ) && ! array_diff( array_keys( $body ), $keys ) && ! array_diff( $keys, array_keys( $body ) ) );
	}

	/** Plain private labels only.
	 *
	 * @param mixed $value Label.
	 */
	public static function label( $value ) {
		self::require( is_string( $value ) && trim( $value ) && strlen( $value ) <= 200 && sanitize_text_field( $value ) === $value );
	}

	/** Select by UUID; callers must already hold the facility lock for writes.
	 *
	 * @param string $name Table.
	 * @param string $uuid UUID.
	 * @return array|null
	 */
	public static function find( $name, $uuid ) {
		global $wpdb;
		self::require( CSF_Scheduling_Audit::uuid( $uuid ) );
		$t   = self::table( $name );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$t` WHERE uuid=%s", $uuid ), ARRAY_A );
		if ( ! CSF_Scheduling_Store::in_calendar_transaction() && ! self::$reading ) {
			if ( in_array( $name, array( 'appointments', 'details' ), true ) ) {
				$ap = 'appointments' === $name ? $row : self::find( 'appointments', $uuid );
				return $ap && CSF_Scheduling_Permissions::appointment_visible( $ap ) ? $row : null;
			}
			$cap = array(
				'riders'       => 'rider_view',
				'horses'       => 'resource_view',
				'resources'    => 'resource_view',
				'availability' => 'resource_view',
				'restrictions' => 'resource_view',
			)[ $name ] ?? null;
			if ( ! $cap || ! CSF_Scheduling_Access::can( $cap ) || ( 'riders' === $name && ! CSF_Scheduling_Access::can( 'calendar_all' ) ) ) {
				return null; }
		}
		return $row;
	}

	/** Read a coordination envelope; callers must still project its child/recipient scope.
	 *
	 * @param string $uuid Record UUID.
	 * @param string $record_type Explicit coordination document kind.
	 * @return array|null
	 */
	public static function coordination_record( $uuid, $record_type ) {
		global $wpdb;
		self::require( CSF_Scheduling_Access::can( 'calendar_view' ) && CSF_Scheduling_Audit::uuid( $uuid ), 'forbidden' );
		self::require( in_array( $record_type, array( 'family_visit', 'booking_notice' ), true ) );
		$t   = self::table( 'details' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$t` WHERE uuid=%s", $uuid ), ARRAY_A );
		self::require( ! $wpdb->last_error, 'write_failed' );
		return $row && ( json_decode( $row['body_json'], true )['record_type'] ?? null ) === $record_type ? $row : null;
	}

	/** Clear the authorized appointment allocations inside the singleton transaction.
	 *
	 * @param int $id Existing appointment database ID.
	 */
	public static function clear_reservations( $id ) {
		global $wpdb;
		self::require( CSF_Scheduling_Store::in_calendar_transaction() && is_int( $id ) && $id > 0, 'forbidden' );
		foreach ( array( 'allocations', 'slots', 'participants' ) as $name ) {
			self::require( false !== $wpdb->delete( self::table( $name ), array( 'appointment_id' => $id ) ), 'write_failed' );
		}
	}

	/** Read the current actor's acknowledgement without a shared permission cache.
	 *
	 * @param string $uuid Event UUID.
	 * @return bool
	 */
	public static function notice_is_acknowledged( $uuid ) {
		global $wpdb;
		self::require( CSF_Scheduling_Access::can( 'calendar_view' ) && CSF_Scheduling_Audit::uuid( $uuid ), 'forbidden' );
		$t  = self::table( 'history' );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$t` WHERE entity_uuid=%s AND JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.kind'))='notice_acknowledged' AND created_by=%d LIMIT 1", $uuid, get_current_user_id() ) );
		self::require( ! $wpdb->last_error, 'write_failed' );
		return (bool) $id;
	}

	/** Fetch one bounded original-recipient page for subsequent current-scope projection.
	 *
	 * @param int  $before Exclusive notice ID cursor.
	 * @param bool $history Include acknowledged events.
	 * @return array
	 */
	public static function notice_rows( $before, $history ) {
		global $wpdb;
		self::require( CSF_Scheduling_Access::can( 'calendar_view' ), 'forbidden' );
		self::require( is_int( $before ) && $before >= 0 && is_bool( $history ) );
		$t      = self::table( 'details' );
		$h      = self::table( 'history' );
		$unread = $history ? '' : $wpdb->prepare( " AND NOT EXISTS (SELECT 1 FROM `$h` h WHERE h.entity_uuid=`$t`.uuid AND h.created_by=%d AND JSON_UNQUOTE(JSON_EXTRACT(h.body_json,'$.kind'))='notice_acknowledged')", get_current_user_id() );
		$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$t` WHERE JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.record_type'))='booking_notice' AND JSON_CONTAINS(body_json,%s,'$.recipient_ids') AND (%d=0 OR id<%d) $unread ORDER BY id DESC LIMIT 51", wp_json_encode( get_current_user_id() ), $before, $before ), ARRAY_A );
		self::require( is_array( $rows ) && ! $wpdb->last_error, 'write_failed' );
		return $rows;
	}

	/** Checked database write.
	 *
	 * @param string $name Table.
	 * @param array  $data Columns.
	 * @return int ID.
	 */
	public static function insert( $name, $data ) {
		global $wpdb;
		self::require( CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		self::require( 1 === $wpdb->insert( self::table( $name ), $data ), 'write_failed' );
		return (int) $wpdb->insert_id;
	}

	/** Versioned JSON row; append-only history uses a fresh UUID each time.
	 *
	 * @param string $name Table.
	 * @param string $uuid Row UUID.
	 * @param string $entity Entity UUID.
	 * @param array  $body Private details.
	 * @param int    $expected Expected entity revision.
	 * @return int New revision.
	 */
	public static function document( $name, $uuid, $entity, $body, $expected ) {
		global $wpdb;
		self::require( CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		$old = self::find( $name, $uuid );
		self::require( is_int( $expected ) && ( $old ? (int) $old['revision'] : 0 ) === $expected, 'stale_appointment' );
		$data = array(
			'uuid'           => $uuid,
			'entity_uuid'    => $entity,
			'revision'       => $expected + 1,
			'body_json'      => wp_json_encode( $body ),
			'created_by'     => get_current_user_id(),
			'created_at_utc' => CSF_Scheduling_Audit::now(),
		);
		if ( $old ) {
			self::require( 1 === $wpdb->update( self::table( $name ), $data, array( 'id' => $old['id'] ) ), 'write_failed' );
		} else {
			self::insert( $name, $data );
		}
		return $expected + 1;
	}

	/** Dispatch inside Store's transaction, never an independently callable writer.
	 *
	 * @param string $kind Command.
	 * @param array  $body Body.
	 * @param array  $settings Locked settings.
	 * @return array Result.
	 */
	public static function apply( $kind, $body, $settings ) {
		global $wpdb;
		// A raw public PHP call must not bypass the transaction boundary.
		self::require( CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		self::require( CSF_Scheduling_Permissions::authorize( $kind, $body ), 'forbidden' );
		if ( 'email_sink_transition' === $kind ) {
			return CSF_Scheduling_Email_Dispatch::apply( $body );
		}
		$policy = json_decode( $settings['policy_json'], true );
		if ( 'buffer_overridden' === $kind ) {
			return CSF_Scheduling_Buffer_Override::apply( $body, $settings );
		}
		if ( 'notice_acknowledged' === $kind ) {
			return CSF_Scheduling_Notifications::acknowledge( $body );
		}
		if ( 'family_visit_saved' === $kind ) {
			return CSF_Scheduling_Family_Visit::apply( $body, $settings );
		}
		if ( in_array( $kind, CSF_Scheduling_Resource_Hours::COMMANDS, true ) ) {
			return CSF_Scheduling_Resource_Hours::apply( $kind, $body, $settings );
		}
		if ( in_array( $kind, array( 'appointment_saved', 'appointment_canceled' ), true ) ) {
			return self::appointment( $kind, $body, $policy, ! empty( $settings['availability_preview'] ) );
		}
		if ( 'horse_deleted' === $kind ) {
			return self::horse_delete( $body ); }
		if ( 'horse_status_saved' === $kind ) {
			return self::horse_status( $body, $policy );
		}
		if ( 'resource_saved' === $kind ) {
			self::keys( $body, array( 'uuid', 'expected', 'data' ) );
			self::keys( $body['data'], array( 'label', 'active' ) );
			self::label( $body['data']['label'] );
			$old = self::find( 'resources', $body['uuid'] );
			self::require( $old && is_int( $body['expected'] ) && (int) $old['revision'] === $body['expected'] && is_bool( $body['data']['active'] ), 'stale_appointment' );
			self::require(
				1 === $wpdb->update(
					self::table( 'resources' ),
					array(
						'label'    => $body['data']['label'],
						'active'   => (int) $body['data']['active'],
						'revision' => $body['expected'] + 1,
					),
					array( 'id' => $old['id'] )
				),
				'write_failed'
			);
			self::document(
				'history',
				wp_generate_uuid4(),
				$body['uuid'],
				array(
					'kind'   => $kind,
					'before' => array(
						'label'  => $old['label'],
						'active' => (bool) $old['active'],
					),
					'after'  => $body['data'],
				),
				0
			);
			return array(
				'uuid'            => $body['uuid'],
				'entity_revision' => $body['expected'] + 1,
			);
		}
		if ( 'restriction_released' === $kind ) {
			self::keys( $body, array( 'uuid', 'expected', 'reason' ) );
			self::label( $body['reason'] );
			$old = self::find( 'restrictions', $body['uuid'] );
			self::require( $old, 'not_found' );
			$before = json_decode( $old['body_json'], true );
			self::require( empty( $before['released'] ) && empty( $before['superseded_at'] ), 'invalid_transition' );
			$after = $before + array(
				'released'       => true,
				'release_reason' => $body['reason'],
			);
			$next  = self::document( 'restrictions', $body['uuid'], $old['entity_uuid'], $after, $body['expected'] );
			self::document(
				'history',
				wp_generate_uuid4(),
				$body['uuid'],
				array(
					'kind'   => $kind,
					'before' => $before,
					'after'  => $after,
				),
				0
			);
			return array(
				'uuid'            => $body['uuid'],
				'entity_revision' => $next,
			);
		}
		self::keys( $body, array( 'uuid', 'expected', 'data' ) );
		self::require( CSF_Scheduling_Audit::uuid( $body['uuid'] ) && is_int( $body['expected'] ) && is_array( $body['data'] ) );
		$d      = $body['data'];
		$entity = $body['uuid'];
		if ( 'rider_saved' === $kind ) {
			self::keys( $d, array( 'label', 'adult', 'release_verified', 'guardian_verified', 'emergency_verified' ) );
			self::label( $d['label'] );
			foreach ( array( 'adult', 'release_verified', 'guardian_verified', 'emergency_verified' ) as $key ) {
				self::require( is_bool( $d[ $key ] ) );
			}
			$table = 'riders';
		} elseif ( 'horse_saved' === $kind ) {
			$tombstone = self::find( 'details', $body['uuid'] );
			self::require( ! $tombstone || 'horse_tombstone' !== ( json_decode( $tombstone['body_json'], true )['record_type'] ?? null ), 'horse_deleted' );
			self::keys( $d, array( 'label', 'source', 'resource_uuid', 'suitable_services', 'suitable_riders', 'training_enrolled' ) );
			self::label( $d['label'] );
			self::require( in_array( $d['source'], array( 'school', 'client' ), true ) && is_array( $d['suitable_services'] ) && array_is_list( $d['suitable_services'] ) && count( $d['suitable_services'] ) <= 30 && is_bool( $d['training_enrolled'] ) );
			self::require( is_array( $d['suitable_riders'] ) && array_is_list( $d['suitable_riders'] ) && count( $d['suitable_riders'] ) <= 100 );
			foreach ( $d['suitable_riders'] as $rider_uuid ) {
				self::require( self::find( 'riders', $rider_uuid ), 'horse_unsafe' ); }
			foreach ( $d['suitable_services'] as $service ) {
				self::require( is_string( $service ) && isset( $policy['services'][ $service ] ), 'horse_unsafe' );
			}
			if ( 'school' === $d['source'] ) {
				$r = self::find( 'resources', $d['resource_uuid'] );
				self::require( $r && 'horse' === $r['kind'] && (int) $r['active'], 'horse_unsafe' );
				$horses = self::documents( 'horses' );
				foreach ( $horses as $horse ) {
					self::require( $horse['uuid'] === $body['uuid'] || $horse['data']['resource_uuid'] !== $d['resource_uuid'], 'horse_unsafe' );
				}
			} else {
				self::require( null === $d['resource_uuid'] );
			}
			$existing_horse = self::find( 'horses', $body['uuid'] );
			$existing_data  = $existing_horse ? json_decode( $existing_horse['body_json'], true ) : array();
			if ( array_key_exists( 'archived', $existing_data ) ) {
				$d['archived'] = $existing_data['archived'];
			}
			if ( $d['training_enrolled'] && empty( $d['archived'] ) ) {
				self::require( is_int( $policy['training_horse_cap'] ), 'policy_incomplete' );
				$total = 1;
				foreach ( self::documents( 'horses' ) as $horse ) {
					$total += $horse['uuid'] !== $body['uuid'] && empty( $horse['data']['archived'] ) && $horse['data']['training_enrolled'] ? 1 : 0;
				}
				self::require( $total <= $policy['training_horse_cap'], 'capacity' );
			}
			$existing_horse = self::find( 'horses', $body['uuid'] );
			if ( $existing_horse ) {
				$identity = json_decode( $existing_horse['body_json'], true );
				if ( $identity['source'] !== $d['source'] || $identity['resource_uuid'] !== $d['resource_uuid'] ) {
					self::unused_horse_mapping( $existing_horse, $identity, $d, true );
				}
			}
			$table = 'horses';
		} elseif ( 'availability_saved' === $kind ) {
			self::keys( $d, array( 'resource_uuid', 'intervals' ) );
			self::require( self::find( 'resources', $d['resource_uuid'] ) && is_array( $d['intervals'] ) && count( $d['intervals'] ) <= 100 );
			$entity = $d['resource_uuid'];
			// One availability document per resource; an empty list means unavailable.
			self::require( $body['uuid'] === $entity );
			foreach ( $d['intervals'] as $interval ) {
				self::keys( $interval, array( 'start', 'end' ) );
				self::require( CSF_Scheduling_Time::parse( $interval['start'], $policy['timezone'] ) < CSF_Scheduling_Time::parse( $interval['end'], $policy['timezone'] ), 'invalid_time' );
			}
			$table = 'availability';
		} else {
			self::keys( $d, array( 'target_uuid', 'start', 'end', 'reason' ) );
			self::require( self::find( 'resources', $d['target_uuid'] ) || self::find( 'horses', $d['target_uuid'] ), 'not_found' );
			self::require( 0 === $body['expected'] && ! self::find( 'restrictions', $body['uuid'] ) );
			self::require( in_array( $d['reason'], array( 'rest', 'unavailable', 'welfare', 'protected_training' ), true ) );
			self::require( CSF_Scheduling_Time::parse( $d['start'], $policy['timezone'] ) < CSF_Scheduling_Time::parse( $d['end'], $policy['timezone'] ), 'invalid_time' );
			$entity = $d['target_uuid'];
			$table  = 'restrictions';
		}
		$prior_document = self::find( $table, $body['uuid'] );
		if ( 'availability_saved' === $kind && $prior_document ) {
			$prior = json_decode( $prior_document['body_json'], true );
			if ( isset( $prior['weekly'] ) ) {
				$d['weekly'] = $prior['weekly']; }
		}
		$next = self::document( $table, $body['uuid'], $entity, $d, $body['expected'] );
		self::document(
			'history',
			wp_generate_uuid4(),
			$body['uuid'],
			array(
				'kind'   => $kind,
				'before' => $prior_document ? json_decode( $prior_document['body_json'], true ) : null,
				'after'  => $d,
			),
			0
		);
		return array(
			'uuid'            => $body['uuid'],
			'entity_revision' => $next,
		);
	}

	/** Delete only a proven unused setup mistake; retain history and an immutable UUID tombstone.
	 *
	 * @param array $body Validated command body.
	 * @throws RuntimeException If any dependency or write guard fails.
	 */
	private static function horse_delete( $body ) {
		global $wpdb;
		self::require( CSF_Scheduling_Store::in_calendar_transaction() && CSF_Scheduling_Access::can( 'resource_manage' ), 'forbidden' );
		self::keys( $body, array( 'uuid', 'expected', 'reason', 'confirm_label', 'irreversible' ) );
		self::require( CSF_Scheduling_Audit::uuid( $body['uuid'] ) && true === $body['irreversible'] );
		self::label( $body['reason'] );
		$old = self::find( 'horses', $body['uuid'] );
		self::require( $old, 'not_found' );
		self::require( is_int( $body['expected'] ) && (int) $old['revision'] === $body['expected'], 'stale_appointment' );
		$before = json_decode( $old['body_json'], true );
		self::require( $body['confirm_label'] === $before['label'], 'invalid_transition' );
		self::require( empty( $before['archived'] ), 'horse_delete_in_use' );
		try {
			self::unused_horse_mapping( $old, $before, $before ); } catch ( RuntimeException $e ) {
			if ( 'horse_mapping_in_use' === $e->getMessage() ) {
				throw new RuntimeException( 'horse_delete_in_use' );
			} throw $e; }
			// Own setup-edit history is retained. Lifecycle/welfare or unknown history blocks deletion.
			$history = self::table( 'history' );
			$used    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$history` WHERE entity_uuid=%s AND COALESCE(JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.kind')),'')<>'horse_saved' LIMIT 1", $body['uuid'] ) );
			self::require( '' === $wpdb->last_error, 'write_failed' );
			self::require( ! $used, 'horse_delete_in_use' );
			self::require( ! self::find( 'details', $body['uuid'] ) && ! self::find( 'appointments', $body['uuid'] ), 'horse_delete_in_use' );
			$tombstone = array(
				'record_type'      => 'horse_tombstone',
				'label'            => $before['label'],
				'reason'           => $body['reason'],
				'deleted_revision' => $body['expected'] + 1,
			);
			self::document( 'details', $body['uuid'], $body['uuid'], $tombstone, 0 );
			self::document(
				'history',
				wp_generate_uuid4(),
				$body['uuid'],
				array(
					'kind'   => 'horse_deleted',
					'before' => $before,
					'after'  => $tombstone,
				),
				0
			);
		self::require(
			1 === $wpdb->delete(
				self::table( 'horses' ),
				array(
					'id'       => (int) $old['id'],
					'uuid'     => $body['uuid'],
					'revision' => $body['expected'],
				)
			),
			'write_failed'
		);
		return array(
			'uuid'            => $body['uuid'],
			'entity_revision' => $body['expected'] + 1,
			'deleted'         => true,
		);
	}

	/** Archive/restore without deleting a horse or its related history.
	 *
	 * @param array $body   Reviewed lifecycle command.
	 * @param array $policy Facility policy.
	 * @return array
	 */
	private static function horse_status( $body, $policy ) {
		global $wpdb;
		self::keys( $body, array( 'uuid', 'expected', 'archived', 'reason', 'confirm_label' ) );
		self::require( CSF_Scheduling_Store::in_calendar_transaction() && CSF_Scheduling_Access::can( 'resource_manage' ), 'forbidden' );
		self::require( is_bool( $body['archived'] ) );
		self::label( $body['reason'] );
		$old = self::find( 'horses', $body['uuid'] );
		self::require( $old, 'not_found' );
		self::require( is_int( $body['expected'] ) && (int) $old['revision'] === $body['expected'], 'stale_appointment' );
		$before = json_decode( $old['body_json'], true );
		self::require( $body['confirm_label'] === $before['label'] && (bool) ( $before['archived'] ?? false ) !== $body['archived'], 'invalid_transition' );
		// Preserve all existing requests, holds and confirmed future appointments.
		// Nothing is silently canceled or reassigned to make archival possible.
		$appointments = self::table( 'appointments' );
		$details      = self::table( 'details' );
		$future       = $wpdb->get_var( $wpdb->prepare( "SELECT a.id FROM `$appointments` a JOIN `$details` d ON d.uuid=a.uuid WHERE a.state<>'canceled' AND a.end_utc>%s AND d.body_json LIKE %s LIMIT 1", CSF_Scheduling_Audit::now(), '%' . $wpdb->esc_like( $body['uuid'] ) . '%' ) );
		self::require( '' === $wpdb->last_error, 'write_failed' );
		self::require( ! $future, 'horse_future_bookings' );
		if ( ! $body['archived'] ) {
			foreach ( $before['suitable_services'] as $service ) {
				self::require( isset( $policy['services'][ $service ] ), 'horse_unsafe' );
			}
			foreach ( $before['suitable_riders'] as $rider ) {
				self::require( self::find( 'riders', $rider ), 'horse_unsafe' );
			}
			if ( 'school' === $before['source'] ) {
				$resource = self::find( 'resources', $before['resource_uuid'] );
				self::require( $resource && 'horse' === $resource['kind'] && (int) $resource['active'] && self::find( 'availability', $before['resource_uuid'] ), 'horse_unsafe' );
				foreach ( self::documents( 'horses' ) as $horse ) {
					self::require( $horse['uuid'] === $body['uuid'] || $horse['data']['resource_uuid'] !== $before['resource_uuid'], 'horse_unsafe' );
				}
			}
			if ( $before['training_enrolled'] ) {
				self::require( is_int( $policy['training_horse_cap'] ), 'policy_incomplete' );
				$total = 1;
				foreach ( self::documents( 'horses' ) as $horse ) {
					$total += $horse['uuid'] !== $body['uuid'] && empty( $horse['data']['archived'] ) && $horse['data']['training_enrolled'] ? 1 : 0;
				}
				self::require( $total <= $policy['training_horse_cap'], 'capacity' );
			}
			// Current protective blocks must be resolved independently, not by restore.
			$now = new DateTimeImmutable( 'now', new DateTimeZone( $policy['timezone'] ) );
			foreach ( array_filter( array( $body['uuid'], $before['resource_uuid'] ) ) as $target ) {
				self::unrestricted( $target, $now, $now->modify( '+1 second' ), $policy );
			}
		}
		$after             = $before;
		$after['archived'] = $body['archived'];
		$next              = self::document( 'horses', $body['uuid'], $body['uuid'], $after, $body['expected'] );
		self::document(
			'history',
			wp_generate_uuid4(),
			$body['uuid'],
			array(
				'kind'   => 'horse_status_saved',
				'before' => $before,
				'after'  => $after,
				'reason' => $body['reason'],
			),
			0
		);
		return array(
			'uuid'            => $body['uuid'],
			'entity_revision' => $next,
			'archived'        => $body['archived'],
		);
	}

	/** List only currently authorized ongoing/future lessons for reconciliation.
	 *
	 * @param string $uuid Horse UUID.
	 * @return array
	 */
	public static function horse_mapping_bookings( $uuid ) {
		global $wpdb;
		self::require( CSF_Scheduling_Access::can( 'resource_manage' ) && CSF_Scheduling_Audit::uuid( $uuid ), 'forbidden' );
		$appointments = self::table( 'appointments' );
		$details      = self::table( 'details' );
		$rows         = $wpdb->get_results( $wpdb->prepare( "SELECT a.* FROM `$appointments` a JOIN `$details` d ON d.uuid=a.uuid WHERE a.state<>'canceled' AND a.end_utc>%s AND d.body_json LIKE %s ORDER BY a.start_utc LIMIT 201", CSF_Scheduling_Audit::now(), '%' . $wpdb->esc_like( $uuid ) . '%' ), ARRAY_A );
		self::require( is_array( $rows ) && '' === $wpdb->last_error, 'write_failed' );
		$out = array();
		foreach ( $rows as $row ) {
			if ( CSF_Scheduling_Permissions::appointment_visible( $row ) ) {
				$out[] = array(
					'uuid'  => $row['uuid'],
					'start' => $row['start_utc'] . '+00:00',
					'state' => $row['state'],
				);
			}
		}
		return $out;
	}

	/** Correct a reconciled classification; deletion always requires completely unused history.
	 *
	 * Remapping may retain historical rows only after future bookings/reservations and
	 * unreleased protective blocks are reconciled. All reads run under Store's facility
	 * lock, so another command cannot book the old mapping between check and save.
	 *
	 * @param array $horse  Existing horse row.
	 * @param array $before Existing classification.
	 * @param array $after  Proposed classification.
	 * @param bool  $allow_history Preserve historical rows for a classification correction only.
	 */
	private static function unused_horse_mapping( $horse, $before, $after, $allow_history = false ) {
		global $wpdb;
		self::require( CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		$details = self::table( 'details' );
		// UUIDs are validated, immutable identifiers. Conservative matching also keeps
		// canceled and requested appointments protected; they may have no slot rows.
		if ( $allow_history ) {
			$appointments = self::table( 'appointments' );
			$used         = $wpdb->get_var( $wpdb->prepare( "SELECT a.id FROM `$appointments` a JOIN `$details` d ON d.uuid=a.uuid WHERE a.state<>'canceled' AND a.end_utc>%s AND d.body_json LIKE %s LIMIT 1", CSF_Scheduling_Audit::now(), '%' . $wpdb->esc_like( $horse['uuid'] ) . '%' ) );
		} else {
			$used = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$details` WHERE body_json LIKE %s LIMIT 1", '%' . $wpdb->esc_like( $horse['uuid'] ) . '%' ) );
		}
		self::require( '' === $wpdb->last_error, 'write_failed' );
		self::require( ! $used, 'horse_mapping_in_use' );
		if ( ! $allow_history ) {
			$history = self::table( 'history' );
			$used    = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$history` WHERE entity_uuid<>%s AND body_json LIKE %s LIMIT 1", $horse['uuid'], '%' . $wpdb->esc_like( $horse['uuid'] ) . '%' ) );
			self::require( '' === $wpdb->last_error, 'write_failed' );
			self::require( ! $used, 'horse_mapping_in_use' );
		}

		$targets = array_unique( array_filter( array( $horse['uuid'], $before['resource_uuid'], $after['resource_uuid'] ) ) );
		$keys    = array( 0 - (int) $horse['id'] );
		foreach ( $targets as $uuid ) {
			$restrictions = self::table( 'restrictions' );
			$active       = $allow_history ? " AND (JSON_EXTRACT(body_json,'$.released') IS NULL OR JSON_EXTRACT(body_json,'$.released')<>true)" : '';
			$used         = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$restrictions` WHERE entity_uuid=%s $active LIMIT 1", $uuid ) );
			self::require( '' === $wpdb->last_error, 'write_failed' );
			self::require( ! $used, 'horse_mapping_in_use' );
			if ( $uuid !== $horse['uuid'] ) {
				$resource = self::find( 'resources', $uuid );
				self::require( $resource && 'horse' === $resource['kind'], 'horse_unsafe' );
				$keys[] = (int) $resource['id'];
			}
		}
		$future = $allow_history ? $wpdb->prepare( ' AND end_utc>%s', CSF_Scheduling_Audit::now() ) : '';
		foreach ( array_unique( $keys ) as $key ) {
			$slots = self::table( 'slots' );
			$used  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$slots` WHERE resource_id=%d $future LIMIT 1", $key ) );
			self::require( '' === $wpdb->last_error, 'write_failed' );
			self::require( ! $used, 'horse_mapping_in_use' );
			if ( $key > 0 ) {
				$allocations = self::table( 'allocations' );
				$used        = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$allocations` WHERE resource_id=%d $future LIMIT 1", $key ) );
				self::require( '' === $wpdb->last_error, 'write_failed' );
				self::require( ! $used, 'horse_mapping_in_use' );
			}
		}
	}

	/** Private documents; never call from public output or unauthenticated endpoints.
	 *
	 * @param string $name Table.
	 * @return array
	 */
	public static function documents( $name ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Store::in_calendar_transaction() && ! self::$reading ) {
			$cap = array(
				'riders'       => 'rider_view',
				'horses'       => 'resource_view',
				'availability' => 'resource_view',
				'restrictions' => 'resource_view',
				'history'      => 'audit_view',
			)[ $name ] ?? null;
			if ( ! $cap || ! CSF_Scheduling_Access::can( $cap ) || ( 'riders' === $name && ! CSF_Scheduling_Access::can( 'calendar_all' ) ) ) {
				return array(); }
		}
		$t    = self::table( $name );
		$rows = $wpdb->get_results( "SELECT * FROM `$t` ORDER BY id", ARRAY_A );
		if ( 'history' === $name && ! CSF_Scheduling_Store::in_calendar_transaction() && ! self::$reading ) {
			$rows = array_values(
				array_filter(
					$rows,
					static function ( $r ) {
						$d = json_decode( $r['body_json'], true );
						if ( in_array( $d['kind'] ?? '', CSF_Scheduling_Permissions::COMMANDS, true ) ) {
							return false; }
						$a = self::find( 'appointments', $r['entity_uuid'] );
						return $a && CSF_Scheduling_Permissions::appointment_visible( $a );
					}
				)
			);
		}
		return array_map(
			static function ( $row ) {
				$row['data'] = json_decode( $row['body_json'], true );
				unset( $row['body_json'] );
				return $row;
			},
			$rows
		);
	}

	/** Create/edit/cancel with explicit state transitions and independent rider clearance.
	 *
	 * @param string $kind Command.
	 * @param array  $b Body.
	 * @param array  $p Facility policy.
	 * @param bool   $availability Read-only occupied-slot checking.
	 * @return array
	 * @throws CSF_Scheduling_Conflict Missing required resource kind.
	 */
	private static function appointment( $kind, $b, $p, $availability = false ) {
		global $wpdb;
		self::require( CSF_Scheduling_Audit::uuid( $b['uuid'] ?? null ) );
		$old = self::find( 'appointments', $b['uuid'] );
		self::require( is_int( $b['expected'] ?? null ) && ( $old ? (int) $old['revision'] : 0 ) === $b['expected'], 'stale_appointment' );
		self::require( ! $old || 'canceled' !== $old['state'], 'invalid_transition' );
		$previous = self::find( 'details', $b['uuid'] );
		$before   = $previous ? json_decode( $previous['body_json'], true ) : null;
		$visit    = class_exists( 'CSF_Scheduling_Family_Visit' ) ? CSF_Scheduling_Family_Visit::context( $b['uuid'] ) : null;
		$visit    = $visit ?? ( class_exists( 'CSF_Scheduling_Buffer_Override' ) ? CSF_Scheduling_Buffer_Override::context( $b['uuid'] ) : null );
		self::require( empty( $before['visit_uuid'] ) || ( $visit && $visit['visit_uuid'] === $before['visit_uuid'] ), 'visit_required' );
		if ( 'appointment_canceled' === $kind ) {
			self::keys( $b, array( 'uuid', 'expected', 'initiator', 'reason', 'notice', 'effective' ) );
			self::require( $old && in_array( $b['initiator'], array( 'client', 'staff', 'facility', 'weather', 'horse_welfare' ), true ), 'invalid_transition' );
			self::label( $b['reason'] );
			$notice    = CSF_Scheduling_Time::parse( $b['notice'], $old['timezone'] );
			$effective = CSF_Scheduling_Time::parse( $b['effective'], $old['timezone'] );
			self::require( CSF_Scheduling_Time::utc( $notice ) <= CSF_Scheduling_Audit::now() && CSF_Scheduling_Time::utc( $effective ) <= CSF_Scheduling_Audit::now() && $notice <= $effective, 'invalid_time' );
			$data   = array(
				'state'               => 'canceled',
				'revision'            => $b['expected'] + 1,
				'canceled_by'         => get_current_user_id(),
				'notice_received_utc' => CSF_Scheduling_Time::utc( $notice ),
			);
			$detail = array_merge(
				$before,
				array(
					'state'        => 'canceled',
					'cancellation' => $b,
				)
			);
		} else {
			self::keys( $b, array( 'uuid', 'expected', 'title', 'service_type', 'state', 'start', 'end', 'hold_expires', 'resources', 'riders', 'rides' ) );
			self::require( ! CSF_Scheduling_Policy::missing( $p ), 'policy_incomplete' );
			self::label( $b['title'] );
			self::require( in_array( $b['state'], array( 'requested', 'held', 'confirmed' ), true ) );
			if ( 'confirmed' === $b['state'] || ( $old && 'confirmed' === $old['state'] ) ) {
				self::require( CSF_Scheduling_Access::can( 'csf_schedule_confirm' ), 'forbidden' );
			}
			self::require( ! $old || 'confirmed' !== $old['state'] || 'confirmed' === $b['state'], 'invalid_transition' );
			$s = $p['services'][ $b['service_type'] ] ?? null;
			self::require( $s && $s['enabled'], 'policy_incomplete' );
			$start = CSF_Scheduling_Time::parse( $b['start'], $p['timezone'] );
			$end   = CSF_Scheduling_Time::parse( $b['end'], $p['timezone'] );
			self::require( $end > $start && $end->getTimestamp() - $start->getTimestamp() <= 86400, 'invalid_time' );
			self::require( $end->getTimestamp() - $start->getTimestamp() === 60 * $s['duration_minutes'], 'service_duration' );
			// Every appointment is contained in one explicit local opening interval; crossing a closed day is never allowed.
			$open = false;
			foreach ( $p['service_hours'][ (int) $start->format( 'w' ) ] as $interval ) {
				$open = $open || ( $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' ) && $start->format( 'H:i' ) >= $interval[0] && $end->format( 'H:i' ) <= $interval[1] );
			}
			self::require( $open, 'outside_hours' );
			$hold = null;
			if ( 'held' === $b['state'] ) {
				$hold = CSF_Scheduling_Time::utc( CSF_Scheduling_Time::parse( $b['hold_expires'], $p['timezone'] ) );
				self::require( $hold > CSF_Scheduling_Audit::now() && $hold <= CSF_Scheduling_Time::utc( new DateTimeImmutable( '+24 hours' ) ) && $hold <= CSF_Scheduling_Time::utc( $start ), 'invalid_time' );
			} else {
				self::require( null === $b['hold_expires'] );
			}
			foreach ( array( 'resources', 'riders', 'rides' ) as $key ) {
				self::require( is_array( $b[ $key ] ) && array_is_list( $b[ $key ] ) && count( $b[ $key ] ) <= 100 );
			}
			self::require( count( $b['riders'] ) <= $s['capacity'] && count( array_unique( $b['riders'], SORT_REGULAR ) ) === count( $b['riders'] ), 'capacity' );
			$resource_rows = array();
			$kinds         = array();
			foreach ( $b['resources'] as $uuid ) {
				$r = self::find( 'resources', $uuid );
				self::require( $r && (int) $r['active'] && 'horse' !== $r['kind'] && ! isset( $resource_rows[ $uuid ] ), 'not_found' );
				$resource_rows[ $uuid ] = $r;
				$kinds[]                = $r['kind'];
			}
			foreach ( $s['required_resources'] as $required ) {
				if ( 'horse' !== $required && ! in_array( $required, $kinds, true ) ) {
					throw new CSF_Scheduling_Conflict( 'lesson_resource', esc_html( ucfirst( $required ) ) );
				}
			}
			foreach ( $b['riders'] as $uuid ) {
				self::require( self::find( 'riders', $uuid ), 'not_found' );
			}
			$detail = $b + ( $visit ?? array( 'resource_reservations' => self::independent_windows( $b, $s, $p, $start, $end, $resource_rows ) ) );
			$data   = array(
				'uuid'                 => $b['uuid'],
				'title'                => $b['title'],
				'service_type'         => $b['service_type'],
				'state'                => $b['state'],
				'start_utc'            => CSF_Scheduling_Time::utc( $start ),
				'end_utc'              => CSF_Scheduling_Time::utc( $end ),
				'start_local'          => $start->format( 'Y-m-d H:i:s.u' ),
				'end_local'            => $end->format( 'Y-m-d H:i:s.u' ),
				'timezone'             => $p['timezone'],
				'start_offset_seconds' => $start->getOffset(),
				'end_offset_seconds'   => $end->getOffset(),
				'hold_expires_utc'     => $hold,
				'revision'             => $b['expected'] + 1,
				'confirmed_by'         => 'confirmed' === $b['state'] ? get_current_user_id() : null,
			);
			self::validate_allocations( $detail, $s, $p, $start, $end, $resource_rows, $old ? (int) $old['id'] : 0, $availability );
		}
		$id = $old ? (int) $old['id'] : 0;
		if ( $old ) {
			self::require( 1 === $wpdb->update( self::table( 'appointments' ), $data, array( 'id' => $id ) ), 'write_failed' );
		} else {
			$id = self::insert(
				'appointments',
				$data + array(
					'created_by'          => get_current_user_id(),
					'created_at_utc'      => CSF_Scheduling_Audit::now(),
					'canceled_by'         => null,
					'notice_received_utc' => null,
				)
			);
		}
		self::clear_reservations( (int) $id );
		if ( 'appointment_saved' === $kind && 'requested' !== $b['state'] ) {
			$buffer = $s['requires_travel'] ? $p['travel_buffer_minutes'] : 0;
			foreach ( $resource_rows as $r ) {
				list( $resource_start, $resource_end ) = self::reservation_window( $detail, $r['uuid'], $start->modify( "-$buffer minutes" ), $end->modify( "+$buffer minutes" ) );
				self::insert(
					'allocations',
					array(
						'appointment_id' => $id,
						'resource_id'    => $r['id'],
						'start_utc'      => CSF_Scheduling_Time::utc( $resource_start ),
						'end_utc'        => CSF_Scheduling_Time::utc( $resource_end ),
					)
				);
			}
			foreach ( $b['riders'] as $uuid ) {
				$rider = self::find( 'riders', $uuid );
				self::insert(
					'participants',
					array(
						'appointment_id' => $id,
						'rider_id'       => $rider['id'],
						'start_utc'      => CSF_Scheduling_Time::utc( $start->modify( "-$buffer minutes" ) ),
						'end_utc'        => CSF_Scheduling_Time::utc( $end->modify( "+$buffer minutes" ) ),
					)
				);
			}
			foreach ( $b['rides'] as $ride ) {
				$horse = json_decode( self::find( 'horses', $ride['horse'] )['body_json'], true );
				// Client horses also get an independent lock identity, without a fictitious school resource.
				$horse_row   = self::find( 'horses', $ride['horse'] );
				$resource_id = 'school' === $horse['source'] ? (int) self::find( 'resources', $horse['resource_uuid'] )['id'] : 0 - (int) $horse_row['id'];
				// slots use signed resource IDs in v2; school >0, client <0.
				self::insert(
					'slots',
					array(
						'appointment_id' => $id,
						'resource_id'    => $resource_id,
						'start_utc'      => CSF_Scheduling_Time::utc( CSF_Scheduling_Time::parse( $ride['start'], $p['timezone'] )->modify( "-$buffer minutes" ) ),
						'end_utc'        => CSF_Scheduling_Time::utc( CSF_Scheduling_Time::parse( $ride['end'], $p['timezone'] )->modify( "+$buffer minutes" ) ),
					)
				);
			}
		}
		$next = self::document( 'details', $b['uuid'], $b['uuid'], $detail, $b['expected'] );
		self::document(
			'history',
			wp_generate_uuid4(),
			$b['uuid'],
			array(
				'service_snapshot' => CSF_Scheduling_Catalog::for_save( $detail, $before, $p ),
				'kind'             => $kind,
				'before'           => $before,
				'after'            => $detail,
			),
			0
		);
		return array(
			'uuid'                  => $b['uuid'],
			'entity_revision'       => $next,
			'state'                 => $data['state'],
			'resource_reservations' => $detail['resource_reservations'] ?? array(),
			'notice_before'         => $before ? array_intersect_key( $before, array_flip( array( 'state', 'start', 'end', 'resources' ) ) ) : null,
			'notice_kind'           => CSF_Scheduling_Notifications::change( $before, array_merge( $detail, array( 'state' => $data['state'] ) ) ),
			'notice_urgent'         => 'confirmed' === ( $before['state'] ?? null ) && CSF_Scheduling_Notifications::urgent( $before, $detail, time() ),
		);
	}

	/** New/resaved independent lessons use explicit server-derived 15+15 windows.
	 *
	 * @param array             $b Normalized lesson or submitted field values.
	 * @param array             $s Service definition.
	 * @param array             $p Facility policy.
	 * @param DateTimeImmutable $start Teaching boundary instant.
	 * @param DateTimeImmutable $end Teaching boundary instant.
	 * @param array             $rows Selected resource rows keyed by UUID.
	 */
	private static function independent_windows( $b, $s, $p, $start, $end, $rows ) {
		$resources = array();
		$travel    = $s['requires_travel'] ? $p['travel_buffer_minutes'] : 0;
		foreach ( $rows as $uuid => $r ) {
			$resources[] = array(
				'uuid'             => $uuid,
				'kind'             => $r['kind'],
				'mandatory_before' => $travel,
				'mandatory_after'  => $travel,
				'shareable'        => false,
			);
		}
		$plan = CSF_Scheduling_Visit_Plan::calculate(
			array(
				array(
					'uuid'       => $b['uuid'],
					'visit_uuid' => null,
					'start'      => $start->getTimestamp(),
					'end'        => $end->getTimestamp(),
					'resources'  => $resources,
				),
			)
		);
		return $plan['lessons'][0]['reservations'];
	}

	/** Internal saved/planned reservation metadata; never accepted in wire bodies.
	 *
	 * @param array             $detail Saved appointment detail.
	 * @param string            $uuid Stable record UUID.
	 * @param DateTimeImmutable $left Default reservation start.
	 * @param DateTimeImmutable $right Default reservation end.
	 */
	private static function reservation_window( $detail, $uuid, $left, $right ) {
		foreach ( $detail['resource_reservations'] ?? array() as $window ) {
			if ( $uuid === $window['resource_uuid'] ) {
				return array( $left->setTimestamp( $window['start'] ), $right->setTimestamp( $window['end'] ) );
			}
		}
		return array( $left, $right );
	}

	/** Buffer envelope must fit actual facility opening instants, including DST.
	 *
	 * @param DateTimeImmutable $a Reservation boundary instant.
	 * @param DateTimeImmutable $z Reservation boundary instant.
	 * @param array             $p Facility policy.
	 */
	private static function facility_window( $a, $z, $p ) {
		$local = $a->setTimezone( new DateTimeZone( $p['timezone'] ) );
		$open  = false;
		foreach ( $p['service_hours'][ (int) $local->format( 'w' ) ] as $range ) {
			$window = CSF_Scheduling_Resource_Hours::window( $local->format( 'Y-m-d' ), $range[0], $range[1], $p['timezone'] );
			$open   = $open || ( $window && strtotime( $window['start'] ) <= $a->getTimestamp() && strtotime( $window['end'] ) >= $z->getTimestamp() );
		}
		self::require( $open, 'outside_hours' );
	}

	/** Allocation gate, run under the singleton lock even for requested previews.
	 *
	 * @param array             $b Appointment.
	 * @param array             $s Service.
	 * @param array             $p Policy.
	 * @param DateTimeImmutable $start Start.
	 * @param DateTimeImmutable $end End.
	 * @param array             $resources Resolved resources.
	 * @param int               $exclude Existing appointment ID.
	 * @param bool              $availability Read-only occupied-slot checking.
	 * @param bool              $historical Display a historical record without treating archival as a new eligibility failure.
	 * @throws CSF_Scheduling_Conflict Selected resource is reserved.
	 */
	private static function validate_allocations( $b, $s, $p, $start, $end, $resources, $exclude, $availability = false, $historical = false ) {
		global $wpdb;
		$buffer = $s['requires_travel'] ? $p['travel_buffer_minutes'] : 0;
		$left   = $start->modify( "-$buffer minutes" );
		$right  = $end->modify( "+$buffer minutes" );
		foreach ( $resources as $uuid => $r ) {
			list( $resource_start, $resource_end ) = self::reservation_window( $b, $uuid, $left, $right );
			if ( ! empty( $b['resource_reservations'] ) ) {
				self::facility_window( $resource_start, $resource_end, $p );
			}
			self::available( $uuid, $resource_start, $resource_end, $p );
			self::unrestricted( $uuid, $resource_start, $resource_end, $p );
			if ( $availability || 'requested' !== $b['state'] ) {
				if ( self::overlap( 'allocations', 'resource_id', (int) $r['id'], $resource_start, $resource_end, $exclude ) ) {
					throw new CSF_Scheduling_Conflict( 'resource_conflict', esc_html( $r['kind'] . ': ' . $r['label'] ) ); }
			}
		}
		foreach ( $b['riders'] as $uuid ) {
			$r = self::find( 'riders', $uuid );
			if ( $availability || 'requested' !== $b['state'] ) {
				if ( self::overlap( 'participants', 'rider_id', (int) $r['id'], $left, $right, $exclude ) ) {
					throw new CSF_Scheduling_Conflict( 'rider_conflict', esc_html( json_decode( $r['body_json'], true )['label'] ) ); }
			}
		}
		$assigned = array();
		$slots    = array();
		foreach ( $b['rides'] as $ride ) {
			self::keys( $ride, array( 'horse', 'rider', 'start', 'end' ) );
			self::require( null === $ride['rider'] || in_array( $ride['rider'], $b['riders'], true ) );
			$h = self::find( 'horses', $ride['horse'] );
			self::require( $h, 'horse_unsafe' );
			$horse = json_decode( $h['body_json'], true );
			self::require( $historical || empty( $horse['archived'] ), 'horse_archived' );
			self::require( null === $ride['rider'] || in_array( $ride['rider'], $horse['suitable_riders'], true ), 'horse_unsafe' );
			self::require( in_array( $b['service_type'], $horse['suitable_services'], true ) && ( 'either' === $s['horse_source'] || $horse['source'] === $s['horse_source'] ), 'horse_unsafe' );
			$a = CSF_Scheduling_Time::parse( $ride['start'], $p['timezone'] );
			$z = CSF_Scheduling_Time::parse( $ride['end'], $p['timezone'] );
			self::require( $start <= $a && $a < $z && $z <= $end, 'invalid_time' );
			self::unrestricted( $h['uuid'], $a, $z, $p );
			if ( 'school' === $horse['source'] ) {
				$r = self::find( 'resources', $horse['resource_uuid'] );
				self::require( $r && (int) $r['active'], 'horse_unsafe' );
				$key = (int) $r['id'];
				self::available( $horse['resource_uuid'], $a, $z, $p );
				self::unrestricted( $horse['resource_uuid'], $a, $z, $p );
			} else {
				$key = 0 - (int) $h['id'];
			}
			foreach ( $slots as $slot ) {
				self::require( ! ( ( $slot['key'] === $key || ( null !== $ride['rider'] && $slot['rider'] === $ride['rider'] ) ) && $slot['start'] < $z && $slot['end'] > $a ), 'resource_conflict' );
			}
			if ( $availability || 'requested' !== $b['state'] ) {
				if ( self::overlap( 'slots', 'resource_id', $key, $a->modify( "-$buffer minutes" ), $z->modify( "+$buffer minutes" ), $exclude ) ) {
					throw new CSF_Scheduling_Conflict( 'resource_conflict', esc_html( 'Horse: ' . $horse['label'] ) ); }
			}
			$slots[] = array(
				'key'   => $key,
				'rider' => $ride['rider'],
				'start' => $a->modify( "-$buffer minutes" ),
				'end'   => $z->modify( "+$buffer minutes" ),
			);
			if ( null !== $ride['rider'] ) {
				$assigned[] = $ride['rider']; }
		}
		if ( 'none' !== $s['horse_source'] ) {
			self::require( $slots && ! array_diff( $b['riders'], $assigned ), 'horse_unsafe' );
		} else {
			self::require( ! $slots );
		}
		// Evaluate the complete candidate plus existing active rides, not one slot at a time.
		foreach ( array_unique( array_column( $slots, 'key' ) ) as $key ) {
			self::require( ! in_array( null, $p['workload'], true ), 'policy_incomplete' );
			$day_start = $start->setTime( 0, 0 );
			$day_end   = $day_start->modify( '+1 day' );
			$t         = self::table( 'slots' );
			$ap        = self::table( 'appointments' );
			$rows      = $wpdb->get_results( $wpdb->prepare( "SELECT x.start_utc,x.end_utc FROM `$t` x JOIN `$ap` a ON a.id=x.appointment_id WHERE x.resource_id=%d AND a.id<>%d AND (a.state='confirmed' OR (a.state='held' AND a.hold_expires_utc>UTC_TIMESTAMP(6))) AND x.start_utc<%s AND x.end_utc>%s", $key, $exclude, CSF_Scheduling_Time::utc( $day_end->modify( '+1 day' ) ), CSF_Scheduling_Time::utc( $day_start->modify( '-1 day' ) ) ), ARRAY_A );
			$times     = array();
			foreach ( $rows as $row ) {
				$times[] = array( strtotime( $row['start_utc'] . ' UTC' ), strtotime( $row['end_utc'] . ' UTC' ) ); }
			foreach ( $slots as $slot ) {
				if ( $slot['key'] === $key ) {
					$times[] = array( $slot['start']->getTimestamp(), $slot['end']->getTimestamp() ); }
			}
			usort( $times, static fn( $a, $b ) => $a[0] <=> $b[0] );
			$minutes     = 0;
			$chain_start = null;
			$last_end    = null;
			foreach ( $times as $time ) {
				$minutes += max( 0, min( $time[1], $day_end->getTimestamp() ) - max( $time[0], $day_start->getTimestamp() ) ) / 60;
				if ( null === $last_end || ( $time[0] > $last_end && $time[0] - $last_end >= 60 * $p['workload']['break_minutes'] ) ) {
					$chain_start = $time[0]; }
				self::require( $time[1] - $chain_start <= 60 * $p['workload']['consecutive_minutes'], 'welfare_conflict' );
				$last_end = $time[1];
			}
			self::require( $minutes <= $p['workload']['daily_minutes'], 'welfare_conflict' );
		}
	}

	/** Explicit availability is required for all farm resources.
	 *
	 * @param string            $uuid Resource.
	 * @param DateTimeImmutable $a Start.
	 * @param DateTimeImmutable $z End.
	 * @param array             $p Policy.
	 * @throws CSF_Scheduling_Conflict Selected resource is unavailable.
	 */
	private static function available( $uuid, $a, $z, $p ) {
		$doc = self::find( 'availability', $uuid );
		$ok  = $doc && CSF_Scheduling_Resource_Hours::covers( json_decode( $doc['body_json'], true ), $a, $z, $p );
		if ( ! $ok ) {
			$r = self::find( 'resources', $uuid );
			throw new CSF_Scheduling_Conflict( 'outside_hours', $r ? esc_html( ucfirst( $r['kind'] ) . ': ' . $r['label'] ) : 'Selected resource' );
		}
	}

	/** Protective restrictions are allowed over existing bookings; reads flag review.
	 *
	 * @param string            $uuid Target.
	 * @param DateTimeImmutable $a Start.
	 * @param DateTimeImmutable $z End.
	 * @param array             $p Policy.
	 */
	private static function unrestricted( $uuid, $a, $z, $p ) {
		foreach ( self::documents( 'restrictions' ) as $doc ) {
			$d = $doc['data'];
			if ( 'resource_closure' === ( $d['closure_kind'] ?? null ) ) {
				self::require( $doc['entity_uuid'] !== $uuid || ! CSF_Scheduling_Resource_Hours::blocks( $d, $a, $z ), 'welfare_conflict' );
				continue;
			}
			self::require( ! empty( $d['released'] ) || $doc['entity_uuid'] !== $uuid || CSF_Scheduling_Time::parse( $d['start'], $p['timezone'] ) >= $z || CSF_Scheduling_Time::parse( $d['end'], $p['timezone'] ) <= $a, 'welfare_conflict' );
		}
	}

	/** Current locking reads see prior worker commits even with REPEATABLE READ.
	 *
	 * @param string            $name Allocation table.
	 * @param string            $field Fixed key.
	 * @param int               $id Identity.
	 * @param DateTimeImmutable $a Start.
	 * @param DateTimeImmutable $z End.
	 * @param int               $exclude Appointment.
	 * @return bool
	 */
	private static function overlap( $name, $field, $id, $a, $z, $exclude ) {
		global $wpdb;
		$t     = self::table( $name );
		$ap    = self::table( 'appointments' );
		$found = $wpdb->get_var( $wpdb->prepare( "SELECT x.id FROM `$t` x JOIN `$ap` a ON a.id=x.appointment_id WHERE x.`$field`=%d AND a.id<>%d AND x.start_utc<%s AND x.end_utc>%s AND (a.state='confirmed' OR (a.state='held' AND a.hold_expires_utc>UTC_TIMESTAMP(6))) LIMIT 1 FOR UPDATE", $id, $exclude, CSF_Scheduling_Time::utc( $z ), CSF_Scheduling_Time::utc( $a ) ) );
		self::require( ! $wpdb->last_error, 'write_failed' );
		return (bool) $found;
	}

	/** Bounded private calendar read. No rider details or sensitive evidence in overview.
	 *
	 * @param string $date Local date.
	 * @param string $view day or week.
	 * @param string $filter Optional resource UUID.
	 * @return array|WP_Error
	 */
	public static function view( $date, $view = 'day', $filter = '' ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Access::can() ) {
			return new WP_Error( 'forbidden', 'Private staff access required.' ); }
		try {
			$p = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true );
			self::require( is_string( $date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/D', $date ) && in_array( $view, array( 'day', 'week' ), true ) && is_string( $p['timezone'] ), 'invalid_time' );
			$start = new DateTimeImmutable( $date . ' 00:00:00', new DateTimeZone( $p['timezone'] ) );
			self::require( $start->format( 'Y-m-d' ) === $date );
			$end  = $start->modify( 'week' === $view ? '+7 days' : '+1 day' );
			$t    = self::table( 'appointments' );
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$t` WHERE start_utc<%s AND end_utc>%s ORDER BY start_utc,id LIMIT 501", CSF_Scheduling_Time::utc( $end ), CSF_Scheduling_Time::utc( $start ) ), ARRAY_A );
			self::require( count( $rows ) <= 500, 'capacity' );
			$result = array();
			foreach ( $rows as $row ) {
				self::$reading = false;
				if ( ! CSF_Scheduling_Permissions::appointment_visible( $row ) ) {
					continue; }
				self::$reading = true;
				$d             = json_decode( self::find( 'details', $row['uuid'] )['body_json'], true );
				$resources     = $d['resources'];
				$horses        = array_column( $d['rides'], 'horse' );
				if ( $filter && ! in_array( $filter, array_merge( $resources, $horses ), true ) ) {
					continue; }
				$clear = true;
				foreach ( $d['riders'] as $rider ) {
					$r     = json_decode( self::find( 'riders', $rider )['body_json'], true );
					$clear = $clear && $r['release_verified'] && $r['emergency_verified'] && ( $r['adult'] || $r['guardian_verified'] );
				}
				$review = false;
				try {
					$a = new DateTimeImmutable( $row['start_utc'], new DateTimeZone( 'UTC' ) );
					$z = new DateTimeImmutable( $row['end_utc'], new DateTimeZone( 'UTC' ) );
					foreach ( array_merge( $resources, $horses ) as $uuid ) {
						self::unrestricted( $uuid, $a, $z, $p );
						if ( in_array( $uuid, $resources, true ) ) {
							self::available( $uuid, $a, $z, $p ); }
					}
					foreach ( $horses as $uuid ) {
						$h = json_decode( self::find( 'horses', $uuid )['body_json'], true );
						foreach ( $d['rides'] as $ride ) {
							if ( $ride['horse'] === $uuid && null !== $ride['rider'] ) {
								self::require( in_array( $ride['rider'], $h['suitable_riders'], true ), 'horse_unsafe' ); }
						}
						if ( $h['resource_uuid'] ) {
							self::unrestricted( $h['resource_uuid'], $a, $z, $p );
							self::available( $h['resource_uuid'], $a, $z, $p );
						} self::require( in_array( $row['service_type'], $h['suitable_services'], true ), 'horse_unsafe' ); }
					$resolved = array();
					foreach ( $resources as $resource_uuid ) {
						$resolved[ $resource_uuid ] = self::find( 'resources', $resource_uuid );
						self::require( $resolved[ $resource_uuid ] && (int) $resolved[ $resource_uuid ]['active'], 'not_found' ); }
					$service = $p['services'][ $row['service_type'] ] ?? null;
					if ( 'canceled' === $row['state'] || $row['end_utc'] <= CSF_Scheduling_Audit::now() ) {
						$service = CSF_Scheduling_Catalog::booked( $row['uuid'], $row['service_type'], $p )['service'] ?? $service;
					}
					self::require( $service && $service['enabled'] && ! CSF_Scheduling_Policy::missing( $p ), 'policy_incomplete' );
					self::validate_allocations( $d, $service, $p, $a->setTimezone( new DateTimeZone( $row['timezone'] ) ), $z->setTimezone( new DateTimeZone( $row['timezone'] ) ), $resolved, (int) $row['id'], false, 'canceled' === $row['state'] || $row['end_utc'] <= CSF_Scheduling_Audit::now() );
				} catch ( Throwable $error ) {
					$review = true; }
				$result[] = array(
					'uuid'                  => $row['uuid'],
					'title'                 => $row['title'],
					'state'                 => $row['state'],
					'start'                 => $d['start'],
					'end'                   => $d['end'],
					'revision'              => (int) $row['revision'],
					'rider_cleared'         => $clear,
					'welfare_review'        => $review,
					'visit_uuid'            => $d['visit_uuid'] ?? null,
					'resource_reservations' => $d['resource_reservations'] ?? array(),
					'buffer_overrides'      => $d['buffer_overrides'] ?? array(),
					'hold_expired'          => 'held' === $row['state'] && $row['hold_expires_utc'] <= CSF_Scheduling_Audit::now(),
				);
			}
			self::$reading = false;
			return $result;
		} catch ( Throwable $error ) {
			self::$reading = false;
			return new WP_Error( 'calendar_unavailable', 'Set a valid facility timezone and bounded date range.' ); }
	}
}

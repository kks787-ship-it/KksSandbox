<?php
/** Shared transactional mutation and idempotent command service.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

// Authoritative transactional data must bypass WordPress object caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

/** Serializes audited policy, staff and resource changes under the facility row. */
class CSF_Scheduling_Store {
	/** Private request state.
	 *
	 * @var bool Internal dispatcher guard. */
	private static $calendar_transaction = false;
	/** Is the internal calendar dispatcher active?
	 *
	 * @return bool
	 */
	public static function in_calendar_transaction() {
		return self::$calendar_transaction; }

	/** Canonical JSON digest independent of object key ordering.
	 *
	 * @param mixed $value Payload.
	 * @return mixed
	 */
	public static function canonical( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		if ( ! array_is_list( $value ) ) {
			ksort( $value );
		}
		return array_map( array( __CLASS__, 'canonical' ), $value );
	}

	/** Replace facility policy after strict shape validation.
	 *
	 * @param array  $policy Full document.
	 * @param int    $revision Expected singleton revision.
	 * @param string $command Command UUID.
	 * @return array|WP_Error
	 */
	public static function configure( $policy, $revision, $command ) {
		if ( ! CSF_Scheduling_Access::can( 'csf_schedule_manage' ) ) {
			return new WP_Error( 'forbidden', 'Scheduling access required.' );
		}
		$clean = CSF_Scheduling_Policy::validate( $policy );
		return is_wp_error( $clean ) ? $clean : self::change( 'policy_replaced', $clean, $revision, $command );
	}

	/** Add only explicit staff-defined resources, not customers or sample horses.
	 *
	 * @param array  $entry Resource fields.
	 * @param int    $revision Expected singleton revision.
	 * @param string $command Command UUID.
	 * @return array|WP_Error
	 */
	public static function resource( $entry, $revision, $command ) {
		if ( ! CSF_Scheduling_Access::can( 'csf_schedule_manage' ) ) {
			return new WP_Error( 'forbidden', 'Scheduling access required.' );
		}
		if ( ! is_array( $entry ) || array_diff( array_keys( $entry ), array( 'uuid', 'kind', 'label' ) ) || ! CSF_Scheduling_Audit::uuid( $entry['uuid'] ?? null ) || ! in_array( $entry['kind'] ?? null, array( 'horse', 'instructor', 'arena', 'location' ), true ) || ! is_string( $entry['label'] ?? null ) || ! trim( $entry['label'] ) || strlen( $entry['label'] ) > 200 || sanitize_text_field( $entry['label'] ) !== $entry['label'] ) {
			return new WP_Error( 'resource_invalid', 'Provide UUID, kind and a plain bounded private label.' );
		}
		return self::change( 'resource_created', $entry, $revision, $command );
	}

	/** Explicit owner provisioning record; permissions are reconciled by Access.
	 *
	 * @param array  $ids Verified users.
	 * @param bool   $verified Owner acknowledgment.
	 * @param int    $revision Reviewed revision.
	 * @param string $command Command UUID.
	 * @return array|WP_Error
	 */
	public static function staff( $ids, $verified, $revision, $command ) {
		if ( true !== $verified || ! get_current_user_id() || ! current_user_can( 'manage_options' ) || ! is_array( $ids ) || count( array_filter( $ids, 'is_int' ) ) !== count( $ids ) || ! $ids || count( $ids ) > 50 || array_values( array_unique( $ids ) ) !== $ids ) {
			return new WP_Error( 'forbidden', 'Explicit owner verification required.' );
		}
		foreach ( $ids as $id ) {
			if ( ! is_int( $id ) || $id < 1 || ! get_userdata( $id ) ) {
				return new WP_Error( 'forbidden', 'Invalid verified user ID.' );
			}
		}
		return self::change( 'staff_provisioned', $ids, $revision, $command );
	}

	/** Calendar commands share the foundation transaction and command ledger.
	 *
	 * @param string $kind Operation.
	 * @param array  $payload Body.
	 * @param int    $revision Facility revision.
	 * @param string $command UUID.
	 * @return array|WP_Error
	 */
	public static function calendar( $kind, $payload, $revision, $command ) {
		if ( ! in_array( $kind, CSF_Scheduling_Calendar::COMMANDS, true ) || ! is_array( $payload ) || strlen( wp_json_encode( $payload ) ) > 65536 ) {
			return new WP_Error( 'invalid_command', 'Bounded calendar command required.' );
		}
		return self::change( $kind, $payload, $revision, $command );
	}

	/** Internal command dispatch. Every path rechecks caller authorization.
	 *
	 * @param string $kind Fixed operation.
	 * @param array  $payload Validated body.
	 * @param int    $revision Expected singleton revision.
	 * @param string $command UUID.
	 * @return array|WP_Error
	 * @throws RuntimeException Caught locally to roll back all transaction participants.
	 */
	private static function change( $kind, $payload, $revision, $command ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Schema::inspect()['ready'] || ! is_int( $revision ) || $revision < 0 || ! CSF_Scheduling_Audit::uuid( $command ) || ! in_array( $kind, array_merge( array( 'staff_provisioned', 'policy_replaced', 'resource_created' ), CSF_Scheduling_Calendar::COMMANDS ), true ) ) {
			return new WP_Error( 'not_ready', 'Verified schema, command and revision required.' );
		}
		if ( 'staff_provisioned' !== $kind && ! CSF_Scheduling_Access::can( 'csf_schedule_manage' ) ) {
			return new WP_Error( 'forbidden', 'Scheduling access required.' );
		}
		if ( 'policy_replaced' === $kind && is_wp_error( CSF_Scheduling_Policy::validate( $payload ) ) ) {
			return new WP_Error( 'policy_invalid', 'Invalid policy.' );
		}
		$settings = CSF_Scheduling_Schema::table( 'settings' );
		$commands = CSF_Scheduling_Schema::table( 'commands' );
		$hash     = hash( 'sha256', wp_json_encode( self::canonical( array( $kind, $payload, $revision ) ) ) );
		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return new WP_Error( 'transaction_failed', 'Cannot start transaction.' );
		}
		try {
			$row = $wpdb->get_row( "SELECT * FROM `$settings` WHERE id=1 FOR UPDATE", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $row || self::schema_version( $row ) !== CSF_Scheduling_Schema::VERSION ) {
				throw new RuntimeException( 'schema_changed' );
			}
			if ( 'staff_provisioned' !== $kind && ! in_array( get_current_user_id(), json_decode( $row['staff_json'], true ), true ) ) {
				throw new RuntimeException( 'forbidden' );
			}
			$prior = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$commands` WHERE uuid=%s", $command ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $prior ) {
				if ( get_current_user_id() !== (int) $prior['actor_id'] || $hash !== $prior['payload_hash'] || $kind !== $prior['kind'] ) {
					throw new RuntimeException( 'command_conflict' );
				}
				$wpdb->query( 'ROLLBACK' );
				return json_decode( $prior['result_json'], true );
			}
			if ( (int) $row['revision'] !== $revision ) {
				throw new RuntimeException( 'stale_revision' );
			}
			$update = array(
				'revision'       => $revision + 1,
				'updated_by'     => get_current_user_id(),
				'updated_at_utc' => CSF_Scheduling_Audit::now(),
			);
			$result = array( 'revision' => $revision + 1 );
			$entity = $row['uuid'];
			if ( 'staff_provisioned' === $kind ) {
				$previous                 = json_decode( $row['staff_json'], true );
				$result['previous_staff'] = is_array( $previous ) ? array_values( array_filter( $previous, static fn( $id ) => is_int( $id ) && $id > 0 ) ) : array();
				$update['staff_json']     = wp_json_encode( $payload );
			} elseif ( 'policy_replaced' === $kind ) {
				$update['policy_json'] = wp_json_encode( $payload );
			} elseif ( in_array( $kind, CSF_Scheduling_Calendar::COMMANDS, true ) ) {
				self::$calendar_transaction = true;
				try {
					$result = CSF_Scheduling_Calendar::apply( $kind, $payload, $row );
				} finally {
					self::$calendar_transaction = false; }
				$entity             = $result['uuid'];
				$result['revision'] = $revision + 1;
			} else {
				$entity = $payload['uuid'];
				if ( 1 !== $wpdb->insert(
					CSF_Scheduling_Schema::table( 'resources' ),
					$payload + array(
						'active'         => 1,
						'revision'       => 1,
						'created_by'     => get_current_user_id(),
						'created_at_utc' => CSF_Scheduling_Audit::now(),
					)
				) ) {
					throw new RuntimeException( 'resource_failed' );
				}
				$result['resource_uuid'] = $entity;
			}
			$policy = 'policy_replaced' === $kind ? $payload : json_decode( $row['policy_json'], true );
			$zone   = $policy['timezone'] ?? null;
			if ( 1 !== $wpdb->update(
				$settings,
				$update,
				array(
					'id'       => 1,
					'revision' => $revision,
				)
			) || is_wp_error( CSF_Scheduling_Audit::append( $command, $kind, $entity, $revision, $revision + 1, $zone ) ) || 1 !== $wpdb->insert(
				$commands,
				array(
					'uuid'           => $command,
					'actor_id'       => get_current_user_id(),
					'kind'           => $kind,
					'payload_hash'   => $hash,
					'result_json'    => wp_json_encode( $result ),
					'created_at_utc' => CSF_Scheduling_Audit::now(),
				)
			) ) {
				throw new RuntimeException( 'write_failed' );
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				throw new RuntimeException( 'commit_unknown' );
			}
			return $result;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			$reason = in_array( $error->getMessage(), array_merge( CSF_Scheduling_Calendar::ERRORS, array( 'schema_changed', 'command_conflict', 'stale_revision', 'resource_failed', 'write_failed', 'commit_unknown' ) ), true ) ? $error->getMessage() : 'storage_error';
			return new WP_Error( 'scheduling_write_failed', 'Command not acknowledged; retry the same UUID after resolving storage or revision conflict.', array( 'reason' => $reason ) );
		}
	}

	/** Typed version value.
	 *
	 * @param array $row Settings.
	 * @return int
	 */
	private static function schema_version( $row ) {
		return (int) $row['schema_version'];
	}
}

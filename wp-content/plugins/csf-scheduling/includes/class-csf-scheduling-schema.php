<?php
/** Explicit, resumable InnoDB schema. No activation migration.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

// Authoritative transactional data must bypass WordPress object caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

/** Physical schema contract and migration gate. */
class CSF_Scheduling_Schema {
	const VERSION = 3;

	/** Checked table identifiers; never accept a caller's table name.
	 *
	 * @param string $name Logical table.
	 * @return string
	 * @throws RuntimeException Unsupported table identifier.
	 */
	public static function table( $name ) {
		global $wpdb;
		if ( ! preg_match( '/^[a-zA-Z0-9_]+$/D', $wpdb->prefix ) || ! isset( self::definitions()[ $name ] ) || strlen( $wpdb->prefix . 'csf_sched_' . $name ) > 64 ) {
			throw new RuntimeException( 'Unsupported scheduling table identifier.' );
		}
		return $wpdb->prefix . 'csf_sched_' . $name;
	}

	/** Versioned additive schema; v1 tables retain their original physical contract.
	 *
	 * @param int $version Contract version.
	 * @return array
	 */
	public static function definitions( $version = self::VERSION ) {
		$id          = array( 'bigint unsigned', false );
		$uuid        = array( 'char(36)', false );
		$time        = array( 'datetime(6)', false );
		$definitions = array(
			'settings'     => array(
				'columns' => array(
					'id'             => $id,
					'uuid'           => $uuid,
					'schema_version' => $id,
					'revision'       => $id,
					'policy_json'    => array( 'longtext', false ),
					'staff_json'     => array( 'longtext', false ),
					'updated_by'     => $id,
					'updated_at_utc' => $time,
				),
				'indexes' => array(
					'PRIMARY' => array( true, array( 'id' ) ),
					'uuid'    => array( true, array( 'uuid' ) ),
				),
			),
			'resources'    => array(
				'columns' => array(
					'id'             => $id,
					'uuid'           => $uuid,
					'kind'           => array( 'varchar(20)', false ),
					'label'          => array( 'varchar(200)', false ),
					'active'         => array( 'tinyint unsigned', false ),
					'revision'       => $id,
					'created_by'     => $id,
					'created_at_utc' => $time,
				),
				'indexes' => array(
					'PRIMARY'     => array( true, array( 'id' ) ),
					'uuid'        => array( true, array( 'uuid' ) ),
					'kind_active' => array( false, array( 'kind', 'active' ) ),
				),
			),
			'appointments' => array(
				'columns' => array(
					'id'                   => $id,
					'uuid'                 => $uuid,
					'title'                => array( 'varchar(200)', false ),
					'service_type'         => array( 'varchar(40)', false ),
					'state'                => array( 'varchar(20)', false ),
					'start_utc'            => $time,
					'end_utc'              => $time,
					'start_local'          => $time,
					'end_local'            => $time,
					'timezone'             => array( 'varchar(64)', false ),
					'start_offset_seconds' => array( 'int', false ),
					'end_offset_seconds'   => array( 'int', false ),
					'hold_expires_utc'     => array( 'datetime(6)', true ),
					'revision'             => $id,
					'created_by'           => $id,
					'confirmed_by'         => array( 'bigint unsigned', true ),
					'canceled_by'          => array( 'bigint unsigned', true ),
					'notice_received_utc'  => array( 'datetime(6)', true ),
					'created_at_utc'       => $time,
				),
				'indexes' => array(
					'PRIMARY'    => array( true, array( 'id' ) ),
					'uuid'       => array( true, array( 'uuid' ) ),
					'state_time' => array( false, array( 'state', 'start_utc', 'end_utc' ) ),
				),
			),
			'allocations'  => array(
				'columns' => array(
					'id'             => $id,
					'appointment_id' => $id,
					'resource_id'    => $id,
					'start_utc'      => $time,
					'end_utc'        => $time,
				),
				'indexes' => array(
					'PRIMARY'              => array( true, array( 'id' ) ),
					'appointment_resource' => array( true, array( 'appointment_id', 'resource_id' ) ),
					'resource_time'        => array( false, array( 'resource_id', 'start_utc', 'end_utc' ) ),
				),
			),
			'commands'     => array(
				'columns' => array(
					'uuid'           => $uuid,
					'actor_id'       => $id,
					'kind'           => array( 'varchar(40)', false ),
					'payload_hash'   => array( 'char(64)', false ),
					'result_json'    => array( 'longtext', false ),
					'created_at_utc' => $time,
				),
				'indexes' => array( 'PRIMARY' => array( true, array( 'uuid' ) ) ),
			),
			'audit'        => array(
				'columns' => array(
					'id'                => $id,
					'uuid'              => $uuid,
					'command_uuid'      => $uuid,
					'actor_id'          => $id,
					'action'            => array( 'varchar(48)', false ),
					'entity_uuid'       => $uuid,
					'old_revision'      => $id,
					'new_revision'      => $id,
					'occurred_at_utc'   => $time,
					'occurred_at_local' => array( 'datetime(6)', true ),
					'timezone'          => array( 'varchar(64)', true ),
				),
				'indexes' => array(
					'PRIMARY'      => array( true, array( 'id' ) ),
					'uuid'         => array( true, array( 'uuid' ) ),
					'command_uuid' => array( true, array( 'command_uuid' ) ),
					'entity_time'  => array( false, array( 'entity_uuid', 'occurred_at_utc' ) ),
				),
			),
		);
		if ( $version >= 2 ) {
			foreach ( array( 'riders', 'horses', 'availability', 'restrictions', 'details', 'history' ) as $name ) {
				$definitions[ $name ] = array(
					'columns' => array(
						'id'             => $id,
						'uuid'           => $uuid,
						'entity_uuid'    => $uuid,
						'revision'       => $id,
						'body_json'      => array( 'longtext', false ),
						'created_by'     => $id,
						'created_at_utc' => $time,
					),
					'indexes' => array(
						'PRIMARY' => array( true, array( 'id' ) ),
						'uuid'    => array( true, array( 'uuid' ) ),
						'entity'  => array( false, array( 'entity_uuid' ) ),
					),
				);
			}
			$definitions['slots']        = array(
				'columns' => array(
					'id'             => $id,
					'appointment_id' => $id,
					'resource_id'    => array( 'bigint', false ),
					'start_utc'      => $time,
					'end_utc'        => $time,
				),
				'indexes' => array(
					'PRIMARY'       => array( true, array( 'id' ) ),
					'resource_time' => array( false, array( 'resource_id', 'start_utc', 'end_utc' ) ),
					'appointment'   => array( false, array( 'appointment_id' ) ),
				),
			);
			$definitions['participants'] = array(
				'columns' => array(
					'id'             => $id,
					'appointment_id' => $id,
					'rider_id'       => $id,
					'start_utc'      => $time,
					'end_utc'        => $time,
				),
				'indexes' => array(
					'PRIMARY'           => array( true, array( 'id' ) ),
					'appointment_rider' => array( true, array( 'appointment_id', 'rider_id' ) ),
					'rider_time'        => array( false, array( 'rider_id', 'start_utc', 'end_utc' ) ),
				),
			);
		}
		if ( $version >= 3 ) {
			$definitions['authorization'] = array(
				'columns' => array(
					'id'            => $id,
					'registry_json' => array( 'longtext', false ),
				),
				'indexes' => array( 'PRIMARY' => array( true, array( 'id' ) ) ),
			);
		}
		return $definitions;
	}

	/** DDL preview, identical to the statements apply uses.
	 *
	 * @return array
	 */
	public static function statements() {
		$statements = array();
		foreach ( self::definitions() as $name => $def ) {
			$parts = array();
			foreach ( $def['columns'] as $field => $spec ) {
				$auto    = 'id' === $field && ! in_array( $name, array( 'settings', 'authorization' ), true );
				$parts[] = '`' . $field . '` ' . $spec[0] . ( str_starts_with( $spec[0], 'char(' ) ? ' CHARACTER SET ascii COLLATE ascii_bin' : '' ) . ( $spec[1] ? ' NULL' : ' NOT NULL' ) . ( $auto ? ' AUTO_INCREMENT' : '' );
			}
			foreach ( $def['indexes'] as $key => $spec ) {
				$parts[] = ( 'PRIMARY' === $key ? 'PRIMARY KEY' : ( $spec[0] ? 'UNIQUE KEY ' : 'KEY ' ) . '`' . $key . '`' ) . ' (`' . implode( '`,`', $spec[1] ) . '`)';
			}
			$statements[ $name ] = 'CREATE TABLE IF NOT EXISTS `' . self::table( $name ) . '` (' . implode( ',', $parts ) . ') ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
		}
		return $statements;
	}

	/** Read-only physical verification; unknown or extra schema fails closed.
	 *
	 * @param int $version Physical contract to verify.
	 * @return array
	 */
	public static function inspect( $version = self::VERSION ) {
		global $wpdb;
		if ( ! extension_loaded( 'mysqli' ) || ! ( $wpdb->dbh instanceof mysqli ) ) {
			return array(
				'storage' => 'mysql_required',
				'missing' => array(),
				'errors'  => array( 'mysql_required' ),
				'version' => null,
				'ready'   => false,
			);
		}
		$missing = array();
		$errors  = array();
		foreach ( self::definitions( $version ) as $name => $def ) {
			$table = self::table( $name );
			$info  = $wpdb->get_row( $wpdb->prepare( 'SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table ), ARRAY_A );
			if ( ! $info ) {
				$missing[] = $name;
				continue;
			}
			if ( 'InnoDB' !== $info['ENGINE'] || 'utf8mb4_unicode_ci' !== $info['TABLE_COLLATION'] ) {
				$errors[] = $name . ':storage';
			}
			// Identifiers come only from the checked internal contract above.
			$columns = $wpdb->get_results( "SHOW FULL COLUMNS FROM `$table`", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$actual  = array_column( $columns ? $columns : array(), null, 'Field' );
			if ( array_keys( $actual ) !== array_keys( $def['columns'] ) ) {
				$errors[] = $name . ':columns';
			}
			foreach ( $def['columns'] as $field => $spec ) {
				$c    = $actual[ $field ] ?? null;
				$type = $c ? preg_replace( '/\b(bigint|int|tinyint)\([0-9]+\)/', '$1', strtolower( $c['Type'] ) ) : '';
				$auto = 'id' === $field && ! in_array( $name, array( 'settings', 'authorization' ), true );
				if ( ! $c || $type !== $spec[0] || ( 'YES' === $c['Null'] ) !== $spec[1] || ( ( $auto ? 'auto_increment' : '' ) !== $c['Extra'] ) || null !== $c['Default'] || ( str_starts_with( $spec[0], 'char(' ) && 'ascii_bin' !== $c['Collation'] ) ) {
					$errors[] = $name . ':' . $field;
				}
			}
			$indexes = $wpdb->get_results( "SHOW INDEX FROM `$table`", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$got     = array();
			foreach ( $indexes as $index ) {
				$got[ $index['Key_name'] ][0]                                     = ! (bool) $index['Non_unique'];
				$got[ $index['Key_name'] ][1][ (int) $index['Seq_in_index'] - 1 ] = $index['Column_name'];
				if ( null !== $index['Sub_part'] || 'BTREE' !== $index['Index_type'] ) {
					$errors[] = $name . ':index_prefix';
				}
			}
			foreach ( $def['indexes'] as $key => $spec ) {
				if ( ! isset( $got[ $key ] ) || $got[ $key ] !== $spec ) {
					$errors[] = $name . ':index:' . $key;
				}
			}
			if ( count( $got ) !== count( $def['indexes'] ) ) {
				$errors[] = $name . ':extra_index';
			}
		}
		$version = null;
		if ( ! in_array( 'settings', $missing, true ) && ! array_filter( $errors, static fn( $e ) => str_starts_with( $e, 'settings:' ) ) ) {
			$row     = self::settings();
			$version = $row ? (int) $row['schema_version'] : null;
			$t       = self::table( 'settings' );
			$count   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$t`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ( $row && ( 1 !== $count || ! CSF_Scheduling_Audit::uuid( $row['uuid'] ) ) ) || ( ! $row && 0 !== $count ) ) {
				$errors[] = 'settings:singleton';
			}
		}
		if ( 3 === $version && ! in_array( 'authorization', $missing, true ) && ! array_filter( $errors, static fn( $e )=>str_starts_with( $e, 'authorization:' ) ) ) {
			$auth = self::table( 'authorization' );
			if ( 1 !== (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$auth`" ) || ! CSF_Scheduling_Permissions::state() ) {
				$errors[] = 'authorization:singleton'; }
		}
		return array(
			'storage' => 'mysql',
			'missing' => $missing,
			'errors'  => array_values( array_unique( $errors ) ),
			'version' => $version,
			'ready'   => ! $missing && ! $errors && self::VERSION === $version,
		);
	}

	/** Authoritative singleton; never a cached WordPress option.
	 *
	 * @return array|null
	 */
	public static function settings() {
		global $wpdb;
		$t = self::table( 'settings' );
		return $wpdb->get_row( "SELECT * FROM `$t` WHERE id=1", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/** Explicit migration only; DDL may survive failure, the ready marker cannot.
	 *
	 * @return true|WP_Error
	 */
	public static function migrate() {
		global $wpdb;
		$previous_error_suppression = $wpdb->suppress_errors( true );
		try {
			if ( ! defined( 'CSF_SCHEDULING_MIGRATION_ALLOWED' ) || true !== CSF_SCHEDULING_MIGRATION_ALLOWED || ! in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true ) || ! get_current_user_id() || ! current_user_can( 'manage_options' ) ) {
				return new WP_Error( 'migration_denied', 'Explicit local/development migration permission and verified operator required.' );
			}
			if ( 'mysql' !== self::inspect()['storage'] ) {
				return new WP_Error( 'mysql_required', 'MySQL/MariaDB required.' );
			}
			$lock = 'csf_sched_' . hash( 'sha256', DB_NAME . ':' . $wpdb->prefix );
			$lock = substr( $lock, 0, 64 );
			if ( '1' !== (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock ) ) ) {
				return new WP_Error( 'migration_busy', 'Migration lock unavailable.' );
			}
			try {
				$state = self::inspect();
				if ( in_array( $state['version'], array( 1, 2 ), true ) ) {
					$base = self::inspect( $state['version'] );
					if ( $base['errors'] || $base['missing'] ) {
						return new WP_Error( 'migration_incompatible', 'Installed v1 physical schema must be intact.' );
					}
					foreach ( array_diff( array_keys( self::definitions() ), array_keys( self::definitions( $state['version'] ) ) ) as $name ) {
						if ( ! in_array( $name, $state['missing'], true ) ) {
							$t = self::table( $name );
							if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$t`" ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
								return new WP_Error( 'migration_existing_data', 'Unversioned v2 data requires reviewed recovery.' );
							}
						}
					}
				}
				if ( ( self::VERSION === $state['version'] && ! $state['ready'] ) || $state['errors'] || ( null !== $state['version'] && ! in_array( $state['version'], array( 0, 1, 2, self::VERSION ), true ) ) ) {
					return new WP_Error( 'migration_incompatible', 'Existing schema is incompatible; no automatic destructive repair.' );
				}
				if ( $state['ready'] ) {
					return true;
				}
				// Never adopt populated tables whose installation marker is absent/incomplete.
				foreach ( self::definitions() as $name => $def ) {
					if ( in_array( $state['version'], array( 1, 2 ), true ) || 'settings' === $name || in_array( $name, $state['missing'], true ) ) {
						continue;
					}
					$t = self::table( $name );
					if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$t`" ) > 0 ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						return new WP_Error( 'migration_existing_data', 'Unversioned populated tables require reviewed recovery, not automatic adoption.' );
					}
				}
				foreach ( self::statements() as $name => $sql ) {
					if ( in_array( $name, $state['missing'], true ) && false === $wpdb->query( $sql ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
						return new WP_Error( 'migration_ddl', 'Schema creation interrupted; retry only after reviewing physical schema.' );
					}
				}
				$physical = self::inspect();
				if ( $physical['missing'] || $physical['errors'] ) {
					return new WP_Error( 'migration_verify', 'Physical schema verification failed.' );
				}
				$t = self::table( 'settings' );
				if ( ! self::settings() && false === $wpdb->insert(
					$t,
					array(
						'id'             => 1,
						'uuid'           => wp_generate_uuid4(),
						'schema_version' => 0,
						'revision'       => 0,
						'policy_json'    => wp_json_encode( CSF_Scheduling_Policy::defaults() ),
						'staff_json'     => '[]',
						'updated_by'     => get_current_user_id(),
						'updated_at_utc' => CSF_Scheduling_Audit::now(),
					)
				) ) {
					return new WP_Error( 'migration_seed', 'Singleton could not be created.' );
				}
				if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
					return new WP_Error( 'migration_transaction', 'Cannot begin transaction.' );
				}
				$wpdb->get_row( "SELECT id FROM `$t` WHERE id=1 FOR UPDATE" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$auth = self::table( 'authorization' );
				if ( ! $wpdb->get_var( "SELECT id FROM `$auth` WHERE id=1" ) ) {
					$legacy = json_decode( self::settings()['staff_json'], true );
					if ( ! is_array( $legacy ) || ! array_is_list( $legacy ) || count( $legacy ) > 50 || count( array_filter( $legacy, static fn( $id ) => is_int( $id ) && $id > 0 ) ) !== count( $legacy ) || 1 !== $wpdb->insert(
						$auth,
						array(
							'id'            => 1,
							'registry_json' => wp_json_encode( CSF_Scheduling_Permissions::initial( $legacy ) ),
						)
					) ) {
						$wpdb->query( 'ROLLBACK' );
						return new WP_Error( 'migration_authorization', 'Authorization seed not acknowledged; no master or staff grants were inferred.' );
					}
				}
				$audit = CSF_Scheduling_Audit::append( wp_generate_uuid4(), 'schema_installed', self::settings()['uuid'], (int) self::settings()['schema_version'], self::VERSION );
				if ( is_wp_error( $audit ) || 1 !== $wpdb->update( $t, array( 'schema_version' => self::VERSION ), array( 'id' => 1 ) ) || false === $wpdb->query( 'COMMIT' ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'migration_commit', 'Migration marker and audit were not acknowledged; inspect before retry.' );
				}
				return self::inspect()['ready'] ? true : new WP_Error( 'migration_verify', 'Final schema not ready.' );
			} finally {
				$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
			}
		} finally {
			$wpdb->suppress_errors( $previous_error_suppression ); }
	}
}

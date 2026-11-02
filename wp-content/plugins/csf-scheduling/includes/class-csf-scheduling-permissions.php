<?php
/** Facility-scoped, default-deny authorization under the scheduling transaction.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// Private primary-database authorization must not use object caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/** Facility permission registry, resolution and transactional changes. */
class CSF_Scheduling_Permissions {
	const COMMANDS = array( 'team_member_saved', 'team_members_grouped', 'team_group_saved', 'master_bootstrapped', 'master_transferred' );

	/** Implemented privileges only; future powers are intentionally absent.
	 *
	 * @return array
	 */
	public static function registry() {
		return array(
			'calendar_all'        => array( 'Scheduling', 'View all calendar appointments' ),
			'calendar_assigned'   => array( 'Scheduling', 'View assigned calendar appointments' ),
			'appointment_edit'    => array( 'Scheduling', 'Create and edit requested/held appointments' ),
			'appointment_confirm' => array( 'Scheduling', 'Confirm and edit confirmed appointments' ),
			'appointment_cancel'  => array( 'Scheduling', 'Cancel appointments, including confirmed' ),
			'rider_view'          => array( 'Riders', 'View permitted rider records' ),
			'rider_edit'          => array( 'Riders', 'Edit rider identity records' ),
			'rider_clearance'     => array( 'Riders', 'Staff-verify clearance evidence (not signatures)' ),
			'resource_view'       => array( 'Horses and resources', 'View approved horses/resources and restrictions' ),
			'resource_manage'     => array( 'Horses and resources', 'Create/edit resource and horse identities' ),
			'availability_manage' => array( 'Horses and resources', 'Change resource availability/protected intervals' ),
			'suitability_manage'  => array( 'Horses and resources', 'Change horse/service/rider suitability' ),
			'welfare_hold'        => array( 'Horses and resources', 'Create a protective horse welfare hold' ),
			'welfare_release'     => array( 'Horses and resources', 'Release a protective hold, including another person’s' ),
			'policy_manage'       => array( 'Farm configuration', 'Change farm-wide policies and limits' ),
			'audit_view'          => array( 'Oversight', 'View permitted appointment history' ),
			'team_view'           => array( 'Team security', 'View team accounts and effective permissions' ),
			'team_assign'         => array( 'Team security', 'Assign employee groups and individual overrides' ),
			'team_groups'         => array( 'Team security', 'Edit permission group templates' ),
			'team_audit'          => array( 'Team security', 'View private permission change history' ),
		);
	}

	/** Editable preset starting points, never WordPress roles or protected master identity.
	 *
	 * @return array
	 */
	public static function presets() {
		$read    = array( 'calendar_all', 'resource_view' );
		$trainer = array_merge( $read, array( 'appointment_edit', 'appointment_confirm', 'appointment_cancel', 'rider_view', 'rider_edit', 'suitability_manage', 'welfare_hold', 'audit_view' ) );
		$sets    = array(
			'operations'      => array( 'Operations Manager', array_merge( $trainer, array( 'resource_manage', 'availability_manage', 'rider_clearance' ) ) ),
			'head_trainer'    => array( 'Head Trainer', $trainer ),
			'instructor'      => array( 'Instructor', array( 'calendar_assigned', 'resource_view', 'welfare_hold' ) ),
			'office'          => array( 'Office / Client Services', array_merge( $read, array( 'appointment_edit', 'appointment_cancel', 'rider_view', 'rider_edit' ) ) ),
			'barn'            => array( 'Barn Staff', array( 'resource_view', 'welfare_hold' ) ),
			'working_student' => array( 'Volunteer / Working Student', array( 'calendar_assigned', 'resource_view' ) ),
			'read_only'       => array( 'Read-only', $read ),
		);
		$out     = array();
		foreach ( $sets as$id => $v ) {
			$out[ $id ] = array(
				'label'       => $v[0],
				'permissions' => $v[1],
			);
		}return $out;
	}

	/** Uncached primary database singleton.
	 *
	 * @return array|null
	 */
	public static function state() {
		global $wpdb;
		$t   = CSF_Scheduling_Schema::table( 'authorization' );
		$row = $wpdb->get_row( "SELECT registry_json FROM `$t` WHERE id=1", ARRAY_A );
		$s   = $row ? json_decode( $row['registry_json'], true ) : null;
		try {
			return self::valid( $s ) ? $s : null;
		} catch ( Throwable $error ) {
			return null; }
	}

	/** Implemented privileges only; future modules/exports/master transfer are not checkboxes.
	 *
	 * @param array $legacy Legacy.
	 */
	public static function initial( $legacy = array() ) {
		$members = array();
		foreach ( $legacy as $id ) {
			if ( is_int( $id ) && $id > 0 ) {
				$members[ (string) $id ] = array(
					'group'     => 'read_only',
					'overrides' => array(),
					'resources' => array(),
					'active'    => false,
					'verified'  => false,
				); }
		}
		return array(
			'version' => 1,
			'master'  => null,
			'groups'  => self::presets(),
			'members' => $members,
		);
	}

	/** Authoritative singleton; no user-controlled facility selector.
	 *
	 * @param array $s Validated facility registry.
	 */
	public static function valid( $s ) {
		if ( ! is_array( $s ) || array_keys( $s ) !== array( 'version', 'master', 'groups', 'members' ) || 1 !== $s['version'] || ! is_array( $s['groups'] ) || ! is_array( $s['members'] ) || count( $s['groups'] ) > 30 || count( $s['members'] ) > 50 ) {
			return false; }
		foreach ( $s['groups'] as $id => $g ) {
			if ( ! is_string( $id ) || ! preg_match( '/^[a-z][a-z0-9_]{0,39}$/D', $id ) || 'master' === $id || ! is_array( $g ) || array_keys( $g ) !== array( 'label', 'permissions' ) || ! is_string( $g['label'] ) || ! trim( $g['label'] ) || strlen( $g['label'] ) > 100 || sanitize_text_field( $g['label'] ) !== $g['label'] || ! is_array( $g['permissions'] ) || ! array_is_list( $g['permissions'] ) || count( array_unique( $g['permissions'] ) ) !== count( $g['permissions'] ) ) {
				return false; }
			foreach ( $g['permissions'] as $p ) {
				if ( ! is_string( $p ) || ! isset( self::registry()[ $p ] ) ) {
					return false; }
			}
		}
		foreach ( $s['members'] as $id => $m ) {
			if ( ! ctype_digit( (string) $id ) || (int) $id < 1 || ! is_array( $m ) || array_keys( $m ) !== array( 'group', 'overrides', 'resources', 'active', 'verified' ) || ! is_string( $m['group'] ?? null ) || ! isset( $s['groups'][ $m['group'] ] ) || ! is_bool( $m['active'] ) || ! is_bool( $m['verified'] ) || ! is_array( $m['overrides'] ) || ! is_array( $m['resources'] ) || ! array_is_list( $m['resources'] ) || count( $m['resources'] ) > 100 ) {
				return false; }
			foreach ( $m['overrides'] as $p => $v ) {
				if ( ! isset( self::registry()[ $p ] ) || ! in_array( $v, array( 'grant', 'deny' ), true ) ) {
					return false; }
			}
			foreach ( $m['resources'] as $uuid ) {
				if ( ! CSF_Scheduling_Audit::uuid( $uuid ) ) {
					return false; }
			}
		}
		return null === $s['master'] || ( is_int( $s['master'] ) && isset( $s['members'][ $s['master'] ] ) && $s['members'][ $s['master'] ]['active'] && $s['members'][ $s['master'] ]['verified'] );
	}

	/** Pure effective result; denial overrides inherited/owner grants.
	 *
	 * @param array $s Validated facility registry.
	 * @param int   $id Verified numeric identity.
	 */
	public static function effective( $s, $id ) {
		$out = array_fill_keys( array_keys( self::registry() ), false );
		$m   = $s['members'][ $id ] ?? null;
		if ( ! $m || ! $m['active'] || ! $m['verified'] ) {
			return $out; }
		foreach ( $out as $p => $v ) {
			$default   = $id === $s['master'] || in_array( $p, $s['groups'][ $m['group'] ]['permissions'], true );
			$out[ $p ] = 'deny' !== ( $m['overrides'][ $p ] ?? null ) && ( 'grant' === ( $m['overrides'][ $p ] ?? null ) || $default );
		}
		return $out;
	}

	/** No bundled provider or status checkbox can assert independently verified MFA.
	 *
	 * @param int $id Verified numeric identity.
	 */
	public static function mfa( $id ) {
		try {
			return true === apply_filters( 'csf_scheduling_mfa_verified', false, $id );
		} catch ( Throwable $error ) {
			return false; }
	}

	/** Enrollment permits reviewed assignment only; it never authenticates a session.
	 *
	 * @param int $id Existing numeric identity.
	 * @return bool
	 */
	public static function enrolled( $id ) {
		try {
			return true === apply_filters( 'csf_scheduling_mfa_enrolled', false, $id );
		} catch ( Throwable $error ) {
			return false;
		}
	}

	/** Bootstrap/transfer is a guarded CLI identity ceremony, not an ordinary permission.
	 *
	 * @param int $id Verified numeric identity.
	 */
	public static function identity_gate( $id ) {
		return class_exists( 'CSF_Scheduling_Identity' ) && CSF_Scheduling_Identity::allows_master( $id );
	}

	/** Record visibility shared by reads and mutations; assigned means explicit resources or author.
	 *
	 * @param array      $row Appointment row.
	 * @param array|null $details Optional resolved appointment details.
	 */
	public static function appointment_visible( $row, $details = null ) {
		if ( CSF_Scheduling_Access::can( 'calendar_all' ) ) {
			return true; }
		if ( ! CSF_Scheduling_Access::can( 'calendar_assigned' ) ) {
			return false; }
		$s = self::state();
		$m = $s['members'][ get_current_user_id() ] ?? null;
		if ( get_current_user_id() === (int) $row['created_by'] ) {
			return true; }
		if ( null === $details ) {
			global $wpdb;
			$t       = CSF_Scheduling_Schema::table( 'details' );
			$details = json_decode( (string) $wpdb->get_var( $wpdb->prepare( "SELECT body_json FROM `$t` WHERE uuid=%s", $row['uuid'] ) ), true ); }
		return (bool) array_intersect( $m['resources'] ?? array(), $details['resources'] ?? array() );
	}

	/** Exact command permission mapping, repeated under the singleton lock.
	 *
	 * @param string $kind Kind.
	 * @param array  $b Strict command body.
	 */
	public static function authorize( $kind, $b ) {
		try {
			global $wpdb;
			if ( in_array( $kind, CSF_Scheduling_Identity::COMMANDS, true ) ) {
				return CSF_Scheduling_Identity::authorize( $kind, $b ); }
			$map = array(
				'policy_replaced'      => 'policy_manage',
				'resource_created'     => 'resource_manage',
				'resource_saved'       => 'resource_manage',
				'horse_saved'          => 'suitability_manage',
				'rider_saved'          => 'rider_edit',
				'availability_saved'   => 'availability_manage',
				'restriction_added'    => 'welfare_hold',
				'restriction_released' => 'welfare_release',
				'appointment_saved'    => 'appointment_edit',
				'appointment_canceled' => 'appointment_cancel',
				'team_member_saved'    => 'team_assign',
				'team_members_grouped' => 'team_assign',
				'team_group_saved'     => 'team_groups',
			);
			if ( in_array( $kind, array( 'master_bootstrapped', 'master_transferred' ), true ) ) {
				return ! empty( $b['owner_verified'] ) && true === $b['owner_verified'] && is_int( $b['user_id'] ?? null ) && self::identity_gate( $b['user_id'] ); }
			if ( 'appointment_saved' === $kind ) {
				$t            = CSF_Scheduling_Schema::table( 'appointments' );
				$old          = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$t` WHERE uuid=%s", $b['uuid'] ?? '' ), ARRAY_A );
				$confirmed    = 'confirmed' === ( $b['state'] ?? '' ) || 'confirmed' === ( $old['state'] ?? '' );
				$map[ $kind ] = $confirmed ? 'appointment_confirm' : 'appointment_edit';
				if ( $confirmed && ! $old && ! CSF_Scheduling_Access::can( 'appointment_edit' ) ) {
					return false;}
			}
			if ( 'rider_saved' === $kind && CSF_Scheduling_Access::can( 'rider_clearance' ) ) {
				$map[ $kind ] = 'rider_clearance'; }
			if ( 'horse_saved' === $kind && CSF_Scheduling_Access::can( 'resource_manage' ) ) {
				$map[ $kind ] = 'resource_manage'; }
			if ( ! isset( $map[ $kind ] ) || ! CSF_Scheduling_Access::can( $map[ $kind ] ) ) {
				return false; }
			if ( in_array( $kind, array( 'team_member_saved', 'team_members_grouped', 'team_group_saved' ), true ) ) {
				return CSF_Scheduling_Access::can( 'team_view' ); }
			if ( 'restriction_added' === $kind && 'welfare' !== ( $b['data']['reason'] ?? null ) && ! CSF_Scheduling_Access::can( 'availability_manage' ) ) {
				return false; }
			if ( 'restriction_added' === $kind && ! CSF_Scheduling_Access::can( 'resource_manage' ) ) {
				$t      = CSF_Scheduling_Schema::table( 'resources' );
				$h      = CSF_Scheduling_Schema::table( 'horses' );
				$target = $b['data']['target_uuid'] ?? '';
				if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$t` WHERE uuid=%s AND kind='horse'", $target ) ) && ! $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$h` WHERE uuid=%s", $target ) ) ) {
					return false; }
			}
			if ( 'rider_saved' === $kind ) {
				$t   = CSF_Scheduling_Schema::table( 'riders' );
				$old = json_decode( (string) $wpdb->get_var( $wpdb->prepare( "SELECT body_json FROM `$t` WHERE uuid=%s", $b['uuid'] ?? '' ) ), true );
				if ( ( $b['data']['label'] ?? null ) !== ( $old['label'] ?? null ) && ! CSF_Scheduling_Access::can( 'rider_edit' ) ) {
					return false;}
				foreach ( array( 'adult', 'release_verified', 'guardian_verified', 'emergency_verified' ) as $key ) {
					if ( ( $b['data'][ $key ] ?? false ) !== ( $old[ $key ] ?? false ) && ! CSF_Scheduling_Access::can( 'rider_clearance' ) ) {
						return false; }
				}
			}
			if ( 'horse_saved' === $kind ) {
				$t   = CSF_Scheduling_Schema::table( 'horses' );
				$old = json_decode( (string) $wpdb->get_var( $wpdb->prepare( "SELECT body_json FROM `$t` WHERE uuid=%s", $b['uuid'] ?? '' ) ), true );
				foreach ( array( 'suitable_services', 'suitable_riders' )as$key ) {
					if ( ( $b['data'][ $key ] ?? array() ) !== ( $old[ $key ] ?? array() ) && ! CSF_Scheduling_Access::can( 'suitability_manage' ) ) {
						return false;}
				}
				foreach ( array( 'label', 'source', 'resource_uuid', 'training_enrolled' ) as $key ) {
					if ( ( $b['data'][ $key ] ?? null ) !== ( $old[ $key ] ?? null ) && ! CSF_Scheduling_Access::can( 'resource_manage' ) ) {
						return false; }
				}
			}
			if ( in_array( $kind, array( 'appointment_saved', 'appointment_canceled' ), true ) ) {
				$t   = CSF_Scheduling_Schema::table( 'appointments' );
				$old = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$t` WHERE uuid=%s", $b['uuid'] ?? '' ), ARRAY_A );
				if ( $old && ! self::appointment_visible( $old ) ) {
					return false; }
				if ( 'appointment_saved' === $kind && ( 'confirmed' === ( $old['state'] ?? '' ) || 'confirmed' === ( $b['state'] ?? '' ) ) && ! CSF_Scheduling_Access::can( 'appointment_confirm' ) ) {
					return false; }
				if ( 'appointment_saved' === $kind && ! CSF_Scheduling_Access::can( 'calendar_all' ) ) {
					$s       = self::state();
					$allowed = $s['members'][ get_current_user_id() ]['resources'] ?? array();
					if ( ! array_intersect( $b['resources'] ?? array(), $allowed ) ) {
						return false; }
				}
			}
			return true;
		} catch ( Throwable $error ) {
			return false; }
	}

	/** Apply only inside the existing Store transaction; preview calls roll back all effects.
	 *
	 * @param string $kind Kind.
	 * @param array  $b Strict command body.
	 */
	public static function apply( $kind, $b ) {
		global $wpdb;
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Store::in_calendar_transaction() && self::authorize( $kind, $b ), 'forbidden' );
		$s = self::state();
		CSF_Scheduling_Calendar::require( is_array( $s ), 'forbidden' );
		$before = $s;
		if ( in_array( $kind, array( 'master_bootstrapped', 'master_transferred' ), true ) ) {
			CSF_Scheduling_Calendar::keys( $b, array( 'user_id', 'owner_verified' ) );
			CSF_Scheduling_Calendar::require( 'master_bootstrapped' === $kind ? null === $s['master'] : null !== $s['master'], 'invalid_transition' );
			$id = $b['user_id'];
			if ( 'master_transferred' === $kind ) {
				CSF_Scheduling_Calendar::require( $id !== $s['master'], 'invalid_transition' );
				$s['members'][ $s['master'] ]['active'] = false;
			} $s['master']       = $id;
			$s['members'][ $id ] = array(
				'group'     => 'read_only',
				'overrides' => array(),
				'resources' => array(),
				'active'    => true,
				'verified'  => true,
			);
		} elseif ( 'team_member_saved' === $kind ) {
			CSF_Scheduling_Calendar::keys( $b, array( 'user_id', 'member', 'sensitive_confirmed' ) );
			$id = $b['user_id'];
			CSF_Scheduling_Calendar::require( is_int( $id ) && $id > 0 && get_userdata( $id ) && is_array( $b['member'] ) && true === $b['sensitive_confirmed'], 'invalid_body' );
			CSF_Scheduling_Calendar::require( $id !== $s['master'] || get_current_user_id() === $s['master'], 'forbidden' );
			$s['members'][ $id ] = $b['member'];
			foreach ( $b['member']['resources'] ?? array() as $uuid ) {
				$t = CSF_Scheduling_Schema::table( 'resources' );
				CSF_Scheduling_Calendar::require( (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM `$t` WHERE uuid=%s AND active=1", $uuid ) ), 'invalid_body' ); }
			CSF_Scheduling_Calendar::require( ! $b['member']['active'] || ( $b['member']['verified'] && self::enrolled( $id ) ), 'forbidden' );
		} elseif ( 'team_members_grouped' === $kind ) {
			CSF_Scheduling_Calendar::keys( $b, array( 'user_ids', 'group', 'sensitive_confirmed' ) );
			CSF_Scheduling_Calendar::require( is_array( $b['user_ids'] ) && array_is_list( $b['user_ids'] ) && $b['user_ids'] && count( $b['user_ids'] ) <= 50 && count( array_unique( $b['user_ids'] ) ) === count( $b['user_ids'] ) && isset( $s['groups'][ $b['group'] ] ) && true === $b['sensitive_confirmed'], 'invalid_body' );
			foreach ( $b['user_ids']as$id ) {
				CSF_Scheduling_Calendar::require( is_int( $id ) && $id !== $s['master'] && isset( $s['members'][ $id ] ) && $s['members'][ $id ]['verified'] && self::enrolled( $id ), 'forbidden' );
				$s['members'][ $id ]['group'] = $b['group'];
			}
		} else {
			CSF_Scheduling_Calendar::keys( $b, array( 'id', 'group', 'sensitive_confirmed' ) );
			CSF_Scheduling_Calendar::require( is_string( $b['id'] ) && true === $b['sensitive_confirmed'], 'invalid_body' );
			$s['groups'][ $b['id'] ] = $b['group'];
		}
		CSF_Scheduling_Calendar::require( self::valid( $s ), 'invalid_body' );
		if ( null !== $s['master'] ) {
			$rights = self::effective( $s, $s['master'] );
			foreach ( array( 'team_view', 'team_assign', 'team_groups', 'team_audit' ) as $p ) {
				CSF_Scheduling_Calendar::require( $rights[ $p ], 'invalid_transition' ); }
		}
		// Only the protected master may delegate security administration or edit security-bearing templates.
		if ( get_current_user_id() !== $before['master'] && ! in_array( $kind, array( 'master_bootstrapped', 'master_transferred' ), true ) ) {
			foreach ( $s['members'] as $id => $m ) {
				$old = self::effective( $before, (int) $id );
				$new = self::effective( $s, (int) $id );
				foreach ( $new as $p => $yes ) {
					if ( $yes && ! $old[ $p ] ) {
						CSF_Scheduling_Calendar::require( get_current_user_id() !== (int) $id && CSF_Scheduling_Access::can( $p ), 'forbidden' ); }
				}
				foreach ( array( 'team_view', 'team_assign', 'team_groups', 'team_audit', 'policy_manage' ) as $p ) {
					CSF_Scheduling_Calendar::require( $old[ $p ] === $new[ $p ], 'forbidden' ); }
			}
			foreach ( $s['groups'] as $id => $g ) {
				foreach ( $g['permissions'] as $p ) {
					if ( ! in_array( $p, $before['groups'][ $id ]['permissions'] ?? array(), true ) ) {
						CSF_Scheduling_Calendar::require( CSF_Scheduling_Access::can( $p ), 'forbidden' );
					} if ( in_array( $p, array( 'team_view', 'team_assign', 'team_groups', 'team_audit', 'policy_manage' ), true ) ) {
						CSF_Scheduling_Calendar::require( isset( $before['groups'][ $id ] ) && in_array( $p, $before['groups'][ $id ]['permissions'], true ), 'forbidden' ); }
				}
			}
		}
		$changes = array();
		foreach ( array_unique( array_merge( array_keys( $before['members'] ), array_keys( $s['members'] ) ) ) as $id ) {
			$a      = self::effective( $before, (int) $id );
			$z      = self::effective( $s, (int) $id );
			$grant  = array_keys( array_filter( $z, static fn( $v, $p ) => $v && ! $a[ $p ], ARRAY_FILTER_USE_BOTH ) );
			$revoke = array_keys( array_filter( $a, static fn( $v, $p ) => $v && ! $z[ $p ], ARRAY_FILTER_USE_BOTH ) );
			if ( ( 'team_group_saved' === $kind && ( $s['members'][ $id ]['group'] ?? null ) === $b['id'] ) || $grant || $revoke || ( $before['members'][ $id ] ?? null ) !== ( $s['members'][ $id ] ?? null ) ) {
				$changes[] = array(
					'user_id'   => (int) $id,
					'granted'   => $grant,
					'revoked'   => $revoke,
					'effective' => array_keys( array_filter( $z ) ),
				); }
		}
		$t = CSF_Scheduling_Schema::table( 'authorization' );
		CSF_Scheduling_Calendar::require( strlen( wp_json_encode( $s ) ) <= 65536 && false !== $wpdb->update( $t, array( 'registry_json' => wp_json_encode( $s ) ), array( 'id' => 1 ) ), 'write_failed' );
		CSF_Scheduling_Calendar::document(
			'history',
			wp_generate_uuid4(),
			CSF_Scheduling_Schema::settings()['uuid'],
			array(
				'kind'    => $kind,
				'before'  => $before,
				'after'   => $s,
				'changes' => $changes,
			),
			0
		);
		$sessions = array();
		foreach ( $before['members']as$id => $m ) {
			if ( $m['active'] && ! ( $s['members'][ $id ]['active'] ?? false ) ) {
				$sessions[] = (int) $id;}
		}
		return array(
			'uuid'             => CSF_Scheduling_Schema::settings()['uuid'],
			'changes'          => $changes,
			'revoked_sessions' => $sessions,
		);
	}
}

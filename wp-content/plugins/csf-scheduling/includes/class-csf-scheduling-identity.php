<?php
/** Independent browser approvals for a narrowly scoped ownership ceremony.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// Private command and capability state must bypass object caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.SlowDBQuery -- Exact bounded identity rows, primary database only.
/** Immutable approval steps share the existing InnoDB singleton lock, ledger and audit. */
final class CSF_Scheduling_Identity {
	const COMMANDS = array( 'identity_requested', 'identity_owner_approved', 'identity_master_approved', 'identity_consumed' );
	/** Exact verified consume context; never set by HTTP input.
	 *
	 * @var array|null
	 */
	private static $consuming = null;
	/** Last native form result.
	 *
	 * @var mixed
	 */
	private static $result = null;

	/** Guard before native REST user controller dispatch, including its empty-roles path.
	 *
	 * @param mixed           $result Previous result.
	 * @param WP_REST_Server  $server Server.
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function rest_user( $result, $server, $request ) {
		unset( $server );
		if ( in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) && preg_match( '#^/wp/v2/users/([0-9]+)$#D', $request->get_route(), $match ) && $request->has_param( 'roles' ) ) {
			$s = self::deletion_state();
			if ( ! $s || ( null !== $s['master'] && $s['master'] === (int) $match[1] ) ) {
				return new WP_Error( 'protected_master', 'Transfer ownership before changing the current master base role.', array( 'status' => 403 ) ); }
		}
		return $result;
	}

	/** Exact code bytes and versions reviewed for this ceremony, excluding documentation and fixtures.
	 *
	 * @return string
	 */
	public static function release_hash() {
		$files = array();
		foreach ( array( 'csf-scheduling', 'csf-mfa' ) as $plugin ) {
			$root = WP_PLUGIN_DIR . '/' . $plugin . '/';
			foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) ) as $file ) {
				if ( $file->isFile() ) {
					$files[ $plugin . '/' . substr( $file->getPathname(), strlen( $root ) ) ] = hash_file( 'sha256', $file->getPathname() ); }
			}
		}
		ksort( $files );
		return hash( 'sha256', wp_json_encode( array( CSF_SCHEDULING_VERSION, CSF_MFA_Session::VERSION, $files ) ) );
	}

	/** Native WordPress deletion must not remove the facility's protected master identity.
	 *
	 * @param array  $caps Mapped requirements.
	 * @param string $cap Requested operation.
	 * @param int    $actor Acting identity.
	 * @param array  $args Target IDs.
	 * @return array
	 */
	public static function protect_master( $caps, $cap, $actor, $args ) {
		unset( $actor );
		if ( in_array( $cap, array( 'delete_user', 'remove_user', 'promote_user' ), true ) ) {
			$s = self::deletion_state();
			if ( ! $s ) {
				return array( 'do_not_allow' ); }
			if ( null !== $s['master'] && (int) ( $args[0] ?? 0 ) === $s['master'] ) {
				return array( 'do_not_allow' ); }
		}
		return $caps;
	}

	/** Guard native trusted deletion helpers as well as capability-checked controllers.
	 *
	 * @param int $id Target identity.
	 */
	public static function deleting( $id ) {
		$s = self::deletion_state();
		if ( ! $s || $s['master'] === (int) $id ) {
			wp_die( 'Transfer ownership through the independent approval ceremony before deleting the current master.', '', array( 'response' => 403 ) ); }
	}

	/** Unavailable ownership state blocks deletion without leaking database errors.
	 *
	 * @return array|null
	 */
	private static function deletion_state() {
		global $wpdb;
		$prior = $wpdb->suppress_errors( true );
		try {
			return CSF_Scheduling_Permissions::state();
		} finally {
			$wpdb->suppress_errors( $prior ); }
	}

	/** Only a finite, owner-authorized private invitation can initiate this ceremony.
	 *
	 * @return array|null
	 */
	public static function invitation() {
		if ( ! defined( 'CSF_SCHEDULING_IDENTITY_PATH' ) || ! is_string( CSF_SCHEDULING_IDENTITY_PATH ) || ! in_array( wp_get_environment_type(), array( 'local', 'development', 'staging' ), true ) ) {
			return null; }
		$path = realpath( CSF_SCHEDULING_IDENTITY_PATH );
		if ( ! $path || is_link( CSF_SCHEDULING_IDENTITY_PATH ) || ! is_file( $path ) || 0 !== ( fileperms( $path ) & 0077 ) || str_starts_with( $path . '/', realpath( ABSPATH ) . '/' ) || filesize( $path ) > 8192 ) {
			return null; }
		$m    = json_decode( (string) file_get_contents( $path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Exact private local approval file.
		$keys = array( 'site', 'environment', 'facility', 'release', 'schema', 'revision', 'policy_hash', 'registry_hash', 'operator', 'owner', 'previous_master', 'action', 'issued', 'expires', 'verification_record_hash', 'commands' );
		if ( ! is_array( $m ) || array_keys( $m ) !== $keys || home_url() !== $m['site'] || site_url() !== $m['site'] || wp_get_environment_type() !== $m['environment'] || CSF_Scheduling_Schema::VERSION !== $m['schema'] || ! is_int( $m['revision'] ) || $m['revision'] < 0 || ! is_int( $m['issued'] ) || ! is_int( $m['expires'] ) || $m['issued'] > time() || $m['expires'] <= time() || $m['expires'] - $m['issued'] > 600 || ! is_int( $m['operator'] ) || ! is_int( $m['owner'] ) || $m['operator'] === $m['owner'] || ! get_userdata( $m['operator'] ) || ! get_userdata( $m['owner'] ) || ! in_array( $m['action'], array( 'bootstrap', 'transfer' ), true ) ) {
			return null; }
		foreach ( array( 'policy_hash', 'registry_hash', 'verification_record_hash' ) as $key ) {
			if ( ! is_string( $m[ $key ] ) || ! preg_match( '/^[a-f0-9]{64}$/D', $m[ $key ] ) ) {
				return null; }
		}
		if ( ! class_exists( 'CSF_MFA_Session' ) || ! is_string( $m['release'] ) || ! hash_equals( self::release_hash(), $m['release'] ) ) {
			return null; }
		$steps = 'transfer' === $m['action'] ? self::COMMANDS : array_values( array_diff( self::COMMANDS, array( 'identity_master_approved' ) ) );
		if ( ! is_array( $m['commands'] ) || array_keys( $m['commands'] ) !== $steps || count( array_unique( $m['commands'] ) ) !== count( $steps ) ) {
			return null; }
		foreach ( $m['commands'] as $id ) {
			if ( ! CSF_Scheduling_Audit::uuid( $id ) ) {
				return null; }
		}
		return self::native_cap( $m['owner'], 'read' ) ? $m : null;
	}
	/** Exact canonical action identity.
	 *
	 * @param array $m Invitation.
	 * @return string
	 */
	public static function digest( $m ) {
		return hash( 'sha256', wp_json_encode( CSF_Scheduling_Store::canonical( $m ) ) ); }
	/** Read one immutable result by exact UUID, not a user directory.
	 *
	 * @param string $command UUID.
	 * @return array|null
	 */
	public static function record( $command ) {
		global $wpdb;
		if ( ! CSF_Scheduling_Audit::uuid( $command ) ) {
			return null; }
		$t = CSF_Scheduling_Schema::table( 'commands' );
		$r = $wpdb->get_row( $wpdb->prepare( "SELECT kind,actor_id,result_json FROM `$t` WHERE uuid=%s", $command ), ARRAY_A );
		return $r && in_array( $r['kind'], self::COMMANDS, true ) ? json_decode( $r['result_json'], true ) : null;
	}
	/** Validate actor independently before and under the normal transaction lock.
	 *
	 * @param string $kind Step.
	 * @param array  $body Full reviewed invitation.
	 * @return bool
	 */
	public static function authorize( $kind, $body ) {
		if ( ! in_array( $kind, self::COMMANDS, true ) || ( defined( 'WP_CLI' ) && WP_CLI ) || ! class_exists( 'CSF_MFA_Session' ) || ! class_exists( 'CSF_MFA_Admin' ) || ! CSF_MFA_Admin::enabled() || ! CSF_Scheduling_Permissions::mfa( get_current_user_id() ) ) {
			return false; }
		if ( ! CSF_MFA_Session::attested( CSF_MFA_Session::attestation(), 300 ) ) {
			return false; }
		$m = self::invitation();
		if ( ! $m || self::digest( $m ) !== self::digest( $body ) || ! isset( $m['commands'][ $kind ] ) ) {
			return false; }
		$id = get_current_user_id();
		if ( in_array( $kind, array( 'identity_requested', 'identity_consumed' ), true ) ) {
			return $m['operator'] === $id && self::operator( $id ); }
		return ( 'identity_owner_approved' === $kind ? $m['owner'] : $m['previous_master'] ) === $id;
	}
	/** Read current primary role/capability state, never a cached WP_User grant.
	 *
	 * @param int $id Exact verified operator.
	 * @return bool
	 */
	private static function operator( $id ) {
		return self::native_cap( $id, 'manage_options' ) && current_user_can( 'manage_options' );
	}

	/** Current primary native capability; no WordPress administration grant is inferred.
	 *
	 * @param int    $id Identity.
	 * @param string $cap Capability.
	 * @return bool
	 */
	private static function native_cap( $id, $cap ) {
		global $wpdb;
		$caps  = CSF_MFA_Session::meta( $id, $wpdb->prefix . 'capabilities' );
		$roles = CSF_MFA_Session::option( $wpdb->prefix . 'user_roles' );
		if ( ! is_array( $caps ) || ! is_array( $roles ) ) {
			return false; }
		$all = array();
		foreach ( $caps as $key => $yes ) {
			if ( true === $yes && isset( $roles[ $key ]['capabilities'] ) ) {
				$all = array_merge( $all, $roles[ $key ]['capabilities'] ); }
		}
		$all = array_merge( $all, $caps );
		return true === ( $all[ $cap ] ?? false );
	}

	/** Freeze proof, credentials and capability state while consuming independent approvals.
	 *
	 * @param array $m Invitation.
	 */
	private static function lock_approvers( $m ) {
		global $wpdb;
		foreach ( array( $wpdb->users, $wpdb->usermeta, $wpdb->options ) as $t ) {
			$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $t ) );
			CSF_Scheduling_Calendar::require( 'InnoDB' === $engine, 'write_failed' );
		}
		CSF_Scheduling_Calendar::require( false !== $wpdb->query( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name IN (%s,%s,%s,%s) ORDER BY option_id FOR UPDATE", 'active_plugins', 'csf_mfa_epoch', 'two_factor_enabled_providers', $wpdb->prefix . 'user_roles' ) ), 'write_failed' );
		$ids = array_unique( array_filter( array( $m['operator'], $m['owner'], $m['previous_master'] ), 'is_int' ) );
		sort( $ids );
		foreach ( $ids as $id ) {
			CSF_Scheduling_Calendar::require( false !== $wpdb->query( $wpdb->prepare( "SELECT ID FROM {$wpdb->users} WHERE ID=%d FOR UPDATE", $id ) ), 'write_failed' );
			CSF_Scheduling_Calendar::require( false !== $wpdb->query( $wpdb->prepare( "SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id=%d ORDER BY umeta_id FOR UPDATE", $id ) ), 'write_failed' );
		}
	}

	/** The existing master mutator accepts only this live, narrowly scoped transaction context.
	 *
	 * @param int $id Intended owner.
	 * @return bool
	 */
	public static function allows_master( $id ) {
		return null !== self::$consuming && CSF_Scheduling_Store::in_calendar_transaction() && self::$consuming['owner'] === $id && get_current_user_id() === self::$consuming['operator'] && self::authorize( 'identity_consumed', self::$consuming );
	}
	/** Apply an immutable step; Store writes this result, revision and audit atomically.
	 *
	 * @param string $kind Step.
	 * @param array  $m Full invitation.
	 * @param array  $settings Locked facility row.
	 * @return array
	 */
	public static function apply( $kind, $m, $settings ) {
		CSF_Scheduling_Calendar::require( CSF_Scheduling_Store::in_calendar_transaction(), 'forbidden' );
		self::lock_approvers( $m );
		CSF_Scheduling_Calendar::require( self::authorize( $kind, $m ), 'forbidden' );
		$s = CSF_Scheduling_Permissions::state();
		CSF_Scheduling_Calendar::require( $s && $m['facility'] === $settings['uuid'] && hash_equals( $m['policy_hash'], hash( 'sha256', $settings['policy_json'] ) ) && hash_equals( $m['registry_hash'], self::digest( $s ) ) && $s['master'] === $m['previous_master'], 'stale_revision' );
		CSF_Scheduling_Calendar::require( ( 'bootstrap' === $m['action'] && null === $s['master'] ) || ( 'transfer' === $m['action'] && is_int( $s['master'] ) && $s['master'] !== $m['owner'] ), 'invalid_transition' );
		$steps = array_keys( $m['commands'] );
		$index = array_search( $kind, $steps, true );
		CSF_Scheduling_Calendar::require( (int) $settings['revision'] === $m['revision'] + $index, 'stale_revision' ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- SQL numeric string.
		foreach ( array_slice( $steps, 0, $index ) as $prior ) {
			$r = self::record( $m['commands'][ $prior ] );
			CSF_Scheduling_Calendar::require( $r && self::signed( $r, $m, $prior ) && CSF_MFA_Session::attested( $r['approval'] ?? null, 300 ), 'forbidden' );
		}
		$ref = CSF_MFA_Session::attestation();
		CSF_Scheduling_Calendar::require( is_array( $ref ), 'forbidden' );
		$out                       = array(
			'uuid'            => $settings['uuid'],
			'invitation_hash' => self::digest( $m ),
			'invitation'      => $m,
			'approval'        => $ref,
			'step'            => $kind,
			'approved_at'     => time(),
		);
		$out['approval_signature'] = self::signature( $out, $m, $kind );
		if ( 'identity_consumed' === $kind ) {
			self::$consuming = $m;
			try {
				$change = CSF_Scheduling_Permissions::apply(
					'bootstrap' === $m['action'] ? 'master_bootstrapped' : 'master_transferred',
					array(
						'user_id'        => $m['owner'],
						'owner_verified' => true,
					)
				);
				self::mirrors( array_filter( array( $m['owner'], $m['previous_master'] ), 'is_int' ), $change['revoked_sessions'] );
				$out['changes'] = $change['changes'];
			} finally {
				self::$consuming = null; }
		}
		return $out;
	}
	/** Bind each approval to the complete action, step, exact UUID, actor, session and time.
	 *
	 * @param array  $result Approval result.
	 * @param array  $m Invitation.
	 * @param string $kind Step.
	 * @return string
	 */
	private static function signature( $result, $m, $kind ) {
		return hash_hmac( 'sha256', wp_json_encode( CSF_Scheduling_Store::canonical( array( $m, $kind, $m['commands'][ $kind ], $result['approval'], $result['approved_at'] ) ) ), wp_salt( 'auth' ) );
	}

	/** Validate the durable action-bound signature before consuming another person's approval.
	 *
	 * @param array  $result Result.
	 * @param array  $m Invitation.
	 * @param string $kind Step.
	 * @return bool
	 */
	private static function signed( $result, $m, $kind ) {
		$actor = 'identity_owner_approved' === $kind ? $m['owner'] : ( 'identity_master_approved' === $kind ? $m['previous_master'] : $m['operator'] );
		return ( $result['step'] ?? null ) === $kind && ( $result['approval']['user'] ?? null ) === $actor && self::digest( $m ) === ( $result['invitation_hash'] ?? null ) && is_int( $result['approved_at'] ?? null ) && $result['approved_at'] >= $m['issued'] && $result['approved_at'] <= time() && is_string( $result['approval_signature'] ?? null ) && hash_equals( self::signature( $result, $m, $kind ), $result['approval_signature'] );
	}

	/** Capability mirrors and revocations join the same verified InnoDB transaction.
	 *
	 * @param array $ids Affected identities.
	 * @param array $revoke Sessions to delete.
	 */
	private static function mirrors( $ids, $revoke ) {
		global $wpdb;
		$engine = $wpdb->get_var( $wpdb->prepare( 'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $wpdb->usermeta ) );
		CSF_Scheduling_Calendar::require( 'InnoDB' === $engine, 'write_failed' );
		$s = CSF_Scheduling_Permissions::state();
		foreach ( array_unique( $ids ) as $id ) {
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT umeta_id,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s FOR UPDATE", $id, $wpdb->prefix . 'capabilities' ), ARRAY_A );
			CSF_Scheduling_Calendar::require( 1 === count( $rows ), 'write_failed' );
			$caps = maybe_unserialize( $rows[0]['meta_value'] );
			CSF_Scheduling_Calendar::require( is_array( $caps ), 'write_failed' );
			foreach ( CSF_Scheduling_Permissions::effective( $s, $id ) as $key => $yes ) {
				$caps[ 'csf_dr_' . $key ] = $yes; }
			CSF_Scheduling_Calendar::require( false !== $wpdb->update( $wpdb->usermeta, array( 'meta_value' => maybe_serialize( $caps ) ), array( 'umeta_id' => (int) $rows[0]['umeta_id'] ) ), 'write_failed' );
		}
		foreach ( $revoke as $id ) {
			CSF_Scheduling_Calendar::require(
				false !== $wpdb->delete(
					$wpdb->usermeta,
					array(
						'user_id'  => $id,
						'meta_key' => 'session_tokens',
					)
				),
				'write_failed'
			); }
	}
	/** Exact post-commit status lookup resolves a lost response without another ownership change.
	 *
	 * @param string $command Original UUID.
	 * @return array|WP_Error
	 */
	public static function status( $command ) {
		if ( ! CSF_Scheduling_Permissions::mfa( get_current_user_id() ) ) {
			return new WP_Error( 'forbidden', 'A current MFA browser is required.' ); }
		$r = self::record( $command );
		if ( ! $r || ! self::signed( $r, $r['invitation'], $r['step'] ) || ! in_array( get_current_user_id(), array( $r['invitation']['operator'], $r['invitation']['owner'], $r['invitation']['previous_master'] ), true ) ) {
			return new WP_Error( 'forbidden', 'No accessible ceremony result.' ); }
		return $r;
	}
	/** Native private screen, no IDs or approvals in GET parameters. */
	public static function menu() {
		if ( CSF_Scheduling_Permissions::mfa( get_current_user_id() ) ) {
			add_menu_page( 'Farm ownership approval', 'Farm ownership approval', 'read', 'csf-identity', array( __CLASS__, 'render' ) ); }
	}
	/** Native nonce and explicit review precede any service call. */
	public static function post() {
		if ( 'POST' !== sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || 'csf-identity' !== sanitize_key( wp_unslash( $_GET['page'] ?? '' ) ) ) {
			return; } // phpcs:ignore WordPress.Security -- Form nonce checked before action.
		check_admin_referer( 'csf_identity' );
		if ( empty( $_POST['reviewed'] ) ) {
			self::$result = new WP_Error( 'confirmation', 'Review the exact identities and action first.' );
			return; }
		$kind = sanitize_key( wp_unslash( $_POST['step'] ?? '' ) );
		if ( 'status' === $kind ) {
			self::$result = self::status( sanitize_text_field( wp_unslash( $_POST['command'] ?? '' ) ) );
			return; }
		$m = self::invitation();
		if ( ! $m || ! isset( $m['commands'][ $kind ] ) ) {
			self::$result = new WP_Error( 'forbidden', 'No current approved invitation.' );
			return; }
		$index        = array_search( $kind, array_keys( $m['commands'] ), true );
		self::$result = CSF_Scheduling_Store::identity( $kind, $m, $m['revision'] + $index, $m['commands'][ $kind ] );
	}
	/** Display only this finite, independent invitation and own accessible result. */
	public static function render() {
		if ( ! CSF_Scheduling_Permissions::mfa( get_current_user_id() ) ) {
			wp_die( 'Fresh MFA required.', '', array( 'response' => 403 ) ); }
		$m = self::invitation();
		if ( $m && ! in_array( get_current_user_id(), array( $m['owner'], $m['operator'], $m['previous_master'] ), true ) ) {
			$m = null; }
		echo '<div class="wrap"><h1>Farm ownership approval</h1><p>This one-use ceremony requires independently verified identities and their own fresh MFA sessions. It does not create accounts or grant WordPress administration. Each approval requires MFA completed within five minutes.</p><p><a href="' . esc_url( add_query_arg( 'action', 'revalidate_2fa', wp_login_url( admin_url( 'admin.php?page=csf-identity' ) ) ) ) . '">Complete a fresh second-factor challenge</a></p>';
		if ( null !== self::$result ) {
			echo '<p role="status">' . esc_html( is_wp_error( self::$result ) ? self::$result->get_error_message() : 'Step committed and audited. Resolve uncertain responses using the original command ID.' ) . '</p>'; }
		if ( $m ) {
			echo '<p>Action: ' . esc_html( $m['action'] ) . '; operator #' . esc_html( $m['operator'] ) . '; proposed Farm Owner / Master #' . esc_html( $m['owner'] ) . '; previous master #' . esc_html( $m['previous_master'] ?? 'none' ) . '; site ' . esc_html( $m['site'] ) . '; base revision ' . esc_html( $m['revision'] ) . '. Expires ' . esc_html( gmdate( 'Y-m-d H:i:s', $m['expires'] ) ) . ' UTC.</p>';
			echo '<details><summary>Exact source, target and approval scope</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">' . esc_html( wp_json_encode( $m, JSON_PRETTY_PRINT ) ) . '</pre></details>';
			foreach ( $m['commands'] as $kind => $command ) {
				if ( ! self::authorize( $kind, $m ) ) {
					continue; }
				echo '<form method="post">';
				wp_nonce_field( 'csf_identity' );
				echo '<input type="hidden" name="step" value="' . esc_attr( $kind ) . '"><label><input type="checkbox" name="reviewed" required> I reviewed these exact independently verified identities, source and one-use action.</label> <button class="button" type="submit">' . esc_html(
					array(
						'identity_requested'       => 'Start operator review',
						'identity_owner_approved'  => 'Approve as proposed owner',
						'identity_master_approved' => 'Approve as current owner',
						'identity_consumed'        => 'Confirm ownership change',
					)[ $kind ]
				) . '</button></form>';
			}
		} else {
			echo '<p>No accessible, unexpired owner-authorized invitation. No ownership change is available.</p>'; }
		echo '<h2>Resolve an original command</h2><form method="post">';
		wp_nonce_field( 'csf_identity' );
		echo '<input type="hidden" name="step" value="status"><label>Original command UUID <input name="command" required></label><label><input name="reviewed" type="checkbox" required> Check my authorized result only</label><button class="button">Check result</button></form></div>';
	}
}

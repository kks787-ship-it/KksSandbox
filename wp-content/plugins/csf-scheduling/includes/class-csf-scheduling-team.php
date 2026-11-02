<?php
/** Private native owner dashboard. No account creation, invitations or public endpoints.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/** Nonce-protected master review, employee overrides and group dashboard. */
class CSF_Scheduling_Team {
	/** Verify, preview and save the exact reviewed command.
	 *
	 * @throws RuntimeException Caught and converted to a fixed review error.
	 */
	public static function post() {
		if ( ! isset( $_POST['csf_team_action'] ) ) {
			return;}
		if ( ! CSF_Scheduling_Access::can( 'team_view' ) ) {
			wp_die( 'Private team access required.', '', array( 'response' => 403 ) );}
		check_admin_referer( 'csf_team' );
		nocache_headers();
		$action = is_string( $_POST['csf_team_action'] ) ? sanitize_key( wp_unslash( $_POST['csf_team_action'] ) ) : '';
		if ( 'load' === $action ) {
			return;}
		try {
			if ( 'save' === $action ) {
				$raw       = isset( $_POST['review'] ) && is_string( $_POST['review'] ) ? wp_unslash( $_POST['review'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact bounded signed JSON, never lossy sanitization.
				$payload   = strlen( $raw ) <= 65536 ? json_decode( $raw, true ) : null;
				$expiry    = self::posted_number( 'expiry' );
				$proof     = isset( $_POST['proof'] ) && is_string( $_POST['proof'] ) ? sanitize_text_field( wp_unslash( $_POST['proof'] ) ) : '';
				$confirmed = isset( $_POST['confirm_sensitive'] ) && is_string( $_POST['confirm_sensitive'] ) ? sanitize_key( wp_unslash( $_POST['confirm_sensitive'] ) ) : '';
				if ( ! is_array( $payload ) || $expiry < time() || $expiry > time() + 600 || ! hash_equals( self::proof( $payload, $expiry ), $proof ) || '1' !== $confirmed ) {
					throw new RuntimeException( 'Review changed or expired.' );}
				CSF_Scheduling_Calendar::keys( $payload, array( 'kind', 'body', 'revision', 'command' ) );
				if ( ! in_array( $payload['kind'], array( 'team_member_saved', 'team_members_grouped', 'team_group_saved' ), true ) ) {
					throw new RuntimeException( 'Unsupported action.' );}
				self::$result = CSF_Scheduling_Store::permissions( $payload['kind'], $payload['body'], $payload['revision'], $payload['command'] );
				if ( is_wp_error( self::$result ) ) {
					self::$retry = array(
						'payload' => $payload,
						'expiry'  => $expiry,
					);}
				return;
			}
			$rev = self::posted_number( 'revision' );
			if ( $rev < 0 ) {
				throw new RuntimeException( 'Invalid revision.' );}
			if ( 'preview_member' === $action ) {
				$id    = self::posted_number( 'employee' );
				$group = isset( $_POST['group'] ) && is_string( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : '';
				$raw   = isset( $_POST['override'] ) && is_array( $_POST['override'] ) ? wp_unslash( $_POST['override'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict registry enums checked below and transactionally.
				if ( array_diff( array_keys( $raw ), array_keys( CSF_Scheduling_Permissions::registry() ) ) ) {
					throw new RuntimeException( 'Unknown permission.' );}
				$overrides = array();
				foreach ( $raw as$p => $v ) {
					if ( 'inherit' !== $v ) {
						if ( ! in_array( $v, array( 'grant', 'deny' ), true ) ) {
							throw new RuntimeException( 'Invalid override.' );
						}$overrides[ $p ] = $v;}
				}
				$resources = isset( $_POST['resources'] ) && is_array( $_POST['resources'] ) ? array_values( wp_unslash( $_POST['resources'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- UUID shape and live resource existence verified by service.
				$body      = array(
					'user_id'             => $id,
					'member'              => array(
						'group'     => $group,
						'overrides' => $overrides,
						'resources' => $resources,
						'active'    => isset( $_POST['active'] ),
						'verified'  => isset( $_POST['identity_verified'] ),
					),
					'sensitive_confirmed' => true,
				);
				$kind      = 'team_member_saved';
			} elseif ( 'preview_bulk' === $action ) {
				$ids   = isset( $_POST['employees'] ) && is_array( $_POST['employees'] ) ? array_values( wp_unslash( $_POST['employees'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict integer identity list checked below and transactionally.
				$clean = array();
				foreach ( $ids as$v ) {
					$id = self::numeric( $v );
					if ( $id < 1 ) {
						throw new RuntimeException( 'Invalid identity.' );
					}$clean[] = $id;}
				$group = isset( $_POST['group'] ) && is_string( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : '';
				$body  = array(
					'user_ids'            => $clean,
					'group'               => $group,
					'sensitive_confirmed' => true,
				);
				$kind  = 'team_members_grouped';
			} elseif ( 'preview_group' === $action ) {
				$id          = isset( $_POST['group_id'] ) && is_string( $_POST['group_id'] ) ? sanitize_key( wp_unslash( $_POST['group_id'] ) ) : '';
				$label       = isset( $_POST['group_label'] ) && is_string( $_POST['group_label'] ) ? sanitize_text_field( wp_unslash( $_POST['group_label'] ) ) : '';
				$permissions = isset( $_POST['permissions'] ) && is_array( $_POST['permissions'] ) ? array_values( wp_unslash( $_POST['permissions'] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact implemented permission registry checked transactionally.
				if ( isset( $_POST['copy_group'] ) ) {
					$id    = 'custom_' . str_replace( '-', '', wp_generate_uuid4() );
					$state = CSF_Scheduling_Permissions::state();
					foreach ( $state['groups']as$existing ) {
						if ( $existing['label'] === $label ) {
							throw new RuntimeException( 'Choose a distinct custom group name.' );}
					}
				}
				$body = array(
					'id'                  => $id,
					'group'               => array(
						'label'       => $label,
						'permissions' => $permissions,
					),
					'sensitive_confirmed' => true,
				);
				$kind = 'team_group_saved';
			} else {
				throw new RuntimeException( 'Unsupported action.' );}
			$payload = array(
				'kind'     => $kind,
				'body'     => $body,
				'revision' => $rev,
				'command'  => wp_generate_uuid4(),
			);
			$result  = CSF_Scheduling_Store::permissions( $kind, $body, $rev, $payload['command'], true );
			if ( is_wp_error( $result ) ) {
				self::$result = $result;
				return;}
			self::$review = array(
				'payload' => $payload,
				'result'  => $result,
				'expiry'  => time() + 600,
			);
		} catch ( Throwable $error ) {
			self::$result = new WP_Error( 'team_review', 'Permission review failed. Reload and verify selection, rights, MFA and current revision.' );}
	}

	/** Strict numeric form reader.
	 *
	 * @param string $key Approved form key.
	 * @return int
	 */
	private static function posted_number( $key ) {
		return self::numeric( isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : null ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called only after the enclosing controller nonce check.
	}

	/** Escape a hidden input.
	 *
	 * @param string $name Field name.
	 * @param mixed  $value Field value.
	 */
	private static function hidden( $name, $value ) {
		echo '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '">';}

	/** Open a nonce-protected form.
	 *
	 * @param string $action Fixed dashboard action.
	 */
	private static function form( $action ) {
		echo '<form method="post">';
		wp_nonce_field( 'csf_team' );
		self::hidden( 'csf_team_action', $action );}

	/** Current rollback-only review.
	 *
	 * @var array|null
	 */
	private static $review = null;
	/** Command outcome.
	 *
	 * @var array|WP_Error|null
	 */
	private static $result = null;
	/** Exact reviewed retry envelope.
	 *
	 * @var array|null
	 */
	private static $retry = null;

	/** Register the private team submenu. */
	public static function menu() {
		if ( CSF_Scheduling_Access::can( 'team_view' ) ) {
			add_submenu_page( 'csf-scheduling', 'Team & Permissions', 'Team & Permissions', 'read', 'csf-team', array( __CLASS__, 'render' ) );
		}
	}

	/** Exact owner preview proof binds actor, fields, revision, command and expiry.
	 *
	 * @param array $payload Reviewed command envelope.
	 * @param int   $expiry Review expiration timestamp.
	 */
	private static function proof( $payload, $expiry ) {
		return hash_hmac( 'sha256', get_current_user_id() . ':' . $expiry . ':' . wp_json_encode( CSF_Scheduling_Store::canonical( $payload ) ), wp_salt( 'auth' ) );
	}

	/** Parse a bounded integer; never coerce malformed input.
	 *
	 * @param mixed $v Untrusted scalar input.
	 */
	private static function numeric( $v ) {
		return is_string( $v ) && preg_match( '/^(0|[1-9][0-9]{0,9})$/D', $v ) ? (int) $v : -1;
	}

	/** POST navigation and preview/save all require a nonce; GET exposes no account selector.
	 *
	 * @param array $s Validated facility registry.
	 * @param int   $id Verified numeric identity.
	 * @param array $member Employee group and overrides.
	 * @param bool  $group_mode Render a group template.
	 * @param bool  $editable May the viewer change the displayed choices.
	 */
	public static function matrix( $s, $id, $member, $group_mode = false, $editable = true ) {
		$actual         = CSF_Scheduling_Permissions::effective( $s, $id );
		$mfa            = ! $group_mode && CSF_Scheduling_Permissions::mfa( $id );
		$current_ok     = $mfa && get_userdata( $id ) && CSF_Scheduling_Access::mirror_matches( $id, $actual );
		$proposed_ready = ! empty( $member['active'] ) && ! empty( $member['verified'] ) && $mfa;
		$current        = '';
		foreach ( CSF_Scheduling_Permissions::registry() as $p => $spec ) {
			if ( $current !== $spec[0] ) {
				if ( $current ) {
					echo '</fieldset></details>';
				}$current = $spec[0];
				echo '<details open><summary>' . esc_html( $current ) . '</summary><fieldset><legend class="screen-reader-text">' . esc_html( $current ) . ' permissions</legend>';}
			$default  = $id === $s['master'] || in_array( $p, $s['groups'][ $member['group'] ]['permissions'], true );
			$override = $member['overrides'][ $p ] ?? 'inherit';
			$yes      = 'deny' !== $override && ( 'grant' === $override || $default );
			echo '<div class="csf-permission" data-default="' . ( $default ? '1' : '0' ) . '" data-mfa="' . ( $mfa ? '1' : '0' ) . '" data-active="' . ( ! empty( $member['active'] ) ? '1' : '0' ) . '" data-verified="' . ( ! empty( $member['verified'] ) ? '1' : '0' ) . '"><label><input type="checkbox" ' . ( $group_mode ? 'name="permissions[]" value="' . esc_attr( $p ) . '"' : 'data-effective' ) . ' ' . checked( $yes, true, false ) . ( $editable ? '' : ' disabled' ) . '> ' . esc_html( $spec[1] ) . '</label>';
			if ( ! $group_mode ) {
				echo '<p>' . ( $id === $s['master'] ? 'Protected master default' : 'Group default' ) . ': <strong>' . ( $default ? 'Grant' : 'No grant' ) . '</strong></p><label>Individual override <select name="override[' . esc_attr( $p ) . ']" data-override' . ( $editable ? '' : ' disabled' ) . '>';
				foreach ( array(
					'inherit' => 'Inherit',
					'grant'   => 'Grant',
					'deny'    => 'Deny',
				)as$v => $l ) {
					echo '<option value="' . esc_attr( $v ) . '" ' . selected( $override, $v, false ) . '>' . esc_html( $l ) . '</option>';
				}echo '</select></label><p>Effective now: <strong data-current>' . ( $current_ok && $actual[ $p ] ? 'Granted' : 'Denied' ) . '</strong></p><p>After save: <strong data-result>' . ( $yes && $proposed_ready ? 'Granted if save succeeds' : 'Denied' ) . '</strong></p>';}
			echo '</div>';
		}
		if ( $current ) {
			echo '</fieldset></details>';}
	}

	/** Render the private owner decision screen. */
	public static function render() {
		if ( ! CSF_Scheduling_Access::can( 'team_view' ) ) {
			wp_die( 'Private team access required.', '', array( 'response' => 403 ) );}
		if ( ! empty( $_POST ) ) {
			check_admin_referer( 'csf_team' ); }
		nocache_headers();
		$s        = CSF_Scheduling_Permissions::state();
		$revision = (int) CSF_Scheduling_Schema::settings()['revision'];
		echo '<div class="wrap csf-team"><h1>Team &amp; Permissions</h1><p>Farm Owner / Master is a separately verified identity, not a WordPress Administrator role or assignable employee group. Changes require preview and confirmation. MFA is independently checked.</p>';
		if ( self::$result ) {
			echo '<div role="status"><p>' . esc_html( is_wp_error( self::$result ) ? 'Save not acknowledged. Verify current permissions before retrying.' : 'Permissions saved and audited.' ) . '</p></div>';}
		if ( self::$retry ) {
			$r = self::$retry;
			self::form( 'save' );
			self::hidden( 'review', wp_json_encode( $r['payload'] ) );
			self::hidden( 'expiry', $r['expiry'] );
			self::hidden( 'proof', self::proof( $r['payload'], $r['expiry'] ) );
			self::hidden( 'confirm_sensitive', '1' );
			echo '<p>Retry the original reviewed command after resolving an uncertain result.</p><button class="button">Retry exact reviewed command</button></form>';}
		if ( self::$review ) {
			$r = self::$review;
			echo '<section aria-labelledby="csf-team-review"><h2 id="csf-team-review">Review changes before saving</h2><p>Revision ' . esc_html( $r['payload']['revision'] ) . '. All affected people are listed below. This preview has not saved any rights.</p>';
			$group_key   = $r['payload']['body']['member']['group'] ?? ( $r['payload']['body']['id'] ?? $r['payload']['body']['group'] ?? '' );
			$group_label = $r['payload']['body']['group']['label'] ?? ( $s['groups'][ $group_key ]['label'] ?? $group_key );
			echo '<p>Permission group: ' . esc_html( $group_label ) . '</p>';
			if ( 'team_group_saved' === $r['payload']['kind'] ) {
				echo '<p>Proposed group privileges: ' . esc_html( self::permission_labels( $r['payload']['body']['group']['permissions'] ) ) . '</p>';}
			foreach ( $r['result']['changes']as$c ) {
				echo '<article><h3>Account #' . esc_html( $c['user_id'] ) . '</h3><p>Newly granted: ' . esc_html( self::permission_labels( $c['granted'] ) ) . '</p><p>Revoked: ' . esc_html( self::permission_labels( $c['revoked'] ) ) . '</p><p>Effective access: ' . esc_html( self::permission_labels( $c['effective'] ) ) . '</p></article>';}
			if ( ! $r['result']['changes'] ) {
				echo '<p>No current employee effective access changes; template/override changes are still audited.</p>';}
			self::form( 'save' );
			self::hidden( 'review', wp_json_encode( $r['payload'] ) );
			self::hidden( 'expiry', $r['expiry'] );
			self::hidden( 'proof', self::proof( $r['payload'], $r['expiry'] ) );
			echo '<label><input type="checkbox" name="confirm_sensitive" value="1" required> I reviewed the selected account/group, every affected member, access changes and sensitive rights.</label><p><button class="button button-primary">Confirm reviewed changes</button></p></form></section>';}
		$users = get_users(
			array(
				'number'  => 101,
				'orderby' => 'ID',
			)
		);
		if ( count( $users ) > 100 ) {
			echo '<p>Account selection exceeds this pilot limit. Stop for review.</p></div>';
			return;}
		self::form( 'load' );
		echo '<label>Employee account <select name="employee">';
		$id = self::numeric( isset( $_POST['employee'] ) && is_string( $_POST['employee'] ) ? sanitize_text_field( wp_unslash( $_POST['employee'] ) ) : null );
		foreach ( $users as$u ) {
			echo '<option value="' . esc_attr( $u->ID ) . '" ' . selected( $id, (int) $u->ID, false ) . '>' . esc_html( $u->display_name . ' (#' . $u->ID . ')' ) . '</option>';
		}echo '</select></label><button class="button">Select employee</button></form>';
		if ( $id > 0 && get_userdata( $id ) ) {
			$m      = $s['members'][ $id ] ?? array(
				'group'     => 'read_only',
				'overrides' => array(),
				'resources' => array(),
				'active'    => false,
				'verified'  => false,
			);
			$chosen = isset( $_POST['group'] ) && is_string( $_POST['group'] ) ? sanitize_key( wp_unslash( $_POST['group'] ) ) : $m['group'];
			if ( isset( $s['groups'][ $chosen ] ) ) {
				$m['group'] = $chosen;}
			echo '<h2>Employee #' . esc_html( $id ) . ( $id === $s['master'] ? ' — verified Farm Owner / Master' : '' ) . '</h2><p>Independent MFA: ' . ( CSF_Scheduling_Permissions::mfa( $id ) ? 'verified by approved mechanism' : 'unverified — active access blocked' ) . '</p>';
			if ( CSF_Scheduling_Access::can( 'team_assign' ) ) {
				self::form( 'load' );
				self::hidden( 'employee', $id );
				echo '<label>Permission group <select name="group">';
				foreach ( $s['groups']as$key => $g ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $m['group'], $key, false ) . '>' . esc_html( $g['label'] ) . '</option>';
				}echo '</select></label><button class="button">Load group defaults</button></form><p>Load the group first, then customize this person. Group templates are not changed by individual overrides.</p>';
				self::form( 'preview_member' );
				self::hidden( 'employee', $id );
				self::hidden( 'group', $m['group'] );
				self::hidden( 'revision', $revision );
				echo '<p><label><input type="checkbox" name="active" ' . checked( $m['active'], true, false ) . '> Active farm access</label></p><p><label><input type="checkbox" name="identity_verified" ' . checked( $m['verified'], true, false ) . '> I independently verified this employee identity</label></p>';
				echo '<fieldset><legend>Explicit assigned calendar resources (used by Assigned view)</legend>';
				global $wpdb;
				$t = CSF_Scheduling_Schema::table( 'resources' );
				foreach ( $wpdb->get_results( "SELECT uuid,label FROM `$t` WHERE active=1 ORDER BY label", ARRAY_A )as$resource ) {
					echo '<label><input type="checkbox" name="resources[]" value="' . esc_attr( $resource['uuid'] ) . '" ' . checked( in_array( $resource['uuid'], $m['resources'], true ), true, false ) . '>' . esc_html( $resource['label'] ) . '</label> ';
				}echo '</fieldset>';
				self::matrix( $s, $id, $m );
				echo '<p><button class="button button-primary">Preview employee changes</button></p></form>';
			} else {
				self::matrix( $s, $id, $m, false, false );
				echo '<p>Read-only team overview. No save authority.</p>';}
		}
		if ( CSF_Scheduling_Access::can( 'team_assign' ) ) {
			echo '<details><summary>Assign one group to several verified employees</summary><p>Individual overrides and resource assignments are retained. The protected master cannot be bulk-assigned.</p>';
			self::form( 'preview_bulk' );
			self::hidden( 'revision', $revision );
			echo '<fieldset><legend>Existing verified team members</legend>';
			foreach ( $users as$u ) {
				if ( (int) $u->ID !== $s['master'] && ! empty( $s['members'][ $u->ID ]['verified'] ) ) {
					echo '<label><input type="checkbox" name="employees[]" value="' . esc_attr( $u->ID ) . '">' . esc_html( $u->display_name . ' (#' . $u->ID . ')' ) . '</label>';
				}
			}echo '</fieldset><label>Group <select name="group">';
			foreach ( $s['groups']as$key => $g ) {
				echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $g['label'] ) . '</option>';
			}echo '</select></label><button class="button">Preview employee group assignments</button></form></details>';
		}
		if ( CSF_Scheduling_Access::can( 'team_groups' ) ) {
			echo '<h2>Permission groups</h2><p>Edit a preset, or check Create a new custom group and give it a distinct name. Preview lists all affected members and retains their individual overrides.</p>';
			foreach ( $s['groups']as$key => $g ) {
				echo '<details><summary>' . esc_html( $g['label'] ) . '</summary>';
				self::form( 'preview_group' );
				self::hidden( 'revision', $revision );
				self::hidden( 'group_id', $key );
				echo '<label>Group name <input name="group_label" value="' . esc_attr( $g['label'] ) . '" data-original-label="' . esc_attr( $g['label'] ) . '" maxlength="100" required></label><label><input type="checkbox" name="copy_group" data-copy> Create a new custom group from these choices</label>';
				self::matrix(
					$s,
					0,
					array(
						'group'     => $key,
						'overrides' => array(),
					),
					true
				);
				echo '<button class="button">Preview group and affected members</button></form></details>';}
		}
		if ( CSF_Scheduling_Access::can( 'team_audit' ) ) {
			self::history();}
		echo '<p>Owner transfer/recovery requires separate identity approval and a guarded bootstrap, never an employee checkbox. No accounts, messages or WordPress roles are created here.</p></div>';
	}

	/** Friendly privilege descriptions for the owner review.
	 *
	 * @param array $permissions Implemented permission keys.
	 * @return string
	 */
	private static function permission_labels( $permissions ) {
		return $permissions ? implode( ', ', array_map( static fn( $p )=>CSF_Scheduling_Permissions::registry()[ $p ][1] ?? 'Unknown permission', $permissions ) ) : 'None';}

	/** Load private page assets before wp-admin head output.
	 *
	 * @param string $hook Admin page hook.
	 */
	public static function assets( $hook ) {
		if ( ! str_ends_with( $hook, '_page_csf-team' ) || ! CSF_Scheduling_Access::can( 'team_view' ) ) {
			return; }
		wp_enqueue_script( 'csf-scheduling-team', plugins_url( '../assets/team.js', __FILE__ ), array(), CSF_SCHEDULING_VERSION, true );
		wp_enqueue_style( 'csf-scheduling-team', plugins_url( '../assets/team.css', __FILE__ ), array(), CSF_SCHEDULING_VERSION );
	}

	/** Filter permission events in SQL before bounded keyset pagination. */
	private static function history() {
		global $wpdb;
		if ( ! empty( $_POST ) ) {
			check_admin_referer( 'csf_team' );}
		$person = self::posted_number( 'history_person' );
		$before = self::posted_number( 'history_before' );
		$from   = isset( $_POST['history_from'] ) && is_string( $_POST['history_from'] ) ? sanitize_text_field( wp_unslash( $_POST['history_from'] ) ) : '';
		$to     = isset( $_POST['history_to'] ) && is_string( $_POST['history_to'] ) ? sanitize_text_field( wp_unslash( $_POST['history_to'] ) ) : '';
		echo '<h2>Permission history</h2>';
		self::form( 'load' );
		echo '<label>Account ID <input type="number" name="history_person" min="1" value="' . esc_attr( $person > 0 ? $person : '' ) . '"></label><label>From (UTC) <input type="date" name="history_from" value="' . esc_attr( $from ) . '"></label><label>Through (UTC) <input type="date" name="history_to" value="' . esc_attr( $to ) . '"></label><button class="button">Filter permission history</button></form>';
		$days = array();
		foreach ( array(
			'from' => $from,
			'to'   => $to,
		)as$key => $value ) {
			if ( '' !== $value ) {
				$d = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, new DateTimeZone( 'UTC' ) );
				if ( ! $d || $d->format( 'Y-m-d' ) !== $value ) {
					echo '<p role="alert">Choose valid UTC dates.</p>';
					return;
				}$days[ $key ] = $d;}
		}
		if ( isset( $days['from'], $days['to'] ) && $days['from'] > $days['to'] ) {
			echo '<p role="alert">From must not be after Through.</p>';
			return;}
		$params = CSF_Scheduling_Permissions::COMMANDS;
		$where  = array( "JSON_UNQUOTE(JSON_EXTRACT(body_json,'$.kind')) IN (" . implode( ',', array_fill( 0, count( $params ), '%s' ) ) . ')' );
		if ( $person > 0 ) {
			$where[]  = "JSON_CONTAINS(JSON_EXTRACT(body_json,'$.changes'),JSON_OBJECT('user_id',%d))";
			$params[] = $person;}
		if ( isset( $days['from'] ) ) {
			$where[]  = 'created_at_utc >= %s';
			$params[] = $days['from']->format( 'Y-m-d H:i:s' );}
		if ( isset( $days['to'] ) ) {
			$where[]  = 'created_at_utc < %s';
			$params[] = $days['to']->modify( '+1 day' )->format( 'Y-m-d H:i:s' );}
		if ( $before > 0 ) {
			$where[]  = 'id < %d';
			$params[] = $before;}
		$t     = CSF_Scheduling_Schema::table( 'history' );
		$query = "SELECT * FROM `$t` WHERE " . implode( ' AND ', $where ) . ' ORDER BY id DESC LIMIT 501';
		$rows  = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Fixed internal identifiers/clauses; every dynamic value has a typed placeholder.
		if ( $wpdb->last_error ) {
			echo '<p role="alert">Permission history is unavailable; no unfiltered fallback was returned.</p>';
			return;}
		echo '<p>Up to 500 matching changes per page. Times are UTC. Use Filter to return to newest matches.</p><ul>';
		$shown = array_slice( $rows, 0, 500 );
		foreach ( $shown as$row ) {
			$d       = json_decode( $row['body_json'], true );
			$changes = $d['changes'] ?? array();
			echo '<li>' . esc_html( $row['created_at_utc'] . ' UTC · staff #' . $row['created_by'] . ' · ' . str_replace( '_', ' ', $d['kind'] ) . ' · affected accounts: ' . implode( ', ', array_column( $changes, 'user_id' ) ) ) . '</li>';}
		echo '</ul>';
		if ( count( $rows ) > 500 ) {
			self::form( 'load' );
			self::hidden( 'history_person', $person > 0 ? $person : '' );
			self::hidden( 'history_from', $from );
			self::hidden( 'history_to', $to );
			self::hidden( 'history_before', $shown[ count( $shown ) - 1 ]['id'] );
			echo '<button class="button">Older matching changes</button></form>';}
	}
}

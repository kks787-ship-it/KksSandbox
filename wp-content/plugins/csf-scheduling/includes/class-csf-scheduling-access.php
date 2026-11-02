<?php
/** Authoritative farm membership and fail-closed, non-authoritative WP mirrors.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;
// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
/** Current authoritative authorization and cache-independent capability reconciliation. */
class CSF_Scheduling_Access {
	/** Check for an independently granted native write action.
	 *
	 * @return bool
	 */
	public static function writer() {
		foreach ( array( 'appointment_edit', 'appointment_confirm', 'appointment_cancel', 'rider_edit', 'rider_clearance', 'suitability_manage', 'resource_manage', 'availability_manage', 'welfare_hold', 'welfare_release' )as$p ) {
			if ( self::can( $p ) ) {
				return true;
			}
		}return false;
	}

	const CAPS = array( 'csf_schedule_view', 'csf_schedule_manage', 'csf_schedule_confirm', 'csf_schedule_audit' );

	/** Every protected operation reads current membership/MFA/capability metadata.
	 *
	 * @param string $cap Implemented permission key.
	 */
	public static function can( $cap = 'calendar_view' ) {
		if ( 'calendar_view' === $cap || 'csf_schedule_view' === $cap ) {
			return self::can( 'calendar_all' ) || self::can( 'calendar_assigned' ); }
		$aliases = array(
			'csf_schedule_confirm' => 'appointment_confirm',
			'csf_schedule_audit'   => 'audit_view',
		);
		$cap     = $aliases[ $cap ] ?? $cap;
		$id      = get_current_user_id();
		if ( ! isset( CSF_Scheduling_Permissions::registry()[ $cap ] ) || ! $id || ! CSF_Scheduling_Schema::inspect()['ready'] || ! CSF_Scheduling_Permissions::mfa( $id ) ) {
			return false; }
		$s = CSF_Scheduling_Permissions::state();
		if ( ! $s || ! ( CSF_Scheduling_Permissions::effective( $s, $id )[ $cap ] ?? false ) ) {
			return false; }
		return self::mirror_matches( $id, CSF_Scheduling_Permissions::effective( $s, $id ) );
	}

	/** Verify the complete raw primary-DB mirror, never cached current_user caps.
	 *
	 * @param int   $id Verified numeric identity.
	 * @param array $rights Effective rights.
	 */
	public static function mirror_matches( $id, $rights ) {
		global $wpdb;
		$raw  = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s", $id, $wpdb->prefix . 'capabilities' ) );
		$caps = maybe_unserialize( $raw );
		if ( ! is_array( $caps ) ) {
			return false;}
		foreach ( $rights as$p => $yes ) {
			if ( $yes ? true !== ( $caps[ 'csf_dr_' . $p ] ?? null ) : ( isset( $caps[ 'csf_dr_' . $p ] ) && false !== $caps[ 'csf_dr_' . $p ] ) ) {
				return false;}
		}
		return true;
	}

	/** Any native command may have its own fine-grained write privilege.
	 *
	 * @param array  $ids Ids.
	 * @param bool   $verified Verified.
	 * @param int    $revision Revision.
	 * @param string $command Command.
	 */
	public static function provision( $ids, $verified, $revision, $command ) {
		unset( $ids, $verified, $revision, $command );
		return new WP_Error( 'provision_retired', 'Use the verified master Team & Permissions review. No blanket grants are made.' );
	}

	/** Post-commit reconciliation; authoritative deny is already immediate. Exact retry repairs mirrors. */
	public static function reconcile() {
		$s = CSF_Scheduling_Permissions::state();
		if ( ! $s ) {
			return new WP_Error( 'permission_state', 'Permission registry unavailable.' ); }
		foreach ( $s['members'] as $id => $m ) {
			$u      = new WP_User( (int) $id );
			$rights = CSF_Scheduling_Permissions::effective( $s, (int) $id );
			foreach ( self::CAPS as $p ) {
				$u->remove_cap( $p ); }
			foreach ( $rights as $p => $yes ) {
				if ( $yes ) {
					$u->add_cap( 'csf_dr_' . $p );
				} else {
					$u->remove_cap( 'csf_dr_' . $p ); }
			}
			clean_user_cache( (int) $id );
			global $wpdb;
			$raw    = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s", $id, $wpdb->prefix . 'capabilities' ) );
			$actual = maybe_unserialize( $raw );
			if ( ! self::mirror_matches( (int) $id, $rights ) ) {
				return new WP_Error( 'provision_reconcile', 'Membership committed; mirror reconciliation incomplete. Retry the same command.' ); }
		}
		return true;
	}
}

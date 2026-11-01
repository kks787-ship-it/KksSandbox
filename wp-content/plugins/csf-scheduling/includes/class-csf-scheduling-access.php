<?php
/** Explicit capabilities plus an authoritative staff allowlist.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

// Authoritative transactional data must bypass WordPress object caches.
// phpcs:disable WordPress.DB.DirectDatabaseQuery

/** Generic WordPress roles never imply scheduling access. */
class CSF_Scheduling_Access {
	const CAPS = array( 'csf_schedule_view', 'csf_schedule_manage', 'csf_schedule_confirm', 'csf_schedule_audit' );

	/** Permission checks fail closed before schema creation or with malformed membership.
	 *
	 * @param string $cap Scheduling capability.
	 * @return bool
	 */
	public static function can( $cap = 'csf_schedule_view' ) {
		if ( ! in_array( $cap, self::CAPS, true ) || ! get_current_user_id() || ! current_user_can( $cap ) || ! CSF_Scheduling_Schema::inspect()['ready'] ) {
			return false;
		}
		$staff = json_decode( CSF_Scheduling_Schema::settings()['staff_json'], true );
		return is_array( $staff ) && array_is_list( $staff ) && count( $staff ) <= 50 && count( array_filter( $staff, static fn( $id ) => is_int( $id ) && $id > 0 ) ) === count( $staff ) && count( array_unique( $staff ) ) === count( $staff ) && in_array( get_current_user_id(), $staff, true );
	}

	/** Explicit owner-verified IDs, not email/display-name inference. No new roles.
	 *
	 * @param array  $ids Verified WP user IDs.
	 * @param bool   $verified CLI operator acknowledgment.
	 * @param int    $revision Reviewed singleton revision.
	 * @param string $command Idempotency UUID.
	 * @return array|WP_Error
	 */
	public static function provision( $ids, $verified, $revision, $command ) {
		if ( true !== $verified || ! get_current_user_id() || ! current_user_can( 'manage_options' ) || ! is_array( $ids ) || count( array_filter( $ids, 'is_int' ) ) !== count( $ids ) || array_values( array_unique( $ids ) ) !== $ids || ! $ids || count( $ids ) > 50 ) {
			return new WP_Error( 'provision_denied', 'Verified owner operator and explicit user IDs required.' );
		}
		foreach ( $ids as $id ) {
			if ( ! is_int( $id ) || $id < 1 || ! get_userdata( $id ) ) {
				return new WP_Error( 'provision_ids', 'All staff IDs must resolve to real WordPress users.' );
			}
		}
		sort( $ids );
		$result = CSF_Scheduling_Store::staff( $ids, $verified, $revision, $command );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		// Capabilities are a reconciled mirror, never the authoritative membership gate.
		// A crash/failure here denies access until replay reconciles; removal is immediate.
		$current = json_decode( CSF_Scheduling_Schema::settings()['staff_json'], true );
		if ( ! is_array( $current ) || ! array_is_list( $current ) ) {
			return new WP_Error( 'provision_reconcile', 'Membership changed unexpectedly; review before replay.' );
		}
		foreach ( array_unique( array_merge( $result['previous_staff'], $ids, $current ) ) as $id ) {
			$user = new WP_User( $id );
			foreach ( self::CAPS as $cap ) {
				if ( in_array( $id, $current, true ) ) {
					$user->add_cap( $cap );
				} else {
					$user->remove_cap( $cap );
				}
			}
			clean_user_cache( $id );
			global $wpdb;
			$raw  = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s", $id, $wpdb->prefix . 'capabilities' ) );
			$caps = maybe_unserialize( $raw );
			foreach ( self::CAPS as $cap ) {
				if ( ! is_array( $caps ) || ! empty( $caps[ $cap ] ) !== in_array( $id, $current, true ) ) {
					return new WP_Error( 'provision_reconcile', 'Membership recorded; capability synchronization incomplete. Replay the same command.' );
				}
			}
		}
		return $result;
	}
}

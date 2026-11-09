<?php
/** Explicit financial access.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;

/** Capabilities are explicit per person; administrator/scheduling access is insufficient. */
final class CSF_Billing_Access {
	/** Fresh capability and real MFA checks, including on idempotent retries.
	 *
	 * @param string $capability Explicit billing capability.
	 */
	public static function require_cap( $capability ) {
		global $wpdb;
		$id = get_current_user_id();
		CSF_Billing_Domain::need( $id > 0 && in_array( $capability, array( 'csf_billing_manage', 'csf_billing_redeem', 'csf_billing_audit' ), true ) && class_exists( 'CSF_MFA_Session' ) && method_exists( 'CSF_MFA_Session', 'web_verified' ) && CSF_MFA_Session::web_verified( $id ), 'billing_access_required' );
		// Read direct user grants only; role grants and stale object caches are not authority.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$values = $wpdb->get_col( $wpdb->prepare( 'SELECT meta_value FROM %i WHERE user_id = %d AND meta_key = %s FOR UPDATE', $wpdb->usermeta, $id, $wpdb->get_blog_prefix() . 'capabilities' ) );
		CSF_Billing_Domain::need( 1 === count( $values ), 'billing_access_required' );
		// Only WordPress scalar/array capability data, never instantiate serialized objects.
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
		$caps = unserialize( $values[0], array( 'allowed_classes' => false ) );
		CSF_Billing_Domain::need( is_array( $caps ) && true === ( $caps[ $capability ] ?? false ), 'billing_access_required' );
		return $id;
	}
}

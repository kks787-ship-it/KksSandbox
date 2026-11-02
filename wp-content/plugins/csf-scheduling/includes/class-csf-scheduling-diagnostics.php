<?php
/** Private readiness diagnostics only.
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

/** Readiness metadata for the private calendar candidate. */
class CSF_Scheduling_Diagnostics {
	/** Safe health metadata with no identities or policy bodies.
	 *
	 * @return array|WP_Error
	 */
	public static function report() {
		if ( ! CSF_Scheduling_Access::can() ) {
			return new WP_Error( 'forbidden', 'Provisioned scheduling staff access required.' );
		}
		$row     = CSF_Scheduling_Schema::settings();
		$policy  = json_decode( $row['policy_json'], true );
		$missing = CSF_Scheduling_Policy::missing( $policy );
		if ( ! is_wp_error( CSF_Scheduling_Policy::validate( $policy ) ) ) {
			global $wpdb;
			$t = CSF_Scheduling_Schema::table( 'resources' );
			// Active resource counts are authoritative and never cross-request cached.
			$counts = $wpdb->get_results( "SELECT kind,COUNT(*) AS total FROM `$t` WHERE active=1 GROUP BY kind", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery
			foreach ( $policy['services'] as $service ) {
				foreach ( $service['enabled'] ? ( $service['required_resources'] ?? array() ) : array() as $kind ) {
					if ( empty( $counts[ $kind ]->total ) ) {
						$missing[] = 'resource:' . $kind;
					}
				}
			}
		}
		global $wpdb;
		$auth = CSF_Scheduling_Permissions::state();
		if ( ! $auth || null === $auth['master'] ) {
			$missing[] = 'verified_master'; }
		foreach ( $auth['members'] ?? array() as $id => $member ) {
			if ( ! CSF_Scheduling_Access::mirror_matches( (int) $id, CSF_Scheduling_Permissions::effective( $auth, (int) $id ) ) ) {
				$missing[] = 'staff_permissions'; }
			if ( $member['active'] && ( ! $member['verified'] || ! CSF_Scheduling_Permissions::mfa( (int) $id ) ) ) {
				$missing[] = 'staff_verification'; }
		}

		return array(
			'plugin_version'               => CSF_SCHEDULING_VERSION,
			'schema'                       => CSF_Scheduling_Schema::inspect(),
			'policy_revision'              => (int) $row['revision'],
			'missing_policy'               => $missing,
			'foundation_ready'             => ! $missing,
			'private_calendar_implemented' => true,
			'appointment_writes_enabled'   => ! $missing,
			'message'                      => 'Private calendar candidate. Explicit availability, roster and acceptance are required before operational use.',
		);
	}

	/** Staff-only wp-admin menu.
	 */
	public static function menu() {
		add_menu_page( 'Scheduling Foundation', 'Scheduling Foundation', 'csf_schedule_view', 'csf-scheduling', array( __CLASS__, 'render' ), 'dashicons-calendar-alt' );
	}

	/** Read-only output; no public pages or forms.
	 */
	public static function render() {
		$report = self::report();
		if ( is_wp_error( $report ) ) {
			wp_die( esc_html( $report->get_error_message() ), '', array( 'response' => 403 ) );
		}
		echo '<div class="wrap"><h1>Scheduling Readiness</h1><p>Private calendar candidate; verify configuration before operational use.</p><pre>' . esc_html( wp_json_encode( $report, JSON_PRETTY_PRINT ) ) . '</pre></div>';
	}
}

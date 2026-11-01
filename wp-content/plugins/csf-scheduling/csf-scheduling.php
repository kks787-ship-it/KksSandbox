<?php
/**
 * Plugin Name: Digital Ranch Scheduling Foundation
 * Description: Private facility scheduling foundation. Not an operational calendar.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.6
 * Text Domain: csf-scheduling
 * License: GPL-2.0-or-later
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

define( 'CSF_SCHEDULING_VERSION', '0.1.0' );
foreach ( array( 'schema', 'audit', 'policy', 'access', 'store', 'diagnostics', 'cli' ) as $csf_module ) {
	require_once __DIR__ . '/includes/class-csf-scheduling-' . $csf_module . '.php';
}
unset( $csf_module );
// Deliberately no activation, deactivation, uninstall, cron or public-route side effects.
add_action( 'admin_menu', array( 'CSF_Scheduling_Diagnostics', 'menu' ) );
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'csf scheduling', 'CSF_Scheduling_CLI' );
}

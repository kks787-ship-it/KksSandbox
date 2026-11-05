<?php
/**
 * Plugin Name: Digital Ranch Staff Scheduling
 * Description: Private staff calendar with explicit confirmation and resource protection.
 * Version: 0.4.2
 * Requires PHP: 8.1
 * Requires at least: 6.6
 * Text Domain: csf-scheduling
 * License: GPL-2.0-or-later
 *
 * @package CSF_Scheduling
 */

defined( 'ABSPATH' ) || exit;

define( 'CSF_SCHEDULING_VERSION', '0.4.2' );
foreach ( array( 'schema', 'audit', 'policy', 'permissions', 'access', 'store', 'time', 'conflict', 'calendar', 'admin', 'ui', 'diagnostics', 'team', 'identity', 'settings', 'cli' ) as $csf_module ) {
	require_once __DIR__ . '/includes/class-csf-scheduling-' . $csf_module . '.php';
}
unset( $csf_module );
// Deliberately no activation, deactivation, uninstall, cron or public-route side effects.
add_action( 'admin_menu', array( 'CSF_Scheduling_Admin', 'menu' ) );
add_action( 'admin_init', array( 'CSF_Scheduling_Admin', 'post' ) );
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'csf scheduling', 'CSF_Scheduling_CLI' );
}

add_action( 'wp_ajax_csf_schedule_tools', array( 'CSF_Scheduling_Admin', 'ajax' ) );
add_action( 'admin_enqueue_scripts', array( 'CSF_Scheduling_UI', 'assets' ) );

add_action( 'admin_menu', array( 'CSF_Scheduling_Team', 'menu' ) );
add_action( 'admin_init', array( 'CSF_Scheduling_Team', 'post' ) );

add_action( 'admin_enqueue_scripts', array( 'CSF_Scheduling_Team', 'assets' ) );

add_action( 'admin_menu', array( 'CSF_Scheduling_Identity', 'menu' ) );
add_action( 'admin_init', array( 'CSF_Scheduling_Identity', 'post' ) );
add_filter( 'map_meta_cap', array( 'CSF_Scheduling_Identity', 'protect_master' ), PHP_INT_MAX, 4 );
add_action( 'delete_user', array( 'CSF_Scheduling_Identity', 'deleting' ), -100 );
add_action( 'remove_user_from_blog', array( 'CSF_Scheduling_Identity', 'deleting' ), -100 );
add_filter( 'rest_pre_dispatch', array( 'CSF_Scheduling_Identity', 'rest_user' ), PHP_INT_MAX, 3 );

add_action( 'admin_init', array( 'CSF_Scheduling_Settings', 'post' ) );

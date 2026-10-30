<?php
/**
 * Plugin Name:       Corner Stone Farm Emergency Management
 * Description:       Emergency horse identification pages, farm emergency status, sitewide emergency banner, and the /emergency landing page.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Corner Stone Farm
 * License:           GPL-2.0-or-later
 * Text Domain:       csf-emergency
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

define( 'CSF_EMERGENCY_VERSION', '0.1.0' );
define( 'CSF_EMERGENCY_DB_VERSION', '1' );
define( 'CSF_EMERGENCY_FILE', __FILE__ );
define( 'CSF_EMERGENCY_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_EMERGENCY_URL', plugin_dir_url( __FILE__ ) );

require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-capabilities.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-tokens.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-statuses.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-event-log.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-settings.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-farm-state.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-horses.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-privacy.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-public.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-banner.php';
require_once CSF_EMERGENCY_DIR . 'includes/class-csf-emergency-admin.php';

/**
 * Activation: create the log table, grant capabilities, register routes and flush rewrites.
 */
function csf_emergency_activate() {
	CSF_Emergency_Event_Log::install();
	CSF_Emergency_Capabilities::install();
	CSF_Emergency_Horses::register_post_type();
	CSF_Emergency_Public::add_rewrite_rules();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'csf_emergency_activate' );

/**
 * Deactivation: remove our rewrite rules. Data and capabilities are kept on purpose;
 * emergency records must never disappear because a plugin was toggled.
 */
function csf_emergency_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'csf_emergency_deactivate' );

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'csf-emergency', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		CSF_Emergency_Event_Log::maybe_upgrade();
		CSF_Emergency_Horses::init();
		CSF_Emergency_Privacy::init();
		CSF_Emergency_Public::init();
		CSF_Emergency_Banner::init();
		if ( is_admin() ) {
			CSF_Emergency_Settings::init();
			CSF_Emergency_Admin::init();
		}
	}
);

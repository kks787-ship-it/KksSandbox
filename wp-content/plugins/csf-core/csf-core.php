<?php
/**
 * Plugin Name:       Corner Stone Farm Core
 * Description:       The Corner Stone Farm public website: brand design system, block patterns, page setup, Knowledge Library, horses, events, farm contact details, and booking buttons.
 * Version:           0.2.0
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Corner Stone Farm
 * License:           GPL-2.0-or-later
 * Text Domain:       csf-core
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'CSF_CORE_VERSION', '0.2.0' );
define( 'CSF_CORE_FILE', __FILE__ );
define( 'CSF_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once CSF_CORE_DIR . 'includes/class-csf-core-fields.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-markup.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-brand.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-farm-info.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-booking.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-library.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-horses.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-events.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-blocks.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-patterns.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-setup.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once CSF_CORE_DIR . 'includes/class-csf-core-cli.php';
	WP_CLI::add_command( 'csf', 'CSF_Core_CLI' );
}

/**
 * Activation: register content types, seed taxonomy terms, flush rewrites.
 * Pages and menus are NOT created on activation; run the site setup deliberately.
 */
function csf_core_activate() {
	CSF_Core_Library::register();
	CSF_Core_Horses::register();
	CSF_Core_Events::register();
	CSF_Core_Library::seed_categories();
	CSF_Core_Horses::seed_terms();
	CSF_Core_Events::seed_terms();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'csf_core_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action(
	'plugins_loaded',
	static function () {
		CSF_Core_Brand::init();
		CSF_Core_Farm_Info::init();
		CSF_Core_Booking::init();
		CSF_Core_Library::init();
		CSF_Core_Horses::init();
		CSF_Core_Events::init();
		CSF_Core_Blocks::init();
		CSF_Core_Patterns::init();
		CSF_Core_Setup::init();
	}
);

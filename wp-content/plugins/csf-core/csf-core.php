<?php
/**
 * Plugin Name:       Corner Stone Farm Core
 * Description:       Site-wide foundations for Corner Stone Farm: the Knowledge Library content model and consistent booking buttons.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Corner Stone Farm
 * License:           GPL-2.0-or-later
 * Text Domain:       csf-core
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'CSF_CORE_VERSION', '0.1.0' );
define( 'CSF_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSF_CORE_URL', plugin_dir_url( __FILE__ ) );

require_once CSF_CORE_DIR . 'includes/class-csf-core-library.php';
require_once CSF_CORE_DIR . 'includes/class-csf-core-booking.php';

/**
 * Activation: register content types, seed library categories, flush rewrites.
 */
function csf_core_activate() {
	CSF_Core_Library::register();
	CSF_Core_Library::seed_categories();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'csf_core_activate' );
register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

add_action(
	'plugins_loaded',
	static function () {
		CSF_Core_Library::init();
		CSF_Core_Booking::init();
	}
);

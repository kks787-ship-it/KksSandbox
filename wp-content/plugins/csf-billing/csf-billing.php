<?php
/**
 * Plugin Name: Corner Stone Farm Billing
 * Description: Private gift-certificate ledger and disabled-by-default PayPal sandbox checkout.
 * Version: 0.2.1-dev
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Text Domain: csf-billing
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
define( 'CSF_BILLING_VERSION', '0.2.1-dev' );
foreach ( array( 'failure', 'domain', 'config', 'session', 'access', 'schema', 'store', 'paypal', 'service', 'http', 'web' ) as $csf_billing_module ) {
	require_once __DIR__ . '/includes/class-csf-billing-' . $csf_billing_module . '.php';
}
unset( $csf_billing_module );
// No activation migration, accounts, grants, page publication, cron or delivery.
add_action( 'init', array( 'CSF_Billing_Web', 'register' ) );
add_action( 'admin_menu', array( 'CSF_Billing_Web', 'menu' ) );
add_action( 'rest_api_init', array( 'CSF_Billing_HTTP', 'register' ) );
add_action( 'wp_enqueue_scripts', array( 'CSF_Billing_Web', 'assets' ) );
add_action( 'admin_enqueue_scripts', array( 'CSF_Billing_Web', 'assets' ) );
add_filter( 'rest_post_dispatch', array( 'CSF_Billing_HTTP', 'no_cache' ), 10, 3 );

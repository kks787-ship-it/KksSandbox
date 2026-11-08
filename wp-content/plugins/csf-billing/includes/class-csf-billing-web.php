<?php
/** Gutenberg/Kadence-compatible presentation, with sales held.
 *
 * @package CSF_Billing
 */

defined( 'ABSPATH' ) || exit;
/** No anonymous certificate lookup or automatic page publication. */
final class CSF_Billing_Web {
	/** Existing editor can place the shortcode/block; installation never publishes it.
	 */
	public static function register() {
		add_shortcode( 'csf_gift_certificates', array( __CLASS__, 'render' ) );
		register_block_type(
			'csf-billing/gift-certificates',
			array(
				'api_version'     => 3,
				'render_callback' => array( __CLASS__, 'render' ),
			)
		);
	}
	/** Public closed state contains no operational implementation or provisional prices.
	 */
	public static function render() {
		if ( CSF_Billing_Config::enabled() ) {
			return '<section class="csf-gift-app" data-mode="public" data-api="' . esc_url( rest_url( 'csf-billing/v1/' ) ) . '"><h2>' . esc_html__( 'Gift certificates — sandbox test checkout', 'csf-billing' ) . '</h2><p>' . esc_html__( 'Test payments only. No real purchase or riding entitlement is created on this test site.', 'csf-billing' ) . '</p><div class="csf-gift-content"></div><p class="csf-gift-status" role="status" aria-live="polite"></p><noscript>' . esc_html__( 'JavaScript is required for secure online checkout.', 'csf-billing' ) . '</noscript></section>';
		}
		return '<section class="csf-gift-certificates"><h2>' . esc_html__( 'Gift certificates', 'csf-billing' ) . '</h2><p>' . esc_html__( 'Online gift certificate purchases are not available yet.', 'csf-billing' ) . '</p><p><a href="' . esc_url( home_url( '/contact/' ) ) . '">' . esc_html__( 'Contact Beth about gift certificates', 'csf-billing' ) . '</a></p></section>';
	}
	/** Load local assets only; PayPal runs on its own hosted approval page.
	 *
	 * @param string $hook Optional admin screen identifier.
	 */
	public static function assets( $hook = '' ) {
		if ( ! CSF_Billing_Config::enabled() || ( is_admin() && 'toplevel_page_csf-gift-certificates' !== $hook ) ) {
			return; }
		$base = dirname( __DIR__ ) . '/csf-billing.php';
		wp_enqueue_script( 'csf-gift-certificates', plugins_url( 'assets/billing.js', $base ), array(), CSF_BILLING_VERSION, true );
		wp_enqueue_style( 'csf-gift-certificates', plugins_url( 'assets/billing.css', $base ), array(), CSF_BILLING_VERSION );
	}

	/** No capability grants or role changes. The callback independently checks real MFA.
	 */
	public static function menu() {
		add_menu_page( __( 'Gift certificates', 'csf-billing' ), __( 'Gift certificates', 'csf-billing' ), 'csf_billing_audit', 'csf-gift-certificates', array( __CLASS__, 'dashboard' ) );
	}
	/** No private financial records displayed before independent access and schema checks.
	 */
	public static function dashboard() {
		try {
			CSF_Billing_Access::require_cap( 'csf_billing_audit' );
		} catch ( Throwable $error ) {
			wp_die( esc_html__( 'Verified billing access is required.', 'csf-billing' ), '', array( 'response' => 403 ) );
		}
		nocache_headers();
		echo '<div class="wrap"><section class="csf-gift-app" data-mode="admin" data-api="' . esc_url( rest_url( 'csf-billing/v1/' ) ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'wp_rest' ) ) . '"><h1>' . esc_html__( 'Private gift certificates', 'csf-billing' ) . '</h1><p>' . esc_html__( 'Sandbox records only. Redemption records a completed ride; it does not book a lesson or establish waiver clearance.', 'csf-billing' ) . '</p><div class="csf-gift-content"></div><p class="csf-gift-status" role="status" aria-live="polite"></p></section></div>';
	}
}

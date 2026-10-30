<?php
/**
 * Sitewide emergency banner.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints the banner at wp_body_open (supported by Kadence). If a theme does not call
 * wp_body_open, the banner is printed in the footer as a fixed bar so it is never lost.
 */
class CSF_Emergency_Banner {

	/**
	 * Whether the banner has already been printed this request.
	 *
	 * @var bool
	 */
	private static $printed = false;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_body_open', array( __CLASS__, 'print_banner' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'print_fallback' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueue banner styles only while a status is active.
	 */
	public static function enqueue() {
		if ( CSF_Emergency_Farm_State::is_active() ) {
			wp_enqueue_style( 'csf-emergency-banner', CSF_EMERGENCY_URL . 'assets/banner.css', array(), CSF_EMERGENCY_VERSION );
		}
	}

	/**
	 * Print at wp_body_open.
	 */
	public static function print_banner() {
		if ( self::$printed || ! CSF_Emergency_Farm_State::is_active() ) {
			return;
		}
		self::$printed = true;
		echo self::markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in markup().
	}

	/**
	 * Footer fallback.
	 */
	public static function print_fallback() {
		if ( self::$printed || ! CSF_Emergency_Farm_State::is_active() ) {
			return;
		}
		self::$printed = true;
		echo self::markup( true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in markup().
	}

	/**
	 * Banner HTML.
	 *
	 * @param bool $fixed Render as fixed bottom bar.
	 * @return string
	 */
	public static function markup( $fixed = false ) {
		$state    = CSF_Emergency_Farm_State::get();
		$status   = CSF_Emergency_Statuses::get_farm( $state['status'] );
		$settings = CSF_Emergency_Settings::get();
		$classes  = 'csf-banner csf-sev-' . $status['severity'] . ( $fixed ? ' csf-banner--fixed' : '' );
		$role     = in_array( $status['severity'], array( 'critical', 'warning' ), true ) ? 'alert' : 'status';

		$html  = '<div class="' . esc_attr( $classes ) . '" role="' . esc_attr( $role ) . '">';
		$html .= '<div class="csf-banner__inner">';
		$html .= '<p class="csf-banner__headline">' . esc_html( $status['headline'] ) . '</p>';
		if ( '' !== $state['message'] ) {
			$html .= '<p class="csf-banner__message">' . esc_html( $state['message'] ) . '</p>';
		}
		if ( 'help_requested' === $state['status'] ) {
			$html .= '<p class="csf-banner__safety">' . esc_html( self::safety_text() ) . '</p>';
		}
		$html .= '<p class="csf-banner__meta">' . esc_html(
			sprintf(
				/* translators: %s: date and time */
				__( 'Updated %s', 'csf-emergency' ),
				CSF_Emergency_Public::format_time( $state['updated_at'] )
			)
		) . '</p>';
		$html .= '<p class="csf-banner__actions">';
		$html .= '<a href="' . esc_url( home_url( '/emergency' ) ) . '">' . esc_html__( 'View update', 'csf-emergency' ) . '</a>';
		$html .= '<a href="' . esc_url( home_url( '/emergency#horses' ) ) . '">' . esc_html__( 'Horse information', 'csf-emergency' ) . '</a>';
		if ( $settings['farm_phone'] ) {
			$html .= '<a href="tel:' . esc_attr( CSF_Emergency_Settings::phone_uri( $settings['farm_phone'] ) ) . '">' . esc_html__( 'Contact farm', 'csf-emergency' ) . '</a>';
		}
		$html .= '</p></div></div>';

		return $html;
	}

	/**
	 * The mandatory volunteer safety statement.
	 *
	 * @return string
	 */
	public static function safety_text() {
		return __( 'Do not self-deploy. Contact Corner Stone Farm and wait for instructions from an authorized coordinator or public safety personnel.', 'csf-emergency' );
	}
}

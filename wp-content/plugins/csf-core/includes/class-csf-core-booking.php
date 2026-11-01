<?php
/**
 * Consistent manual scheduling contact buttons.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Internal scheduling contact route. Legacy block/shortcode names and attributes
 * remain compatible, but provider options and custom labels are deliberately ignored.
 */
class CSF_Core_Booking {

	const OPTION = 'csf_core_booking';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_shortcode( 'csf_book_button', array( __CLASS__, 'shortcode' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_filter( 'wp_nav_menu_objects', array( __CLASS__, 'contact_menu' ) );
	}

	/**
	 * Fixed contact destination; legacy provider options cannot restore public booking.
	 *
	 * @return array{url:string,label:string,verified:int}
	 */
	public static function get() {
		return array(
			'url'      => home_url( '/contact/' ),
			'label'    => __( 'Contact Beth to Schedule', 'csf-core' ),
			'verified' => 1,
		);
	}

	/**
	 * [csf_book_button label="…" style="primary|outline|light"]
	 *
	 * @param array|string $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'label' => '',
				'style' => 'primary',
			),
			$atts,
			'csf_book_button'
		);
		return self::render_button(
			array(
				'label'   => $atts['label'],
				'variant' => $atts['style'],
			)
		);
	}

	/**
	 * The one place booking links are built. Used by the csf/book-button block, the shortcode,
	 * and the header button. Legacy label attributes are accepted but not displayed.
	 *
	 * @param array $attrs { label, variant, align }.
	 * @return string
	 */
	public static function render_button( $attrs ) {
		$settings = self::get();
		$label    = $settings['label'];
		$style    = in_array( $attrs['variant'] ?? '', array( 'primary', 'outline', 'light' ), true ) ? $attrs['variant'] : 'primary';
		$align    = in_array( $attrs['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? ' csf-book--' . $attrs['align'] : '';

		$html = sprintf(
			'<div class="csf-book%1$s"><a class="csf-book-button csf-book-button--%2$s" href="%3$s" data-csf-booking="1">%4$s</a>',
			esc_attr( $align ),
			esc_attr( $style ),
			esc_url( $settings['url'] ),
			esc_html( $label )
		);
		return $html . '</div>';
	}

	/**
	 * Keep the existing booking navigation route useful without rewriting saved menus.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public static function contact_menu( $items ) {
		$booking = CSF_Core_Setup::find_page( 'booking' );
		foreach ( $items as $item ) {
			if ( ( $booking && 'page' === $item->object && (int) $item->object_id === (int) $booking->ID ) || in_array( 'csf-menu-cta', (array) $item->classes, true ) ) {
				$item->url   = self::get()['url'];
				$item->title = self::get()['label'];
			}
		}
		return $items;
	}

	/**
	 * Register the setting.
	 */
	public static function register_setting() {
		register_setting(
			self::OPTION,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => static function () {
					return self::get();
				},
			)
		);
	}

	/**
	 * Settings page under Settings → Booking.
	 */
	public static function menu() {
		add_options_page( __( 'Scheduling', 'csf-core' ), __( 'Scheduling', 'csf-core' ), 'manage_options', 'csf-booking', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render settings page.
	 */
	public static function render() {
		echo '<div class="wrap"><h1>' . esc_html__( 'Scheduling', 'csf-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'All scheduling buttons use Contact Beth to Schedule and link to /contact/. Beth or Kris confirms lesson dates directly. Previous provider settings are ignored.', 'csf-core' ) . '</p>';
		echo '<p>' . esc_html__( 'Enter only owner-approved public contact channels in Settings → Farm Details. The inquiry form remains disabled pending separate approval.', 'csf-core' ) . '</p></div>';
	}
}

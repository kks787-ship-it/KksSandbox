<?php
/**
 * Consistent "Book a Lesson" buttons.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * One configurable booking URL used everywhere via [csf_book_button]. Changing the
 * scheduling provider later means changing one setting, not every page.
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
	}

	/**
	 * Settings with defaults. The Acuity URL comes from the specification but must be
	 * verified by the owner before launch (see docs/03-owner-confirmation-register.md).
	 *
	 * @return array{url:string,label:string,verified:int}
	 */
	public static function get() {
		return wp_parse_args(
			(array) get_option( self::OPTION, array() ),
			array(
				'url'      => 'https://cornerstonefarm.as.me/?appointmentType=70075205',
				'label'    => __( 'Book a Lesson', 'csf-core' ),
				'verified' => 0,
			)
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
	 * and the header button, so changing provider means changing one setting.
	 *
	 * @param array $attrs { label, variant, align }.
	 * @return string
	 */
	public static function render_button( $attrs ) {
		$settings = self::get();
		if ( ! $settings['url'] ) {
			return current_user_can( 'manage_options' ) ? '<p class="csf-admin-hint">' . esc_html__( 'Admin note: no booking URL configured (Settings → Booking).', 'csf-core' ) . '</p>' : '';
		}

		$label = ! empty( $attrs['label'] ) ? $attrs['label'] : $settings['label'];
		$style = in_array( $attrs['variant'] ?? '', array( 'primary', 'outline', 'light' ), true ) ? $attrs['variant'] : 'primary';
		$align = in_array( $attrs['align'] ?? '', array( 'left', 'center', 'right' ), true ) ? ' csf-book--' . $attrs['align'] : '';

		$html = sprintf(
			'<div class="csf-book%1$s"><a class="csf-book-button csf-book-button--%2$s" href="%3$s" data-csf-booking="1">%4$s</a>',
			esc_attr( $align ),
			esc_attr( $style ),
			esc_url( $settings['url'] ),
			esc_html( $label )
		);
		if ( ! $settings['verified'] && current_user_can( 'manage_options' ) ) {
			$html .= '<small class="csf-admin-hint">' . esc_html__( 'Booking link not yet verified (admins only see this).', 'csf-core' ) . '</small>';
		}
		return $html . '</div>';
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
				'sanitize_callback' => static function ( $input ) {
					$input = is_array( $input ) ? $input : array();
					return array(
						'url'      => esc_url_raw( $input['url'] ?? '' ),
						'label'    => sanitize_text_field( $input['label'] ?? '' ),
						'verified' => empty( $input['verified'] ) ? 0 : 1,
					);
				},
			)
		);
	}

	/**
	 * Settings page under Settings → Booking.
	 */
	public static function menu() {
		add_options_page( __( 'Booking', 'csf-core' ), __( 'Booking', 'csf-core' ), 'manage_options', 'csf-booking', array( __CLASS__, 'render' ) );
	}

	/**
	 * Render settings page.
	 */
	public static function render() {
		$s = self::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Booking', 'csf-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( self::OPTION ); ?>
				<table class="form-table">
					<tr><th><label for="csf-book-url"><?php esc_html_e( 'Booking URL', 'csf-core' ); ?></label></th>
						<td><input type="url" id="csf-book-url" class="large-text" name="<?php echo esc_attr( self::OPTION ); ?>[url]" value="<?php echo esc_attr( $s['url'] ); ?>"></td></tr>
					<tr><th><label for="csf-book-label"><?php esc_html_e( 'Default button label', 'csf-core' ); ?></label></th>
						<td><input type="text" id="csf-book-label" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[label]" value="<?php echo esc_attr( $s['label'] ); ?>"></td></tr>
					<tr><th><?php esc_html_e( 'Verified', 'csf-core' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[verified]" value="1" <?php checked( 1, (int) $s['verified'] ); ?>> <?php esc_html_e( 'I opened this link and confirmed it books the correct appointment type.', 'csf-core' ); ?></label></td></tr>
				</table>
				<p><?php esc_html_e( 'Use the "Book a Lesson Button" block in the editor, or the [csf_book_button] shortcode (for example in a Kadence header HTML element). Every booking button on the site uses this URL.', 'csf-core' ); ?></p>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

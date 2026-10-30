<?php
/**
 * Farm contact details used across the public website.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Contact details are empty until the owners enter confirmed values. Empty values are simply
 * not shown; the site never displays guessed details. Emergency contacts are separate
 * (csf-emergency) so that plugin keeps working on its own.
 */
class CSF_Core_Farm_Info {

	const OPTION = 'csf_core_farm_info';

	/**
	 * Field definitions.
	 *
	 * @return array<string,array{label:string,type:string,help?:string}>
	 */
	public static function fields() {
		return array(
			'phone'      => array(
				'label' => __( 'Public phone', 'csf-core' ),
				'type'  => 'tel',
			),
			'email'      => array(
				'label' => __( 'Public email', 'csf-core' ),
				'type'  => 'email',
			),
			'location'   => array(
				'label' => __( 'Location shown publicly', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'e.g. "Omak, Washington". A street address is optional and only shown if entered below.', 'csf-core' ),
			),
			'address'    => array(
				'label' => __( 'Street address (optional)', 'csf-core' ),
				'type'  => 'text',
			),
			'hours'      => array(
				'label' => __( 'Hours / availability note', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'e.g. "By appointment. Closed to clients on Sundays." Owner to confirm wording.', 'csf-core' ),
			),
			'response'   => array(
				'label' => __( 'Response time note', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'e.g. "We reply within two business days." Leave empty if not committed.', 'csf-core' ),
			),
			'facebook'   => array(
				'label' => __( 'Facebook URL', 'csf-core' ),
				'type'  => 'url',
			),
			'instagram'  => array(
				'label' => __( 'Instagram URL', 'csf-core' ),
				'type'  => 'url',
			),
			'youtube'    => array(
				'label' => __( 'YouTube URL', 'csf-core' ),
				'type'  => 'url',
			),
			'legal_name' => array(
				'label' => __( 'Legal business name (footer copyright)', 'csf-core' ),
				'type'  => 'text',
			),
		);
	}

	/**
	 * Current values.
	 *
	 * @return array<string,string>
	 */
	public static function get() {
		$defaults = array_fill_keys( array_keys( self::fields() ), '' );
		return wp_parse_args( (array) get_option( self::OPTION, array() ), array( 'location' => 'Omak, Washington' ) + $defaults );
	}

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
	}

	/**
	 * Register the option.
	 */
	public static function register_setting() {
		register_setting(
			self::OPTION,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);
		register_setting(
			self::OPTION,
			CSF_Core_Brand::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => static function ( $input ) {
					$input = is_array( $input ) ? $input : array();
					return array(
						'brand_typography' => empty( $input['brand_typography'] ) ? 0 : 1,
						'owner_notes'      => empty( $input['owner_notes'] ) ? 0 : 1,
					);
				},
			)
		);
	}

	/**
	 * Sanitize.
	 *
	 * @param mixed $input Input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = array();
		foreach ( self::fields() as $key => $field ) {
			$raw = (string) ( $input[ $key ] ?? '' );
			switch ( $field['type'] ) {
				case 'email':
					$clean[ $key ] = sanitize_email( $raw );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( $raw );
					break;
				case 'tel':
					$clean[ $key ] = trim( preg_replace( '/[^0-9+()\-. ]/', '', $raw ) );
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $raw );
			}
		}
		return $clean;
	}

	/**
	 * Settings page.
	 */
	public static function menu() {
		add_options_page( __( 'Farm Details', 'csf-core' ), __( 'Farm Details', 'csf-core' ), 'manage_options', 'csf-farm-details', array( __CLASS__, 'render_page' ) );
	}

	/**
	 * Render settings page.
	 */
	public static function render_page() {
		$values  = self::get();
		$display = CSF_Core_Brand::options();
		echo '<div class="wrap"><h1>' . esc_html__( 'Farm Details', 'csf-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Shown on the Contact page, footer, and horse/event inquiries. Enter only details the owners have confirmed. Empty fields are not displayed.', 'csf-core' ) . '</p>';
		echo '<form method="post" action="options.php">';
		settings_fields( self::OPTION );
		echo '<table class="form-table" role="presentation">';
		foreach ( self::fields() as $key => $field ) {
			$id = 'csf-farm-' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';
			echo '<input type="' . esc_attr( $field['type'] ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION . '[' . $key . ']' ) . '" value="' . esc_attr( $values[ $key ] ) . '" class="regular-text">';
			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}
			echo '</td></tr>';
		}
		echo '</table><h2>' . esc_html__( 'Display', 'csf-core' ) . '</h2><table class="form-table" role="presentation">';
		echo '<tr><th scope="row">' . esc_html__( 'Brand typography', 'csf-core' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( CSF_Core_Brand::OPTION ) . '[brand_typography]" value="1"' . checked( 1, (int) $display['brand_typography'], false ) . '> ' . esc_html__( 'Apply Playfair Display headings and Lato body text site-wide (turn off if set in the Kadence Customizer instead).', 'csf-core' ) . '</label></td></tr>';
		echo '<tr><th scope="row">' . esc_html__( 'Owner review notes', 'csf-core' ) . '</th><td><label><input type="checkbox" name="' . esc_attr( CSF_Core_Brand::OPTION ) . '[owner_notes]" value="1"' . checked( 1, (int) $display['owner_notes'], false ) . '> ' . esc_html__( 'Show "Owner to confirm" notes on pages. Turn off to preview the public view. All notes must be resolved before launch.', 'csf-core' ) . '</label></td></tr>';
		echo '</table>';
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Phone number for tel: links.
	 *
	 * @param string $phone Display phone.
	 * @return string
	 */
	public static function tel( $phone ) {
		return preg_replace( '/[^0-9+]/', '', $phone );
	}

	/**
	 * Render the contact details block.
	 *
	 * @param array $attrs Block attributes.
	 * @return string
	 */
	public static function render_contact( $attrs = array() ) {
		$info  = self::get();
		$items = array();

		if ( $info['phone'] ) {
			$items[] = '<li class="csf-contact__item"><span class="csf-contact__label">' . esc_html__( 'Call or text', 'csf-core' ) . '</span><a href="tel:' . esc_attr( self::tel( $info['phone'] ) ) . '">' . esc_html( $info['phone'] ) . '</a></li>';
		}
		if ( $info['email'] ) {
			$items[] = '<li class="csf-contact__item"><span class="csf-contact__label">' . esc_html__( 'Email', 'csf-core' ) . '</span><a href="mailto:' . esc_attr( antispambot( $info['email'] ) ) . '">' . esc_html( antispambot( $info['email'] ) ) . '</a></li>';
		}
		if ( $info['location'] || $info['address'] ) {
			$where   = $info['address'] ? $info['address'] : $info['location'];
			$items[] = '<li class="csf-contact__item"><span class="csf-contact__label">' . esc_html__( 'Location', 'csf-core' ) . '</span><span>' . esc_html( $where ) . '</span></li>';
		}
		if ( $info['hours'] ) {
			$items[] = '<li class="csf-contact__item"><span class="csf-contact__label">' . esc_html__( 'Hours', 'csf-core' ) . '</span><span>' . esc_html( $info['hours'] ) . '</span></li>';
		}

		$social = array();
		foreach ( array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'youtube'   => 'YouTube',
		) as $key => $label ) {
			if ( $info[ $key ] ) {
				$social[] = '<a href="' . esc_url( $info[ $key ] ) . '" rel="noopener">' . esc_html( $label ) . '</a>';
			}
		}

		$variant = ( $attrs['variant'] ?? '' ) === 'compact' ? ' csf-contact--compact' : '';
		$html    = '<div class="csf-contact' . esc_attr( $variant ) . '">';
		$html   .= '<ul class="csf-contact__list">' . implode( '', $items ) . '</ul>';
		if ( $social ) {
			$html .= '<p class="csf-contact__social">' . implode( '<span aria-hidden="true"> · </span>', $social ) . '</p>';
		}
		if ( $info['response'] && ! $variant ) {
			$html .= '<p class="csf-contact__response">' . esc_html( $info['response'] ) . '</p>';
		}
		if ( ! $info['phone'] && ! $info['email'] && current_user_can( 'manage_options' ) ) {
			$html .= '<p class="csf-admin-hint">' . esc_html__( 'Admin note: no phone or email entered yet (Settings → Farm Details). Visitors only see the location.', 'csf-core' ) . '</p>';
		}
		return $html . '</div>';
	}
}

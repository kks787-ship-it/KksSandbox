<?php
/**
 * Emergency contact settings.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings are empty by default. Contact details must be entered and confirmed by the
 * farm; the plugin never ships with assumed phone numbers.
 */
class CSF_Emergency_Settings {

	const OPTION = 'csf_emergency_settings';
	const PAGE   = 'csf-emergency-settings';

	/**
	 * Defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'farm_name'           => 'Corner Stone Farm',
			'farm_phone'          => '',
			'secondary_label'     => '',
			'secondary_phone'     => '',
			'report_sms'          => '',
			'official_info_label' => '',
			'official_info_url'   => '',
			'show_horse_list'     => 0,
			'contacts_verified'   => '',
		);
	}

	/**
	 * Current settings merged with defaults.
	 *
	 * @return array<string,mixed>
	 */
	public static function get() {
		return wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
	}

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_filter( 'option_page_capability_' . self::OPTION, array( __CLASS__, 'capability' ) );
	}

	/**
	 * Capability required to save these settings.
	 *
	 * @return string
	 */
	public static function capability() {
		return CSF_Emergency_Capabilities::MANAGE_EMERGENCY;
	}

	/**
	 * Register the setting and fields.
	 */
	public static function register() {
		register_setting(
			self::OPTION,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		add_settings_section( 'contacts', __( 'Emergency contacts', 'csf-emergency' ), array( __CLASS__, 'contacts_intro' ), self::PAGE );
		add_settings_section( 'public', __( 'Public emergency page', 'csf-emergency' ), '__return_null', self::PAGE );

		$fields = array(
			'farm_name'           => array( 'contacts', __( 'Farm name', 'csf-emergency' ), 'text' ),
			'farm_phone'          => array( 'contacts', __( 'Primary emergency phone (CALL FARM)', 'csf-emergency' ), 'tel' ),
			'secondary_label'     => array( 'contacts', __( 'Secondary contact label', 'csf-emergency' ), 'text' ),
			'secondary_phone'     => array( 'contacts', __( 'Secondary contact phone', 'csf-emergency' ), 'tel' ),
			'report_sms'          => array( 'contacts', __( 'Text number for found-horse reports', 'csf-emergency' ), 'tel' ),
			'contacts_verified'   => array( 'contacts', __( 'Contacts last verified (date)', 'csf-emergency' ), 'date' ),
			'official_info_label' => array( 'public', __( 'Official emergency source name', 'csf-emergency' ), 'text' ),
			'official_info_url'   => array( 'public', __( 'Official emergency source URL', 'csf-emergency' ), 'url' ),
			'show_horse_list'     => array( 'public', __( 'List individual horses on /emergency', 'csf-emergency' ), 'checkbox' ),
		);

		foreach ( $fields as $key => $field ) {
			add_settings_field(
				$key,
				$field[1],
				array( __CLASS__, 'render_field' ),
				self::PAGE,
				$field[0],
				array(
					'key'       => $key,
					'type'      => $field[2],
					'label_for' => 'csf-' . $key,
				)
			);
		}
	}

	/**
	 * Section intro.
	 */
	public static function contacts_intro() {
		echo '<p>' . esc_html__( 'These numbers are printed on every horse tag and shown on every emergency page. Enter only numbers the farm has confirmed will be answered during an emergency.', 'csf-emergency' ) . '</p>';
	}

	/**
	 * Render a single field.
	 *
	 * @param array $args Field args.
	 */
	public static function render_field( $args ) {
		$settings = self::get();
		$key      = $args['key'];
		$name     = self::OPTION . '[' . $key . ']';
		$id       = 'csf-' . $key;

		if ( 'checkbox' === $args['type'] ) {
			printf(
				'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $name ),
				checked( 1, (int) $settings[ $key ], false ),
				esc_html__( 'Off by default. When off, /emergency shows only aggregate counts so there is no public directory of horses.', 'csf-emergency' )
			);
			return;
		}

		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text">',
			esc_attr( $args['type'] ),
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( (string) $settings[ $key ] )
		);

		if ( 'official_info_url' === $key ) {
			echo '<p class="description">' . esc_html__( 'Where the public should look for official evacuation orders (e.g. county emergency management). Owner must confirm the correct source.', 'csf-emergency' ) . '</p>';
		}
	}

	/**
	 * Sanitize settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();
		$clean = self::defaults();

		foreach ( array( 'farm_name', 'secondary_label', 'official_info_label' ) as $key ) {
			$clean[ $key ] = sanitize_text_field( $input[ $key ] ?? '' );
		}
		foreach ( array( 'farm_phone', 'secondary_phone', 'report_sms' ) as $key ) {
			$clean[ $key ] = self::sanitize_phone( $input[ $key ] ?? '' );
		}
		$clean['official_info_url'] = esc_url_raw( $input['official_info_url'] ?? '' );
		$clean['show_horse_list']   = empty( $input['show_horse_list'] ) ? 0 : 1;
		$date                       = sanitize_text_field( $input['contacts_verified'] ?? '' );
		$clean['contacts_verified'] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '';

		return $clean;
	}

	/**
	 * Keep digits, spaces, and common phone punctuation for display.
	 *
	 * @param string $phone Raw phone.
	 * @return string
	 */
	public static function sanitize_phone( $phone ) {
		return trim( preg_replace( '/[^0-9+()\-. ]/', '', (string) $phone ) );
	}

	/**
	 * Phone number formatted for a tel:/sms: URI.
	 *
	 * @param string $phone Display phone.
	 * @return string
	 */
	public static function phone_uri( $phone ) {
		return preg_replace( '/[^0-9+]/', '', (string) $phone );
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( CSF_Emergency_Capabilities::MANAGE_EMERGENCY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage emergency settings.', 'csf-emergency' ) );
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Emergency Settings', 'csf-emergency' ) . '</h1><form method="post" action="options.php">';
		settings_fields( self::OPTION );
		do_settings_sections( self::PAGE );
		submit_button();
		echo '</form></div>';
	}
}

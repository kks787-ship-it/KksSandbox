<?php
/**
 * Brand design system: palette, fonts, block styles, and the brand stylesheet.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the brand palette and fonts as WordPress presets so editors pick them from the
 * normal colour and typography controls, in any theme (Kadence included). Presets are
 * appended to the theme's own palette rather than replacing it.
 */
class CSF_Core_Brand {

	const OPTION = 'csf_core_display';

	/**
	 * Brand palette. Muted Gold and Stone Gray fail WCAG contrast for small text on light
	 * backgrounds and are labelled accordingly; "Ink Gray" is the accessible secondary text.
	 *
	 * @return array<int,array{slug:string,name:string,color:string}>
	 */
	public static function palette() {
		return array(
			array(
				'slug'  => 'csf-barn-black',
				'name'  => __( 'Barn Black', 'csf-core' ),
				'color' => '#111111',
			),
			array(
				'slug'  => 'csf-heritage-brown',
				'name'  => __( 'Heritage Brown', 'csf-core' ),
				'color' => '#4A3C2F',
			),
			array(
				'slug'  => 'csf-sage-green',
				'name'  => __( 'Sage Green', 'csf-core' ),
				'color' => '#6B7A6B',
			),
			array(
				'slug'  => 'csf-warm-tan',
				'name'  => __( 'Warm Tan', 'csf-core' ),
				'color' => '#D6C2A6',
			),
			array(
				'slug'  => 'csf-linen-white',
				'name'  => __( 'Linen White', 'csf-core' ),
				'color' => '#F5F2EE',
			),
			array(
				'slug'  => 'csf-white',
				'name'  => __( 'White', 'csf-core' ),
				'color' => '#FFFFFF',
			),
			array(
				'slug'  => 'csf-ink-gray',
				'name'  => __( 'Ink Gray (small text)', 'csf-core' ),
				'color' => '#5E5C58',
			),
			array(
				'slug'  => 'csf-stone-gray',
				'name'  => __( 'Stone Gray (large text only)', 'csf-core' ),
				'color' => '#8A8883',
			),
			array(
				'slug'  => 'csf-muted-gold',
				'name'  => __( 'Muted Gold (decorative only)', 'csf-core' ),
				'color' => '#B68B43',
			),
		);
	}

	/**
	 * Self-hosted font families (SIL OFL; files in assets/fonts).
	 *
	 * @return array
	 */
	public static function font_families() {
		$url  = CSF_CORE_URL . 'assets/fonts/';
		$face = static function ( $family, $file, $weight, $style = 'normal' ) use ( $url ) {
			return array(
				'fontFamily'  => $family,
				'fontWeight'  => $weight,
				'fontStyle'   => $style,
				'fontDisplay' => 'swap',
				'src'         => array( $url . $file ),
			);
		};
		return array(
			array(
				'slug'       => 'csf-heading',
				'name'       => 'Playfair Display',
				'fontFamily' => '"Playfair Display", Georgia, "Times New Roman", serif',
				'fontFace'   => array(
					$face( 'Playfair Display', 'playfair-display-latin-400-normal.woff2', '400' ),
					$face( 'Playfair Display', 'playfair-display-latin-400-italic.woff2', '400', 'italic' ),
					$face( 'Playfair Display', 'playfair-display-latin-600-normal.woff2', '600' ),
					$face( 'Playfair Display', 'playfair-display-latin-700-normal.woff2', '700' ),
				),
			),
			array(
				'slug'       => 'csf-body',
				'name'       => 'Lato',
				'fontFamily' => 'Lato, "Helvetica Neue", Arial, sans-serif',
				'fontFace'   => array(
					$face( 'Lato', 'lato-latin-400-normal.woff2', '400' ),
					$face( 'Lato', 'lato-latin-400-italic.woff2', '400', 'italic' ),
					$face( 'Lato', 'lato-latin-700-normal.woff2', '700' ),
					$face( 'Lato', 'lato-latin-700-italic.woff2', '700', 'italic' ),
				),
			),
		);
	}

	/**
	 * Display options.
	 *
	 * @return array{brand_typography:int,owner_notes:int}
	 */
	public static function options() {
		return wp_parse_args(
			(array) get_option( self::OPTION, array() ),
			array(
				'brand_typography' => 1,
				'owner_notes'      => 1,
			)
		);
	}

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'wp_theme_json_data_theme', array( __CLASS__, 'theme_json' ) );
		add_action( 'init', array( __CLASS__, 'register_assets' ) );
		add_action( 'init', array( __CLASS__, 'register_block_styles' ) );
		add_action( 'enqueue_block_assets', array( __CLASS__, 'enqueue' ) );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_filter( 'render_block', array( __CLASS__, 'filter_owner_notes' ), 10, 2 );
	}

	/**
	 * Append brand presets to the theme's data.
	 *
	 * @param WP_Theme_JSON_Data $data Theme data.
	 * @return WP_Theme_JSON_Data
	 */
	public static function theme_json( $data ) {
		$raw = $data->get_data();

		// For classic themes the filtered data replaces editor-color-palette support, so carry it forward.
		$palette = $raw['settings']['color']['palette']['theme'] ?? array();
		if ( ! $palette && ! wp_theme_has_theme_json() ) {
			$support = get_theme_support( 'editor-color-palette' );
			$palette = is_array( $support ) && isset( $support[0] ) && is_array( $support[0] ) ? $support[0] : array();
		}
		$fonts = $raw['settings']['typography']['fontFamilies']['theme'] ?? array();

		$data->update_with(
			array(
				'version'  => 3,
				'settings' => array(
					'color'      => array( 'palette' => self::merge_by_slug( $palette, self::palette() ) ),
					'typography' => array( 'fontFamilies' => self::merge_by_slug( $fonts, self::font_families() ) ),
				),
			)
		);
		return $data;
	}

	/**
	 * Append presets whose slug is not already present.
	 *
	 * @param array $existing Existing presets.
	 * @param array $add      Presets to add.
	 * @return array
	 */
	private static function merge_by_slug( array $existing, array $add ) {
		$slugs = wp_list_pluck( $existing, 'slug' );
		foreach ( $add as $preset ) {
			if ( ! in_array( $preset['slug'], $slugs, true ) ) {
				$existing[] = $preset;
			}
		}
		return array_values( $existing );
	}

	/**
	 * Register the stylesheet.
	 */
	public static function register_assets() {
		wp_register_style( 'csf-core-brand', CSF_CORE_URL . 'assets/css/brand.css', array(), CSF_CORE_VERSION );
	}

	/**
	 * Load the brand stylesheet on the front end and inside the block editor canvas.
	 */
	public static function enqueue() {
		wp_enqueue_style( 'csf-core-brand' );
	}

	/**
	 * Body classes that switch on brand typography and owner-note display.
	 *
	 * @param string[] $classes Classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		$options = self::options();
		if ( $options['brand_typography'] ) {
			$classes[] = 'csf-brand-type';
		}
		return $classes;
	}

	/**
	 * Owner review notes ("Owner to confirm …") are shown while the site is under review and
	 * can be hidden to preview the public view. All must be resolved before launch.
	 *
	 * @param string $content Block HTML.
	 * @param array  $block   Block.
	 * @return string
	 */
	public static function filter_owner_notes( $content, $block ) {
		if ( 'core/group' !== $block['blockName'] || empty( $block['attrs']['className'] ) ) {
			return $content;
		}
		if ( false !== strpos( $block['attrs']['className'], 'csf-owner-note' ) && ! self::options()['owner_notes'] ) {
			return '';
		}
		return $content;
	}

	/**
	 * Block styles editors can switch in the Styles panel.
	 */
	public static function register_block_styles() {
		$styles = array(
			'core/group'     => array(
				'csf-section-linen' => __( 'Section: Linen', 'csf-core' ),
				'csf-section-white' => __( 'Section: White', 'csf-core' ),
				'csf-section-tan'   => __( 'Section: Warm Tan', 'csf-core' ),
				'csf-section-dark'  => __( 'Section: Barn Black', 'csf-core' ),
				'csf-section-brown' => __( 'Section: Heritage Brown', 'csf-core' ),
				'csf-card'          => __( 'Card', 'csf-core' ),
			),
			'core/button'    => array(
				'csf-primary' => __( 'CSF Primary', 'csf-core' ),
				'csf-outline' => __( 'CSF Outline', 'csf-core' ),
				'csf-light'   => __( 'CSF Light (on dark)', 'csf-core' ),
			),
			'core/paragraph' => array(
				'csf-eyebrow' => __( 'Eyebrow', 'csf-core' ),
				'csf-lead'    => __( 'Lead', 'csf-core' ),
			),
			'core/list'      => array(
				'csf-checklist' => __( 'Checklist', 'csf-core' ),
			),
			'core/image'     => array(
				'csf-soft' => __( 'Soft corners', 'csf-core' ),
			),
			'core/separator' => array(
				'csf-ornament' => __( 'Ornament', 'csf-core' ),
			),
			'core/quote'     => array(
				'csf-tagline' => __( 'Tagline', 'csf-core' ),
			),
		);
		foreach ( $styles as $block => $variations ) {
			foreach ( $variations as $name => $label ) {
				register_block_style(
					$block,
					array(
						'name'         => $name,
						'label'        => $label,
						'style_handle' => 'csf-core-brand',
					)
				);
			}
		}
	}
}

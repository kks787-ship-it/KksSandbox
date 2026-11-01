<?php
/**
 * Dynamic blocks and shared card/listing markup.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Server-rendered blocks so listings stay current (new horses, events, articles) without
 * editing pages. The editor script is plain JavaScript with no build step: block metadata
 * comes from the server registration below, and previews use ServerSideRender.
 */
class CSF_Core_Blocks {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'block_categories_all', array( __CLASS__, 'category' ) );
	}

	/**
	 * Block definitions: attributes, controls for the editor, and render callbacks.
	 *
	 * @return array<string,array>
	 */
	public static function definitions() {
		$listing_attrs    = array(
			'heading'       => array(
				'type'    => 'string',
				'default' => '',
			),
			'emptyMessage'  => array(
				'type'    => 'string',
				'default' => '',
			),
			'hideWhenEmpty' => array(
				'type'    => 'boolean',
				'default' => false,
			),
			'sectionStyle'  => array(
				'type'    => 'string',
				'default' => '',
			),
		);
		$listing_controls = array(
			array( 'heading', 'text', __( 'Heading (optional)', 'csf-core' ) ),
			array( 'emptyMessage', 'text', __( 'Message when nothing is listed', 'csf-core' ) ),
			array( 'hideWhenEmpty', 'toggle', __( 'Hide this whole section when nothing is listed', 'csf-core' ) ),
			array(
				'sectionStyle',
				'select',
				__( 'Full-width section background', 'csf-core' ),
				array(
					''      => __( 'None (place inside a section)', 'csf-core' ),
					'linen' => __( 'Linen', 'csf-core' ),
					'white' => __( 'White', 'csf-core' ),
					'tan'   => __( 'Warm Tan', 'csf-core' ),
				),
			),
		);

		return array(
			'csf/inquiry-form'     => array(
				'title'       => __( 'Farm Inquiry Form', 'csf-core' ),
				'description' => __( 'General inquiries; disabled until recipient delivery is verified in Settings → Farm Inquiries.', 'csf-core' ),
				'icon'        => 'email',
				'attributes'  => array(),
				'controls'    => array(),
				'render'      => array( 'CSF_Core_Inquiry', 'render' ),
			),
			'csf/book-button'      => array(
				'title'       => __( 'Contact Beth to Schedule', 'csf-core' ),
				'description' => __( 'Scheduling contact button linking to the Contact page.', 'csf-core' ),
				'icon'        => 'calendar-alt',
				'attributes'  => array(
					'label'   => array(
						'type'    => 'string',
						'default' => '',
					),
					'variant' => array(
						'type'    => 'string',
						'default' => 'primary',
					),
					'align'   => array(
						'type'    => 'string',
						'default' => 'left',
					),
				),
				'controls'    => array(
					array(
						'variant',
						'select',
						__( 'Style', 'csf-core' ),
						array(
							'primary' => __( 'Primary', 'csf-core' ),
							'outline' => __( 'Outline', 'csf-core' ),
							'light'   => __( 'Light (on dark)', 'csf-core' ),
						),
					),
					array(
						'align',
						'select',
						__( 'Alignment', 'csf-core' ),
						array(
							'left'   => __( 'Left', 'csf-core' ),
							'center' => __( 'Center', 'csf-core' ),
							'right'  => __( 'Right', 'csf-core' ),
						),
					),
				),
				'render'      => array( 'CSF_Core_Booking', 'render_button' ),
			),
			'csf/farm-contact'     => array(
				'title'       => __( 'Farm Contact Details', 'csf-core' ),
				'description' => __( 'Phone, email, location, hours and social links from Settings → Farm Details.', 'csf-core' ),
				'icon'        => 'phone',
				'attributes'  => array(
					'variant' => array(
						'type'    => 'string',
						'default' => 'full',
					),
				),
				'controls'    => array(
					array(
						'variant',
						'select',
						__( 'Layout', 'csf-core' ),
						array(
							'full'    => __( 'Full', 'csf-core' ),
							'compact' => __( 'Compact (footer)', 'csf-core' ),
						),
					),
				),
				'render'      => array( 'CSF_Core_Farm_Info', 'render_contact' ),
			),
			'csf/horses'           => array(
				'title'       => __( 'Horse Listing', 'csf-core' ),
				'description' => __( 'Farm horses, sale horses, or lease horses.', 'csf-core' ),
				'icon'        => 'pets',
				'attributes'  => array(
					'listing'       => array(
						'type'    => 'string',
						'default' => 'for-sale',
					),
					'count'         => array(
						'type'    => 'number',
						'default' => 12,
					),
					'includeClosed' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				) + $listing_attrs,
				'controls'    => array_merge(
					array(
						array( 'listing', 'select', __( 'Show', 'csf-core' ), CSF_Core_Horses::listings() ),
						array( 'count', 'number', __( 'Maximum horses', 'csf-core' ) ),
						array( 'includeClosed', 'toggle', __( 'Include sold / leased horses', 'csf-core' ) ),
					),
					$listing_controls
				),
				'render'      => array( 'CSF_Core_Horses', 'render_list' ),
			),
			'csf/events'           => array(
				'title'       => __( 'Upcoming Camps & Clinics', 'csf-core' ),
				'description' => __( 'Upcoming events, soonest first. Past events are hidden automatically.', 'csf-core' ),
				'icon'        => 'calendar',
				'attributes'  => array(
					'count' => array(
						'type'    => 'number',
						'default' => 6,
					),
					'type'  => array(
						'type'    => 'string',
						'default' => '',
					),
				) + $listing_attrs,
				'controls'    => array_merge(
					array(
						array( 'count', 'number', __( 'Maximum events', 'csf-core' ) ),
						array( 'type', 'text', __( 'Only this event type (slug, optional)', 'csf-core' ) ),
					),
					$listing_controls
				),
				'render'      => array( 'CSF_Core_Events', 'render_list' ),
			),
			'csf/library'          => array(
				'title'       => __( 'Library Articles', 'csf-core' ),
				'description' => __( 'Latest Knowledge Library articles, optionally from one topic.', 'csf-core' ),
				'icon'        => 'book-alt',
				'attributes'  => array(
					'count' => array(
						'type'    => 'number',
						'default' => 6,
					),
					'topic' => array(
						'type'    => 'string',
						'default' => '',
					),
				) + $listing_attrs,
				'controls'    => array_merge(
					array(
						array( 'count', 'number', __( 'Maximum articles', 'csf-core' ) ),
						array( 'topic', 'text', __( 'Only this topic (slug, optional)', 'csf-core' ) ),
					),
					$listing_controls
				),
				'render'      => array( 'CSF_Core_Library', 'render_list' ),
			),
			'csf/library-topics'   => array(
				'title'       => __( 'Library Topics', 'csf-core' ),
				'description' => __( 'Topics that have published articles, with counts.', 'csf-core' ),
				'icon'        => 'category',
				'attributes'  => array(),
				'controls'    => array(),
				'render'      => array( 'CSF_Core_Library', 'render_topics' ),
			),
			'csf/related-articles' => array(
				'title'       => __( 'Related Articles', 'csf-core' ),
				'description' => __( 'Articles on the same topics as the current article.', 'csf-core' ),
				'icon'        => 'admin-links',
				'attributes'  => array(
					'count' => array(
						'type'    => 'number',
						'default' => 3,
					),
				),
				'controls'    => array(
					array( 'count', 'number', __( 'Number of articles', 'csf-core' ) ),
				),
				'render'      => array( 'CSF_Core_Library', 'render_related' ),
			),
			'csf/copyright'        => array(
				'title'       => __( 'Copyright Line', 'csf-core' ),
				'description' => __( 'Current year and business name.', 'csf-core' ),
				'icon'        => 'editor-code',
				'attributes'  => array(),
				'controls'    => array(),
				'render'      => array( __CLASS__, 'render_copyright' ),
			),
		);
	}

	/**
	 * Register the editor script and blocks.
	 */
	public static function register() {
		wp_register_script(
			'csf-core-blocks',
			CSF_CORE_URL . 'assets/js/blocks.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
			CSF_CORE_VERSION,
			true
		);

		$controls = array();
		foreach ( self::definitions() as $name => $def ) {
			$controls[ $name ] = $def['controls'];
			$render            = $def['render'];
			register_block_type(
				$name,
				array(
					'api_version'     => 3,
					'title'           => $def['title'],
					'description'     => $def['description'],
					'icon'            => $def['icon'],
					'category'        => 'csf',
					'attributes'      => $def['attributes'],
					'supports'        => array( 'html' => false ),
					'editor_script'   => 'csf-core-blocks',
					'render_callback' => static fn( $attrs ) => call_user_func( $render, $attrs ),
				)
			);
		}
		wp_add_inline_script( 'csf-core-blocks', 'window.csfBlockControls = ' . wp_json_encode( $controls ) . ';', 'before' );
	}

	/**
	 * Block inserter category.
	 *
	 * @param array $categories Categories.
	 * @return array
	 */
	public static function category( $categories ) {
		array_unshift(
			$categories,
			array(
				'slug'  => 'csf',
				'title' => __( 'Corner Stone Farm', 'csf-core' ),
			)
		);
		return $categories;
	}

	/**
	 * Card markup shared by every listing.
	 *
	 * @param array $args { url, title, text, meta, badge (HTML), image (attachment ID), heading, ratio }.
	 * @return string
	 */
	public static function card( array $args ) {
		$tag   = in_array( $args['heading'] ?? '', array( 'h2', 'h3', 'h4' ), true ) ? $args['heading'] : 'h3';
		$ratio = ( $args['ratio'] ?? '' ) === 'portrait' ? ' csf-card__media--portrait' : '';
		$html  = '<article class="csf-card">';
		if ( ! empty( $args['image'] ) ) {
			$html .= '<div class="csf-card__media' . esc_attr( $ratio ) . '">' . wp_get_attachment_image(
				$args['image'],
				'medium_large',
				false,
				array(
					'alt'     => '',
					'loading' => 'lazy',
				)
			) . '</div>';
		}
		$html .= '<div class="csf-card__body">';
		$html .= $args['badge'] ?? ''; // Pre-escaped badge HTML.
		$html .= '<' . $tag . ' class="csf-card__title"><a href="' . esc_url( $args['url'] ) . '">' . esc_html( $args['title'] ) . '</a></' . $tag . '>';
		if ( ! empty( $args['meta'] ) ) {
			$html .= '<p class="csf-card__meta">' . esc_html( $args['meta'] ) . '</p>';
		}
		if ( ! empty( $args['text'] ) ) {
			$html .= '<p class="csf-card__text">' . esc_html( wp_strip_all_tags( $args['text'] ) ) . '</p>';
		}
		return $html . '</div></article>';
	}

	/**
	 * Listing wrapper: optional heading, card grid or empty message, optional "more" link.
	 *
	 * @param array    $attrs   Block attributes.
	 * @param string[] $cards   Card HTML.
	 * @param string   $empty_message Default empty message.
	 * @param array    $more    Optional { url, label }.
	 * @return string
	 */
	public static function listing( array $attrs, array $cards, $empty_message, array $more = array() ) {
		if ( ! $cards && ! empty( $attrs['hideWhenEmpty'] ) ) {
			return '';
		}
		$html = '<div class="csf-listing">';
		if ( ! empty( $attrs['heading'] ) ) {
			$html .= '<h2 class="csf-listing__heading">' . esc_html( $attrs['heading'] ) . '</h2>';
		}
		if ( $cards ) {
			$cols  = count( $cards ) >= 3 ? 3 : count( $cards );
			$html .= '<div class="csf-cards csf-cards--' . $cols . '">' . implode( '', $cards ) . '</div>';
		} else {
			$html .= '<p class="csf-listing__empty">' . esc_html( ! empty( $attrs['emptyMessage'] ) ? $attrs['emptyMessage'] : $empty_message ) . '</p>';
		}
		if ( $more && ! empty( $more['url'] ) ) {
			$html .= '<p class="csf-listing__more"><a href="' . esc_url( $more['url'] ) . '">' . esc_html( $more['label'] ) . ' <span aria-hidden="true">→</span></a></p>';
		}
		$html .= '</div>';

		// Optionally render as its own full-width section, so an empty, hidden listing leaves no empty band.
		$section = $attrs['sectionStyle'] ?? '';
		if ( in_array( $section, array( 'linen', 'white', 'tan' ), true ) ) {
			$html = '<section class="wp-block-group alignfull is-style-csf-section-' . esc_attr( $section ) . ' csf-listing-section"><div class="csf-listing-section__inner">' . $html . '</div></section>';
		}
		return $html;
	}

	/**
	 * Copyright line.
	 *
	 * @return string
	 */
	public static function render_copyright() {
		$name = CSF_Core_Farm_Info::get()['legal_name'];
		$name = $name ? $name : 'Corner Stone Farm';
		return '<p class="csf-copyright">&copy; ' . esc_html( wp_date( 'Y' ) . ' ' . $name ) . '</p>';
	}
}

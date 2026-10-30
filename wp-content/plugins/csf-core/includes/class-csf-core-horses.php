<?php
/**
 * Public horse profiles: farm horses, sale horses, lease horses.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Public marketing profiles, deliberately separate from csf-emergency's unlisted emergency ID
 * profiles. Only information the owner has approved for publication belongs here.
 */
class CSF_Core_Horses {

	const POST_TYPE = 'csf_horse';
	const LISTING   = 'csf_horse_listing';
	const NONCE     = 'csf_horse_fields';

	/**
	 * Listing terms (slug => name).
	 *
	 * @return array<string,string>
	 */
	public static function listings() {
		return array(
			'farm-horse' => __( 'Farm Horse', 'csf-core' ),
			'for-sale'   => __( 'For Sale', 'csf-core' ),
			'for-lease'  => __( 'For Lease', 'csf-core' ),
		);
	}

	/**
	 * Availability options.
	 *
	 * @return array<string,string>
	 */
	public static function availability() {
		return array(
			'available' => __( 'Available', 'csf-core' ),
			'pending'   => __( 'Sale/lease pending', 'csf-core' ),
			'sold'      => __( 'Sold', 'csf-core' ),
			'leased'    => __( 'Leased', 'csf-core' ),
			'na'        => __( 'Not applicable (farm horse)', 'csf-core' ),
		);
	}

	/**
	 * Fields.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'registered_name'  => array(
				'label' => __( 'Registered name', 'csf-core' ),
				'type'  => 'text',
			),
			'breed'            => array(
				'label' => __( 'Breed', 'csf-core' ),
				'type'  => 'text',
			),
			'sex'              => array(
				'label'   => __( 'Sex', 'csf-core' ),
				'type'    => 'select',
				'options' => array(
					''         => '—',
					'Mare'     => __( 'Mare', 'csf-core' ),
					'Gelding'  => __( 'Gelding', 'csf-core' ),
					'Stallion' => __( 'Stallion', 'csf-core' ),
				),
			),
			'foaled'           => array(
				'label' => __( 'Year foaled', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'Age is calculated from this.', 'csf-core' ),
			),
			'height'           => array(
				'label' => __( 'Height', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'e.g. 16.1 hh', 'csf-core' ),
			),
			'color'            => array(
				'label' => __( 'Color', 'csf-core' ),
				'type'  => 'text',
			),
			'discipline'       => array(
				'label' => __( 'Discipline / level', 'csf-core' ),
				'type'  => 'text',
			),
			'suitability'      => array(
				'label' => __( 'Suitability', 'csf-core' ),
				'type'  => 'textarea',
				'help'  => __( 'Who this horse is right for, e.g. "Confident junior or adult amateur".', 'csf-core' ),
			),
			'training_history' => array(
				'label' => __( 'Training history', 'csf-core' ),
				'type'  => 'textarea',
			),
			'availability'     => array(
				'label'   => __( 'Availability', 'csf-core' ),
				'type'    => 'select',
				'options' => self::availability(),
			),
			'price'            => array(
				'label' => __( 'Asking price / lease terms', 'csf-core' ),
				'type'  => 'text',
			),
			'price_approved'   => array(
				'label'          => __( 'Publish price', 'csf-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Owner has approved publishing this price', 'csf-core' ),
			),
			'video'            => array(
				'label' => __( 'Video URL (YouTube or Vimeo)', 'csf-core' ),
				'type'  => 'url',
			),
		);
	}

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'the_content', array( __CLASS__, 'decorate_single' ), 20 );
		if ( is_admin() ) {
			add_action(
				'add_meta_boxes_' . self::POST_TYPE,
				static function () {
					add_meta_box( 'csf-horse-details', __( 'Horse details', 'csf-core' ), array( __CLASS__, 'render_box' ), self::POST_TYPE, 'normal', 'high' );
				}
			);
			add_action(
				'save_post_' . self::POST_TYPE,
				static function ( $post_id ) {
					CSF_Core_Fields::save( $post_id, self::fields(), self::NONCE );
				}
			);
		}
	}

	/**
	 * Register post type and listing taxonomy.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'          => __( 'Horses', 'csf-core' ),
					'singular_name' => __( 'Horse', 'csf-core' ),
					'add_new_item'  => __( 'Add Horse', 'csf-core' ),
					'edit_item'     => __( 'Edit Horse', 'csf-core' ),
					'menu_name'     => __( 'Horses', 'csf-core' ),
					'excerpt_field' => __( 'Short description', 'csf-core' ),
				),
				'public'        => true,
				'has_archive'   => false,
				'rewrite'       => array(
					'slug'       => 'horses',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-pets',
				'menu_position' => 22,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			)
		);
		register_taxonomy(
			self::LISTING,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Listings', 'csf-core' ),
					'singular_name' => __( 'Listing', 'csf-core' ),
				),
				'public'            => false,
				'show_ui'           => true,
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => false,
			)
		);
	}

	/**
	 * Seed listing terms.
	 */
	public static function seed_terms() {
		foreach ( self::listings() as $slug => $name ) {
			if ( ! term_exists( $slug, self::LISTING ) ) {
				wp_insert_term( $name, self::LISTING, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Details meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_box( $post ) {
		echo '<p class="description">' . esc_html__( 'Everything here is public. Do not enter private veterinary records or owner information. Prices only appear when "Publish price" is ticked.', 'csf-core' ) . '</p>';
		CSF_Core_Fields::render( $post->ID, self::fields(), self::NONCE );
	}

	/**
	 * Public detail rows for a horse.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	public static function detail_rows( $post_id ) {
		$f    = static fn( $k ) => CSF_Core_Fields::get( $post_id, $k );
		$year = (int) $f( 'foaled' );
		$age  = $year > 1980 ? (int) wp_date( 'Y' ) - $year : 0;
		return array(
			__( 'Registered name', 'csf-core' ) => $f( 'registered_name' ),
			__( 'Breed', 'csf-core' )           => $f( 'breed' ),
			__( 'Sex', 'csf-core' )             => $f( 'sex' ),
			/* translators: 1: age, 2: year */
			__( 'Age', 'csf-core' )             => $age ? sprintf( __( '%1$d (foaled %2$d)', 'csf-core' ), $age, $year ) : '',
			__( 'Height', 'csf-core' )          => $f( 'height' ),
			__( 'Color', 'csf-core' )           => $f( 'color' ),
			__( 'Discipline', 'csf-core' )      => $f( 'discipline' ),
			__( 'Price', 'csf-core' )           => '1' === $f( 'price_approved' ) ? $f( 'price' ) : '',
		);
	}

	/**
	 * Availability badge HTML (empty for farm horses).
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function badge( $post_id ) {
		$key = CSF_Core_Fields::get( $post_id, 'availability' );
		if ( ! $key || 'na' === $key ) {
			return '';
		}
		$labels = self::availability();
		return '<span class="csf-badge csf-badge--' . esc_attr( $key ) . '">' . esc_html( $labels[ $key ] ?? '' ) . '</span>';
	}

	/**
	 * Inquiry link: mailto with subject when an email is configured, else the Contact page.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function inquiry_url( $post_id ) {
		$email = CSF_Core_Farm_Info::get()['email'];
		if ( $email ) {
			/* translators: %s: horse name */
			return 'mailto:' . antispambot( $email ) . '?subject=' . rawurlencode( sprintf( __( 'Inquiry about %s', 'csf-core' ), get_the_title( $post_id ) ) );
		}
		$contact = get_page_by_path( 'contact' );
		return $contact ? get_permalink( $contact ) : home_url( '/' );
	}

	/**
	 * Single view: details, suitability, history, video, inquiry.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function decorate_single( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$id   = get_the_ID();
		$f    = static fn( $k ) => CSF_Core_Fields::get( $id, $k );
		$top  = '<div class="csf-horse-summary">' . self::badge( $id ) . CSF_Core_Fields::definition_list( self::detail_rows( $id ) ) . '</div>';
		$more = '';
		foreach ( array(
			'suitability'      => __( 'Suitability', 'csf-core' ),
			'training_history' => __( 'Training history', 'csf-core' ),
		) as $key => $label ) {
			if ( $f( $key ) ) {
				$more .= '<section class="csf-horse-section"><h2>' . esc_html( $label ) . '</h2>' . wpautop( esc_html( $f( $key ) ) ) . '</section>';
			}
		}
		if ( $f( 'video' ) ) {
			$embed = wp_oembed_get( $f( 'video' ) );
			$more .= '<section class="csf-horse-section"><h2>' . esc_html__( 'Video', 'csf-core' ) . '</h2>' . ( $embed ? '<div class="csf-embed">' . $embed . '</div>' : '<p><a href="' . esc_url( $f( 'video' ) ) . '">' . esc_html__( 'Watch video', 'csf-core' ) . '</a></p>' ) . '</section>';
		}
		$listing = has_term( array( 'for-sale', 'for-lease' ), self::LISTING, $id );
		if ( $listing && in_array( $f( 'availability' ), array( 'available', 'pending' ), true ) ) {
			$more .= '<p class="csf-horse-inquiry"><a class="csf-book-button csf-book-button--primary" href="' . esc_url( self::inquiry_url( $id ) ) . '">' . esc_html(
				/* translators: %s: horse name */
				sprintf( __( 'Ask about %s', 'csf-core' ), get_the_title( $id ) )
			) . '</a></p>';
		}
		return $top . $content . $more;
	}

	/**
	 * Horse card.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function card( $post ) {
		$f    = static fn( $k ) => CSF_Core_Fields::get( $post->ID, $k );
		$meta = implode( ' · ', array_filter( array( $f( 'breed' ), $f( 'sex' ), $f( 'height' ) ) ) );
		return CSF_Core_Blocks::card(
			array(
				'url'   => get_permalink( $post ),
				'title' => get_the_title( $post ),
				'text'  => $post->post_excerpt,
				'meta'  => $meta,
				'badge' => self::badge( $post->ID ),
				'image' => (int) get_post_thumbnail_id( $post ),
				'ratio' => 'portrait',
			)
		);
	}

	/**
	 * Listing block.
	 *
	 * @param array $attrs { listing, count, heading, emptyMessage, hideWhenEmpty, includeClosed }.
	 * @return string
	 */
	public static function render_list( $attrs ) {
		$listing = sanitize_key( $attrs['listing'] ?? 'for-sale' );
		$args    = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => max( 1, min( 24, (int) ( $attrs['count'] ?? 12 ) ) ),
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		);
		if ( array_key_exists( $listing, self::listings() ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => self::LISTING,
					'field'    => 'slug',
					'terms'    => $listing,
				),
			);
		}
		if ( 'farm-horse' !== $listing && empty( $attrs['includeClosed'] ) ) {
			$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_csf_availability',
					'value'   => array( 'available', 'pending' ),
					'compare' => 'IN',
				),
			);
		}
		$posts = get_posts( $args );
		return CSF_Core_Blocks::listing( $attrs, array_map( array( __CLASS__, 'card' ), $posts ), __( 'No horses are listed right now.', 'csf-core' ) );
	}
}

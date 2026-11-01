<?php
/**
 * Camps, clinics, and other dated programs.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Events have dates, ages and a registration status.
 * Registration is arranged directly using owner-approved contact channels; this
 * plugin directs visitors to the internal Contact page.
 */
class CSF_Core_Events {

	const POST_TYPE = 'csf_event';
	const TYPE      = 'csf_event_type';
	const NONCE     = 'csf_event_fields';

	/**
	 * Default event types.
	 *
	 * @return string[]
	 */
	public static function default_types() {
		return array( 'Summer Camp', 'Mini Camp', 'Horsemanship Clinic', 'Confidence Clinic', 'Dressage Foundations', 'Adult Amateur Experience', 'School-Break Program', '4-H & Pony Club' );
	}

	/**
	 * Registration statuses.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'tba'      => __( 'Registration opens soon', 'csf-core' ),
			'open'     => __( 'Registration open', 'csf-core' ),
			'waitlist' => __( 'Waitlist only', 'csf-core' ),
			'full'     => __( 'Full', 'csf-core' ),
			'closed'   => __( 'Registration closed', 'csf-core' ),
		);
	}

	/**
	 * Fields.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'start_date'     => array(
				'label' => __( 'Start date', 'csf-core' ),
				'type'  => 'date',
			),
			'end_date'       => array(
				'label' => __( 'End date', 'csf-core' ),
				'type'  => 'date',
				'help'  => __( 'Leave empty for one-day events. Events disappear from listings after this date.', 'csf-core' ),
			),
			'time_note'      => array(
				'label' => __( 'Times', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'e.g. "9:00 am – 12:00 pm daily"', 'csf-core' ),
			),
			'ages'           => array(
				'label' => __( 'Ages / level', 'csf-core' ),
				'type'  => 'text',
			),
			'location'       => array(
				'label' => __( 'Location', 'csf-core' ),
				'type'  => 'text',
				'help'  => __( 'e.g. "At Corner Stone Farm" or the host venue.', 'csf-core' ),
			),
			'capacity'       => array(
				'label' => __( 'Group size note', 'csf-core' ),
				'type'  => 'text',
			),
			'price'          => array(
				'label' => __( 'Price', 'csf-core' ),
				'type'  => 'text',
			),
			'price_approved' => array(
				'label'          => __( 'Publish price', 'csf-core' ),
				'type'           => 'checkbox',
				'checkbox_label' => __( 'Owner has approved publishing this price', 'csf-core' ),
			),
			'status'         => array(
				'label'   => __( 'Registration status', 'csf-core' ),
				'type'    => 'select',
				'options' => self::statuses(),
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
					add_meta_box( 'csf-event-details', __( 'Event details', 'csf-core' ), array( __CLASS__, 'render_box' ), self::POST_TYPE, 'normal', 'high' );
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
	 * Register post type and taxonomy.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'          => __( 'Camps & Clinics', 'csf-core' ),
					'singular_name' => __( 'Event', 'csf-core' ),
					'add_new_item'  => __( 'Add Camp or Clinic', 'csf-core' ),
					'edit_item'     => __( 'Edit Event', 'csf-core' ),
					'menu_name'     => __( 'Camps & Clinics', 'csf-core' ),
					'excerpt_field' => __( 'Short description', 'csf-core' ),
				),
				'public'        => true,
				'has_archive'   => false,
				'rewrite'       => array(
					'slug'       => 'events',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-calendar-alt',
				'menu_position' => 23,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			)
		);
		register_taxonomy(
			self::TYPE,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Event Types', 'csf-core' ),
					'singular_name' => __( 'Event Type', 'csf-core' ),
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
	 * Seed event types.
	 *
	 * @return WP_Error[] Failed term insertions.
	 */
	public static function seed_terms() {
		$errors = array();
		foreach ( self::default_types() as $name ) {
			if ( ! term_exists( $name, self::TYPE ) ) {
				$result = wp_insert_term( $name, self::TYPE );
				if ( is_wp_error( $result ) ) {
					$errors[] = $result;
				}
			}
		}
		return $errors;
	}

	/**
	 * Meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public static function render_box( $post ) {
		CSF_Core_Fields::render( $post->ID, self::fields(), self::NONCE );
	}

	/**
	 * Human date range.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function date_range( $post_id ) {
		$start = CSF_Core_Fields::format_date( CSF_Core_Fields::get( $post_id, 'start_date' ) );
		$end   = CSF_Core_Fields::format_date( CSF_Core_Fields::get( $post_id, 'end_date' ) );
		if ( ! $start ) {
			return __( 'Dates to be announced', 'csf-core' );
		}
		return $end && $end !== $start ? $start . ' – ' . $end : $start;
	}

	/**
	 * Status badge.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function badge( $post_id ) {
		$key      = CSF_Core_Fields::get( $post_id, 'status' );
		$statuses = self::statuses();
		$key      = isset( $statuses[ $key ] ) ? $key : 'tba';
		return '<span class="csf-badge csf-badge--' . esc_attr( $key ) . '">' . esc_html( $statuses[ $key ] ) . '</span>';
	}

	/**
	 * Single view: details and registration button.
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
		$rows = array(
			__( 'Dates', 'csf-core' )        => self::date_range( $id ),
			__( 'Times', 'csf-core' )        => $f( 'time_note' ),
			__( 'Ages / level', 'csf-core' ) => $f( 'ages' ),
			__( 'Location', 'csf-core' )     => $f( 'location' ),
			__( 'Group size', 'csf-core' )   => $f( 'capacity' ),
			__( 'Price', 'csf-core' )        => '1' === $f( 'price_approved' ) ? $f( 'price' ) : '',
		);
		$top  = '<div class="csf-event-summary">' . self::badge( $id ) . CSF_Core_Fields::definition_list( $rows ) . self::register_button( $id ) . '</div>';
		return $top . $content;
	}

	/**
	 * Registration button when registration is possible.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function register_button( $post_id ) {
		if ( ! in_array( CSF_Core_Fields::get( $post_id, 'status' ), array( 'open', 'waitlist' ), true ) ) {
			return '';
		}
		return CSF_Core_Booking::render_button( array() );
	}

	/**
	 * Event card.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function card( $post ) {
		$ages = CSF_Core_Fields::get( $post->ID, 'ages' );
		return CSF_Core_Blocks::card(
			array(
				'url'   => get_permalink( $post ),
				'title' => get_the_title( $post ),
				'text'  => $post->post_excerpt,
				'meta'  => self::date_range( $post->ID ) . ( $ages ? ' · ' . $ages : '' ),
				'badge' => self::badge( $post->ID ),
				'image' => (int) get_post_thumbnail_id( $post ),
			)
		);
	}

	/**
	 * Upcoming events listing.
	 *
	 * @param array $attrs { count, type, heading, emptyMessage, hideWhenEmpty }.
	 * @return string
	 */
	public static function render_list( $attrs ) {
		$today = wp_date( 'Y-m-d' );
		$args  = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => max( 1, min( 24, (int) ( $attrs['count'] ?? 6 ) ) ),
			'meta_key'       => '_csf_start_date', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'OR',
				array(
					'key'     => '_csf_end_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
				array(
					'relation' => 'AND',
					array(
						'key'     => '_csf_end_date',
						'value'   => '',
						'compare' => '=',
					),
					array(
						'relation' => 'OR',
						array(
							'key'     => '_csf_start_date',
							'value'   => $today,
							'compare' => '>=',
							'type'    => 'DATE',
						),
						array(
							'key'     => '_csf_start_date',
							'value'   => '',
							'compare' => '=',
						),
					),
				),
			),
		);
		if ( ! empty( $attrs['type'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => self::TYPE,
					'field'    => 'slug',
					'terms'    => sanitize_title( $attrs['type'] ),
				),
			);
		}
		$posts = get_posts( $args );
		return CSF_Core_Blocks::listing( $attrs, array_map( array( __CLASS__, 'card' ), $posts ), __( 'Upcoming dates will be posted here.', 'csf-core' ) );
	}
}

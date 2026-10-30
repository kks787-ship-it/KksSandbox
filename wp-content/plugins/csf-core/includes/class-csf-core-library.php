<?php
/**
 * Knowledge Library content model.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Articles ("Resources") with categories, audiences, a required summary (the excerpt),
 * reading time, an expert-review date, revision (modified) date, and related articles.
 *
 * URLs: /library/{article}/, /library/topic/{category}/, archive at /library/.
 * The editorial landing page is the ordinary "Resources" page (/resources/).
 */
class CSF_Core_Library {

	const POST_TYPE = 'csf_resource';
	const CATEGORY  = 'csf_resource_category';
	const AUDIENCE  = 'csf_resource_audience';
	const NONCE     = 'csf_resource_review';
	const WPM       = 200;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_filter( 'the_content', array( __CLASS__, 'decorate_single' ), 20 );
		if ( is_admin() ) {
			add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'meta_boxes' ) );
			add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ) );
			add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
			add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		}
	}

	/**
	 * Initial categories from the specification.
	 *
	 * @return string[]
	 */
	public static function default_categories() {
		return array(
			'Beginner Riding',
			'Dressage',
			'Horse Care',
			'Horsemanship',
			'Horse Behavior',
			'Safety',
			'Parent Resources',
			'Camps',
			'Adult Riders',
			'Horse Development',
			'FEI Foundations',
			'Training Philosophy',
			'Horse Sales',
			'Working Students',
			'Farm Operations',
		);
	}

	/**
	 * Register the post type and taxonomies.
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'        => array(
					'name'          => __( 'Library Articles', 'csf-core' ),
					'singular_name' => __( 'Library Article', 'csf-core' ),
					'add_new_item'  => __( 'Add Article', 'csf-core' ),
					'edit_item'     => __( 'Edit Article', 'csf-core' ),
					'menu_name'     => __( 'Knowledge Library', 'csf-core' ),
					'excerpt_field' => __( 'Summary', 'csf-core' ),
				),
				'public'        => true,
				'has_archive'   => 'library',
				'rewrite'       => array(
					'slug'       => 'library',
					'with_front' => false,
				),
				'menu_icon'     => 'dashicons-book-alt',
				'menu_position' => 21,
				'show_in_rest'  => true,
				'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
			)
		);

		register_taxonomy(
			self::CATEGORY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Library Topics', 'csf-core' ),
					'singular_name' => __( 'Library Topic', 'csf-core' ),
				),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'library/topic',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			self::AUDIENCE,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Audiences', 'csf-core' ),
					'singular_name' => __( 'Audience', 'csf-core' ),
				),
				'hierarchical'      => false,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'library/for',
					'with_front' => false,
				),
			)
		);

		register_post_meta(
			self::POST_TYPE,
			'_csf_reviewed',
			array(
				'type'          => 'string',
				'single'        => true,
				'auth_callback' => static fn() => current_user_can( 'edit_posts' ),
			)
		);
	}

	/**
	 * Create any missing default categories. Safe to run repeatedly.
	 */
	public static function seed_categories() {
		foreach ( self::default_categories() as $name ) {
			if ( ! term_exists( $name, self::CATEGORY ) ) {
				wp_insert_term( $name, self::CATEGORY );
			}
		}
	}

	/**
	 * Estimated reading time in minutes (minimum 1).
	 *
	 * @param int|WP_Post $post Post.
	 * @return int
	 */
	public static function reading_time( $post ) {
		$post  = get_post( $post );
		$words = $post ? str_word_count( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) : 0;
		return max( 1, (int) ceil( $words / self::WPM ) );
	}

	/**
	 * Summary: the excerpt, or a trimmed fallback.
	 *
	 * @param int|WP_Post $post Post.
	 * @return string
	 */
	public static function summary( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return '';
		}
		return $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 28 );
	}

	/**
	 * Related articles: same topics first, then most recent.
	 *
	 * @param int $post_id Post ID.
	 * @param int $count   Number of posts.
	 * @return WP_Post[]
	 */
	public static function related( $post_id, $count = 3 ) {
		$terms   = wp_get_post_terms( $post_id, self::CATEGORY, array( 'fields' => 'ids' ) );
		$related = array();
		if ( $terms && ! is_wp_error( $terms ) ) {
			$related = get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'posts_per_page' => $count,
					'post__not_in'   => array( $post_id ), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- small library.
					'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
						array(
							'taxonomy' => self::CATEGORY,
							'terms'    => $terms,
						),
					),
				)
			);
		}
		if ( count( $related ) < $count ) {
			$exclude = array_merge( array( $post_id ), wp_list_pluck( $related, 'ID' ) );
			$related = array_merge(
				$related,
				get_posts(
					array(
						'post_type'      => self::POST_TYPE,
						'posts_per_page' => $count - count( $related ),
						'post__not_in'   => $exclude, // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in -- small library.
					)
				)
			);
		}
		return $related;
	}

	/**
	 * Add the article header (topics, reading time, dates) and related articles to single
	 * article views, independent of the theme's single template.
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public static function decorate_single( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$post_id = get_the_ID();
		return self::render_meta( $post_id ) . $content . self::render_related( array( 'postId' => $post_id ) );
	}

	/**
	 * Article meta line.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function render_meta( $post_id ) {
		$parts = array();
		$terms = get_the_terms( $post_id, self::CATEGORY );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$links = array();
			foreach ( $terms as $term ) {
				$links[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
			}
			$parts[] = '<span class="csf-article-meta__topics">' . implode( ', ', $links ) . '</span>';
		}
		$minutes = self::reading_time( $post_id );
		/* translators: %d: minutes */
		$parts[] = '<span class="csf-article-meta__time">' . esc_html( sprintf( _n( '%d min read', '%d min read', $minutes, 'csf-core' ), $minutes ) ) . '</span>';

		$published = get_post_time( 'U', true, $post_id );
		$modified  = get_post_modified_time( 'U', true, $post_id );
		if ( $modified - $published > DAY_IN_SECONDS ) {
			/* translators: %s: date */
			$parts[] = '<span class="csf-article-meta__updated">' . esc_html( sprintf( __( 'Updated %s', 'csf-core' ), wp_date( get_option( 'date_format' ), $modified ) ) ) . '</span>';
		} else {
			/* translators: %s: date */
			$parts[] = '<span class="csf-article-meta__published">' . esc_html( sprintf( __( 'Published %s', 'csf-core' ), wp_date( get_option( 'date_format' ), $published ) ) ) . '</span>';
		}
		$reviewed = CSF_Core_Fields::format_date( (string) get_post_meta( $post_id, '_csf_reviewed', true ) );
		if ( $reviewed ) {
			/* translators: %s: date */
			$parts[] = '<span class="csf-article-meta__reviewed">' . esc_html( sprintf( __( 'Reviewed by Corner Stone Farm %s', 'csf-core' ), $reviewed ) ) . '</span>';
		}
		return '<p class="csf-article-meta">' . implode( '<span class="csf-article-meta__sep" aria-hidden="true">·</span>', $parts ) . '</p>';
	}

	/**
	 * Related articles section.
	 *
	 * @param array $attrs { postId, count }.
	 * @return string
	 */
	public static function render_related( $attrs ) {
		$post_id = (int) ( $attrs['postId'] ?? get_the_ID() );
		$related = $post_id ? self::related( $post_id, max( 1, min( 6, (int) ( $attrs['count'] ?? 3 ) ) ) ) : array();
		if ( ! $related ) {
			return '';
		}
		$html = '<aside class="csf-related" aria-labelledby="csf-related-heading"><h2 id="csf-related-heading" class="csf-related__heading">' . esc_html__( 'Related reading', 'csf-core' ) . '</h2><div class="csf-cards csf-cards--3">';
		foreach ( $related as $post ) {
			$html .= self::card( $post, 'h3' );
		}
		return $html . '</div></aside>';
	}

	/**
	 * Article card.
	 *
	 * @param WP_Post $post    Post.
	 * @param string  $heading Heading tag.
	 * @return string
	 */
	public static function card( $post, $heading = 'h3' ) {
		$terms   = get_the_terms( $post, self::CATEGORY );
		$topic   = $terms && ! is_wp_error( $terms ) ? $terms[0]->name : '';
		$minutes = self::reading_time( $post );
		$meta    = trim( $topic . ( $topic ? ' · ' : '' ) . sprintf( /* translators: %d: minutes */ _n( '%d min read', '%d min read', $minutes, 'csf-core' ), $minutes ) );
		return CSF_Core_Blocks::card(
			array(
				'url'     => get_permalink( $post ),
				'title'   => get_the_title( $post ),
				'text'    => self::summary( $post ),
				'meta'    => $meta,
				'image'   => (int) get_post_thumbnail_id( $post ),
				'heading' => $heading,
			)
		);
	}

	/**
	 * Article listing block.
	 *
	 * @param array $attrs { count, topic, heading, emptyMessage, hideWhenEmpty }.
	 * @return string
	 */
	public static function render_list( $attrs ) {
		$args = array(
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => max( 1, min( 24, (int) ( $attrs['count'] ?? 6 ) ) ),
		);
		if ( ! empty( $attrs['topic'] ) ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => self::CATEGORY,
					'field'    => 'slug',
					'terms'    => sanitize_title( $attrs['topic'] ),
				),
			);
		}
		$posts = get_posts( $args );

		return CSF_Core_Blocks::listing(
			$attrs,
			array_map( array( __CLASS__, 'card' ), $posts ),
			__( 'New articles are being written. Check back soon.', 'csf-core' ),
			$posts ? array(
				'url'   => get_post_type_archive_link( self::POST_TYPE ),
				'label' => __( 'Browse the full library', 'csf-core' ),
			) : array()
		);
	}

	/**
	 * Topic list with article counts (topics without published articles are hidden).
	 *
	 * @return string
	 */
	public static function render_topics() {
		$terms = get_terms(
			array(
				'taxonomy'   => self::CATEGORY,
				'hide_empty' => true,
			)
		);
		if ( ! $terms || is_wp_error( $terms ) ) {
			return '';
		}
		$html = '<nav class="csf-topics" aria-label="' . esc_attr__( 'Library topics', 'csf-core' ) . '"><ul>';
		foreach ( $terms as $term ) {
			$html .= '<li><a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . ' <span class="csf-topics__count">' . esc_html( number_format_i18n( $term->count ) ) . '</span></a></li>';
		}
		return $html . '</ul></nav>';
	}

	/**
	 * Review meta box.
	 */
	public static function meta_boxes() {
		add_meta_box(
			'csf-resource-review',
			__( 'Expert review', 'csf-core' ),
			static function ( $post ) {
				wp_nonce_field( self::NONCE, self::NONCE );
				echo '<p><label for="csf-reviewed">' . esc_html__( 'Content last reviewed for accuracy', 'csf-core' ) . '</label><input type="date" id="csf-reviewed" name="csf_reviewed" class="widefat" value="' . esc_attr( (string) get_post_meta( $post->ID, '_csf_reviewed', true ) ) . '"></p>';
				echo '<p class="description">' . esc_html__( 'Set when Beth (or another qualified reviewer) has checked the article. Shown to readers. The "Updated" date comes from the last saved revision.', 'csf-core' ) . '</p>';
				echo '<p class="description">' . esc_html__( 'The Summary (Excerpt panel) is shown on cards and search results — please always fill it in.', 'csf-core' ) . '</p>';
			},
			self::POST_TYPE,
			'side'
		);
	}

	/**
	 * Save review date.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE ] ) ), self::NONCE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$date = isset( $_POST['csf_reviewed'] ) ? sanitize_text_field( wp_unslash( $_POST['csf_reviewed'] ) ) : '';
		update_post_meta( $post_id, '_csf_reviewed', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : '' );
	}

	/**
	 * Admin columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$columns['csf_summary']  = __( 'Summary', 'csf-core' );
		$columns['csf_time']     = __( 'Reading time', 'csf-core' );
		$columns['csf_reviewed'] = __( 'Reviewed', 'csf-core' );
		return $columns;
	}

	/**
	 * Admin column output.
	 *
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 */
	public static function column( $column, $post_id ) {
		switch ( $column ) {
			case 'csf_summary':
				echo has_excerpt( $post_id ) ? esc_html__( 'Yes', 'csf-core' ) : '<strong>' . esc_html__( 'Missing', 'csf-core' ) . '</strong>';
				break;
			case 'csf_time':
				/* translators: %d: minutes */
				echo esc_html( sprintf( __( '%d min', 'csf-core' ), self::reading_time( $post_id ) ) );
				break;
			case 'csf_reviewed':
				$reviewed = CSF_Core_Fields::format_date( (string) get_post_meta( $post_id, '_csf_reviewed', true ) );
				echo esc_html( $reviewed ? $reviewed : '—' );
				break;
		}
	}
}

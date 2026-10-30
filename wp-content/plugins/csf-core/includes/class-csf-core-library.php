<?php
/**
 * Knowledge Library content model.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * A "Resource" post type with a hierarchical category taxonomy and an audience taxonomy.
 * The excerpt is the required summary. Content is edited in Gutenberg and exposed in REST so
 * a future search index or educational assistant can read only published, public articles.
 */
class CSF_Core_Library {

	const POST_TYPE = 'csf_resource';
	const CATEGORY  = 'csf_resource_category';
	const AUDIENCE  = 'csf_resource_audience';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
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
				'labels'       => array(
					'name'          => __( 'Resources', 'csf-core' ),
					'singular_name' => __( 'Resource', 'csf-core' ),
					'add_new_item'  => __( 'Add Resource', 'csf-core' ),
					'edit_item'     => __( 'Edit Resource', 'csf-core' ),
					'menu_name'     => __( 'Knowledge Library', 'csf-core' ),
				),
				'public'       => true,
				'has_archive'  => 'resources',
				'rewrite'      => array(
					'slug'       => 'resources',
					'with_front' => false,
				),
				'menu_icon'    => 'dashicons-book-alt',
				'show_in_rest' => true,
				'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author' ),
				'template'     => array(
					array(
						'core/paragraph',
						array( 'placeholder' => __( 'Start the article…', 'csf-core' ) ),
					),
				),
			)
		);

		register_taxonomy(
			self::CATEGORY,
			self::POST_TYPE,
			array(
				'labels'            => array(
					'name'          => __( 'Library Categories', 'csf-core' ),
					'singular_name' => __( 'Library Category', 'csf-core' ),
				),
				'hierarchical'      => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'resources/category',
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
					'slug'       => 'resources/for',
					'with_front' => false,
				),
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
}

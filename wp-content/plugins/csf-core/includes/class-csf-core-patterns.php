<?php
/**
 * Block pattern registration.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Section patterns (patterns/*.php) appear in the inserter under "Corner Stone Farm" for
 * building or extending pages. Full-page layouts (content/pages/*.php) are registered too,
 * so any page can be rebuilt from its original layout.
 */
class CSF_Core_Patterns {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
	}

	/**
	 * Register categories and patterns.
	 */
	public static function register() {
		register_block_pattern_category( 'csf-sections', array( 'label' => __( 'Corner Stone Farm: Sections', 'csf-core' ) ) );
		register_block_pattern_category( 'csf-pages', array( 'label' => __( 'Corner Stone Farm: Page layouts', 'csf-core' ) ) );

		foreach ( self::section_files() as $slug => $file ) {
			$def = include $file;
			register_block_pattern(
				'csf/' . $slug,
				array(
					'title'       => $def['title'],
					'description' => $def['description'] ?? '',
					'categories'  => array( 'csf-sections' ),
					'keywords'    => $def['keywords'] ?? array(),
					'content'     => $def['content'],
				)
			);
		}

		foreach ( CSF_Core_Setup::pages() as $page ) {
			register_block_pattern(
				'csf/page-' . $page['slug'],
				array(
					/* translators: %s: page title */
					'title'      => sprintf( __( 'Page layout: %s', 'csf-core' ), $page['title'] ),
					'categories' => array( 'csf-pages' ),
					'postTypes'  => array( 'page' ),
					'content'    => self::page_content( $page['slug'] ),
				)
			);
		}
	}

	/**
	 * Section pattern files keyed by slug.
	 *
	 * @return array<string,string>
	 */
	public static function section_files() {
		$files = array();
		foreach ( glob( CSF_CORE_DIR . 'patterns/*.php' ) as $file ) {
			$files[ basename( $file, '.php' ) ] = $file;
		}
		return $files;
	}

	/**
	 * Content of a section pattern.
	 *
	 * @param string $slug Slug without "csf/".
	 * @return string
	 */
	public static function section( $slug ) {
		$file = CSF_CORE_DIR . 'patterns/' . sanitize_file_name( $slug ) . '.php';
		if ( ! file_exists( $file ) ) {
			return '';
		}
		$def = include $file;
		return $def['content'];
	}

	/**
	 * Content of a page layout.
	 *
	 * @param string $slug Page slug.
	 * @return string
	 */
	public static function page_content( $slug ) {
		$file = CSF_CORE_DIR . 'content/pages/' . sanitize_file_name( $slug ) . '.php';
		return file_exists( $file ) ? (string) include $file : '';
	}
}

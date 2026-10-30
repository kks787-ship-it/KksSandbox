<?php
/**
 * Test-only stand-in for Kadence. Not for deployment.
 *
 * @package CSF_Standin
 */

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'custom-logo' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
		register_nav_menus(
			array(
				'primary' => 'Primary Navigation',
				'mobile'  => 'Mobile Navigation',
				'footer'  => 'Footer Navigation',
			)
		);
	}
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		wp_enqueue_style( 'csf-standin', get_stylesheet_uri(), array(), '0.1.0' );
	}
);

/**
 * Mirrors Kadence's per-page "hide title" / "fullwidth" settings.
 *
 * @return array{title:bool,full:bool}
 */
function csf_standin_layout() {
	$id = get_queried_object_id();
	return array(
		'title' => 'hide' !== get_post_meta( $id, '_kad_post_title', true ),
		'full'  => 'fullwidth' === get_post_meta( $id, '_kad_post_layout', true ),
	);
}

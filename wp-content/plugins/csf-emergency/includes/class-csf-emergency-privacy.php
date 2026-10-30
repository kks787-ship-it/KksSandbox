<?php
/**
 * Keep horse emergency pages unlisted: noindex, no sitemaps, no search.
 *
 * Unlisted is not private. These measures reduce discovery; they do not protect data,
 * which is why only information approved for public emergency use is stored.
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Belt-and-braces exclusions. The post type is already non-public, so core sitemaps and
 * search ignore it; these filters also cover common SEO plugins.
 */
class CSF_Emergency_Privacy {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'wp_sitemaps_post_types', array( __CLASS__, 'remove_from_core_sitemaps' ) );
		add_filter( 'wpseo_sitemap_exclude_post_type', array( __CLASS__, 'exclude_post_type' ), 10, 2 );
		add_filter( 'rank_math/sitemap/exclude_post_type', array( __CLASS__, 'exclude_post_type' ), 10, 2 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots_txt' ), 10, 2 );
	}

	/**
	 * Remove from WordPress core sitemaps.
	 *
	 * @param array $post_types Post types.
	 * @return array
	 */
	public static function remove_from_core_sitemaps( $post_types ) {
		unset( $post_types[ CSF_Emergency_Horses::POST_TYPE ] );
		return $post_types;
	}

	/**
	 * Exclude from Yoast / Rank Math sitemaps.
	 *
	 * @param bool   $excluded  Current value.
	 * @param string $post_type Post type.
	 * @return bool
	 */
	public static function exclude_post_type( $excluded, $post_type ) {
		return CSF_Emergency_Horses::POST_TYPE === $post_type ? true : $excluded;
	}

	/**
	 * Ask well-behaved crawlers not to crawl /h/. Pages also send noindex headers, which is
	 * the stronger signal; robots.txt alone would not remove an already-linked URL.
	 *
	 * @param string $output Robots output.
	 * @param bool   $is_public Whether the site is public.
	 * @return string
	 */
	public static function robots_txt( $output, $is_public ) {
		if ( ! $is_public ) {
			return $output;
		}
		$rule = 'Disallow: ' . wp_parse_url( home_url( '/h/' ), PHP_URL_PATH );
		// Join the existing "User-agent: *" group rather than adding a second one.
		if ( false !== strpos( $output, "User-agent: *\n" ) ) {
			return preg_replace( '/User-agent: \*\n/', "User-agent: *\n" . $rule . "\n", $output, 1 );
		}
		return $output . "\nUser-agent: *\n" . $rule . "\n";
	}

	/**
	 * Headers for every emergency route: never index, never cache for long.
	 */
	public static function send_headers() {
		header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
		header( 'Referrer-Policy: no-referrer', true );
		nocache_headers();
	}
}

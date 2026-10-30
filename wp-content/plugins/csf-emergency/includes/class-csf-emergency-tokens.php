<?php
/**
 * Public identifier generation for horse emergency URLs (/h/{token}).
 *
 * @package CSF_Emergency
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tokens are random, non-sequential, and never derived from database IDs.
 *
 * The alphabet omits 0/o, 1/l/i so a token can be read aloud or typed from a worn tag.
 * 8 characters from a 31-character alphabet gives ~8.5 x 10^11 combinations.
 */
class CSF_Emergency_Tokens {

	const ALPHABET = '23456789abcdefghjkmnpqrstuvwxyz';
	const LENGTH   = 8;
	const META_KEY = '_csf_token';

	/**
	 * Generate a token using a cryptographically secure RNG.
	 *
	 * @return string
	 */
	public static function generate() {
		$max   = strlen( self::ALPHABET ) - 1;
		$token = '';
		for ( $i = 0; $i < self::LENGTH; $i++ ) {
			$token .= self::ALPHABET[ random_int( 0, $max ) ];
		}
		return $token;
	}

	/**
	 * Generate a token not already assigned to any horse.
	 *
	 * @return string
	 */
	public static function generate_unique() {
		do {
			$token = self::generate();
		} while ( self::find_post_id( $token ) );
		return $token;
	}

	/**
	 * Whether a string has the shape of a token. Used before any database lookup.
	 *
	 * @param string $token Candidate.
	 * @return bool
	 */
	public static function is_valid_format( $token ) {
		return is_string( $token )
			&& strlen( $token ) === self::LENGTH
			&& strspn( $token, self::ALPHABET ) === self::LENGTH;
	}

	/**
	 * Find the horse profile post ID for a token.
	 *
	 * @param string $token Token.
	 * @return int Post ID, or 0.
	 */
	public static function find_post_id( $token ) {
		if ( ! self::is_valid_format( $token ) ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'        => CSF_Emergency_Horses::POST_TYPE,
				'post_status'      => 'any',
				'meta_key'         => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'       => $token, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'           => 'ids',
				'posts_per_page'   => 1,
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}
}

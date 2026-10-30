<?php
/**
 * Local test fixtures (run via `wp eval-file tests/e2e/fixtures.php create|delete`).
 * Every post is marked _csf_test_fixture so it can be removed afterwards. Never run on a real site.
 *
 * @package CSF
 */

if ( 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Fixtures only run when WP_ENVIRONMENT_TYPE is "local".' );
}

$csf_action = $args[0] ?? 'create';

if ( 'delete' === $csf_action ) {
	$csf_ids = get_posts(
		array(
			'post_type'      => array( 'csf_resource', 'csf_horse', 'csf_event' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_csf_test_fixture', // phpcs:ignore
		)
	);
	foreach ( $csf_ids as $csf_id ) {
		wp_delete_post( $csf_id, true );
	}
	echo wp_json_encode( array( 'deleted' => count( $csf_ids ) ) );
	return;
}

$csf_make = static function ( $type, $title, $status, $excerpt, $content, array $meta = array(), array $terms = array() ) {
	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => $status,
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
		)
	);
	update_post_meta( $id, '_csf_test_fixture', 1 );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	foreach ( $terms as $tax => $slugs ) {
		wp_set_object_terms( $id, $slugs, $tax );
	}
	return $id;
};

$csf_words = static fn( $n ) => '<!-- wp:paragraph --><p>' . trim( str_repeat( 'Sample text for automated testing only. ', $n ) ) . '</p><!-- /wp:paragraph -->';

$csf_article = $csf_make( 'csf_resource', 'Grooming basics', 'publish', 'Sample summary for testing.', $csf_words( 120 ), array( '_csf_reviewed' => '2026-10-01' ), array( 'csf_resource_category' => array( 'horse-care' ) ) );
$csf_make( 'csf_resource', 'Hoof care basics', 'publish', 'Sample summary for testing.', $csf_words( 40 ), array(), array( 'csf_resource_category' => array( 'horse-care' ) ) );
$csf_make( 'csf_resource', 'Helmet fit', 'publish', 'Sample summary for testing.', $csf_words( 40 ), array(), array( 'csf_resource_category' => array( 'safety' ) ) );
$csf_make( 'csf_resource', 'Draft article', 'draft', 'Should not appear.', $csf_words( 10 ) );

$csf_horse = $csf_make(
	'csf_horse',
	'Sample Sale Horse',
	'publish',
	'Sample description for testing.',
	$csf_words( 20 ),
	array(
		'_csf_breed'          => 'Sample breed',
		'_csf_sex'            => 'Gelding',
		'_csf_foaled'         => '2018',
		'_csf_height'         => '16.0 hh',
		'_csf_availability'   => 'available',
		'_csf_price'          => '$12,345',
		'_csf_price_approved' => '0',
		'_csf_suitability'    => 'Sample suitability.',
	),
	array( 'csf_horse_listing' => array( 'for-sale' ) )
);
$csf_make( 'csf_horse', 'Sample Sold Horse', 'publish', 'Sold.', $csf_words( 5 ), array( '_csf_availability' => 'sold' ), array( 'csf_horse_listing' => array( 'for-sale' ) ) );

$csf_event = $csf_make(
	'csf_event',
	'Sample Summer Camp',
	'publish',
	'Sample camp for testing.',
	$csf_words( 20 ),
	array(
		'_csf_start_date'   => gmdate( 'Y-m-d', strtotime( '+30 days' ) ),
		'_csf_end_date'     => gmdate( 'Y-m-d', strtotime( '+34 days' ) ),
		'_csf_ages'         => 'Ages 8–12 (sample)',
		'_csf_status'       => 'open',
		'_csf_register_url' => 'https://example.invalid/register',
	),
	array( 'csf_event_type' => array( 'summer-camp' ) )
);
$csf_make(
	'csf_event',
	'Sample Past Clinic',
	'publish',
	'Past.',
	$csf_words( 5 ),
	array(
		'_csf_start_date' => gmdate( 'Y-m-d', strtotime( '-30 days' ) ),
		'_csf_end_date'   => '',
		'_csf_status'     => 'closed',
	)
);

echo wp_json_encode(
	array(
		'article'         => get_permalink( $csf_article ),
		'horse'           => get_permalink( $csf_horse ),
		'event'           => get_permalink( $csf_event ),
		'library_archive' => get_post_type_archive_link( 'csf_resource' ),
	)
);

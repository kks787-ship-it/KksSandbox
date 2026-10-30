<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'About', 'csf-core' ),
	__( 'Meet the Horses', 'csf-core' ),
	esc_html__( 'The horses who teach our riders and anchor our program.', 'csf-core' )
)
. $csf::section(
	$csf::dynamic(
		'csf/horses',
		array(
			'listing'      => 'farm-horse',
			'count'        => 24,
			'emptyMessage' => __( 'Horse profiles are on their way.', 'csf-core' ),
		)
	)
	. $csf::note( esc_html__( 'which horses to feature, with an approved photo and a few lines about each (Horses → Add Horse, listing "Farm Horse").', 'csf-core' ) ),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

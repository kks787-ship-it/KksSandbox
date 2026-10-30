<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Resources', 'csf-core' ),
	__( 'Educational resources', 'csf-core' ),
	esc_html__( 'Practical articles on riding, horse care, safety, and training philosophy from the Corner Stone Farm Knowledge Library.', 'csf-core' )
)
. $csf::section(
	$csf::dynamic( 'csf/library-topics' )
	. $csf::dynamic(
		'csf/library',
		array(
			'count'        => 9,
			'emptyMessage' => __( 'Our first articles are being written and reviewed. Check back soon.', 'csf-core' ),
		)
	),
	'white'
)
. $csf::section(
	$csf::columns(
		array(
			$csf::card( $csf::h( __( 'Frequently asked questions', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Answers to common questions about lessons, training, and booking.', 'csf-core' ) ) . $csf::p( '<a href="' . $csf::url( 'resources/faq' ) . '">' . esc_html__( 'Read the FAQ', 'csf-core' ) . '</a>', 'csf-card-link' ) ),
			$csf::card( $csf::h( __( 'New to riding?', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Start with our youth and adult lesson pages to see what a first lesson looks like.', 'csf-core' ) ) . $csf::p( '<a href="' . $csf::url( 'lessons' ) . '">' . esc_html__( 'Riding lessons', 'csf-core' ) . '</a>', 'csf-card-link' ) ),
		),
		'',
		'csf-card-row'
	),
	'linen'
)
. CSF_Core_Patterns::section( 'cta-band' );

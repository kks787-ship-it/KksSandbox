<?php
/**
 * Testimonial — for REAL testimonials only.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Testimonial (real quotes only)', 'csf-core' ),
	'description' => __( 'Use only genuine testimonials with written permission to publish. Not placed on any page by default.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::group(
			$csf::eyebrow( __( 'In their words', 'csf-core' ) )
			. $csf::lead( esc_html__( '[Paste the exact testimonial text here, used with written permission.]', 'csf-core' ) )
			. $csf::p( esc_html__( '— [Name], [relationship, e.g. parent of a youth rider]', 'csf-core' ) ),
			array( 'className' => 'csf-section-intro' )
		),
		'linen'
	),
);

<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Lessons', 'csf-core' ),
	__( 'Adult Amateurs', 'csf-core' ),
	esc_html__( 'Starting out, coming back to riding, or refining your partnership with your own horse — lessons designed around adult goals, schedules, and confidence.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'Riding on your terms', 'csf-core' ) )
			. $csf::p( esc_html__( 'Adult riders bring different goals and different worries. We build a plan around both, with clear explanations of the why behind every exercise.', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Beginner through advanced', 'csf-core' ),
					esc_html__( 'English, Western, and dressage foundations', 'csf-core' ),
					esc_html__( 'Confidence-focused lessons for nervous or returning riders', 'csf-core' ),
					esc_html__( 'Haul-in lessons on your own horse', 'csf-core' ),
					esc_html__( 'Adult amateur clinics and experiences', 'csf-core' ),
				)
			),
			$csf::placeholder( 'adult-amateurs' ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'getting-started' )
. CSF_Core_Patterns::section( 'cta-band' );

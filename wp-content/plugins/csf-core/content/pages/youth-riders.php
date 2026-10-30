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
	__( 'Youth Riders', 'csf-core' ),
	esc_html__( 'Structured, safety-first lessons where young riders grow in skill, horsemanship, and confidence — on the ground and in the saddle.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::placeholder( 'youth-riders' ),
			$csf::h( __( 'What young riders learn', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Safe handling, grooming, and tacking up', 'csf-core' ),
					esc_html__( 'A secure, balanced position and correct basics', 'csf-core' ),
					esc_html__( 'Reading and respecting the horse', 'csf-core' ),
					esc_html__( 'Confidence that comes from real understanding', 'csf-core' ),
					esc_html__( 'Responsibility, teamwork, and leadership', 'csf-core' ),
				)
			)
			. $csf::p( esc_html__( 'We support riders involved in 4-H and Pony Club, and youth mentorship opportunities are available for dedicated students.', 'csf-core' ) ),
		),
		'center'
	),
	'white'
)
. $csf::section(
	$csf::intro( __( 'For parents', 'csf-core' ), __( 'What families can expect', 'csf-core' ) )
	. $csf::columns(
		array(
			$csf::card( $csf::h( __( 'Safety first', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Every lesson is supervised and paced to the rider. Required agreements and emergency contacts are completed before the first ride.', 'csf-core' ) ) ),
			$csf::card( $csf::h( __( 'Clear progress', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Riders work toward goals that fit their age, experience, and confidence — never rushed, never stalled.', 'csf-core' ) ) ),
			$csf::card( $csf::h( __( 'A welcoming barn', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'A family-centered environment where kids feel they belong, make friends, and learn from older riders.', 'csf-core' ) ) ),
		),
		'',
		'csf-card-row'
	)
	. $csf::note( esc_html__( 'minimum age, required riding attire and helmet policy, and whether parents may watch lessons.', 'csf-core' ) ),
	'linen'
)
. CSF_Core_Patterns::section( 'cta-band' );

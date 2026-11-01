<?php
/**
 * Getting started steps.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'How to get started', 'csf-core' ),
	'description' => __( 'Three steps from booking to the first ride.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::intro( __( 'New riders', 'csf-core' ), __( 'How to get started', 'csf-core' ) )
		. $csf::columns(
			array(
				$csf::card( $csf::eyebrow( __( 'Step 1', 'csf-core' ) ) . $csf::h( __( 'Contact Beth to Schedule', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Contact Beth to discuss a lesson. Beth or Kris confirms your appointment directly.', 'csf-core' ) ) ),
				$csf::card( $csf::eyebrow( __( 'Step 2', 'csf-core' ) ) . $csf::h( __( 'Complete rider paperwork', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Required agreements and emergency contacts are completed before the first ride — for every rider, every time.', 'csf-core' ) ) ),
				$csf::card( $csf::eyebrow( __( 'Step 3', 'csf-core' ) ) . $csf::h( __( 'Ride with a plan', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'After your first lesson, we recommend a lesson plan based on your goals and experience.', 'csf-core' ) ) ),
			),
			'',
			'csf-card-row'
		)
		. $csf::note( esc_html__( 'the onboarding steps, how agreements are delivered and signed, and whether new riders start with an evaluation lesson.', 'csf-core' ) ),
		'white'
	),
);

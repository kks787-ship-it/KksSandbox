<?php
/**
 * Lessons / Training / Camps pathways.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Pathways: Lessons, Training, Camps', 'csf-core' ),
	'description' => __( 'Three cards guiding visitors to the main programs.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::intro(
			__( 'Find your path', 'csf-core' ),
			__( 'How we can help', 'csf-core' ),
			esc_html__( 'Whether you are new to horses, returning to the saddle, or bringing along a young horse, there is a place for you here.', 'csf-core' )
		)
		. $csf::columns(
			array(
				$csf::link_card( 'lessons', __( 'Riding Lessons', 'csf-core' ), esc_html__( 'English and Western instruction for youth and adult riders, beginner through advanced — built around confidence and correct foundations.', 'csf-core' ), 'lessons', __( 'Explore lessons', 'csf-core' ) ),
				$csf::link_card( 'training', __( 'Horse Training', 'csf-core' ), esc_html__( 'Boutique training for a small number of horses at a time: young horse development, dressage foundations, restarts, and sale preparation.', 'csf-core' ), 'horse-training', __( 'About horse training', 'csf-core' ) ),
				$csf::link_card( 'camps', __( 'Camps & Clinics', 'csf-core' ), esc_html__( 'Summer camps, mini camps, and clinics that build horsemanship, confidence, and friendships.', 'csf-core' ), 'camps-clinics', __( 'See camps & clinics', 'csf-core' ) ),
			),
			'',
			'csf-card-row'
		),
		'linen'
	),
);

<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Riding Lessons', 'csf-core' ),
	__( 'Lessons built around the rider', 'csf-core' ),
	esc_html__( 'English and Western instruction for youth and adult riders, beginner through advanced. Every lesson is grounded in safety, correct foundations, and steady confidence-building.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'What we teach', 'csf-core' ) )
			. $csf::p( esc_html__( 'Lessons at Corner Stone Farm are individualized mentorship, not a conveyor belt. We take a small number of students so each rider gets a clear plan and real attention.', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'English and Western riding', 'csf-core' ),
					esc_html__( 'Beginner through advanced riders', 'csf-core' ),
					esc_html__( 'Youth riders and adult amateurs', 'csf-core' ),
					esc_html__( 'Dressage foundations', 'csf-core' ),
					esc_html__( 'Confidence-focused lessons', 'csf-core' ),
					esc_html__( 'Haul-in lessons on your own horse', 'csf-core' ),
					esc_html__( 'Off-site coaching', 'csf-core' ),
				)
			),
			$csf::placeholder( 'lessons' ),
		),
		'center'
	),
	'white'
)
. $csf::section(
	$csf::intro( __( 'Programs', 'csf-core' ), __( 'Find the right lessons', 'csf-core' ) )
	. $csf::columns(
		array(
			$csf::link_card( 'youth-riders', __( 'Youth Riders', 'csf-core' ), esc_html__( 'Safe, structured lessons that build horsemanship and confidence.', 'csf-core' ), 'lessons/youth-riders', __( 'Youth riders', 'csf-core' ) ),
			$csf::link_card( 'adult-amateurs', __( 'Adult Amateurs', 'csf-core' ), esc_html__( 'Goal-focused instruction for adults at any stage.', 'csf-core' ), 'lessons/adult-amateurs', __( 'Adult amateurs', 'csf-core' ) ),
			$csf::link_card( 'dressage', __( 'Dressage Foundations', 'csf-core' ), esc_html__( 'Correct basics that help every horse and rider.', 'csf-core' ), 'lessons/dressage-foundations', __( 'Dressage foundations', 'csf-core' ) ),
		),
		'',
		'csf-card-row'
	)
	. $csf::columns(
		array(
			$csf::link_card( 'offsite', __( 'Off-Site Lessons', 'csf-core' ), esc_html__( 'Coaching at your barn, grouped by area.', 'csf-core' ), 'lessons/off-site-lessons', __( 'Off-site lessons', 'csf-core' ) ),
			$csf::link_card( 'after-school', __( 'After-School Program', 'csf-core' ), esc_html__( 'Horsemanship and riding after the school day.', 'csf-core' ), 'lessons/after-school-program', __( 'After-school program', 'csf-core' ) ),
			$csf::link_card( 'leasing', __( 'Leasing & Mentorship', 'csf-core' ), esc_html__( 'Half leases, on-site leases, and working students.', 'csf-core' ), 'horse-sales/leasing-mentorship', __( 'Leasing & mentorship', 'csf-core' ) ),
		),
		'',
		'csf-card-row'
	),
	'linen'
)
. CSF_Core_Patterns::section( 'getting-started' )
. $csf::section(
	$csf::h( __( 'Lesson pricing', 'csf-core' ) )
	. $csf::p( esc_html__( 'Current lesson options and rates are shown when you book online.', 'csf-core' ) )
	. $csf::note( esc_html__( 'whether to publish lesson prices on this page, lesson lengths, private vs. group options, and the cancellation policy.', 'csf-core' ) ),
	'linen'
)
. CSF_Core_Patterns::section( 'cta-band' );

<?php
/**
 * Featured programs grid.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

$csf_programs = array(
	array( __( 'Youth Riders', 'csf-core' ), esc_html__( 'Safe, structured lessons that build horsemanship and confidence in and out of the saddle.', 'csf-core' ), 'lessons/youth-riders' ),
	array( __( 'Adult Amateurs', 'csf-core' ), esc_html__( 'Goal-focused instruction for adults starting out, returning, or refining their riding.', 'csf-core' ), 'lessons/adult-amateurs' ),
	array( __( 'Dressage Foundations', 'csf-core' ), esc_html__( 'The correct basics that make every horse and every discipline better.', 'csf-core' ), 'lessons/dressage-foundations' ),
	array( __( 'Off-Site Lessons', 'csf-core' ), esc_html__( 'Coaching at your barn, scheduled by area on our off-site day.', 'csf-core' ), 'lessons/off-site-lessons' ),
	array( __( 'After-School Program', 'csf-core' ), esc_html__( 'Horsemanship and riding for young people after the school day.', 'csf-core' ), 'lessons/after-school-program' ),
	array( __( 'FEI Young Horse Development', 'csf-core' ), esc_html__( 'Thoughtful, progressive development for young horses with a future.', 'csf-core' ), 'horse-training/fei-young-horse-development' ),
);

$csf_cards = array_map(
	static fn( $p ) => $csf::card( $csf::h( $p[0], 3 ) . $csf::p( $p[1] ) . $csf::p( '<a href="' . $csf::url( $p[2] ) . '">' . esc_html__( 'Learn more', 'csf-core' ) . '<span class="screen-reader-text"> ' . esc_html( $p[0] ) . '</span></a>', 'csf-card-link' ) ),
	$csf_programs
);

return array(
	'title'       => __( 'Featured programs', 'csf-core' ),
	'description' => __( 'Six program cards in two rows.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::intro(
			__( 'Programs', 'csf-core' ),
			__( 'Riding and training for every stage', 'csf-core' ),
			esc_html__( 'Every program starts with safety, correct basics, and a plan that fits the rider — or the horse.', 'csf-core' )
		)
		. $csf::columns( array_slice( $csf_cards, 0, 3 ), '', 'csf-card-row' )
		. $csf::columns( array_slice( $csf_cards, 3, 3 ), '', 'csf-card-row' ),
		'linen'
	),
);

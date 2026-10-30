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
	__( 'After-School Program', 'csf-core' ),
	esc_html__( 'Time with horses after the school day: horsemanship, riding, and responsibility in a supportive group.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::placeholder( 'after-school' ),
			$csf::h( __( 'About the program', 'csf-core' ) )
			. $csf::p( esc_html__( 'Our after-school program brings young people to the barn to learn horse care, safe handling, and riding skills alongside friends, with the same emphasis on correct foundations and confidence as all of our lessons.', 'csf-core' ) )
			. $csf::note( esc_html__( 'program days and times, season and start date, ages, group size, what is included (riding, ground lessons, horse care), pricing, and how families enroll. This page should not be published until these are confirmed.', 'csf-core' ) )
			. $csf::buttons( array( array( __( 'Register interest', 'csf-core' ), $csf::url( 'contact' ), 'csf-primary' ) ) ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

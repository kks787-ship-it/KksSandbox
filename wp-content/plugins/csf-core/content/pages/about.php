<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'About Corner Stone Farm', 'csf-core' ),
	__( 'Our philosophy', 'csf-core' ),
	esc_html__( 'A boutique equestrian training, education, mentorship, and young horse development facility in Omak, Washington — professional standards in an approachable, family-centered barn.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'Who we are', 'csf-core' ) )
			. $csf::p( esc_html__( 'Corner Stone Farm is led by Beth Siemon, head trainer, and Kris Siemon, who leads operations and the business. Together they have built a program that values long-term relationships with riders, families, and horses over volume.', 'csf-core' ) )
			. $csf::note( esc_html__( 'the farm’s story: when and why Corner Stone Farm began, and anything the owners would like visitors to know about the family.', 'csf-core' ) ),
			$csf::placeholder( 'philosophy' ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'philosophy' )
. $csf::section(
	$csf::intro(
		__( 'Sustainable by design', 'csf-core' ),
		__( 'Why we keep things small', 'csf-core' ),
		esc_html__( 'Great horsemanship needs time and attention. We protect both with a few deliberate boundaries.', 'csf-core' )
	)
	. $csf::columns(
		array(
			$csf::card( $csf::h( __( 'A limited training string', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'No more than five horses in full training at once, so each one gets consistent work.', 'csf-core' ) ) ),
			$csf::card( $csf::h( __( 'Structured scheduling', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Clear booking, clear onboarding, and centralized communication — so lesson time is spent riding.', 'csf-core' ) ) ),
			$csf::card( $csf::h( __( 'Time to recover', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'The farm is closed to clients on Sundays. Rested horses and people do better work.', 'csf-core' ) ) ),
		),
		'',
		'csf-card-row'
	)
	. $csf::note( esc_html__( 'publishing the five-horse limit and Sunday closure.', 'csf-core' ) ),
	'linen'
)
. $csf::section(
	$csf::intro( '', __( 'Get to know us', 'csf-core' ) )
	. $csf::columns(
		array(
			$csf::link_card( '', __( 'Meet Beth', 'csf-core' ), esc_html__( 'Head trainer and rider educator.', 'csf-core' ), 'about/meet-beth', __( 'Meet Beth', 'csf-core' ) ),
			$csf::link_card( '', __( 'Meet the Team', 'csf-core' ), esc_html__( 'The people behind the program.', 'csf-core' ), 'about/meet-the-team', __( 'Meet the team', 'csf-core' ) ),
			$csf::link_card( '', __( 'Meet the Horses', 'csf-core' ), esc_html__( 'The horses who teach our riders.', 'csf-core' ), 'about/meet-the-horses', __( 'Meet the horses', 'csf-core' ) ),
			$csf::link_card( '', __( 'Facilities', 'csf-core' ), esc_html__( 'Where lessons and training happen.', 'csf-core' ), 'about/facilities', __( 'See the facilities', 'csf-core' ) ),
		),
		'',
		'csf-card-row'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

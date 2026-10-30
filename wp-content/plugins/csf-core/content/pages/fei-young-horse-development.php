<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Horse Training', 'csf-core' ),
	__( 'FEI Young Horse Development', 'csf-core' ),
	esc_html__( 'Patient, progressive development for young horses with a future — building the strength, balance, and trust that advanced work depends on.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'Our approach', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Correct foundations over shortcuts', 'csf-core' ),
					esc_html__( 'Training paced to the individual horse’s physical and mental maturity', 'csf-core' ),
					esc_html__( 'Classical dressage principles applied from the first rides', 'csf-core' ),
					esc_html__( 'Regular, honest progress updates for owners', 'csf-core' ),
				)
			)
			. $csf::p( esc_html__( 'Beth brings experience with FEI young horse development and advanced dressage education to every horse in the program.', 'csf-core' ) )
			. $csf::note( esc_html__( 'age range accepted, typical program stages, and how owners receive updates (video, lessons, written notes).', 'csf-core' ) ),
			$csf::placeholder( 'young-horse' ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

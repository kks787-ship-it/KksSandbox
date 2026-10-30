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
	__( 'Dressage Foundations', 'csf-core' ),
	esc_html__( 'Rhythm, relaxation, connection, and balance: the correct basics that make every horse more comfortable and every rider more effective — whatever your discipline.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::placeholder( 'dressage' ),
			$csf::h( __( 'Who it is for', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Riders new to dressage who want a correct start', 'csf-core' ),
					esc_html__( 'Riders of any discipline building a better-balanced horse', 'csf-core' ),
					esc_html__( 'Adult amateurs and youth riders aiming to show', 'csf-core' ),
					esc_html__( 'Owners of young horses laying down good habits', 'csf-core' ),
				)
			)
			. $csf::p( esc_html__( 'Instruction is led by Beth Siemon, a USDF Bronze and Silver Medalist with experience developing young horses toward FEI-level work.', 'csf-core' ) )
			. $csf::note( esc_html__( 'credential wording.', 'csf-core' ) ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

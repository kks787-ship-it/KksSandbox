<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'About', 'csf-core' ),
	__( 'Meet the Team', 'csf-core' ),
	esc_html__( 'A small, family-run team focused on doing things well.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::card( $csf::placeholder( 'beth-portrait' ) . $csf::h( __( 'Beth Siemon', 'csf-core' ), 3 ) . $csf::eyebrow( __( 'Head trainer', 'csf-core' ) ) . $csf::p( esc_html__( 'Leads horse training, rider education, clinics, and mentorship.', 'csf-core' ) ) . $csf::p( '<a href="' . $csf::url( 'about/meet-beth' ) . '">' . esc_html__( 'Read Beth’s story', 'csf-core' ) . '</a>', 'csf-card-link' ) ),
			$csf::card( $csf::placeholder( 'kris-portrait' ) . $csf::h( __( 'Kris Siemon', 'csf-core' ), 3 ) . $csf::eyebrow( __( 'Operations & business', 'csf-core' ) ) . $csf::p( esc_html__( 'Leads scheduling, client communication, facilities, and the business side of the farm — so Beth’s time stays focused on horses and riders.', 'csf-core' ) ) ),
		),
		'',
		'csf-card-row'
	)
	. $csf::note( esc_html__( 'Kris’s preferred title and bio, approved portraits, and any other staff, instructors, or working students to feature (with their permission).', 'csf-core' ) ),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

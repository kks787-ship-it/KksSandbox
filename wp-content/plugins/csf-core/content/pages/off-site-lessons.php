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
	__( 'Off-Site Lessons', 'csf-core' ),
	esc_html__( 'Coaching at your barn, with appointments grouped by area so travel time stays reasonable and lesson quality stays high.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'How off-site lessons work', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Wednesday is our primary off-site lesson day.', 'csf-core' ),
					esc_html__( 'Lessons are scheduled in geographic groups, so nearby riders share a travel day.', 'csf-core' ),
					esc_html__( 'Prefer to come to us? Haul-in lessons are available at Corner Stone Farm.', 'csf-core' ),
				)
			)
			. $csf::p( esc_html__( 'Off-site lessons are arranged directly with us rather than booked online. Tell us where you are located and what you would like to work on.', 'csf-core' ) )
			. $csf::note( esc_html__( 'the off-site service area, any travel fee, and the minimum number of riders per location.', 'csf-core' ) )
			. $csf::buttons( array( array( __( 'Ask about off-site lessons', 'csf-core' ), $csf::url( 'contact' ), 'csf-primary' ) ) ),
			$csf::placeholder( 'offsite' ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

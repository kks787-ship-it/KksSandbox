<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Camps & Clinics', 'csf-core' ),
	__( 'Camps & Clinics', 'csf-core' ),
	esc_html__( 'Focused time with horses: camps for young riders, clinics for horsemanship and confidence, and experiences designed for adult amateurs.', 'csf-core' )
)
. $csf::section(
	$csf::dynamic(
		'csf/events',
		array(
			'heading'      => __( 'Upcoming dates', 'csf-core' ),
			'count'        => 9,
			'emptyMessage' => __( 'New camp and clinic dates will be posted here. Contact us to hear about them first.', 'csf-core' ),
		)
	),
	'white'
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'What we offer', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Summer camps and mini camps', 'csf-core' ),
					esc_html__( 'Horsemanship clinics', 'csf-core' ),
					esc_html__( 'Confidence clinics', 'csf-core' ),
					esc_html__( 'Dressage foundations clinics', 'csf-core' ),
					esc_html__( 'Adult amateur experiences', 'csf-core' ),
					esc_html__( 'School-break programs', 'csf-core' ),
					esc_html__( '4-H and Pony Club support', 'csf-core' ),
				)
			)
			. $csf::note( esc_html__( 'how camp registration works (online, form, or phone), ages, and the camp health and safety information parents should know.', 'csf-core' ) ),
			$csf::placeholder( 'camps' ),
		),
		'center'
	),
	'linen'
)
. CSF_Core_Patterns::section( 'cta-band' );

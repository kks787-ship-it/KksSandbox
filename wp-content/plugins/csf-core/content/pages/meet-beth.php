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
	__( 'Meet Beth Siemon', 'csf-core' ),
	esc_html__( 'Head trainer and equestrian visionary at Corner Stone Farm.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			array( $csf::placeholder( 'beth-portrait' ), '40%' ),
			array(
				$csf::h( __( 'Training, teaching, and mentorship', 'csf-core' ) )
				. $csf::p( esc_html__( 'Beth leads horse training, rider education, clinics, mentorship, and the farm’s development strategy. Her work combines advanced dressage education with extensive experience instructing riders and developing horses, including young horses on an FEI path.', 'csf-core' ) )
				. $csf::p( esc_html__( 'Her teaching is built on correct foundations and emotional safety: riders understand why they are doing something, horses are given time to learn, and confidence grows from real skill.', 'csf-core' ) )
				. $csf::h( __( 'Credentials', 'csf-core' ), 3 )
				. $csf::ul(
					array(
						esc_html__( 'USDF Bronze Medal', 'csf-core' ),
						esc_html__( 'USDF Silver Medal', 'csf-core' ),
						esc_html__( 'Pony Club B rating', 'csf-core' ),
						esc_html__( 'Experience with FEI young horse development', 'csf-core' ),
						esc_html__( 'Advanced dressage education and training', 'csf-core' ),
					)
				)
				. $csf::note( esc_html__( 'Beth’s biography in her own words, exact credential wording, years riding and teaching, notable horses or achievements she wishes to share, and an approved portrait.', 'csf-core' ) ),
				'60%',
			),
		),
		'top'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

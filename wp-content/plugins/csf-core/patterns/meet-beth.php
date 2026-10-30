<?php
/**
 * Meet Beth teaser.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Meet Beth', 'csf-core' ),
	'description' => __( 'Portrait and introduction to head trainer Beth Siemon.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::columns(
			array(
				array( $csf::placeholder( 'beth-portrait' ), '40%' ),
				array(
					$csf::eyebrow( __( 'Meet your trainer', 'csf-core' ) )
					. $csf::h( __( 'Beth Siemon', 'csf-core' ) )
					. $csf::lead( esc_html__( 'Head trainer and equestrian visionary at Corner Stone Farm.', 'csf-core' ) )
					. $csf::p( esc_html__( 'Beth leads horse training, rider education, clinics, and mentorship. Her teaching pairs advanced dressage education with a patient, confidence-first approach that meets each horse and rider where they are.', 'csf-core' ) )
					. $csf::ul(
						array(
							esc_html__( 'USDF Bronze Medal', 'csf-core' ),
							esc_html__( 'USDF Silver Medal', 'csf-core' ),
							esc_html__( 'Pony Club B rating', 'csf-core' ),
							esc_html__( 'Experience developing young horses toward FEI-level work', 'csf-core' ),
						)
					)
					. $csf::note( esc_html__( 'exact credential wording, any additional qualifications, and Beth’s preferred introduction.', 'csf-core' ) )
					. $csf::buttons( array( array( __( 'Meet Beth', 'csf-core' ), $csf::url( 'about/meet-beth' ), 'csf-outline' ) ) ),
					'60%',
				),
			),
			'center'
		),
		'white'
	),
);

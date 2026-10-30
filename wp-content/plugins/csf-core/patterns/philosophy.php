<?php
/**
 * Philosophy band.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Our philosophy', 'csf-core' ),
	'description' => __( 'Dark band with the five Corner Stone Farm principles and the brand tagline.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::columns(
			array(
				$csf::eyebrow( __( 'Our philosophy', 'csf-core' ) )
				. $csf::h( __( 'Correct foundations, built with care', 'csf-core' ) )
				. $csf::p( esc_html__( 'Corner Stone Farm is a boutique equestrian training, education, and mentorship facility. We hold ourselves to professional standards while keeping the barn welcoming, family-centered, and emotionally safe for horses and people alike.', 'csf-core' ) )
				. $csf::buttons( array( array( __( 'Read our philosophy', 'csf-core' ), $csf::url( 'about' ), 'csf-outline' ) ) ),
				$csf::ul(
					array(
						'<strong>' . esc_html__( 'Quality', 'csf-core' ) . '</strong> ' . esc_html__( 'over quantity', 'csf-core' ),
						'<strong>' . esc_html__( 'Community', 'csf-core' ) . '</strong> ' . esc_html__( 'over ego', 'csf-core' ),
						'<strong>' . esc_html__( 'Correct foundations', 'csf-core' ) . '</strong> ' . esc_html__( 'over shortcuts', 'csf-core' ),
						'<strong>' . esc_html__( 'Mentorship', 'csf-core' ) . '</strong> ' . esc_html__( 'over transactional lessons', 'csf-core' ),
						'<strong>' . esc_html__( 'Sustainability', 'csf-core' ) . '</strong> ' . esc_html__( 'over burnout', 'csf-core' ),
					)
				),
			),
			'center'
		)
		. $csf::ornament()
		. $csf::p( esc_html__( 'Perfection Found Through Play and Practice', 'csf-core' ), 'csf-tagline' ),
		'dark'
	),
);

<?php
/**
 * Mentorship & community.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Mentorship & community', 'csf-core' ),
	'description' => __( 'Community, mentorship, working students and leasing.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::columns(
			array(
				$csf::eyebrow( __( 'Mentorship & community', 'csf-core' ) )
				. $csf::h( __( 'More than a lesson', 'csf-core' ) )
				. $csf::p( esc_html__( 'Riders here become part of a barn community. Youth riders, adult amateurs, and families learn alongside one another, and our mentorship programs give dedicated students room to grow in skill, responsibility, and leadership.', 'csf-core' ) )
				. $csf::ul(
					array(
						esc_html__( 'Youth mentorship and leadership opportunities', 'csf-core' ),
						esc_html__( 'Working student development', 'csf-core' ),
						esc_html__( 'Half leases and on-site leases', 'csf-core' ),
						esc_html__( '4-H and Pony Club support', 'csf-core' ),
					)
				)
				. $csf::buttons( array( array( __( 'Leasing & mentorship', 'csf-core' ), $csf::url( 'horse-sales/leasing-mentorship' ), 'csf-outline' ) ) ),
				$csf::placeholder( 'mentorship' ),
			),
			'center'
		),
		'tan'
	),
);

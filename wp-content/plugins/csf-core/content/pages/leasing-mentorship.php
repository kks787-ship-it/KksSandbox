<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Horse Sales', 'csf-core' ),
	__( 'Leasing & Mentorship', 'csf-core' ),
	esc_html__( 'Ways to spend more time with horses and grow as a horseperson: leases, youth mentorship, and working student opportunities.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::card( $csf::h( __( 'Leases', 'csf-core' ), 3 ) . $csf::ul( array( esc_html__( 'Half leases', 'csf-core' ), esc_html__( 'On-site leases', 'csf-core' ) ) ) . $csf::p( esc_html__( 'A lease gives a rider regular time with one horse, with guidance and lessons to support the partnership.', 'csf-core' ) ) ),
			$csf::card( $csf::h( __( 'Youth mentorship', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Dedicated young riders take on more responsibility at the barn and learn leadership alongside horsemanship.', 'csf-core' ) ) ),
			$csf::card( $csf::h( __( 'Working students', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'A structured path for committed students to develop practical skills, work ethic, and professional horsemanship.', 'csf-core' ) ) ),
		),
		'',
		'csf-card-row'
	)
	. $csf::note( esc_html__( 'lease terms and requirements, mentorship and working student eligibility (ages, commitment), and how to apply.', 'csf-core' ) ),
	'white'
)
. $csf::section(
	$csf::dynamic(
		'csf/horses',
		array(
			'listing'      => 'for-lease',
			'heading'      => __( 'Horses available for lease', 'csf-core' ),
			'emptyMessage' => __( 'No lease horses are listed right now. Ask us about upcoming opportunities.', 'csf-core' ),
		)
	),
	'linen'
)
. CSF_Core_Patterns::section( 'cta-band' );

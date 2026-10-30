<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Contact', 'csf-core' ),
	__( 'Get in touch', 'csf-core' ),
	esc_html__( 'Questions about lessons, training, camps, sales, or leasing? We would love to hear from you.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::card(
				$csf::h( __( 'Contact details', 'csf-core' ), 2 )
				. $csf::dynamic( 'csf/farm-contact' )
			),
			$csf::card(
				$csf::h( __( 'Ready to ride?', 'csf-core' ), 2 )
				. $csf::p( esc_html__( 'Lessons can be booked online at any time.', 'csf-core' ) )
				. $csf::book()
				. $csf::p( esc_html__( 'Training, off-site lessons, leases, and camps are arranged directly — get in touch using the details here.', 'csf-core' ) )
			),
		),
		'',
		'csf-card-row'
	),
	'white'
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'When you write to us', 'csf-core' ), 3 )
			. $csf::ul(
				array(
					esc_html__( 'Tell us who is riding (age and experience) or about your horse.', 'csf-core' ),
					esc_html__( 'Let us know which program you are interested in.', 'csf-core' ),
					esc_html__( 'Please do not send payment details, medical records, or other sensitive information by email or message.', 'csf-core' ),
				)
			),
			$csf::h( __( 'Emergencies', 'csf-core' ), 3 )
			. $csf::p( esc_html__( 'Found a horse wearing a Corner Stone Farm tag, or looking for the latest farm emergency status?', 'csf-core' ) )
			. $csf::p( '<a href="' . $csf::url( 'emergency' ) . '">' . esc_html__( 'Farm emergency information', 'csf-core' ) . '</a>', 'csf-card-link' )
			. $csf::p( esc_html__( 'In a life-threatening emergency, call 911.', 'csf-core' ) ),
		)
	)
	. $csf::note( esc_html__( 'the public phone and email (Settings → Farm Details). An online inquiry form is intentionally not enabled yet: it will be added once spam protection, secure delivery, and notifications have been chosen and tested.', 'csf-core' ) ),
	'linen'
);

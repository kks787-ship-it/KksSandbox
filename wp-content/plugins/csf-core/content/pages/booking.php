<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Booking', 'csf-core' ),
	__( 'Book a lesson', 'csf-core' ),
	esc_html__( 'See available times and book your lesson online.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'Online booking', 'csf-core' ) )
			. $csf::p( esc_html__( 'Our scheduling system shows current availability and lesson options. You will receive a confirmation after booking.', 'csf-core' ) )
			. $csf::book( 'primary', __( 'See available times', 'csf-core' ) )
			. $csf::p( esc_html__( 'Booking opens our secure scheduling page (Acuity Scheduling) in this window.', 'csf-core' ) ),
			$csf::h( __( 'Before your first ride', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Complete the required rider agreements and emergency contacts.', 'csf-core' ),
					esc_html__( 'Parents or guardians complete paperwork for riders under 18.', 'csf-core' ),
					esc_html__( 'Arrive a few minutes early so we can start on time.', 'csf-core' ),
				)
			)
			. $csf::note( esc_html__( 'the pre-ride checklist above, and how riders receive agreements to sign.', 'csf-core' ) ),
		)
	),
	'white'
)
. $csf::section(
	$csf::h( __( 'Not booked online', 'csf-core' ), 3 )
	. $csf::p( esc_html__( 'Horse training, off-site lessons, leases, mentorship, and camp inquiries are arranged directly.', 'csf-core' ) )
	. $csf::buttons( array( array( __( 'Contact us', 'csf-core' ), $csf::url( 'contact' ), 'csf-outline' ) ) ),
	'linen'
);

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
	__( 'Contact Beth to Schedule', 'csf-core' ),
	esc_html__( 'Contact Beth to request a lesson. Beth or Kris confirms lesson dates directly.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'Arrange your lesson', 'csf-core' ) )
			. $csf::p( esc_html__( 'Contacting the farm is a request, not a confirmed appointment. Beth or Kris will agree on a lesson date with you directly.', 'csf-core' ) )
			. $csf::book( 'primary', __( 'Contact Beth to Schedule', 'csf-core' ) )
			. $csf::p( esc_html__( 'Use the approved call or message options on our Contact page.', 'csf-core' ) ),
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
	$csf::h( __( 'Other services', 'csf-core' ), 3 )
	. $csf::p( esc_html__( 'Horse training, off-site lessons, leases, mentorship, and camp inquiries are arranged directly.', 'csf-core' ) )
	. $csf::buttons( array( array( __( 'Contact us', 'csf-core' ), $csf::url( 'contact' ), 'csf-outline' ) ) ),
	'linen'
);

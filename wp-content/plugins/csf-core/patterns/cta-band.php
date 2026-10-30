<?php
/**
 * Closing booking / contact call to action.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Call to action: book or contact', 'csf-core' ),
	'description' => __( 'Heritage Brown band with booking button and contact link.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::group(
			$csf::eyebrow( __( 'Begin', 'csf-core' ) )
			. $csf::h( __( 'Ready to ride with us?', 'csf-core' ) )
			. $csf::lead( esc_html__( 'Book a lesson online, or get in touch to talk about training, camps, or leasing.', 'csf-core' ) )
			. $csf::book_row( __( 'Contact us', 'csf-core' ), $csf::url( 'contact' ), 'light', true ),
			array( 'className' => 'csf-section-intro' )
		),
		'brown'
	),
);

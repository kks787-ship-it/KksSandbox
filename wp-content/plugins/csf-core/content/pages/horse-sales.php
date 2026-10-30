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
	__( 'Horses for sale', 'csf-core' ),
	esc_html__( 'Honest descriptions, correct foundations, and careful matching. We want every horse to go to a partnership where it will thrive.', 'csf-core' )
)
. $csf::section(
	$csf::dynamic(
		'csf/horses',
		array(
			'listing'      => 'for-sale',
			'emptyMessage' => __( 'No horses are listed for sale right now. Contact us if you are looking for a particular type of horse.', 'csf-core' ),
		)
	),
	'white'
)
. $csf::section(
	$csf::columns(
		array(
			$csf::placeholder( 'horse-sales' ),
			$csf::h( __( 'How we sell horses', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'Clear photographs and video', 'csf-core' ),
					esc_html__( 'Training history and suitability described honestly', 'csf-core' ),
					esc_html__( 'Time to try the horse and ask questions', 'csf-core' ),
				)
			)
			. $csf::p( esc_html__( 'Selling a horse? Sale horse preparation is part of our training program.', 'csf-core' ) )
			. $csf::buttons( array( array( __( 'Sale horse preparation', 'csf-core' ), $csf::url( 'horse-training' ), 'csf-outline' ), array( __( 'Leasing & mentorship', 'csf-core' ), $csf::url( 'horse-sales/leasing-mentorship' ), 'csf-outline' ) ) )
			. $csf::note( esc_html__( 'trial and pre-purchase exam policy, and whether asking prices will be published.', 'csf-core' ) ),
		),
		'center'
	),
	'linen'
)
. CSF_Core_Patterns::section( 'cta-band' );

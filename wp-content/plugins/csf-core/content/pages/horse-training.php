<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return $csf::page_header(
	__( 'Horse Training', 'csf-core' ),
	__( 'Boutique horse training', 'csf-core' ),
	esc_html__( 'A small, selective training program. We keep numbers deliberately low so every horse receives consistent, thoughtful work and every owner receives real attention.', 'csf-core' )
)
. $csf::section(
	$csf::columns(
		array(
			$csf::h( __( 'Training services', 'csf-core' ) )
			. $csf::ul(
				array(
					esc_html__( 'FEI-oriented young horse development', 'csf-core' ),
					esc_html__( 'Dressage foundations', 'csf-core' ),
					esc_html__( 'Restart and problem-solving programs', 'csf-core' ),
					esc_html__( 'Sale horse preparation', 'csf-core' ),
					esc_html__( 'Select training and development partnerships', 'csf-core' ),
				)
			)
			. $csf::p( esc_html__( 'We accept a maximum of five horses in full training at one time. Selective intake protects the quality of every horse’s program.', 'csf-core' ) )
			. $csf::note( esc_html__( 'publishing the five-horse limit, training program lengths, pricing, and intake requirements (e.g. vaccinations, Coggins, farrier).', 'csf-core' ) ),
			$csf::placeholder( 'training' ),
		),
		'center'
	),
	'white'
)
. $csf::section(
	$csf::intro( __( 'Intake', 'csf-core' ), __( 'How training begins', 'csf-core' ) )
	. $csf::columns(
		array(
			$csf::card( $csf::eyebrow( __( 'Step 1', 'csf-core' ) ) . $csf::h( __( 'Inquiry', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Tell us about your horse, its history, and your goals.', 'csf-core' ) ) ),
			$csf::card( $csf::eyebrow( __( 'Step 2', 'csf-core' ) ) . $csf::h( __( 'Evaluation', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'Beth assesses whether the program is the right fit for the horse.', 'csf-core' ) ) ),
			$csf::card( $csf::eyebrow( __( 'Step 3', 'csf-core' ) ) . $csf::h( __( 'Agreement & plan', 'csf-core' ), 3 ) . $csf::p( esc_html__( 'The training agreement is completed before the horse arrives, and we agree on a development plan.', 'csf-core' ) ) ),
		),
		'',
		'csf-card-row'
	)
	. $csf::note( esc_html__( 'the intake steps above and whether evaluations happen on-site or by video.', 'csf-core' ) )
	. $csf::group( $csf::buttons( array( array( __( 'Inquire about training', 'csf-core' ), $csf::url( 'contact' ), 'csf-primary' ) ), 'center' ), array( 'className' => 'csf-section-intro' ) ),
	'linen'
)
. $csf::section(
	$csf::columns(
		array(
			$csf::placeholder( 'young-horse' ),
			$csf::eyebrow( __( 'Young horses', 'csf-core' ) )
			. $csf::h( __( 'FEI young horse development', 'csf-core' ) )
			. $csf::p( esc_html__( 'Correct foundations over shortcuts. Young horses are developed progressively, with their long-term soundness and confidence ahead of short-term results.', 'csf-core' ) )
			. $csf::buttons( array( array( __( 'Young horse development', 'csf-core' ), $csf::url( 'horse-training/fei-young-horse-development' ), 'csf-outline' ) ) ),
		),
		'center'
	),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

<?php
/**
 * Page layout.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

$csf_q = static fn( $question, $answer ) => $csf::details( $question, $csf::p( $answer ) );

return $csf::page_header(
	__( 'Resources', 'csf-core' ),
	__( 'Frequently asked questions', 'csf-core' ),
	esc_html__( 'Quick answers about lessons, training, and visiting the farm.', 'csf-core' )
)
. $csf::section(
	$csf::h( __( 'Lessons', 'csf-core' ) )
	. $csf_q( __( 'Do you teach English or Western?', 'csf-core' ), esc_html__( 'Both. We teach English and Western riding, along with dressage foundations.', 'csf-core' ) )
	. $csf_q( __( 'I have never ridden before. Can I take lessons?', 'csf-core' ), esc_html__( 'Yes. We teach riders from complete beginners through advanced, with a strong focus on safety and confidence.', 'csf-core' ) )
	. $csf_q( __( 'Do you teach adults?', 'csf-core' ), esc_html__( 'Absolutely. Adult amateurs — new, returning, or experienced — are a core part of our program.', 'csf-core' ) )
	. $csf_q( __( 'Can I bring my own horse?', 'csf-core' ), esc_html__( 'Yes. Haul-in lessons are available, and off-site lessons can be arranged at your barn.', 'csf-core' ) )
	. $csf_q( __( 'How do I book a lesson?', 'csf-core' ), esc_html__( 'Use the Book a Lesson button anywhere on this site to see available times and book online.', 'csf-core' ) )
	. $csf_q( __( 'What paperwork is needed before the first ride?', 'csf-core' ), esc_html__( 'Required agreements and emergency contact details must be completed before any rider’s first lesson.', 'csf-core' ) )
	. $csf::h( __( 'Training', 'csf-core' ) )
	. $csf_q( __( 'How many horses do you take in training?', 'csf-core' ), esc_html__( 'We keep our training program small, with a maximum of five horses in full training at one time.', 'csf-core' ) )
	. $csf_q( __( 'What kinds of horses do you train?', 'csf-core' ), esc_html__( 'Young horses with FEI-oriented goals, horses needing dressage foundations, restarts and problem-solving, and sale preparation.', 'csf-core' ) )
	. $csf::h( __( 'Visiting', 'csf-core' ) )
	. $csf_q( __( 'Are you open on Sundays?', 'csf-core' ), esc_html__( 'The farm is closed to clients on Sundays.', 'csf-core' ) )
	. $csf_q( __( 'Where are you located?', 'csf-core' ), esc_html__( 'Corner Stone Farm is in Omak, Washington.', 'csf-core' ) )
	. $csf::note( esc_html__( 'every answer above, plus answers to: What should I wear? Is a helmet required/provided? What is the minimum age? What is the cancellation policy? What do lessons cost? Are directions provided at booking?', 'csf-core' ) ),
	'white'
)
. CSF_Core_Patterns::section( 'cta-band' );

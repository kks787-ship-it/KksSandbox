<?php
/**
 * Image + text split.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Image and text', 'csf-core' ),
	'description' => __( 'Photo beside a heading, text and button.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::columns(
			array(
				$csf::placeholder( 'philosophy' ),
				$csf::h( __( 'Heading', 'csf-core' ) ) . $csf::p( esc_html__( 'Write a few sentences here.', 'csf-core' ) ) . $csf::buttons( array( array( __( 'Learn more', 'csf-core' ), $csf::url( 'contact' ), 'csf-outline' ) ) ),
			),
			'center'
		),
		'white'
	),
);

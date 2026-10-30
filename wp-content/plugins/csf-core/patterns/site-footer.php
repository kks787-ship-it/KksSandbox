<?php
/**
 * Site footer (inserted into the Kadence footer as a synced pattern by the site setup).
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

$csf_links = static function ( array $items ) use ( $csf ) {
	return array_map( static fn( $i ) => '<a href="' . $csf::url( $i[1] ) . '">' . esc_html( $i[0] ) . '</a>', $items );
};

return array(
	'title'       => __( 'Site footer', 'csf-core' ),
	'description' => __( 'Brand, navigation, contact details, booking, emergency link and copyright.', 'csf-core' ),
	'content'     => $csf::section(
		$csf::columns(
			array(
				$csf::p( esc_html__( 'Corner Stone Farm', 'csf-core' ), 'csf-footer__brand' )
				. $csf::p( esc_html__( 'Perfection Found Through Play and Practice', 'csf-core' ), 'csf-tagline csf-tagline--left' )
				. $csf::p( esc_html__( 'Boutique equestrian training, education, and mentorship in Omak, Washington.', 'csf-core' ) ),
				$csf::h( __( 'Explore', 'csf-core' ), 2 )
				. $csf::ul(
					$csf_links(
						array(
							array( __( 'Lessons', 'csf-core' ), 'lessons' ),
							array( __( 'Horse Training', 'csf-core' ), 'horse-training' ),
							array( __( 'Camps & Clinics', 'csf-core' ), 'camps-clinics' ),
							array( __( 'Horse Sales', 'csf-core' ), 'horse-sales' ),
							array( __( 'Resources', 'csf-core' ), 'resources' ),
							array( __( 'About', 'csf-core' ), 'about' ),
						)
					),
					''
				),
				$csf::h( __( 'Contact', 'csf-core' ), 2 )
				. $csf::dynamic( 'csf/farm-contact', array( 'variant' => 'compact' ) )
				. $csf::book( 'light' ),
			)
		)
		. $csf::group(
			$csf::columns(
				array(
					$csf::dynamic( 'csf/copyright' ),
					$csf::p( implode( ' · ', $csf_links( array( array( __( 'Farm emergency information', 'csf-core' ), 'emergency' ), array( __( 'FAQ', 'csf-core' ), 'resources/faq' ), array( __( 'Contact', 'csf-core' ), 'contact' ) ) ) ), 'csf-footer__links' ),
				),
				'center'
			),
			array( 'className' => 'csf-footer__bottom' )
		),
		'dark',
		'csf-footer'
	),
);

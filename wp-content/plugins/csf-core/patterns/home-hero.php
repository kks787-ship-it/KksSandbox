<?php
/**
 * Home hero.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Home hero: Where Confidence Grows', 'csf-core' ),
	'description' => __( 'Full-width photo hero with the primary headline and booking button.', 'csf-core' ),
	'keywords'    => array( 'hero', 'home', 'banner' ),
	'content'     => $csf::hero(
		$csf::placeholder_url( 'hero-home' ),
		$csf::placeholder_alt( 'hero-home' ),
		$csf::eyebrow( __( 'Corner Stone Farm · Omak, Washington', 'csf-core' ) )
		. $csf::h( __( 'Where Confidence Grows.', 'csf-core' ), 1 )
		. $csf::lead( esc_html__( 'Building confident riders, developing exceptional horses, and creating a welcoming equestrian community through mentorship and correct horsemanship.', 'csf-core' ) )
		. $csf::book_row( __( 'Explore our programs', 'csf-core' ), $csf::url( 'lessons' ), 'light' )
	),
);

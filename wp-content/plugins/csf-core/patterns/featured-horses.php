<?php
/**
 * Featured sale horses (hidden automatically when none are listed).
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Featured horses for sale', 'csf-core' ),
	'description' => __( 'Shows up to three available sale horses; the section disappears when none are listed.', 'csf-core' ),
	'content'     => $csf::dynamic(
		'csf/horses',
		array(
			'listing'       => 'for-sale',
			'count'         => 3,
			'heading'       => __( 'Featured horses', 'csf-core' ),
			'hideWhenEmpty' => true,
			'sectionStyle'  => 'white',
		)
	),
);

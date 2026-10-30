<?php
/**
 * Latest library articles (hidden when none are published).
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Educational resources teaser', 'csf-core' ),
	'description' => __( 'Latest three Knowledge Library articles; hidden when none are published.', 'csf-core' ),
	'content'     => $csf::dynamic(
		'csf/library',
		array(
			'count'         => 3,
			'heading'       => __( 'From the Knowledge Library', 'csf-core' ),
			'hideWhenEmpty' => true,
			'sectionStyle'  => 'white',
		)
	),
);

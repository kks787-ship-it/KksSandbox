<?php
/**
 * Inner page header.
 *
 * @package CSF_Core
 */

defined( 'ABSPATH' ) || exit;

$csf = 'CSF_Core_Markup';

return array(
	'title'       => __( 'Page header', 'csf-core' ),
	'description' => __( 'Warm Tan band with eyebrow, page title (H1) and introduction. Use once per page, at the top.', 'csf-core' ),
	'content'     => $csf::page_header( __( 'Section name', 'csf-core' ), __( 'Page title', 'csf-core' ), esc_html__( 'A short, welcoming introduction to this page.', 'csf-core' ) ),
);

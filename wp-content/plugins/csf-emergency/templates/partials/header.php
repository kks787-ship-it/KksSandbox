<?php
/**
 * Standalone document header for emergency routes. Deliberately does not call wp_head():
 * no theme, builder, analytics, or optimisation scripts can break or slow these pages.
 *
 * @package CSF_Emergency
 * @var array $csf
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow, noarchive">
<meta name="referrer" content="no-referrer">
<title><?php echo esc_html( $csf['title'] . ' — ' . $csf['settings']['farm_name'] ); ?></title>
<style>
<?php
// Inline so the page renders with a single request.
echo file_get_contents( CSF_EMERGENCY_DIR . 'assets/public.css' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- static bundled CSS.
echo file_get_contents( CSF_EMERGENCY_DIR . 'assets/banner.css' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped,WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- static bundled CSS.
?>
</style>
</head>
<body class="csf-standalone">
<?php
if ( CSF_Emergency_Farm_State::is_active() ) {
	echo CSF_Emergency_Banner::markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in markup().
}
?>
<header class="csf-top">
	<a class="csf-top__name" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $csf['settings']['farm_name'] ); ?></a>
	<span class="csf-top__place"><?php esc_html_e( 'Omak, Washington', 'csf-emergency' ); ?></span>
</header>
<main class="csf-main" id="main">

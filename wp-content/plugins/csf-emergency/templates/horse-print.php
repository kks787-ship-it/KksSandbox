<?php
/**
 * Print-ready emergency profile (/h/{token}/print): one sheet to keep with the horse's
 * papers or post at a temporary stall. Reuses the profile and adds print styling.
 *
 * @package CSF_Emergency
 * @var array $csf
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="csf-print-sheet">
	<p class="csf-print-hint"><button type="button" onclick="window.print()"><?php esc_html_e( 'Print this page', 'csf-emergency' ); ?></button></p>
	<?php require CSF_EMERGENCY_DIR . 'templates/horse-profile.php'; ?>
	<p class="csf-print-url"><?php echo esc_html( home_url( '/h/' . $csf['token'] ) ); ?></p>
</div>

<?php
/**
 * Shown for unknown, unapproved, or retired tags. Identical for all three so the response
 * reveals nothing about which tag codes exist — but a finder still gets the farm's number.
 *
 * @package CSF_Emergency
 * @var array $csf
 */

defined( 'ABSPATH' ) || exit;
?>
<article class="csf-horse">
	<p class="csf-eyebrow"><?php esc_html_e( 'Corner Stone Farm horse tag', 'csf-emergency' ); ?></p>
	<h1><?php esc_html_e( 'Found a horse wearing this tag?', 'csf-emergency' ); ?></h1>
	<p><?php esc_html_e( 'This tag does not have an active online profile. Please contact the farm directly and read them the tag code.', 'csf-emergency' ); ?></p>
	<?php if ( CSF_Emergency_Tokens::is_valid_format( $csf['token'] ) ) : ?>
		<p class="csf-tagcode"><?php esc_html_e( 'Tag code:', 'csf-emergency' ); ?> <strong><?php echo esc_html( $csf['token'] ); ?></strong></p>
	<?php endif; ?>
	<?php require CSF_EMERGENCY_DIR . 'templates/partials/contact-buttons.php'; ?>
</article>

<?php
/**
 * Large mobile contact controls. Rendered only for numbers the farm has entered.
 *
 * @package CSF_Emergency
 * @var array  $csf
 * @var string $csf_horse_name Optional horse name for the found-horse message.
 */

defined( 'ABSPATH' ) || exit;

$csf_s     = $csf['settings'];
$csf_sms   = $csf_s['report_sms'] ? $csf_s['report_sms'] : $csf_s['farm_phone'];
$csf_found = isset( $csf_horse_name )
	/* translators: 1: horse name, 2: tag code */
	? sprintf( __( 'I have found %1$s (tag %2$s). I am at: ', 'csf-emergency' ), $csf_horse_name, $csf['token'] ?? '' )
	: __( 'I have found a Corner Stone Farm horse. I am at: ', 'csf-emergency' );
?>
<div class="csf-actions">
	<?php if ( $csf_s['farm_phone'] ) : ?>
		<a class="csf-btn csf-btn--call" href="tel:<?php echo esc_attr( CSF_Emergency_Settings::phone_uri( $csf_s['farm_phone'] ) ); ?>">
			<span class="csf-btn__label"><?php esc_html_e( 'Call farm', 'csf-emergency' ); ?></span>
			<span class="csf-btn__sub"><?php echo esc_html( $csf_s['farm_phone'] ); ?></span>
		</a>
	<?php endif; ?>
	<?php if ( $csf_sms ) : ?>
		<a class="csf-btn csf-btn--found" href="sms:<?php echo esc_attr( CSF_Emergency_Settings::phone_uri( $csf_sms ) ); ?>?&amp;body=<?php echo rawurlencode( $csf_found ); ?>">
			<span class="csf-btn__label"><?php esc_html_e( 'Report found horse', 'csf-emergency' ); ?></span>
			<span class="csf-btn__sub"><?php esc_html_e( 'Send a text with your location', 'csf-emergency' ); ?></span>
		</a>
	<?php endif; ?>
	<?php if ( $csf_s['secondary_phone'] ) : ?>
		<a class="csf-btn csf-btn--secondary" href="tel:<?php echo esc_attr( CSF_Emergency_Settings::phone_uri( $csf_s['secondary_phone'] ) ); ?>">
			<span class="csf-btn__label"><?php echo esc_html( $csf_s['secondary_label'] ? $csf_s['secondary_label'] : __( 'Alternate contact', 'csf-emergency' ) ); ?></span>
			<span class="csf-btn__sub"><?php echo esc_html( $csf_s['secondary_phone'] ); ?></span>
		</a>
	<?php endif; ?>
	<?php if ( ! $csf_s['farm_phone'] && ! $csf_sms ) : ?>
		<p class="csf-warning"><?php esc_html_e( 'Emergency contact numbers have not been configured yet. Please contact local animal control or the county sheriff.', 'csf-emergency' ); ?></p>
	<?php endif; ?>
</div>

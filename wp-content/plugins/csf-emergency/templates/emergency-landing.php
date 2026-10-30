<?php
/**
 * Emergency landing page (/emergency).
 *
 * @package CSF_Emergency
 * @var array $csf
 */

defined( 'ABSPATH' ) || exit;

$csf_state    = $csf['state'];
$csf_settings = $csf['settings'];
$csf_status   = CSF_Emergency_Statuses::get_farm( $csf_state['status'] );
$csf_summary  = CSF_Emergency_Horses::summary();
$csf_hstat    = CSF_Emergency_Statuses::horse();
?>
<article class="csf-landing">
	<p class="csf-eyebrow"><?php esc_html_e( 'Farm emergency status', 'csf-emergency' ); ?></p>
	<h1><?php echo esc_html( 'normal' === $csf_state['status'] ? __( 'No active emergency', 'csf-emergency' ) : $csf_status['headline'] ); ?></h1>

	<div class="csf-status-card csf-sev-<?php echo esc_attr( $csf_status['severity'] ); ?>">
		<p class="csf-status-card__label"><?php echo esc_html( $csf_status['label'] ); ?></p>
		<?php if ( $csf_state['message'] ) : ?>
			<p class="csf-status-card__message"><?php echo esc_html( $csf_state['message'] ); ?></p>
		<?php endif; ?>
		<?php if ( $csf_state['updated_at'] ) : ?>
			<p class="csf-status-card__time">
				<?php
				/* translators: %s: date and time */
				echo esc_html( sprintf( __( 'Latest farm update: %s', 'csf-emergency' ), CSF_Emergency_Public::format_time( $csf_state['updated_at'] ) ) );
				?>
			</p>
		<?php endif; ?>
		<?php if ( $csf_state['next_update'] ) : ?>
			<p class="csf-status-card__time">
				<?php
				/* translators: %s: free text such as "6:00 PM" */
				echo esc_html( sprintf( __( 'Next update expected: %s', 'csf-emergency' ), $csf_state['next_update'] ) );
				?>
			</p>
		<?php endif; ?>
	</div>

	<section class="csf-section csf-official">
		<h2><?php esc_html_e( 'Official evacuation orders', 'csf-emergency' ); ?></h2>
		<p><?php esc_html_e( 'This page shows information reported by Corner Stone Farm. It is not an official evacuation order. Always follow instructions from public safety personnel.', 'csf-emergency' ); ?></p>
		<?php if ( $csf_settings['official_info_url'] ) : ?>
			<p><a class="csf-link-strong" href="<?php echo esc_url( $csf_settings['official_info_url'] ); ?>" rel="noopener"><?php echo esc_html( $csf_settings['official_info_label'] ? $csf_settings['official_info_label'] : __( 'Official emergency information', 'csf-emergency' ) ); ?></a></p>
		<?php endif; ?>
	</section>

	<?php if ( $csf_state['public_info'] && 'normal' !== $csf_state['status'] ) : ?>
		<section class="csf-section">
			<h2><?php esc_html_e( 'Public information from the farm', 'csf-emergency' ); ?></h2>
			<?php echo wp_kses_post( wpautop( esc_html( $csf_state['public_info'] ) ) ); ?>
		</section>
	<?php endif; ?>

	<?php if ( 'help_requested' === $csf_state['status'] ) : ?>
		<section class="csf-section csf-help">
			<h2><?php esc_html_e( 'Evacuation assistance', 'csf-emergency' ); ?></h2>
			<p class="csf-safety"><strong><?php echo esc_html( CSF_Emergency_Banner::safety_text() ); ?></strong></p>
			<p><?php esc_html_e( 'If you have a trailer or horse-handling experience and can help, call the farm. Online volunteer sign-up is not available yet.', 'csf-emergency' ); ?></p>
		</section>
	<?php endif; ?>

	<section class="csf-section">
		<h2><?php esc_html_e( 'Contact the farm', 'csf-emergency' ); ?></h2>
		<?php require CSF_EMERGENCY_DIR . 'templates/partials/contact-buttons.php'; ?>
	</section>

	<section class="csf-section" id="horses">
		<h2><?php esc_html_e( 'Horse status', 'csf-emergency' ); ?></h2>
		<?php if ( ! $csf_summary['total'] ) : ?>
			<p><?php esc_html_e( 'No horse status has been published.', 'csf-emergency' ); ?></p>
		<?php else : ?>
			<ul class="csf-counts">
				<?php foreach ( $csf_hstat as $csf_key => $csf_label ) : ?>
					<?php if ( ! empty( $csf_summary['counts'][ $csf_key ] ) ) : ?>
						<li><strong><?php echo esc_html( number_format_i18n( $csf_summary['counts'][ $csf_key ] ) ); ?></strong> <?php echo esc_html( $csf_label ); ?></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
			<?php if ( $csf_settings['show_horse_list'] && 'normal' !== $csf_state['status'] ) : ?>
				<ul class="csf-horse-list">
					<?php foreach ( $csf_summary['horses'] as $csf_hid ) : ?>
						<?php $csf_hkey = CSF_Emergency_Horses::get( $csf_hid, 'evac_status' ) ? CSF_Emergency_Horses::get( $csf_hid, 'evac_status' ) : 'at_farm'; ?>
						<li><a href="<?php echo esc_url( CSF_Emergency_Horses::public_url( $csf_hid ) ); ?>"><?php echo esc_html( get_the_title( $csf_hid ) ); ?></a> — <?php echo esc_html( $csf_hstat[ $csf_hkey ] ?? '' ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p class="csf-small"><?php esc_html_e( 'Found a horse with a Corner Stone Farm tag? Scan its QR code or call the farm.', 'csf-emergency' ); ?></p>
		<?php endif; ?>
	</section>
</article>

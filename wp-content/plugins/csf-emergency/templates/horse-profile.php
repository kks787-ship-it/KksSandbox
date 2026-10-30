<?php
/**
 * Public emergency horse profile (/h/{token}).
 *
 * @package CSF_Emergency
 * @var array $csf
 */

defined( 'ABSPATH' ) || exit;

$csf_id         = (int) $csf['post_id'];
$csf_horse_name = get_the_title( $csf_id );
$csf_get        = static function ( $key ) use ( $csf_id ) {
	return CSF_Emergency_Horses::get( $csf_id, $key );
};
$csf_statuses   = CSF_Emergency_Statuses::horse();
$csf_evac       = $csf_get( 'evac_status' ) ? $csf_get( 'evac_status' ) : 'at_farm';
$csf_sexes      = CSF_Emergency_Horses::sex_options();
$csf_photos     = CSF_Emergency_Horses::photo_ids( $csf_id );

$csf_identity = array(
	__( 'Registered name', 'csf-emergency' ) => $csf_get( 'registered_name' ),
	__( 'Breed', 'csf-emergency' )           => $csf_get( 'breed' ),
	__( 'Sex', 'csf-emergency' )             => $csf_get( 'sex' ) ? $csf_sexes[ $csf_get( 'sex' ) ] ?? '' : '',
	__( 'Color', 'csf-emergency' )           => $csf_get( 'color' ),
	__( 'Height', 'csf-emergency' )          => $csf_get( 'height' ),
	__( 'Registration', 'csf-emergency' )    => $csf_get( 'registration' ),
);
if ( $csf_get( 'microchip' ) ) {
	$csf_identity[ __( 'Microchip', 'csf-emergency' ) ] = '1' === $csf_get( 'show_microchip' )
		? $csf_get( 'microchip' )
		: __( 'Yes — scan to verify; the farm can confirm the number', 'csf-emergency' );
}

$csf_care = array(
	'markings' => __( 'Identifying markings', 'csf-emergency' ),
	'handling' => __( 'Safe handling', 'csf-emergency' ),
	'trailer'  => __( 'Trailer loading', 'csf-emergency' ),
	'medical'  => __( 'Emergency medical considerations', 'csf-emergency' ),
	'feeding'  => __( 'Temporary feeding', 'csf-emergency' ),
);
?>
<article class="csf-horse">
	<p class="csf-eyebrow"><?php esc_html_e( 'Emergency horse identification', 'csf-emergency' ); ?></p>
	<h1 class="csf-horse__name"><?php echo esc_html( $csf_horse_name ); ?></h1>

	<p class="csf-horse-status csf-horse-status--<?php echo esc_attr( $csf_evac ); ?>">
		<?php echo esc_html( $csf_statuses[ $csf_evac ] ?? $csf_statuses['unknown'] ); ?>
		<?php if ( $csf_get( 'evac_note' ) ) : ?>
			<span class="csf-horse-status__note"><?php echo esc_html( $csf_get( 'evac_note' ) ); ?></span>
		<?php endif; ?>
	</p>

	<?php if ( $csf_photos ) : ?>
		<div class="csf-photos">
			<?php
			foreach ( $csf_photos as $csf_i => $csf_photo ) {
				echo wp_get_attachment_image(
					$csf_photo,
					'large',
					false,
					array(
						'loading' => 0 === $csf_i ? 'eager' : 'lazy',
						/* translators: %s: horse name */
						'alt'     => sprintf( __( 'Photo of %s', 'csf-emergency' ), $csf_horse_name ),
					)
				);
			}
			?>
		</div>
	<?php endif; ?>

	<?php require CSF_EMERGENCY_DIR . 'templates/partials/contact-buttons.php'; ?>

	<a class="csf-btn csf-btn--info" href="#info"><span class="csf-btn__label"><?php esc_html_e( 'View emergency information', 'csf-emergency' ); ?></span></a>

	<section id="info" class="csf-section">
		<h2><?php esc_html_e( 'Identification', 'csf-emergency' ); ?></h2>
		<dl class="csf-dl">
			<?php foreach ( array_filter( $csf_identity ) as $csf_label => $csf_value ) : ?>
				<dt><?php echo esc_html( $csf_label ); ?></dt>
				<dd><?php echo esc_html( $csf_value ); ?></dd>
			<?php endforeach; ?>
		</dl>
	</section>

	<?php foreach ( $csf_care as $csf_key => $csf_label ) : ?>
		<?php if ( $csf_get( $csf_key ) ) : ?>
			<section class="csf-section csf-section--<?php echo esc_attr( $csf_key ); ?>">
				<h2><?php echo esc_html( $csf_label ); ?></h2>
				<?php echo wp_kses_post( wpautop( esc_html( $csf_get( $csf_key ) ) ) ); ?>
			</section>
		<?php endif; ?>
	<?php endforeach; ?>

	<p class="csf-verified">
		<?php
		$csf_verified = CSF_Emergency_Public::format_date( $csf_get( 'last_verified' ) );
		echo esc_html(
			$csf_verified
				/* translators: %s: date */
				? sprintf( __( 'Information last verified by the farm: %s', 'csf-emergency' ), $csf_verified )
				: __( 'Verification date not recorded — confirm details with the farm.', 'csf-emergency' )
		);
		?>
	</p>
	<p class="csf-tagcode"><?php esc_html_e( 'Tag code:', 'csf-emergency' ); ?> <strong><?php echo esc_html( $csf['token'] ); ?></strong></p>
</article>

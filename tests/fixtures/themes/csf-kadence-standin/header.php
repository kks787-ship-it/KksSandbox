<?php
/**
 * Header.
 *
 * @package CSF_Standin
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">Skip to content</a>
<header class="site-header" id="masthead">
	<div class="site-header__inner">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
		<?php endif; ?>
		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-nav">Menu</button>
		<nav class="primary-nav" id="primary-nav" aria-label="Primary">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>
	</div>
</header>
<script>
document.querySelector('.nav-toggle').addEventListener('click', function () {
	var nav = document.getElementById('primary-nav');
	var open = nav.classList.toggle('is-open');
	this.setAttribute('aria-expanded', open ? 'true' : 'false');
	document.getElementById('masthead').classList.toggle('has-open-nav', open);
});
</script>

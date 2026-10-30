<?php
/**
 * Single, page, and archive template.
 *
 * @package CSF_Standin
 */

get_header();
$csf_layout = is_singular() ? csf_standin_layout() : array(
	'title' => true,
	'full'  => false,
);
?>
<main id="main" class="site-main <?php echo $csf_layout['full'] ? 'site-main--full' : 'site-main--contained'; ?>">
	<?php if ( is_singular() ) : ?>
		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>
			<article <?php post_class(); ?>>
				<?php if ( $csf_layout['title'] ) : ?>
					<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php endif; ?>
				<?php if ( ! $csf_layout['full'] && has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php endif; ?>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<h1 class="entry-title"><?php the_archive_title(); ?></h1>
		<div class="archive-list">
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article <?php post_class(); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination(); ?>
	<?php endif; ?>
</main>
<?php
get_footer();

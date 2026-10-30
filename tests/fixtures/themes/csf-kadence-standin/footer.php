<?php
/**
 * Footer: renders the "Site Footer" synced pattern, as a Kadence footer widget would.
 *
 * @package CSF_Standin
 */

$csf_footer = (int) get_option( 'csf_core_footer_block' );
?>
<footer class="site-footer">
	<?php
	if ( $csf_footer ) {
		echo do_blocks( '<!-- wp:block {"ref":' . $csf_footer . '} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
	?>
</footer>
<?php wp_footer(); ?>
</body>
</html>

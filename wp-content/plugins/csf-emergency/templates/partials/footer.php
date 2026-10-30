<?php
/**
 * Standalone document footer.
 *
 * @package CSF_Emergency
 * @var array $csf
 */

defined( 'ABSPATH' ) || exit;
?>
</main>
<footer class="csf-foot">
	<p><?php esc_html_e( 'Information on this page is reported by Corner Stone Farm. In an emergency, official evacuation orders and instructions from public safety personnel always take precedence.', 'csf-emergency' ); ?></p>
	<p><a href="<?php echo esc_url( home_url( '/emergency' ) ); ?>"><?php esc_html_e( 'Farm emergency status', 'csf-emergency' ); ?></a></p>
</footer>
</body>
</html>

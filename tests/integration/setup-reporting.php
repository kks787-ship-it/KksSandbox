<?php
/**
 * Setup failure-path regressions in a separate temporary local SQLite installation.
 *
 * @package CSF
 */

$csf_test_root = getenv( 'CSF_SETUP_TEST_ROOT' );
if ( ! $csf_test_root || 0 !== strpos( $csf_test_root, '/tmp/csf-setup-regression.' ) || ABSPATH !== $csf_test_root . '/site/' || FQDB !== $csf_test_root . '/test.sqlite' || 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Use run-setup-reporting.sh; these checks must never mutate an existing site.' );
}

$csf_checks = 0;
$csf_check  = static function ( $ok, $name ) use ( &$csf_checks ) {
	if ( ! $ok ) {
		throw new RuntimeException( $name );
	}
	++$csf_checks;
	WP_CLI::log( 'PASS: ' . $name );
};
$csf_clean = static function () {
	foreach ( array( CSF_Core_Setup::MENU_PRIMARY, CSF_Core_Setup::MENU_FOOTER ) as $name ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			wp_delete_nav_menu( $menu->term_id );
		}
	}
	foreach ( array_reverse( CSF_Core_Setup::pages() ) as $page ) {
		$post = get_page_by_path( $page['path'], OBJECT, 'page' );
		if ( $post ) {
			wp_delete_post( $post->ID, true );
		}
	}
	$footer = (int) get_option( 'csf_core_footer_block' );
	if ( $footer ) {
		wp_delete_post( $footer, true );
	}
	delete_option( 'csf_core_footer_block' );
	delete_option( 'csf_core_setup' );
	set_theme_mod( 'nav_menu_locations', array() );
};
$csf_fail = static function ( $target, $message ) use ( $csf_check ) {
	$log = CSF_Core_Setup::run();
	$csf_check( CSF_Core_Setup::has_errors( $log ) && false !== strpos( implode( "\n", $log ), $target ), $message );
	$csf_check( false === get_option( 'csf_core_setup' ), 'failed run does not stamp setup as completed' );
	return $log;
};

$csf_clean();
$csf_reject_page = static fn( $empty, $post ) => 'page' === ( $post['post_type'] ?? '' ) && 'lessons' === ( $post['post_name'] ?? '' ) ? true : $empty;
add_filter( 'wp_insert_post_empty_content', $csf_reject_page, 10, 2 );
$csf_fail( 'Could not create /lessons/', 'page insertion failure is reported' );
remove_filter( 'wp_insert_post_empty_content', $csf_reject_page );
$csf_check( ! get_page_by_path( 'youth-riders', OBJECT, 'page' ) && ! get_page_by_path( 'lessons/youth-riders', OBJECT, 'page' ), 'failed parent does not create orphan child pages' );

$csf_clean();
$csf_reject_footer = static fn( $empty, $post ) => 'wp_block' === ( $post['post_type'] ?? '' ) ? true : $empty;
add_filter( 'wp_insert_post_empty_content', $csf_reject_footer, 10, 2 );
$csf_fail( 'Could not create the footer pattern', 'footer insertion failure is reported' );
remove_filter( 'wp_insert_post_empty_content', $csf_reject_footer );
$csf_check( ! get_option( 'csf_core_footer_block' ), 'failed footer does not store an invalid reference' );

$csf_clean();
$csf_reject_menu = static fn( $term, $taxonomy ) => 'nav_menu' === $taxonomy ? new WP_Error( 'fixture_menu', 'Synthetic menu failure' ) : $term;
add_filter( 'pre_insert_term', $csf_reject_menu, 10, 2 );
$csf_fail( 'Could not create the primary menu', 'menu creation error is reported without casting WP_Error to an ID' );
remove_filter( 'pre_insert_term', $csf_reject_menu );

$csf_clean();
$csf_reject_item = static fn( $empty, $post ) => 'nav_menu_item' === ( $post['post_type'] ?? '' ) ? true : $empty;
add_filter( 'wp_insert_post_empty_content', $csf_reject_item, 10, 2 );
$csf_fail( 'Could not save primary menu item', 'menu item failure is reported' );
remove_filter( 'wp_insert_post_empty_content', $csf_reject_item );

$csf_clean();
$csf_reject_meta = static fn( $check, $id, $key ) => '_csf_setup_hash' === $key ? false : $check;
add_filter( 'add_post_metadata', $csf_reject_meta, 10, 3 );
$csf_fail( 'Could not save _csf_setup_hash', 'metadata write failure is reported' );
remove_filter( 'add_post_metadata', $csf_reject_meta );

$csf_clean();
$csf_reject_option = static fn( $value, $old ) => $old;
add_filter( 'pre_update_option_page_on_front', $csf_reject_option, 10, 2 );
$csf_fail( 'Could not save option page_on_front', 'front-page option failure is reported' );
remove_filter( 'pre_update_option_page_on_front', $csf_reject_option );

$csf_clean();
$csf_reject_locations = static fn( $value, $old ) => $old;
add_filter( 'pre_set_theme_mod_nav_menu_locations', $csf_reject_locations, 10, 2 );
$csf_fail( 'Menu locations could not be saved', 'failed menu location write is reported' );
remove_filter( 'pre_set_theme_mod_nav_menu_locations', $csf_reject_locations );

$csf_clean();
update_option( 'rewrite_rules', array( 'synthetic-invalid-rule' => 'index.php' ) );
add_filter( 'pre_update_option_rewrite_rules', $csf_reject_option, 10, 2 );
$csf_fail( 'Rewrite rules could not be saved', 'failed rewrite write is reported' );
remove_filter( 'pre_update_option_rewrite_rules', $csf_reject_option );

$csf_clean();
$csf_terms = get_terms( array( 'taxonomy' => CSF_Core_Library::CATEGORY, 'hide_empty' => false ) );
foreach ( $csf_terms as $csf_term ) {
	wp_delete_term( $csf_term->term_id, CSF_Core_Library::CATEGORY );
}
$csf_reject_term = static fn( $term, $taxonomy ) => CSF_Core_Library::CATEGORY === $taxonomy ? new WP_Error( 'fixture_term', 'Synthetic taxonomy failure' ) : $term;
add_filter( 'pre_insert_term', $csf_reject_term, 10, 2 );
$csf_fail( 'Taxonomy setup:', 'taxonomy seed failure is reported' );
remove_filter( 'pre_insert_term', $csf_reject_term );

$csf_clean();
$csf_good = CSF_Core_Setup::run();
$csf_check( ! CSF_Core_Setup::has_errors( $csf_good ) && get_option( 'csf_core_setup' ), 'normal setup saves its completed marker' );
$csf_again = CSF_Core_Setup::run();
$csf_check( ! CSF_Core_Setup::has_errors( $csf_again ), 'unchanged options and metadata are not falsely treated as errors' );

$csf_home = CSF_Core_Setup::find_page( 'home' );
$csf_original_content = $csf_home->post_content;
$csf_original_hash = get_post_meta( $csf_home->ID, '_csf_setup_hash', true );
update_post_meta( $csf_home->ID, '_csf_setup_source', 'stale-source-fixture' );
delete_option( 'csf_core_setup' );
$csf_reject_update = static fn( $empty, $post ) => (int) ( $post['ID'] ?? 0 ) === $csf_home->ID ? true : $empty;
add_filter( 'wp_insert_post_empty_content', $csf_reject_update, 10, 2 );
$csf_fail( 'Could not update /home/', 'page update error is reported' );
remove_filter( 'wp_insert_post_empty_content', $csf_reject_update );
$csf_check( get_post_field( 'post_content', $csf_home->ID, 'raw' ) === $csf_original_content && get_post_meta( $csf_home->ID, '_csf_setup_hash', true ) === $csf_original_hash && 'stale-source-fixture' === get_post_meta( $csf_home->ID, '_csf_setup_source', true ), 'failed page update preserves content and source/hash metadata' );

$csf_admin = get_users( array( 'role' => 'administrator', 'number' => 1 ) )[0];
wp_set_current_user( $csf_admin->ID );
set_transient( 'csf_setup_log_' . $csf_admin->ID, array( 'ERROR    Synthetic failure <script>unsafe</script>' ), 60 );
$_GET['ran'] = '1';
ob_start();
CSF_Core_Setup::render();
$csf_html = ob_get_clean();
$csf_check( false !== strpos( $csf_html, 'notice-error' ) && false === strpos( $csf_html, 'notice-success' ) && false !== strpos( $csf_html, '&lt;script&gt;' ), 'admin notice reports incomplete setup and escapes failure details' );
unset( $_GET['ran'] );

// Execute the real CLI command in a fresh process, with a controlled failure hook.
$csf_require = $csf_test_root . '/fail-completion.php';
file_put_contents( $csf_require, '<?php WP_CLI::add_hook("after_wp_load", static function () { update_option("csf_core_setup", array("version"=>-1,"time"=>1)); add_filter("pre_update_option_csf_core_setup", static fn($value,$old)=>$old, 10, 2); });' );
$csf_command = array( 'php', dirname( __DIR__, 2 ) . '/.wp-local/wp-cli.phar', '--path=' . ABSPATH, '--require=' . $csf_require, 'csf', 'setup' );
$csf_process = proc_open( $csf_command, array( array( 'pipe', 'r' ), array( 'pipe', 'w' ), array( 'pipe', 'w' ) ), $csf_pipes );
fclose( $csf_pipes[0] );
$csf_stdout = stream_get_contents( $csf_pipes[1] );
$csf_stderr = stream_get_contents( $csf_pipes[2] );
fclose( $csf_pipes[1] );
fclose( $csf_pipes[2] );
$csf_exit = proc_close( $csf_process );
$csf_check( 0 !== $csf_exit && false === strpos( $csf_stdout, 'Success:' ) && false !== strpos( $csf_stderr, 'Site setup is incomplete' ), 'real WP-CLI returns nonzero and no success on a failed completion write' );

WP_CLI::success( $csf_checks . ' isolated setup reporting checks passed.' );

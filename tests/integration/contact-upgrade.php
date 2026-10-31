<?php
/** Upgrade an actual 0.2.0 Contact template only inside the isolated local fixture. */

$csf_root = getenv( 'CSF_SETUP_TEST_ROOT' );
if ( ! $csf_root || 0 !== strpos( $csf_root, '/tmp/csf-setup-regression.' ) || ABSPATH !== $csf_root . '/site/' || FQDB !== $csf_root . '/test.sqlite' || 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Use run-setup-reporting.sh; never run against an existing site.' );
}
wp_set_current_user( 0 );
delete_option( CSF_Core_Inquiry::OPTION );
$csf_mail_calls = 0;
add_filter( 'pre_wp_mail', static function () use ( &$csf_mail_calls ) { ++$csf_mail_calls; return false; } );
$csf_checks = 0;
$csf_check = static function ( $ok, $name ) use ( &$csf_checks ) {
	if ( ! $ok ) { throw new RuntimeException( $name ); }
	++$csf_checks;
	WP_CLI::log( 'PASS: ' . $name );
};
$csf_baseline = CSF_Core_Setup::run();
$csf_check( ! CSF_Core_Setup::has_errors( $csf_baseline ), 'prepare clean isolated fixture' );
$csf_contact = get_page_by_path( 'contact', OBJECT, 'page' );
$csf_old_source = require $csf_root . '/contact-0.2.0.php';
$csf_check( ! has_block( 'csf/inquiry-form', $csf_old_source ), 'reviewed 0.2.0 Contact template has no inquiry block' );
$csf_seed_old = static function () use ( $csf_contact, $csf_old_source ) {
	$result = wp_update_post( array( 'ID' => $csf_contact->ID, 'post_content' => $csf_old_source ), true );
	if ( is_wp_error( $result ) || ! $result ) { throw new RuntimeException( 'Could not seed old Contact fixture' ); }
	$stored = get_post_field( 'post_content', $csf_contact->ID, 'raw' );
	update_post_meta( $csf_contact->ID, '_csf_setup_hash', md5( $stored ) );
	update_post_meta( $csf_contact->ID, '_csf_setup_source', md5( $csf_old_source ) );
	return $stored;
};
$csf_other_pages = static function () use ( $csf_contact ) {
	$pages = array();
	foreach ( CSF_Core_Setup::pages() as $page ) {
		$post = get_page_by_path( $page['path'], OBJECT, 'page' );
		if ( (int) $post->ID !== (int) $csf_contact->ID ) { $pages[ $post->ID ] = array( $post->post_content, get_post_meta( $post->ID ) ); }
	}
	return $pages;
};
$csf_old_saved = $csf_seed_old();
$csf_other_before = $csf_other_pages();
$csf_menus_before = serialize( array( wp_get_nav_menus(), get_theme_mod( 'nav_menu_locations' ), wp_get_nav_menu_items( CSF_Core_Setup::MENU_PRIMARY ), wp_get_nav_menu_items( CSF_Core_Setup::MENU_FOOTER ) ) );
$csf_preview = CSF_Core_Setup::run( array( 'dry_run' => true ) );
$csf_check( in_array( 'update   /contact/', $csf_preview, true ) && $csf_old_saved === get_post_field( 'post_content', $csf_contact->ID, 'raw' ), 'non-forced dry run previews Contact update without writing' );
$csf_applied = CSF_Core_Setup::run();
$csf_new_saved = get_post_field( 'post_content', $csf_contact->ID, 'raw' );
$csf_check( ! CSF_Core_Setup::has_errors( $csf_applied ) && (int) get_page_by_path( 'contact', OBJECT, 'page' )->ID === (int) $csf_contact->ID && 1 === substr_count( $csf_new_saved, '<!-- wp:csf/inquiry-form' ), 'non-forced upgrade adds one inquiry block to existing unedited Contact ID' );
$csf_check( md5( $csf_new_saved ) === get_post_meta( $csf_contact->ID, '_csf_setup_hash', true ) && md5( CSF_Core_Patterns::page_content( 'contact' ) ) === get_post_meta( $csf_contact->ID, '_csf_setup_source', true ), 'upgrade records persisted-content and current-source hashes' );
$csf_check( $csf_other_before === $csf_other_pages(), 'other twenty page IDs, content and metadata remain unchanged' );
$csf_check( $csf_menus_before === serialize( array( wp_get_nav_menus(), get_theme_mod( 'nav_menu_locations' ), wp_get_nav_menu_items( CSF_Core_Setup::MENU_PRIMARY ), wp_get_nav_menu_items( CSF_Core_Setup::MENU_FOOTER ) ) ), 'menus and location assignments remain unchanged' );
$csf_again = CSF_Core_Setup::run();
$csf_check( in_array( 'ok       /contact/', $csf_again, true ) && $csf_new_saved === get_post_field( 'post_content', $csf_contact->ID, 'raw' ), 'repeat upgrade is idempotent and adds no duplicate block' );
$csf_check( ! CSF_Core_Inquiry::settings()['enabled'] && false === strpos( do_blocks( $csf_new_saved ), '<form' ) && 0 === $csf_mail_calls, 'inquiry stays disabled, hidden for visitors, and sends no mail' );

$csf_seed_old();
$csf_edited = $csf_old_saved . CSF_Core_Markup::p( 'Synthetic owner-edited text: preserve exactly.' );
wp_update_post( array( 'ID' => $csf_contact->ID, 'post_content' => $csf_edited ), true );
$csf_edited = get_post_field( 'post_content', $csf_contact->ID, 'raw' );
$csf_meta_before = get_post_meta( $csf_contact->ID );
foreach ( array( true, false ) as $csf_dry ) {
	$csf_log = CSF_Core_Setup::run( array( 'dry_run' => $csf_dry ) );
	$csf_check( in_array( 'kept     /contact/ (edited in WordPress — not overwritten)', $csf_log, true ) && false !== strpos( implode( "\n", $csf_log ), 'add the Farm Inquiry Form block manually' ) && $csf_edited === get_post_field( 'post_content', $csf_contact->ID, 'raw' ) && $csf_meta_before === get_post_meta( $csf_contact->ID ), ( $csf_dry ? 'dry run' : 'apply' ) . ' preserves owner-edited Contact and its hashes; reports required manual block placement' );
}
// An approved manual block insertion is retained; setup must neither regenerate nor duplicate it.
wp_update_post( array( 'ID' => $csf_contact->ID, 'post_content' => $csf_edited . '<!-- wp:csf/inquiry-form /-->' ), true );
$csf_manual = get_post_field( 'post_content', $csf_contact->ID, 'raw' );
$csf_manual_log = CSF_Core_Setup::run();
$csf_check( $csf_manual === get_post_field( 'post_content', $csf_contact->ID, 'raw' ) && false === strpos( implode( "\n", $csf_manual_log ), 'add the Farm Inquiry Form block manually' ), 'edited Contact with manually placed block remains unchanged without misleading warning' );
$csf_seed_old();
delete_post_meta( $csf_contact->ID, '_csf_setup_hash' );
$csf_no_hash = get_post_field( 'post_content', $csf_contact->ID, 'raw' );
CSF_Core_Setup::run();
$csf_check( $csf_no_hash === get_post_field( 'post_content', $csf_contact->ID, 'raw' ) && ! has_block( 'csf/inquiry-form', $csf_no_hash ), 'missing ownership hash fails safe: existing Contact is never overwritten' );
$csf_check( ! CSF_Core_Inquiry::settings()['enabled'] && 0 === $csf_mail_calls, 'no tested upgrade path enables or sends inquiries' );
WP_CLI::success( $csf_checks . ' Contact upgrade checks passed.' );

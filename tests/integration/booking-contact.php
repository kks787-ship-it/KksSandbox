<?php
/** Scheduling removal regressions. Run only via run-booking-contact.sh. */
$csf_root = getenv( 'CSF_BOOKING_TEST_ROOT' );
if ( ! $csf_root || 0 !== strpos( $csf_root, '/tmp/csf-booking-regression.' ) || ABSPATH !== $csf_root . '/site/' || FQDB !== $csf_root . '/test.sqlite' || 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'Use the isolated scheduling regression runner.' );
}
wp_set_current_user( 0 );
$csf_checks = 0;
$csf_check = static function ( $ok, $name ) use ( &$csf_checks ) {
	if ( ! $ok ) { throw new RuntimeException( $name ); }
	++$csf_checks;
	WP_CLI::log( 'PASS: ' . $name );
};
$csf_check( ! CSF_Core_Inquiry::settings()['enabled'], 'fixture starts with inquiries disabled' );
$csf_check( ! is_plugin_active( 'csf-emergency/csf-emergency.php' ), 'emergency stays inactive' );
$csf_mail_calls = 0;
add_filter( 'pre_wp_mail', static function () use ( &$csf_mail_calls ) { ++$csf_mail_calls; return false; } );
foreach ( array( array(), array( 'url' => 'https://cornerstonefarm.as.me/?appointmentType=70075205', 'label' => 'Book online', 'verified' => 1 ), 'malformed legacy option' ) as $csf_old ) {
	$csf_legacy = static function () use ( $csf_old ) { return $csf_old; };
	add_filter( 'pre_option_' . CSF_Core_Booking::OPTION, $csf_legacy );
	$csf_html = do_shortcode( '[csf_book_button label="See available times" style="outline"]' );
	remove_filter( 'pre_option_' . CSF_Core_Booking::OPTION, $csf_legacy );
	$csf_check( false !== strpos( $csf_html, 'href="' . home_url( '/contact/' ) . '"' ) && false !== strpos( $csf_html, '>Contact Beth to Schedule</a>' ) && false === strpos( $csf_html, 'as.me' ) && false === strpos( $csf_html, 'available times' ), 'legacy options and shortcode labels cannot restore provider links or claims' );
}
$csf_check( false !== strpos( do_blocks( '<!-- wp:csf/book-button {"label":"Book online","variant":"light","align":"center"} /-->' ), 'csf-book-button--light' ), 'old Gutenberg attributes still render compatible styles' );
$csf_check( false === strpos( CSF_Core_Booking::render_button( array( 'variant' => 'bad" onclick="alert(1)', 'align' => 'bad' ) ), 'onclick' ), 'invalid style/alignment cannot add attributes' );

// Preview all existing fixture pages without writing, before the isolated local upgrade.
$csf_saved_before = array();
foreach ( CSF_Core_Setup::pages() as $csf_page ) {
	$csf_post = get_page_by_path( $csf_page['path'], OBJECT, 'page' );
	$csf_saved_before[ $csf_post->ID ] = array( $csf_post->post_content, get_post_meta( $csf_post->ID ) );
}
CSF_Core_Setup::run( array( 'dry_run' => true ) );
foreach ( $csf_saved_before as $csf_id => $csf_snapshot ) {
	$csf_check( $csf_snapshot === array( get_post_field( 'post_content', $csf_id, 'raw' ), get_post_meta( $csf_id ) ), 'dry-run preserves saved page ' . $csf_id );
}
// Apply only to this disposable fixture. Preserve owner edits and missing hashes below.
CSF_Core_Setup::run();
$csf_contact = get_page_by_path( 'contact', OBJECT, 'page' );
wp_update_post( array( 'ID' => $csf_contact->ID, 'post_content' => $csf_contact->post_content . '<p>Synthetic owner edit: preserve.</p>' ) );
$csf_faq = get_page_by_path( 'resources/faq', OBJECT, 'page' );
delete_post_meta( $csf_faq->ID, '_csf_setup_hash' );
$csf_before = get_post_field( 'post_content', $csf_contact->ID, 'raw' );
$csf_meta = get_post_meta( $csf_contact->ID );
$csf_faq_before = get_post_field( 'post_content', $csf_faq->ID, 'raw' );
$csf_menu_before = serialize( array( wp_get_nav_menu_items( CSF_Core_Setup::MENU_PRIMARY ), wp_get_nav_menu_items( CSF_Core_Setup::MENU_FOOTER ), get_theme_mod( 'nav_menu_locations' ) ) );
$csf_log = CSF_Core_Setup::run( array( 'dry_run' => true ) );
$csf_check( false !== strpos( implode( "\n", $csf_log ), 'kept     /contact/' ) && $csf_before === get_post_field( 'post_content', $csf_contact->ID, 'raw' ), 'dry-run identifies and preserves owner-edited content' );
CSF_Core_Setup::run();
$csf_check( $csf_before === get_post_field( 'post_content', $csf_contact->ID, 'raw' ) && $csf_meta === get_post_meta( $csf_contact->ID ) && $csf_faq_before === get_post_field( 'post_content', $csf_faq->ID, 'raw' ), 'non-forced setup preserves edited/untracked pages and metadata' );
$csf_check( $csf_menu_before === serialize( array( wp_get_nav_menu_items( CSF_Core_Setup::MENU_PRIMARY ), wp_get_nav_menu_items( CSF_Core_Setup::MENU_FOOTER ), get_theme_mod( 'nav_menu_locations' ) ) ), 'setup and rendered navigation never rewrite saved menus' );
$csf_menu = wp_nav_menu( array( 'menu' => CSF_Core_Setup::MENU_PRIMARY, 'echo' => false ) );
$csf_check( false !== strpos( $csf_menu, 'Contact Beth to Schedule' ) && false === strpos( $csf_menu, 'Book a Lesson' ), 'existing booking navigation renders the contact label' );

foreach ( CSF_Core_Setup::pages() as $csf_page ) {
	$csf_html = do_blocks( CSF_Core_Patterns::page_content( $csf_page['slug'] ) );
	$csf_check( ! preg_match( '~acuity|as\.me|book(?:ed)? online|online booking|see available times|current availability|confirmation after booking~i', $csf_html ), $csf_page['path'] . ' generated render has no provider or public-booking claims' );
	$csf_check( false === strpos( $csf_html, '<form' ), $csf_page['path'] . ' generated render exposes no inquiry form' );
	preg_match_all( '~<a[^>]*data-csf-booking="1"[^>]*>(.*?)</a>~s', $csf_html, $csf_buttons );
	foreach ( $csf_buttons[0] as $csf_button ) {
		$csf_check( false !== strpos( $csf_button, 'href="' . home_url( '/contact/' ) . '"' ) && false !== strpos( $csf_button, '>Contact Beth to Schedule</a>' ), $csf_page['path'] . ' scheduling CTA uses fixed destination and accessible name' );
	}
}
update_option( CSF_Core_Farm_Info::OPTION, array() );
$csf_empty = CSF_Core_Farm_Info::render_contact();
$csf_check( false !== strpos( $csf_empty, 'Public scheduling contact details are not yet available' ) && false === strpos( $csf_empty, 'mailto:' ) && false === strpos( $csf_empty, 'tel:' ), 'unset public channels show safe fallback with no invented details' );
update_option( CSF_Core_Farm_Info::OPTION, array( 'phone' => '+1 202 555 0100', 'email' => 'fixture@example.invalid' ) );
$csf_known = CSF_Core_Farm_Info::render_contact();
$csf_check( false !== strpos( $csf_known, 'tel:+12025550100' ) && false !== strpos( $csf_known, 'mailto:' ) && false === strpos( $csf_known, 'sms:' ), 'only explicitly configured public channels render; phone does not imply text consent' );
$csf_event = wp_insert_post( array( 'post_type' => CSF_Core_Events::POST_TYPE, 'post_status' => 'draft', 'post_title' => 'Synthetic registration regression' ) );
// Use the field helper's metadata prefix, as defined by the existing event implementation.
update_post_meta( $csf_event, '_csf_status', 'open' );
update_post_meta( $csf_event, '_csf_register_url', 'https://cornerstonefarm.as.me/' );
$csf_event_html = CSF_Core_Events::register_button( $csf_event );
$csf_check( false !== strpos( $csf_event_html, 'Contact Beth to Schedule' ) && false === strpos( $csf_event_html, 'as.me' ), 'saved event registration URLs cannot restore external scheduling' );
$csf_check( ! CSF_Core_Inquiry::settings()['enabled'] && 0 === $csf_mail_calls && ! is_plugin_active( 'csf-emergency/csf-emergency.php' ), 'inquiry off, zero mail calls, emergency inactive throughout' );
WP_CLI::success( $csf_checks . ' scheduling-contact checks passed.' );

<?php
/**
 * Local-only WordPress regression checks. Run using wp eval-file.
 *
 * @package CSF
 */

if ( 'local' !== wp_get_environment_type() ) {
	WP_CLI::error( 'These checks require a disposable local site.' );
}

$csf_check = static function ( $ok, $name ) {
	if ( ! $ok ) {
		throw new RuntimeException( $name );
	}
	WP_CLI::log( 'PASS: ' . $name );
};
$csf_original = get_option( CSF_Emergency_Farm_State::OPTION, null );
$csf_hooks    = 0;
$csf_hook     = static function () use ( &$csf_hooks ) { ++$csf_hooks; };
$csf_block    = static function ( $value, $old ) { return $old; };
$csf_uuid    = '';
add_action( 'csf_emergency_status_changed', $csf_hook );
try {
	$csf_before = CSF_Emergency_Farm_State::get();
	$csf_log    = CSF_Emergency_Event_Log::recent( 1 );
	add_filter( 'pre_update_option_' . CSF_Emergency_Farm_State::OPTION, $csf_block, 10, 2 );
	$csf_result = CSF_Emergency_Farm_State::change( 'monitoring', 'Write failure regression ' . wp_generate_uuid4() );
	remove_filter( 'pre_update_option_' . CSF_Emergency_Farm_State::OPTION, $csf_block );
	$csf_check( is_wp_error( $csf_result ) && 'csf_state_write_failed' === $csf_result->get_error_code(), 'failed state persistence returns an error' );
	$csf_check( $csf_before === CSF_Emergency_Farm_State::get(), 'failed persistence preserves current status' );
	$csf_check( 0 === $csf_hooks && $csf_log == CSF_Emergency_Event_Log::recent( 1 ), 'failed persistence emits no change hook or success log' );
	$csf_uuid = CSF_Emergency_Farm_State::change( 'monitoring', 'Successful regression ' . wp_generate_uuid4() );
	$csf_check( is_string( $csf_uuid ) && 1 === $csf_hooks, 'successful persistence fires the change hook' );
	$csf_check( $csf_uuid === CSF_Emergency_Farm_State::get()['event_uuid'] && $csf_uuid === CSF_Emergency_Event_Log::recent( 1 )[0]->event_uuid, 'successful state and audit event share an identifier' );
	$csf_duplicate = CSF_Emergency_Farm_State::change( 'monitoring', CSF_Emergency_Farm_State::get()['message'] );
	$csf_check( is_wp_error( $csf_duplicate ) && 1 === $csf_hooks, 'sequential duplicate rejected without a second hook' );

	set_error_handler( static function ( $severity, $message ) { throw new ErrorException( $message, 0, $severity ); } );
	try {
		$csf_card = CSF_Core_Blocks::card( array( 'url' => home_url( '/' ), 'title' => 'Regression horse' ) );
		$csf_check( false !== strpos( $csf_card, '<h3 class="csf-card__title">' ), 'card without explicit heading renders valid H3 without warnings' );
		$csf_card = CSF_Core_Blocks::card( array( 'url' => home_url( '/' ), 'title' => '<script>alert(1)</script>', 'heading' => 'h2' ) );
		$csf_check( false !== strpos( $csf_card, '<h2 ' ) && false === strpos( $csf_card, '<script>' ), 'explicit card heading works and title is escaped' );
	} finally {
		restore_error_handler();
	}
} finally {
	remove_filter( 'pre_update_option_' . CSF_Emergency_Farm_State::OPTION, $csf_block );
	remove_action( 'csf_emergency_status_changed', $csf_hook );
	if ( null === $csf_original ) {
		delete_option( CSF_Emergency_Farm_State::OPTION );
	} else {
		update_option( CSF_Emergency_Farm_State::OPTION, $csf_original );
	}
	// Remove only the synthetic event created by this local regression run.
	if ( is_string( $csf_uuid ) && $csf_uuid ) {
		global $wpdb;
		$wpdb->delete( CSF_Emergency_Event_Log::table(), array( 'event_uuid' => $csf_uuid ), array( '%s' ) );
	}
	CSF_Emergency_Farm_State::purge_page_caches();
}
WP_CLI::success( '8 takeover regression checks passed.' );

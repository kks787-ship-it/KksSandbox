<?php
/** Separate-process fixture worker. Never use a farm database. */
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { WP_CLI::error( 'Disposable fixture required.' ); }
wp_set_current_user( 1 );
$worker = getenv( 'CSF_LIFECYCLE_WORKER' );
if ( ! in_array( $worker, array( '1', '2' ), true ) ) { WP_CLI::error( 'Worker required.' ); }
$spec = json_decode( file_get_contents( '/test/lifecycle-race.json' ), true );
$kind = '1' === $worker ? 'appointment_saved' : 'horse_status_saved';
$body = $spec[ $kind ];
file_put_contents( '/test/lifecycle-ready-' . $worker, 'ready' );
$deadline = microtime( true ) + 20;
while ( ! file_exists( '/test/lifecycle-go' ) ) { if ( microtime( true ) > $deadline ) { WP_CLI::error( 'Barrier timeout.' ); } usleep( 10000 ); }
$result = CSF_Scheduling_Store::calendar( $kind, $body, $spec['revision'], wp_generate_uuid4() );
if ( is_wp_error( $result ) && 'stale_revision' === ( $result->get_error_data()['reason'] ?? '' ) ) {
	$result = CSF_Scheduling_Store::calendar( $kind, $body, (int) CSF_Scheduling_Schema::settings()['revision'], wp_generate_uuid4() );
}
$reason = is_wp_error( $result ) ? ( $result->get_error_data()['reason'] ?? $result->get_error_code() ) : 'committed';
file_put_contents( '/test/lifecycle-outcome-' . $worker, json_encode( array( 'reason' => $reason ) ) );

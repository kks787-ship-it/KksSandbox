<?php
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { WP_CLI::error( 'Disposable fixture required.' ); }
wp_set_current_user( 1 ); $worker = getenv( 'CSF_FAMILY_WORKER' );
if ( ! in_array( $worker, array( '1', '2' ), true ) ) { WP_CLI::error( 'Worker required.' ); }
$f = json_decode( file_get_contents( '/test/family-race.json' ), true );
file_put_contents( '/test/family-ready-' . $worker, 'ready' ); $deadline = microtime( true ) + 20;
while ( ! file_exists( '/test/family-go' ) ) { if ( microtime( true ) > $deadline ) { WP_CLI::error( 'Barrier timeout.' ); } usleep( 10000 ); }
$b = $f['bodies'][(int) $worker - 1]; $r = CSF_Scheduling_Store::calendar( 'family_visit_saved', $b, $f['revision'], wp_generate_uuid4() );
if ( is_wp_error( $r ) && 'stale_revision' === ( $r->get_error_data()['reason'] ?? '' ) ) { $r = CSF_Scheduling_Store::calendar( 'family_visit_saved', $b, (int) CSF_Scheduling_Schema::settings()['revision'], wp_generate_uuid4() ); }
file_put_contents( '/test/family-outcome-' . $worker, wp_json_encode( array( 'reason' => is_wp_error( $r ) ? ( $r->get_error_data()['reason'] ?? $r->get_error_code() ) : 'committed' ) ) );

<?php
/** Populated compatibility migration and horse lifecycle: disposable fixture only. */
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { WP_CLI::error( 'Disposable fixture required.' ); }
global $wpdb;
wp_set_current_user( 1 );
$GLOBALS['lifecycle_checks'] = 0;
function hl_check( $ok, $label ) { if ( ! $ok ) { throw new RuntimeException( $label ); } ++$GLOBALS['lifecycle_checks']; WP_CLI::log( 'PASS ' . $label ); }
function hl_rev() { return (int) CSF_Scheduling_Schema::settings()['revision']; }
function hl_rows() { global $wpdb; $out = array(); foreach ( CSF_Scheduling_Schema::definitions() as $name => $definition ) { $t = CSF_Scheduling_Schema::table( $name ); $out[ $name ] = $wpdb->get_results( "SELECT * FROM `$t` ORDER BY 1", ARRAY_A ); } return $out; }
function hl_command( $kind, $body ) { return CSF_Scheduling_Store::calendar( $kind, $body, hl_rev(), wp_generate_uuid4() ); }
function hl_status( $uuid, $expected, $archived, $label ) { return array( 'uuid' => $uuid, 'expected' => $expected, 'archived' => $archived, 'reason' => 'Synthetic lifecycle review', 'confirm_label' => $label ); }
function hl_denied( $body, $reason ) { $before = hl_rows(); $r = hl_command( 'horse_status_saved', $body ); hl_check( is_wp_error( $r ) && ( $r->get_error_data()['reason'] ?? $r->get_error_code() ) === $reason, 'reject ' . $reason ); hl_check( $before === hl_rows(), 'rejection preserves all scheduling records' ); }
// Rehearse a populated v3 -> v4 marker migration, with a genuine failed audit.
$t = CSF_Scheduling_Schema::table( 'settings' );
$wpdb->update( $t, array( 'schema_version' => 3 ), array( 'id' => 1 ) );
$before = hl_rows();
hl_check( ! CSF_Scheduling_Access::can(), 'v4 code refuses unmigrated v3 data' );
$fail = static fn( $q ) => str_contains( $q, 'INSERT INTO `fixture_csf_sched_audit`' ) ? 'SELECT * FROM fixture_lifecycle_missing' : $q;
add_filter( 'query', $fail ); $result = CSF_Scheduling_Schema::migrate(); remove_filter( 'query', $fail );
hl_check( is_wp_error( $result ) && hl_rows() === $before, 'failed marker migration rolls back audit and marker' );
$result = CSF_Scheduling_Schema::migrate();
hl_check( ! is_wp_error( $result ) && CSF_Scheduling_Schema::inspect()['ready'], 'explicit populated v3 to v4 migration succeeds' );
$after = hl_rows(); $after['settings'][0]['schema_version'] = '3'; $after['audit'] = array_slice( $after['audit'], 0, count( $before['audit'] ) );
hl_check( $after === $before, 'migration preserves all existing data, policy, identity and grants' );
require '/repo/.wp-local/lifecycle-v3-schema.php';
$writes = array(); $capture = static function( $q ) use ( &$writes ) { if ( preg_match( '/^\s*(CREATE|ALTER|DROP|INSERT|UPDATE|DELETE|REPLACE)\b/i', $q ) ) { $writes[] = $q; } return $q; };
add_filter( 'query', $capture ); $ready = CSF_Scheduling_Schema_V3_Fixture::inspect()['ready']; $old_result = CSF_Scheduling_Schema_V3_Fixture::migrate(); remove_filter( 'query', $capture );
hl_check( ! $ready && is_wp_error( $old_result ) && ! $writes, 'pinned v3 code rejects v4 and cannot downgrade or write' );
$uuid = wp_generate_uuid4();
$data = array( 'label' => 'Synthetic lifecycle horse', 'source' => 'client', 'resource_uuid' => null, 'suitable_services' => array(), 'suitable_riders' => array(), 'training_enrolled' => false );
hl_check( ! is_wp_error( hl_command( 'horse_saved', array( 'uuid' => $uuid, 'expected' => 0, 'data' => $data ) ) ), 'create lifecycle fixture' );
$body = hl_status( $uuid, 1, true, $data['label'] ); $rev = hl_rev(); $command = wp_generate_uuid4();
$result = CSF_Scheduling_Store::calendar( 'horse_status_saved', $body, $rev, $command );
hl_check( ! is_wp_error( $result ) && $result['archived'], 'archive retains horse record' );
$before = hl_rows();
hl_check( CSF_Scheduling_Store::calendar( 'horse_status_saved', $body, $rev, $command ) === $result && hl_rows() === $before, 'archive replay adds no duplicate history or audit' );
$ctx = CSF_Scheduling_UI::context();
hl_check( isset( $ctx['horses'][ $uuid ] ) && ! isset( $ctx['active_horses'][ $uuid ] ), 'archived horse retains historical label but leaves booking choices' );
$bad = $data; $bad['archived'] = false;
hl_check( is_wp_error( hl_command( 'horse_saved', array( 'uuid' => $uuid, 'expected' => 2, 'data' => $bad ) ) ), 'ordinary horse edit cannot forge restoration' );
$data['label'] = 'Synthetic renamed archived horse';
hl_check( ! is_wp_error( hl_command( 'horse_saved', array( 'uuid' => $uuid, 'expected' => 2, 'data' => $data ) ) ), 'archived record remains editable without restoring it' );
hl_check( json_decode( CSF_Scheduling_Calendar::find( 'horses', $uuid )['body_json'], true )['archived'], 'ordinary save preserves archive state' );
$fixture = json_decode( file_get_contents( '/test/calendar-fixture.json' ), true );
$used = CSF_Scheduling_Calendar::find( 'horses', $fixture['horse'] );
$used_data = json_decode( $used['body_json'], true );
hl_denied( hl_status( $used['uuid'], (int) $used['revision'], true, $used_data['label'] ), 'horse_future_bookings' );
wp_set_current_user( $fixture['staff'] ); hl_denied( hl_status( $uuid, 3, false, $data['label'] ), 'forbidden' ); wp_set_current_user( 1 );
hl_check( ! is_wp_error( hl_command( 'horse_status_saved', hl_status( $uuid, 3, false, $data['label'] ) ) ), 'restore after review' );
$ctx = CSF_Scheduling_UI::context(); hl_check( isset( $ctx['active_horses'][ $uuid ] ), 'restored horse returns to booking choices' );
// Archived enrollment must not consume the active training cap; restoring rechecks it.
$p = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true ); $limited = $p; $limited['training_horse_cap'] = 1;
hl_check( ! is_wp_error( CSF_Scheduling_Store::configure( $limited, hl_rev(), wp_generate_uuid4() ) ), 'set synthetic training cap' );
$data['training_enrolled'] = true;
hl_check( ! is_wp_error( hl_command( 'horse_saved', array( 'uuid' => $uuid, 'expected' => 4, 'data' => $data ) ) ), 'enroll active fixture' );
hl_check( ! is_wp_error( hl_command( 'horse_status_saved', hl_status( $uuid, 5, true, $data['label'] ) ) ), 'archive enrolled fixture' );
$second = wp_generate_uuid4();
hl_check( ! is_wp_error( hl_command( 'horse_saved', array( 'uuid' => $second, 'expected' => 0, 'data' => $data ) ) ), 'archived enrollment frees active cap without deleting enrollment history' );
hl_denied( hl_status( $uuid, 6, false, $data['label'] ), 'capacity' );
hl_check( ! is_wp_error( CSF_Scheduling_Store::configure( $p, hl_rev(), wp_generate_uuid4() ) ), 'restore original fixture policy' );
WP_CLI::success( $GLOBALS['lifecycle_checks'] . ' horse lifecycle and compatibility migration checks' );

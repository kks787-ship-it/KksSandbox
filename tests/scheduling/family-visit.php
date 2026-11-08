<?php
/** Real database checks; only the guarded disposable WordPress fixture. */
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { WP_CLI::error( 'Disposable fixture required.' ); }
global $wpdb, $checks; wp_set_current_user( 1 ); $checks = 0;
function fv_check( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$checks; WP_CLI::log( 'PASS ' . $label ); }
function fv_rev() { return (int) CSF_Scheduling_Schema::settings()['revision']; }
function fv_save( $kind, $body ) { return CSF_Scheduling_Store::calendar( $kind, $body, fv_rev(), wp_generate_uuid4() ); }
function fv_rows() { global $wpdb; $out = array(); foreach ( CSF_Scheduling_Schema::definitions() as $n => $d ) { $t = CSF_Scheduling_Schema::table( $n ); $out[$n] = $wpdb->get_results( "SELECT * FROM `$t` ORDER BY 1", ARRAY_A ); } return $out; }
function fv_reason( $r ) { return is_wp_error( $r ) ? ( $r->get_error_data()['reason'] ?? $r->get_error_code() ) : 'committed'; }
$original = json_decode( CSF_Scheduling_Schema::settings()['policy_json'], true ); $p = $original;
$p['timezone'] = 'UTC'; $p['service_hours'] = array_fill( 0, 7, array( array( '08:00', '18:00' ) ) );
$p['services']['family_test'] = array( 'enabled' => true, 'capacity' => 1, 'duration_minutes' => 30, 'horse_source' => 'none', 'requires_travel' => false, 'required_resources' => array( 'instructor', 'arena' ) );
fv_check( ! is_wp_error( CSF_Scheduling_Store::configure( $p, fv_rev(), wp_generate_uuid4() ) ), 'configure synthetic family policy' );
$resources = array();
foreach ( array( 'instructor', 'arena' ) as $kind ) {
 $uuid = wp_generate_uuid4(); $resources[] = $uuid;
 fv_check( ! is_wp_error( CSF_Scheduling_Store::resource( array( 'uuid' => $uuid, 'kind' => $kind, 'label' => 'Synthetic family ' . $kind ), fv_rev(), wp_generate_uuid4() ) ), 'create isolated family resource' );
 fv_check( ! is_wp_error( fv_save( 'availability_saved', array( 'uuid' => $uuid, 'expected' => 0, 'data' => array( 'resource_uuid' => $uuid, 'intervals' => array( array( 'start' => '2027-01-01T00:00+00:00', 'end' => '2027-12-01T00:00+00:00' ) ) ) ) ) ), 'set isolated resource availability' );
}
$make = static function( $hour, $minute ) use ( $resources ) { $start = new DateTimeImmutable( sprintf( '2027-05-12T%02d:%02d+00:00', $hour, $minute ) ); return array( 'uuid' => wp_generate_uuid4(), 'expected' => 0, 'title' => 'Synthetic family item', 'service_type' => 'family_test', 'state' => 'confirmed', 'start' => $start->format( 'Y-m-d\TH:iP' ), 'end' => $start->modify( '+30 minutes' )->format( 'Y-m-d\TH:iP' ), 'hold_expires' => null, 'resources' => $resources, 'riders' => array(), 'rides' => array() ); };
$body = array( 'uuid' => wp_generate_uuid4(), 'expected' => 0, 'same_family' => true, 'items' => array( array( 'kind' => 'appointment_saved', 'body' => $make( 10, 0 ) ), array( 'kind' => 'appointment_saved', 'body' => $make( 10, 30 ) ) ) );
$before = fv_rows(); $r = CSF_Scheduling_Store::visit_preview( $body, fv_rev(), wp_generate_uuid4() ); fv_check( ! is_wp_error( $r ) && fv_rows() === $before, 'locked group preview leaves all tables unchanged' );
$bad = $body; $bad['items'][1]['body']['end'] = '2027-05-12T11:15+00:00'; $r = fv_save( 'family_visit_saved', $bad ); fv_check( 'service_duration' === fv_reason( $r ) && fv_rows() === $before, 'second child failure rolls back first child allocations history and ledger' );
$fail = static fn( $q ) => str_contains( $q, 'INSERT INTO `fixture_csf_sched_commands`' ) ? 'SELECT * FROM fixture_family_missing' : $q;
add_filter( 'query', $fail ); $r = fv_save( 'family_visit_saved', $body ); remove_filter( 'query', $fail ); fv_check( is_wp_error( $r ) && fv_rows() === $before, 'late ledger failure rolls back complete family' );
$revision = fv_rev(); $command = wp_generate_uuid4(); $r = CSF_Scheduling_Store::calendar( 'family_visit_saved', $body, $revision, $command ); fv_check( ! is_wp_error( $r ) && count( $r['items'] ) === 2, 'commit two individually identified lessons' );
$before = fv_rows(); fv_check( CSF_Scheduling_Store::calendar( 'family_visit_saved', $body, $revision, $command ) === $r && fv_rows() === $before, 'exact group replay has no duplicate records' );
$dt = CSF_Scheduling_Schema::table( 'details' );
$notice = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$dt` WHERE uuid=%s", $command ), ARRAY_A );
$notice_data = $notice ? json_decode( $notice['body_json'], true ) : array();
fv_check( 'booking_notice' === ( $notice_data['record_type'] ?? null ) && count( $notice_data['changes'] ) === 2 && 'held_no_delivery_authorization' === $notice_data['email_state'], 'one consolidated durable family event with transport held' );
fv_check( $notice_data['changes'][0]['after_time']['start'] === $body['items'][0]['body']['start'], 'durable notice stores immutable event-time date' );
fv_check( null !== CSF_Scheduling_Notifications::event( $command ), 'entitled actor can read committed event' );
$state_before_ack = fv_rows(); $ack_command = wp_generate_uuid4(); $ack_rev = fv_rev();
$ack = CSF_Scheduling_Store::calendar( 'notice_acknowledged', array( 'uuid' => $command ), $ack_rev, $ack_command );
fv_check( ! is_wp_error( $ack ), 'actor acknowledges durable notice' );
$state_after_ack = fv_rows();
fv_check( $state_before_ack['appointments'] === $state_after_ack['appointments'] && $state_before_ack['allocations'] === $state_after_ack['allocations'], 'acknowledgement does not mutate bookings or reservations' );
fv_check( CSF_Scheduling_Store::calendar( 'notice_acknowledged', array( 'uuid' => $command ), $ack_rev, $ack_command ) === $ack && fv_rows() === $state_after_ack, 'acknowledgement replay is idempotent' );
$unread = CSF_Scheduling_Notifications::page();
fv_check( ! in_array( $command, array_column( $unread['items'], 'uuid' ), true ), 'acknowledged notice leaves persistent unread banners' );
$changed = $body; $changed['same_family'] = false; fv_check( 'command_conflict' === fv_reason( CSF_Scheduling_Store::calendar( 'family_visit_saved', $changed, $revision, $command ) ), 'reused command rejects changed payload' );
$a = $body['items'][0]['body']; $a['expected'] = 1; fv_check( 'visit_required' === fv_reason( fv_save( 'appointment_saved', $a ) ), 'standalone edit cannot bypass visit membership' );
$read = CSF_Scheduling_Family_Visit::read( $body['uuid'] ); fv_check( count( $read['items'] ) === 2 && 1 === $read['expected'], 'scoped group read returns exact current child revisions' );
$read['items'][0] = array( 'kind' => 'appointment_canceled', 'body' => array( 'uuid' => $a['uuid'], 'expected' => 1, 'initiator' => 'staff', 'reason' => 'Synthetic family cancellation', 'notice' => gmdate( 'Y-m-d\TH:i+00:00' ), 'effective' => gmdate( 'Y-m-d\TH:i+00:00' ) ) );
$incomplete = $read; array_shift( $incomplete['items'] ); $before = fv_rows(); fv_check( 'visit_members_required' === fv_reason( fv_save( 'family_visit_saved', $incomplete ) ) && $before === fv_rows(), 'omitted sibling cannot silently lose reservations' );
fv_check( ! is_wp_error( fv_save( 'family_visit_saved', $read ) ), 'cancel one child and rebuild survivor in one transaction' );
$survivor = CSF_Scheduling_Calendar::find( 'appointments', $read['items'][1]['body']['uuid'] ); $t = CSF_Scheduling_Schema::table( 'allocations' ); $windows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `$t` WHERE appointment_id=%d", $survivor['id'] ), ARRAY_A );
fv_check( count( $windows ) === 2 && '2027-05-12 10:15:00.000000' === $windows[0]['start_utc'] && '2027-05-12 11:15:00.000000' === $windows[0]['end_utc'], 'survivor has actual recalculated outer allocations' );
fv_check( '2027-05-12 10:30:00.000000' === $survivor['start_utc'], 'survivor teaching interval unchanged' );
$closure = array( 'uuid' => wp_generate_uuid4(), 'expected' => 0, 'impact_acknowledged' => true, 'data' => array( 'target_uuid' => $resources[0], 'start' => '2027-05-12T10:15+00:00', 'end' => '2027-05-12T10:30+00:00', 'category' => 'unavailable', 'note' => 'Synthetic buffer impact', 'repeat_until' => null ) );
$before = fv_rows(); $preview = CSF_Scheduling_Store::resource_preview( 'resource_closure_added', $closure, fv_rev(), wp_generate_uuid4() );
fv_check( ! is_wp_error( $preview ) && 1 === $preview['affected_appointments'] && $before === fv_rows(), 'closure preview counts family buffer-only overlap and persists nothing' );
fv_check( ! is_wp_error( fv_save( 'resource_closure_added', $closure ) ), 'record reviewed synthetic future closure without canceling booking' );
$edit = $closure; $edit['expected'] = 1; $edit['data']['start'] = '2027-05-12T14:00+00:00'; $edit['data']['end'] = '2027-05-12T14:15+00:00'; $edit['data']['note'] = 'Reviewed future closure correction';
$before = fv_rows(); $preview = CSF_Scheduling_Store::resource_preview( 'resource_closure_edited', $edit, fv_rev(), wp_generate_uuid4() );
fv_check( ! is_wp_error( $preview ) && 0 === $preview['affected_appointments'] && $before === fv_rows(), 'future closure edit previews exact replacement without writes' );
fv_check( ! is_wp_error( fv_save( 'resource_closure_edited', $edit ) ), 'future closure edit commits versioned history' );
$after = fv_rows(); fv_check( $before['appointments'] === $after['appointments'] && $before['allocations'] === $after['allocations'], 'closure edit never cancels or moves bookings or buffers' );
$single = $make( 16, 0 ); $independent = fv_save( 'appointment_saved', $single );
fv_check( ! is_wp_error( $independent ) && 15 === $independent['resource_reservations'][0]['pre_minutes'] && 15 === $independent['resource_reservations'][0]['post_minutes'], 'independent saved lesson reserves standard 15+15' );
$before = fv_rows(); $adjacent = fv_save( 'appointment_saved', $make( 16, 30 ) );
fv_check( 'resource_conflict' === fv_reason( $adjacent ) && $before === fv_rows(), 'unrelated adjacent lesson cannot consume standard buffers' );
// A separately reviewed synthetic fixture grant, restored immediately; never a farm grant.
$override = array( 'uuid' => $single['uuid'], 'expected' => 1, 'resource_uuid' => $resources[0], 'segment' => 'pre', 'minutes' => 0, 'reason' => 'Synthetic approved exception' );
fv_check( 'forbidden' === fv_reason( fv_save( 'buffer_overridden', $override ) ), 'exception is default-deny even for existing fixture master' );
$member = CSF_Scheduling_Permissions::state()['members'][1]; $grant = $member; $grant['overrides']['buffer_override'] = 'grant';
fv_check( ! is_wp_error( CSF_Scheduling_Store::permissions( 'team_member_saved', array( 'user_id' => 1, 'member' => $grant, 'sensitive_confirmed' => true ), fv_rev(), wp_generate_uuid4() ) ), 'explicit disposable-fixture exception grant' );
$before = fv_rows(); $preview = CSF_Scheduling_Store::buffer_preview( $override, fv_rev(), wp_generate_uuid4() );
fv_check( ! is_wp_error( $preview ) && $before === fv_rows(), 'buffer exception preview rolls back all rows and notices' );
$ov_rev = fv_rev(); $ov_cmd = wp_generate_uuid4(); $approved = CSF_Scheduling_Store::calendar( 'buffer_overridden', $override, $ov_rev, $ov_cmd );
fv_check( ! is_wp_error( $approved ), 'approved segment exception commits with booking validation' );
$before = fv_rows(); fv_check( CSF_Scheduling_Store::calendar( 'buffer_overridden', $override, $ov_rev, $ov_cmd ) === $approved && $before === fv_rows(), 'exception exact replay cannot duplicate approvals or notices' );
$approved_doc = json_decode( CSF_Scheduling_Calendar::find( 'details', $single['uuid'] )['body_json'], true );
$approved_windows = array_column( $approved_doc['resource_reservations'], null, 'resource_uuid' );
$target_window = $approved_windows[$override['resource_uuid']];
fv_check( count( $approved_doc['buffer_overrides'] ) === 1 && 0 === $target_window['pre_minutes'] && 15 === $target_window['post_minutes'], 'exception persisted exact reviewed resource segment and approval marker' );
foreach ( $approved_windows as $resource_uuid => $window ) { if ( $resource_uuid !== $override['resource_uuid'] ) { fv_check( 15 === $window['pre_minutes'] && 15 === $window['post_minutes'], 'exception leaves every other resource buffer unchanged' ); } }
fv_check( ! is_wp_error( CSF_Scheduling_Store::permissions( 'team_member_saved', array( 'user_id' => 1, 'member' => $member, 'sensitive_confirmed' => true ), fv_rev(), wp_generate_uuid4() ) ), 'restore original disposable-fixture rights' );
// Group holds share an exact expiry. Fixture-only clock simulation avoids sleeping.
$held_group = array( 'uuid' => wp_generate_uuid4(), 'expected' => 0, 'same_family' => true, 'items' => array() );
$hold_expiry = ( new DateTimeImmutable( '+10 minutes', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:iP' );
foreach ( array( array( 8, 30 ), array( 9, 0 ) ) as $time ) { $l = $make( $time[0], $time[1] ); $l['state'] = 'held'; $l['hold_expires'] = $hold_expiry; $held_group['items'][] = array( 'kind' => 'appointment_saved', 'body' => $l ); }
fv_check( ! is_wp_error( fv_save( 'family_visit_saved', $held_group ) ), 'whole family hold reserves as one expiry cohort' );
$before = fv_rows(); $mixed = CSF_Scheduling_Family_Visit::read( $held_group['uuid'] ); $mixed['items'][0]['body']['state'] = 'confirmed'; $mixed['items'][0]['body']['hold_expires'] = null;
fv_check( 'visit_state' === fv_reason( fv_save( 'family_visit_saved', $mixed ) ) && $before === fv_rows(), 'partial confirmation cannot split shared-buffer expiry' );
$ap = CSF_Scheduling_Schema::table( 'appointments' );
foreach ( $held_group['items'] as $item ) { $wpdb->query( $wpdb->prepare( "UPDATE `$ap` SET hold_expires_utc='2020-01-01 00:00:00' WHERE uuid=%s", $item['body']['uuid'] ) ); }
$probe = $make( 8, 30 ); $preview = CSF_Scheduling_Store::preview( $probe, fv_rev(), wp_generate_uuid4(), true );
fv_check( ! is_wp_error( $preview ), 'expired cohort frees all resource windows through normal SQL occupancy predicates' );
$renew = CSF_Scheduling_Family_Visit::read( $held_group['uuid'] ); foreach ( $renew['items'] as &$item ) { $item['body']['state'] = 'confirmed'; $item['body']['hold_expires'] = null; } unset( $item );
fv_check( ! is_wp_error( fv_save( 'family_visit_saved', $renew ) ), 'expired visit can be fully rechecked and confirmed, never silently revived' );
$fixture = json_decode( file_get_contents( '/test/calendar-fixture.json' ), true ); wp_set_current_user( $fixture['staff'] );
fv_check( 'forbidden' === fv_reason( fv_save( 'family_visit_saved', $body ) ), 'unprivileged staff cannot group-write' ); wp_set_current_user( 1 );
$race = $body; $race['uuid'] = wp_generate_uuid4(); $race['items'][0]['body'] = $make( 12, 0 ); $race['items'][1]['body'] = $make( 12, 30 );
$other = $race; $other['uuid'] = wp_generate_uuid4(); foreach ( $other['items'] as &$item ) { $item['body']['uuid'] = wp_generate_uuid4(); } unset( $item );
// Preserve the catalog identity referenced by synthetic booking history; cleanup must obey the same guard.
fv_check( 'service_in_use' === fv_reason( CSF_Scheduling_Store::configure( $original, fv_rev(), wp_generate_uuid4() ) ), 'cleanup cannot delete a service with booking history' );
$original['services']['family_test'] = $p['services']['family_test'];
file_put_contents( '/test/family-race.json', wp_json_encode( array( 'policy' => $p, 'original_policy' => $original, 'bodies' => array( $race, $other ) ) ) );
fv_check( ! is_wp_error( CSF_Scheduling_Store::configure( $original, fv_rev(), wp_generate_uuid4() ) ), 'restore fixture policy while retaining referenced service history' );
WP_CLI::success( $checks . ' real database family visit checks' );

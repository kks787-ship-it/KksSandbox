<?php
/** Real Calendar/Family code with an in-memory DB double, NOT database isolation evidence. */
define( 'ABSPATH', '/synthetic/' ); define( 'ARRAY_A', 'ARRAY_A' );
function esc_html( $v ) { return $v; } function sanitize_text_field( $v ) { return strip_tags( $v ); } function wp_specialchars_decode( $v, $f ) { return $v; }
function wp_json_encode( $v ) { return json_encode( $v ); } function get_current_user_id() { return 1; }
function fid( $n ) { return sprintf( '00000000-0000-4000-8000-%012d', $n ); }
function wp_generate_uuid4() { static $n = 1000; return fid( ++$n ); }
class CSF_Scheduling_Store { public static $active = true; public static function in_calendar_transaction() { return self::$active; } }
class CSF_Scheduling_Schema { public static function table( $n ) { return 'f_' . $n; } }
class CSF_Scheduling_Access { public static $buffer = false; public static function can( $cap ) { return 'buffer_override' !== $cap || self::$buffer; } }
class CSF_Scheduling_Permissions { public static function appointment_visible( $a ) { return $a['uuid'] !== self::$denied; } public static $denied = null; public static function authorize( $k, $b ) { return ( $b['uuid'] ?? '' ) !== self::$denied; } }
class CSF_Scheduling_Policy { public static function missing( $p ) { return array(); } }
class CSF_Scheduling_Catalog { public static function for_save( $d, $b, $p ) { return array(); } }
class FamilyDB {
	public $rows = array(); public $last_error = ''; public $insert_id = 0; public $fail_table = null;
	public function prepare( $sql, ...$args ) { foreach ( $args as $a ) { $sql = preg_replace( '/%[sd]/', "'" . $a . "'", $sql, 1 ); } return $sql; }
	public function get_row( $sql, $format ) { preg_match( '/FROM `([^`]+)` WHERE uuid=\x27([^\x27]+)\x27/', $sql, $m ); foreach ( $this->rows[$m[1] ?? ''] ?? array() as $r ) { if ( ( $r['uuid'] ?? null ) === ( $m[2] ?? '' ) ) { return $r; } } return null; }
	public function get_results( $sql, $format ) { preg_match( '/FROM `([^`]+)`/', $sql, $m ); return array_values( $this->rows[$m[1]] ?? array() ); }
	public function insert( $table, $data ) { if ( $table === $this->fail_table ) { return false; } $data['id'] = ++$this->insert_id; $this->rows[$table][$data['id']] = $data; return 1; }
	public function update( $table, $data, $where ) { foreach ( $this->rows[$table] ?? array() as $id => $r ) { if ( $id === (int) $where['id'] ) { $this->rows[$table][$id] = array_merge( $r, $data ); return 1; } } return 0; }
	public function delete( $table, $where ) { $n = 0; foreach ( $this->rows[$table] ?? array() as $id => $r ) { if ( $r['appointment_id'] === $where['appointment_id'] ) { unset( $this->rows[$table][$id] ); ++$n; } } return $n; }
	public function get_var( $sql ) {
		if ( ! preg_match( '/FROM `([^`]+)` x.*x.`([^`]+)`=\x27([^\x27]+)\x27 AND a.id<>\x27([^\x27]+)\x27 AND x.start_utc<\x27([^\x27]+)\x27 AND x.end_utc>\x27([^\x27]+)\x27/', $sql, $m ) ) { throw new RuntimeException( 'Unexpected SQL in double: ' . $sql ); }
		foreach ( $this->rows[$m[1]] ?? array() as $r ) { if ( (int) $r[$m[2]] === (int) $m[3] && $r['appointment_id'] !== (int) $m[4] && $r['start_utc'] < $m[5] && $r['end_utc'] > $m[6] ) { return $r['id']; } } return null;
	}
}
$root = dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/';
foreach ( array( 'audit', 'time', 'conflict', 'calendar', 'resource-hours', 'visit-plan', 'family-visit', 'notifications', 'buffer-override' ) as $name ) { require $root . 'class-csf-scheduling-' . $name . '.php'; }
$wpdb = new FamilyDB();
foreach ( array( 200 => 'instructor', 201 => 'arena' ) as $id => $kind ) {
	$wpdb->insert( 'f_resources', array( 'uuid' => fid( $id ), 'kind' => $kind, 'label' => $kind, 'active' => 1 ) );
	$wpdb->insert( 'f_availability', array( 'uuid' => fid( $id ), 'body_json' => json_encode( array( 'intervals' => array( array( 'start' => '2027-01-01T00:00+00:00', 'end' => '2027-12-01T00:00+00:00' ) ) ) ) ) );
}
$p = array( 'timezone' => 'UTC', 'service_hours' => array_fill( 0, 7, array( array( '08:00', '18:00' ) ) ), 'services' => array( 'lesson' => array( 'enabled' => true, 'duration_minutes' => 30, 'capacity' => 2, 'required_resources' => array( 'instructor', 'arena' ), 'horse_source' => 'none', 'requires_travel' => false ) ) );
$settings = array( 'policy_json' => json_encode( $p ) );
function lesson( $id, $start, $end ) { return array( 'uuid' => fid( $id ), 'expected' => 0, 'title' => 'Synthetic lesson', 'service_type' => 'lesson', 'state' => 'confirmed', 'start' => '2027-01-25T' . $start . '+00:00', 'end' => '2027-01-25T' . $end . '+00:00', 'hold_expires' => null, 'resources' => array( fid( 200 ), fid( 201 ) ), 'riders' => array(), 'rides' => array() ); }
$a = lesson( 1, '10:00', '10:30' ); $b = lesson( 2, '10:30', '11:00' );
$body = array( 'uuid' => fid( 100 ), 'expected' => 0, 'same_family' => true, 'items' => array( array( 'kind' => 'appointment_saved', 'body' => $a ), array( 'kind' => 'appointment_saved', 'body' => $b ) ) );
$checks = 0;
function fc( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$checks; }
function run_family( $body, $preview = false ) { global $wpdb, $settings; $snapshot = $wpdb->rows; try { $result = CSF_Scheduling_Calendar::apply( 'family_visit_saved', $body, $settings ); if ( $preview ) { $wpdb->rows = $snapshot; } return $result; } catch ( Throwable $e ) { $wpdb->rows = $snapshot; throw $e; } }
function rejected( $body, $reason ) { global $wpdb; $before = $wpdb->rows; $got = null; try { run_family( $body ); } catch ( Throwable $e ) { $got = $e->getMessage(); } fc( $got === $reason, 'Expected ' . $reason . ', got ' . var_export( $got, true ) ); fc( $wpdb->rows === $before, 'double transaction rollback preserves rows' ); fc( null === CSF_Scheduling_Family_Visit::context( fid( 1 ) ), 'context cleared after failure' ); }
$before = $wpdb->rows; $result = run_family( $body, true ); fc( $wpdb->rows === $before && count( $result['items'] ) === 2, 'preview double rolls back actual child writes' );
$bad = $body; $bad['items'][1]['body']['state'] = 'requested'; rejected( $bad, 'visit_state' );
$bad = $body; $bad['same_family'] = false; rejected( $bad, 'invalid_body' );
$bad = $body; $bad['items'][1]['body']['end'] = '2027-01-25T11:15+00:00'; rejected( $bad, 'service_duration' );
$bad = $body; $bad['items'][1]['body']['resources'][] = fid( 201 ); rejected( $bad, 'visit_plan_invalid' );
CSF_Scheduling_Permissions::$denied = fid( 2 ); rejected( $body, 'forbidden' ); CSF_Scheduling_Permissions::$denied = null;
$wpdb->fail_table = 'f_history'; rejected( $body, 'write_failed' ); $wpdb->fail_table = null;
$bad = $body; $bad['items'][0]['body'] = lesson( 1, '08:00', '08:30' ); $bad['items'][1]['body'] = lesson( 2, '08:30', '09:00' ); rejected( $bad, 'outside_hours' );
// Actual horse workload gate remains active across sequential sibling writes.
$wpdb->insert( 'f_horses', array( 'uuid' => fid( 300 ), 'body_json' => json_encode( array( 'label' => 'Synthetic shared horse', 'source' => 'client', 'resource_uuid' => null, 'suitable_services' => array( 'lesson' ), 'suitable_riders' => array() ) ) ) );
$original_settings = $settings;
$hp = $p; $hp['services']['lesson']['horse_source'] = 'client'; $hp['workload'] = array( 'daily_minutes' => 120, 'consecutive_minutes' => 45, 'break_minutes' => 15 );
$settings['policy_json'] = json_encode( $hp ); $horse_body = $body;
foreach ( $horse_body['items'] as &$item ) { $item['body']['rides'] = array( array( 'horse' => fid( 300 ), 'rider' => null, 'start' => $item['body']['start'], 'end' => $item['body']['end'] ) ); } unset( $item );
rejected( $horse_body, 'welfare_conflict' ); $settings = $original_settings;
$result = run_family( $body ); fc( count( $wpdb->rows['f_appointments'] ) === 2 && count( $wpdb->rows['f_allocations'] ) === 4 && count( $wpdb->rows['f_history'] ) === 3, 'atomic candidate writes itemized appointments allocations and history' );
$alloc = array_values( $wpdb->rows['f_allocations'] ); fc( $alloc[0]['start_utc'] === '2027-01-25 09:45:00.000000' && $alloc[0]['end_utc'] === '2027-01-25 10:30:00.000000' && $alloc[2]['start_utc'] === '2027-01-25 10:30:00.000000' && $alloc[2]['end_utc'] === '2027-01-25 11:15:00.000000', 'actual allocations contain shared outer buffers' );
$got = null; $a['expected'] = 1; try { CSF_Scheduling_Calendar::apply( 'appointment_saved', $a, $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'visit_required' === $got, 'standalone edit cannot detach family lesson' );
$body['expected'] = 1; foreach ( $body['items'] as &$item ) { $item['body']['expected'] = 1; } unset( $item );
$bad = $body; array_pop( $bad['items'] ); rejected( $bad, 'visit_members_required' );
$bad = $body; $bad['items'][1]['body']['expected'] = 0; rejected( $bad, 'stale_appointment' );
// A legacy outside booking occupies the newly exposed buffer after canceling lesson 1.
$wpdb->insert( 'f_allocations', array( 'appointment_id' => 999, 'resource_id' => 1, 'start_utc' => '2027-01-25 10:15:00.000000', 'end_utc' => '2027-01-25 10:30:00.000000' ) ); $outside_id = $wpdb->insert_id;
$cancel = array( 'uuid' => fid( 1 ), 'expected' => 1, 'initiator' => 'staff', 'reason' => 'Synthetic cancellation', 'notice' => '2026-01-01T10:00+00:00', 'effective' => '2026-01-01T10:00+00:00' );
$body['items'][0] = array( 'kind' => 'appointment_canceled', 'body' => $cancel ); rejected( $body, 'resource_conflict' ); unset( $wpdb->rows['f_allocations'][$outside_id] );
$result = run_family( $body );
$survivor = CSF_Scheduling_Calendar::find( 'appointments', fid( 2 ) ); $canceled = CSF_Scheduling_Calendar::find( 'appointments', fid( 1 ) );
fc( 'canceled' === $canceled['state'] && '2027-01-25 10:30:00.000000' === $survivor['start_utc'], 'cancel preserves history and survivor teaching time' );
$alloc = array_values( $wpdb->rows['f_allocations'] ); fc( count( $alloc ) === 2 && '2027-01-25 10:15:00.000000' === $alloc[0]['start_utc'] && '2027-01-25 11:15:00.000000' === $alloc[0]['end_utc'], 'cancel rebuilds surviving outer buffers' );
fc( null === CSF_Scheduling_Family_Visit::context( fid( 2 ) ), 'successful command clears temporary context' );
CSF_Scheduling_Store::$active = false; rejected( $body, 'forbidden' );
CSF_Scheduling_Store::$active = true;
require $root . 'class-csf-scheduling-booking.php'; require $root . 'class-csf-scheduling-family-ui.php';
$native = array( 'visit_uuid' => fid( 900 ), 'visit_expected' => '0', 'same_family' => '1', 'lessons' => array( array( 'uuid' => fid( 901 ), 'expected' => '0', 'title' => 'Native lesson', 'service_type' => 'lesson', 'rider' => fid( 500 ), 'horse' => '', 'start' => '2027-01-25T10:00', 'choice' => '', 'resources' => array( fid( 200 ), fid( 201 ) ) ) ) );
$parsed = CSF_Scheduling_Family_UI::parse( $native, $p );
fc( $parsed['items'][0]['body']['end'] === '2027-01-25T10:30+00:00' && $parsed['items'][0]['body']['riders'] === array( fid( 500 ) ), 'native family fields use authoritative service duration and explicit rider' );
$fold = $native; $fold['lessons'][0]['start'] = '2027-11-07T01:30'; $fold['lessons'][0]['choice'] = '-07:00'; $foldp = $p; $foldp['timezone'] = 'America/Los_Angeles';
$parsed = CSF_Scheduling_Family_UI::parse( $fold, $foldp );
fc( $parsed['items'][0]['body']['end'] === '2027-11-07T01:00-08:00', 'native repeated-hour choice resolves exact elapsed duration' );
$fold['lessons'][0]['choice'] = ''; $got = null; try { CSF_Scheduling_Family_UI::parse( $fold, $foldp ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'ambiguous_time' === $got, 'native fold requires explicit occurrence' );
$fold['lessons'][0]['start'] = '2027-03-14T02:30'; $got = null; try { CSF_Scheduling_Family_UI::parse( $fold, $foldp ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'nonexistent_time' === $got, 'native missing hour rejected' );
$native['lessons'][0]['resources'][] = array(); $got = null; try { CSF_Scheduling_Family_UI::parse( $native, $p ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'invalid_body' === $got, 'native resource arrays cannot inject interval or compatibility fields' );
// New independent lessons reserve both outer buffers; unrelated neighbors cannot merge.
$single = lesson( 700, '14:00', '14:30' );
$r = CSF_Scheduling_Calendar::apply( 'appointment_saved', $single, $settings );
fc( $r['resource_reservations'][0]['pre_minutes'] === 15 && $r['resource_reservations'][0]['post_minutes'] === 15, 'independent writer returns authoritative 15+15 snapshot' );
$stored = CSF_Scheduling_Calendar::find( 'appointments', $single['uuid'] );
$windows = array_values( array_filter( $wpdb->rows['f_allocations'], static fn( $r ) => $r['appointment_id'] === $stored['id'] ) );
fc( $windows[0]['start_utc'] === '2027-01-25 13:45:00.000000' && $windows[0]['end_utc'] === '2027-01-25 14:45:00.000000' && $stored['start_utc'] === '2027-01-25 14:00:00.000000', 'saved independent occupancy includes buffers without altering teaching' );
$adjacent = lesson( 701, '14:30', '15:00' ); $got = null; try { CSF_Scheduling_Calendar::apply( 'appointment_saved', $adjacent, $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'resource_conflict' === $got, 'unrelated adjacent teaching cannot merge arrival buffers' );
$separate = lesson( 702, '15:00', '15:30' ); $r = CSF_Scheduling_Calendar::apply( 'appointment_saved', $separate, $settings ); fc( $r['state'] === 'confirmed', 'independent half-open outer reservations can meet exactly' );
$draft = lesson( 703, '16:00', '16:30' ); $draft['state'] = 'requested'; $before = count( $wpdb->rows['f_allocations'] );
$r = CSF_Scheduling_Calendar::apply( 'appointment_saved', $draft, $settings ); fc( $before === count( $wpdb->rows['f_allocations'] ) && $r['resource_reservations'][0]['pre_minutes'] === 15 && null === $r['notice_kind'], 'requested preview metadata never implies actual reservation or new lesson notice' );
$travel = lesson( 704, '17:00', '17:30' ); $travel_policy = $p; $travel_policy['services']['lesson']['requires_travel'] = true; $travel_policy['travel_buffer_minutes'] = 30;
$r = CSF_Scheduling_Calendar::apply( 'appointment_saved', $travel, array( 'policy_json' => json_encode( $travel_policy ) ) ); fc( $r['resource_reservations'][0]['pre_minutes'] === 30 && $r['resource_reservations'][0]['post_minutes'] === 30, 'larger mandatory travel envelope is never reduced to 15' );
$settings['revision'] = 10;
$override = array( 'uuid' => $single['uuid'], 'expected' => 1, 'resource_uuid' => fid( 200 ), 'segment' => 'pre', 'minutes' => 0, 'reason' => 'Synthetic reviewed arrival exception' );
$got = null; try { CSF_Scheduling_Calendar::apply( 'buffer_overridden', $override, $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'forbidden' === $got, 'buffer exception requires its separate permission' );
CSF_Scheduling_Access::$buffer = true;
$r = CSF_Scheduling_Calendar::apply( 'buffer_overridden', $override, $settings );
$details = json_decode( CSF_Scheduling_Calendar::find( 'details', $single['uuid'] )['body_json'], true );
fc( $details['resource_reservations'][0]['pre_minutes'] === 0 && $details['resource_reservations'][1]['pre_minutes'] === 15 && $details['resource_reservations'][0]['post_minutes'] === 15, 'exception trims only the reviewed resource and segment' );
$marker = $details['buffer_overrides'][0]; fc( $marker['approved_by'] === 1 && $marker['reason'] === $override['reason'] && $marker['facility_revision'] === 10 && $marker['before']['start'] < $marker['after']['start'], 'exception stores reviewer reason revisions and exact before/after windows' );
fc( null === CSF_Scheduling_Buffer_Override::context( $single['uuid'] ), 'override context clears after write' );
$bad = $override; $bad['expected'] = 2; $bad['minutes'] = -1; $got = null; try { CSF_Scheduling_Calendar::apply( 'buffer_overridden', $bad, $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'invalid_body' === $got, 'negative buffer cannot shorten teaching' );
$travel_override = $override; $travel_override['uuid'] = $travel['uuid']; $travel_override['minutes'] = 15; $got = null;
try { CSF_Scheduling_Calendar::apply( 'buffer_overridden', $travel_override, array( 'revision' => 10, 'policy_json' => json_encode( $travel_policy ) ) ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'buffer_mandatory' === $got, 'override never reduces mandatory travel' );
// Current protective restrictions are rechecked even though this is a smaller window.
$wpdb->insert( 'f_restrictions', array( 'uuid' => fid( 999 ), 'entity_uuid' => fid( 200 ), 'body_json' => json_encode( array( 'start' => '2027-01-25T14:00+00:00', 'end' => '2027-01-25T14:30+00:00', 'reason' => 'unavailable' ) ) ) );
$bad = $override; $bad['expected'] = 2; $bad['segment'] = 'post'; $got = null; try { CSF_Scheduling_Calendar::apply( 'buffer_overridden', $bad, $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); }
fc( 'welfare_conflict' === $got && null === CSF_Scheduling_Buffer_Override::context( $single['uuid'] ), 'exception cannot waive current restrictions and context clears on failure' );
$family_override = array( 'uuid' => fid( 2 ), 'expected' => 2, 'resource_uuid' => fid( 201 ), 'segment' => 'post', 'minutes' => 0, 'reason' => 'Synthetic reviewed family departure' );
$r = CSF_Scheduling_Calendar::apply( 'buffer_overridden', $family_override, $settings );
$d = json_decode( CSF_Scheduling_Calendar::find( 'details', fid( 2 ) )['body_json'], true );
fc( $d['visit_uuid'] === fid( 100 ) && $d['resource_reservations'][1]['post_minutes'] === 0, 'explicit child buffer trim preserves family linkage' );
$group = json_decode( CSF_Scheduling_Calendar::find( 'details', fid( 100 ) )['body_json'], true ); fc( $group['active_members'] === array( fid( 2 ) ), 'buffer trim never detaches or deletes family members' );
// Whole-group state transitions and one shared expiry; no independent expiry mutation.
$base = $wpdb->rows;
$requested = array( 'uuid' => fid( 800 ), 'expected' => 0, 'same_family' => true, 'items' => array() );
$tomorrow = ( new DateTimeImmutable( 'tomorrow', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d' );
foreach ( array( 801 => array( '10:00', '10:30' ), 802 => array( '10:30', '11:00' ) ) as $id => $times ) { $l = lesson( $id, $times[0], $times[1] ); $l['start'] = $tomorrow . 'T' . $times[0] . '+00:00'; $l['end'] = $tomorrow . 'T' . $times[1] . '+00:00'; $l['state'] = 'requested'; $requested['items'][] = array( 'kind' => 'appointment_saved', 'body' => $l ); }
// This synthetic availability spans the dynamically chosen next-day fixture.
foreach ( $wpdb->rows['f_availability'] as &$row ) { $row['body_json'] = json_encode( array( 'intervals' => array( array( 'start' => '2020-01-01T00:00+00:00', 'end' => '2040-01-01T00:00+00:00' ) ) ) ); } unset( $row );
$n = count( $wpdb->rows['f_allocations'] ); $r = run_family( $requested );
fc( count( $wpdb->rows['f_allocations'] ) === $n && null === $r['items'][0]['notice_kind'], 'requested family creates no reservations or confirmed notices' );
$held = $requested; $held['expected'] = 1; $expiry = ( new DateTimeImmutable( '+5 minutes', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:iP' );
foreach ( $held['items'] as &$item ) { $item['body']['expected'] = 1; $item['body']['state'] = 'held'; $item['body']['hold_expires'] = $expiry; } unset( $item );
$bad = $held; $bad['items'][1]['body']['hold_expires'] = ( new DateTimeImmutable( '+6 minutes', new DateTimeZone( 'UTC' ) ) )->format( 'Y-m-d\TH:iP' ); rejected( $bad, 'visit_state' );
$r = run_family( $held );
$ha = CSF_Scheduling_Calendar::find( 'appointments', fid( 801 ) ); $hb = CSF_Scheduling_Calendar::find( 'appointments', fid( 802 ) );
fc( $ha['hold_expires_utc'] === $hb['hold_expires_utc'] && count( $wpdb->rows['f_allocations'] ) === $n + 4 && null === $r['items'][0]['notice_kind'], 'all held children reserve shared windows with identical expiry and no confirmed notice' );
$bad = $held; $bad['expected'] = 2; foreach ( $bad['items'] as &$item ) { $item['body']['expected'] = 2; $item['body']['hold_expires'] = '2020-01-01T00:00+00:00'; } unset( $item ); rejected( $bad, 'invalid_time' );
$confirmed = $held; $confirmed['expected'] = 2; foreach ( $confirmed['items'] as &$item ) { $item['body']['expected'] = 2; $item['body']['state'] = 'confirmed'; $item['body']['hold_expires'] = null; } unset( $item );
$r = run_family( $confirmed ); fc( $r['items'][0]['notice_kind'] === 'NEW' && $r['items'][1]['notice_kind'] === 'NEW', 'whole-group confirmation creates new-lesson changes for both children' );
$bad = $confirmed; $bad['expected'] = 3; foreach ( $bad['items'] as &$item ) { $item['body']['expected'] = 3; $item['body']['state'] = 'requested'; } unset( $item ); rejected( $bad, 'invalid_transition' );
// Existing independent lessons can be explicitly attached, but never stolen from another visit.
$wpdb->rows = $base;
$attach = array( 'uuid' => fid( 810 ), 'expected' => 0, 'same_family' => true, 'items' => array( array( 'kind' => 'appointment_saved', 'body' => $separate ) ) ); $attach['items'][0]['body']['expected'] = 1;
$r = run_family( $attach ); $linked = json_decode( CSF_Scheduling_Calendar::find( 'details', $separate['uuid'] )['body_json'], true ); fc( $linked['visit_uuid'] === fid( 810 ), 'explicit authorized group save attaches independent lesson' );
$bad = $attach; $bad['uuid'] = fid( 811 ); $bad['items'][0]['body']['expected'] = 2; rejected( $bad, 'invalid_transition' );
$cancel_all = $attach; $cancel_all['expected'] = 1; $cancel_all['items'][0] = array( 'kind' => 'appointment_canceled', 'body' => array_merge( $cancel, array( 'uuid' => $separate['uuid'], 'expected' => 2 ) ) ); run_family( $cancel_all );
$empty = CSF_Scheduling_Family_Visit::read( fid( 810 ) ); fc( $empty['items'] === array() && $empty['expected'] === 2, 'all-canceled visit remains readable without reviving canceled children' );
CSF_Scheduling_Permissions::$denied = $separate['uuid']; $got = null; try { CSF_Scheduling_Family_Visit::read( fid( 810 ) ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'forbidden' === $got, 'empty visit still enforces historical child scope' ); CSF_Scheduling_Permissions::$denied = null;
$reuse = $attach; $reuse['expected'] = 2; $reuse['items'][0]['body'] = lesson( 812, '15:00', '15:30' ); run_family( $reuse );
$d = json_decode( CSF_Scheduling_Calendar::find( 'details', fid( 810 ) )['body_json'], true ); fc( count( $d['all_members'] ) === 2 && $d['active_members'] === array( fid( 812 ) ), 'new lessons reuse empty visit while canceled history is retained' );
$native['lessons'][0]['resources'] = array( fid( 200 ), fid( 201 ) ); $native['visit_state'] = 'held'; $native['visit_expiry'] = substr( $expiry, 0, 16 ); $native['expiry_choice'] = '+00:00';
$parsed = CSF_Scheduling_Family_UI::parse( $native, $p ); fc( $parsed['items'][0]['body']['state'] === 'held' && $parsed['items'][0]['body']['hold_expires'] === $expiry, 'native editor derives common hold state and unambiguous expiry' );
$canceled_details = json_decode( CSF_Scheduling_Calendar::find( 'details', $separate['uuid'] )['body_json'], true ); fc( 'canceled' === $canceled_details['state'], 'cancellation detail/history snapshot agrees with appointment state' );
$advanced = $native; $advanced['visit_state'] = 'confirmed'; $advanced['lessons'][0]['advanced'] = '1'; $advanced['lessons'][0]['participants'] = array( fid( 500 ), fid( 501 ) );
$advanced['lessons'][0]['turns'] = array( array( 'horse' => fid( 300 ), 'rider' => fid( 500 ), 'start' => '2027-01-25T10:00', 'end' => '2027-01-25T10:15', 'start_choice' => '', 'end_choice' => '' ), array( 'horse' => fid( 301 ), 'rider' => fid( 501 ), 'start' => '2027-01-25T10:15', 'end' => '2027-01-25T10:30', 'start_choice' => '', 'end_choice' => '' ) );
$parsed = CSF_Scheduling_Family_UI::parse( $advanced, $p ); $ab = $parsed['items'][0]['body'];
fc( $ab['riders'] === array( fid( 500 ), fid( 501 ) ) && count( $ab['rides'] ) === 2 && $ab['end'] === '2027-01-25T10:30+00:00', 'detailed family parser retains explicit multiple riders/turns and authoritative duration' );
fc( $ab['rides'][0]['end'] === '2027-01-25T10:15+00:00' && $ab['rides'][1]['horse'] === fid( 301 ), 'separate horse turns are not merged or attributed to an assumed rider' );
$advanced['lessons'][0]['participants'][] = array(); $got = null; try { CSF_Scheduling_Family_UI::parse( $advanced, $p ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'invalid_body' === $got, 'nested detailed rider input rejected' );
$before = $wpdb->rows; $handoff = CSF_Scheduling_Family_UI::from_lesson( $single['uuid'], $p );
fc( count( $handoff['lessons'] ) === 2 && $handoff['lessons'][0]['uuid'] === $single['uuid'] && $handoff['lessons'][1]['expected'] === '0' && $handoff['lessons'][1]['start'] === '2027-01-25T14:30' && $wpdb->rows === $before, 'ordinary lesson handoff proposes next item without writing or moving the existing lesson' );
CSF_Scheduling_Permissions::$denied = $single['uuid']; $got = null; try { CSF_Scheduling_Family_UI::from_lesson( $single['uuid'], $p ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } fc( 'forbidden' === $got, 'ordinary-to-family handoff enforces existing lesson scope' ); CSF_Scheduling_Permissions::$denied = null;
echo $checks . " family command checks passed with in-memory database double\n";

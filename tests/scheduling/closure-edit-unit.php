<?php
/** Real resource-hours writer with synthetic document/DB boundary doubles. */
define( 'ABSPATH', '/synthetic/' ); define( 'ARRAY_A', 'ARRAY_A' );
function esc_html( $v ) { return $v; } function wp_generate_uuid4() { static $n = 50; return cid( ++$n ); }
function cid( $n ) { return sprintf( '00000000-0000-4000-8000-%012d', $n ); }
class CSF_Scheduling_Store { public static $active = true; public static function in_calendar_transaction() { return self::$active; } }
class CSF_Scheduling_Access { public static $allowed = true; public static function can( $cap ) { return self::$allowed; } }
class CSF_Scheduling_Schema { public static function table( $n ) { return 'f_' . $n; } }
class CSF_Scheduling_Catalog { public static function text( $v, $limit ) { return is_string( $v ) && strlen( $v ) <= $limit; } }
class CSF_Scheduling_Calendar {
 public static $rows = array();
 public static function require( $ok, $reason = 'invalid_body' ) { if ( ! $ok ) { throw new RuntimeException( $reason ); } }
 public static function keys( $b, $keys ) { self::require( is_array( $b ) && ! array_diff( array_keys( $b ), $keys ) && ! array_diff( $keys, array_keys( $b ) ) ); }
 public static function find( $table, $uuid ) { return self::$rows[$table][$uuid] ?? null; }
 public static function document( $table, $uuid, $entity, $body, $expected ) { $old = self::find( $table, $uuid ); self::require( $expected === ( $old ? $old['revision'] : 0 ), 'stale_appointment' ); self::$rows[$table][$uuid] = array( 'uuid' => $uuid, 'entity_uuid' => $entity, 'revision' => $expected + 1, 'body_json' => json_encode( $body ) ); return $expected + 1; }
}
class ClosureDB {
 public $last_error = ''; public $sql = ''; public $fail = false;
 public function prepare( $sql, ...$args ) { return $sql; }
 public function get_results( $sql, $mode ) { $this->sql = $sql; $this->last_error = $this->fail ? 'synthetic failure' : ''; return array( array( 'start_utc' => '2030-01-07 10:30:00', 'end_utc' => '2030-01-07 11:00:00', 'reserve_start' => '2030-01-07 10:00:00', 'reserve_end' => '2030-01-07 11:15:00' ) ); }
}
$root = dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/';
require $root . 'class-csf-scheduling-audit.php'; require $root . 'class-csf-scheduling-time.php'; require $root . 'class-csf-scheduling-resource-hours.php';
$wpdb = new ClosureDB(); $n = 0;
function cc( $ok, $label ) { global $n; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$n; }
$resource = cid( 1 ); $closure = cid( 2 );
CSF_Scheduling_Calendar::$rows['resources'][$resource] = array( 'uuid' => $resource, 'id' => 1, 'kind' => 'arena' );
$previous = array( 'target_uuid' => $resource, 'start' => '2030-01-07T10:00+00:00', 'end' => '2030-01-07T10:30+00:00', 'reason' => 'unavailable', 'note' => 'Original maintenance', 'closure_kind' => 'resource_closure', 'recurrence' => array( 'timezone' => 'UTC', 'from' => '2030-01-07', 'until' => '2030-02-04', 'weekday' => 1, 'start' => '10:00', 'end' => '10:30' ), 'reopen_dates' => array( '2030-01-14' ) );
CSF_Scheduling_Calendar::document( 'restrictions', $closure, $resource, $previous, 0 );
$body = array( 'uuid' => $closure, 'expected' => 1, 'impact_acknowledged' => true, 'data' => array( 'target_uuid' => $resource, 'start' => '2030-01-07T10:15+00:00', 'end' => '2030-01-07T10:30+00:00', 'category' => 'unavailable', 'note' => 'Reviewed maintenance correction', 'repeat_until' => '2030-02-04' ) );
$settings = array( 'policy_json' => '{"timezone":"UTC"}' );
function closure_reject( $body, $reason ) { global $settings; $before = CSF_Scheduling_Calendar::$rows; $got = null; try { CSF_Scheduling_Resource_Hours::apply( 'resource_closure_edited', $body, $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } cc( $got === $reason, 'expected ' . $reason . ', got ' . var_export( $got, true ) ); cc( $before === CSF_Scheduling_Calendar::$rows, 'rejected edit leaves documents unchanged' ); }
$bad = $body; $bad['expected'] = 0; closure_reject( $bad, 'stale_appointment' );
$bad = $body; $bad['data']['target_uuid'] = cid( 9 ); closure_reject( $bad, 'invalid_transition' );
$bad = $body; $bad['data']['start'] = '2000-01-01T10:00+00:00'; closure_reject( $bad, 'closure_edit_future_only' );
$bad = $body; $bad['impact_acknowledged'] = false; closure_reject( $bad, 'resource_hours_invalid' );
$wpdb->fail = true; closure_reject( $body, 'write_failed' ); $wpdb->fail = false;
CSF_Scheduling_Access::$allowed = false; closure_reject( $body, 'forbidden' ); CSF_Scheduling_Access::$allowed = true;
$result = CSF_Scheduling_Resource_Hours::apply( 'resource_closure_edited', $body, $settings );
cc( $result['entity_revision'] === 2 && $result['affected_appointments'] === 1, 'future edit counts a buffer-only overlap' );
cc( str_contains( $wpdb->sql, 'FROM `f_allocations`' ) && ! str_contains( $wpdb->sql, 'f_slots' ), 'impact queries non-horse resource allocations, not horse slots' );
$after = json_decode( CSF_Scheduling_Calendar::$rows['restrictions'][$closure]['body_json'], true );
cc( $after['reopen_dates'] === $previous['reopen_dates'], 'editing series preserves reopened dates' );
$history = array_values( CSF_Scheduling_Calendar::$rows['history'] ); $h = json_decode( $history[0]['body_json'], true );
cc( $h['before'] === $previous && $h['after'] === $after && $h['kind'] === 'resource_closure_edited', 'exact original closure and replacement retained in history' );
$bad = $body; $bad['expected'] = 2; $old = CSF_Scheduling_Calendar::$rows['restrictions'][$closure]; $past = $after; $past['start'] = '2000-01-03T10:00+00:00'; $past['recurrence']['from'] = '2000-01-03'; CSF_Scheduling_Calendar::$rows['restrictions'][$closure]['body_json'] = json_encode( $past );
$split = CSF_Scheduling_Resource_Hours::apply( 'resource_closure_edited', $bad, $settings );
$retained = json_decode( CSF_Scheduling_Calendar::$rows['restrictions'][$closure]['body_json'], true );
$replacement = json_decode( CSF_Scheduling_Calendar::$rows['restrictions'][$split['replacement_uuid']]['body_json'], true );
cc( $retained['start'] === $past['start'] && $retained['recurrence'] === $past['recurrence'] && $retained['reopen_dates'] === $past['reopen_dates'], 'original recurrence and exceptions retained' );
cc( $retained['superseded_at'] === '2030-01-07T00:00+00:00' && $retained['superseded_by'] === $split['replacement_uuid'] && $replacement['supersedes_uuid'] === $closure, 'forward split linked at future local midnight' );
$blocks = static fn( $d, $a, $z ) => CSF_Scheduling_Resource_Hours::blocks( $d, new DateTimeImmutable( $a ), new DateTimeImmutable( $z ) );
cc( $blocks( $retained, '2029-12-31T10:15+00:00', '2029-12-31T10:30+00:00' ), 'old occurrence remains protected before cutoff' );
cc( ! $blocks( $retained, '2030-01-07T10:15+00:00', '2030-01-07T10:30+00:00' ) && $blocks( $replacement, '2030-01-07T10:15+00:00', '2030-01-07T10:30+00:00' ), 'replacement alone governs future occurrence' );
cc( ! $blocks( $replacement, '2030-01-14T10:15+00:00', '2030-01-14T10:30+00:00' ), 'reopened date remains reopened after split' );
$bad['expected'] = 3; closure_reject( $bad, 'invalid_transition' );
$last = end( CSF_Scheduling_Calendar::$rows['history'] ); $audit = json_decode( $last['body_json'], true );
cc( $audit['before'] === $past && $audit['replacement']['data'] === $replacement, 'audit keeps exact before and full replacement' );
$span = array( 'start'=>'2030-01-06T00:00+00:00', 'end'=>'2030-01-09T00:00+00:00', 'superseded_at'=>'2030-01-07T00:00+00:00' );
cc( $blocks( $span, '2030-01-06T23:45+00:00', '2030-01-07T00:00+00:00' ) && ! $blocks( $span, '2030-01-07T00:00+00:00', '2030-01-07T00:15+00:00' ), 'multi-day closure clips exactly at cutoff' );
CSF_Scheduling_Calendar::$rows['restrictions'][$closure] = $old;
CSF_Scheduling_Calendar::$rows['resources'][$resource]['kind'] = 'horse'; closure_reject( $bad, 'forbidden' );
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-resource-ui.php';
$stored = '2027-05-12T14:00+00:00'; $local = CSF_Scheduling_Resource_UI::stored_time( $stored, 'America/Los_Angeles' );
cc( $local->format( 'Y-m-d\TH:iP' ) === '2027-05-12T07:00-07:00' && $local->getTimestamp() === strtotime( $stored ), 'closure editor converts stored instant after timezone change without moving it' );
$fold = CSF_Scheduling_Resource_UI::stored_time( '2027-11-07T09:30+00:00', 'America/Los_Angeles' );
cc( $fold->format( 'Y-m-d\TH:iP' ) === '2027-11-07T01:30-08:00', 'closure editor retains the exact repeated-hour occurrence' );
echo $n . " isolated closure edit/impact checks passed\n";

<?php
/** Real lifecycle code, isolated in-memory database double; not an isolation test. */
define( 'ABSPATH', '/synthetic-unit-only/' );
define( 'ARRAY_A', 'ARRAY_A' );
function wp_specialchars_decode( $v, $flags = ENT_NOQUOTES ) { return html_entity_decode( $v, $flags ); }
function wp_strip_all_tags( $v ) { return strip_tags( $v ); }
function esc_html( $value ) { return $value; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function get_current_user_id() { return 1; }
function wp_generate_uuid4() { static $id = 10; return sprintf( '00000000-0000-4000-8000-%012d', ++$id ); }
class CSF_Scheduling_Store { public static $active = true; public static function in_calendar_transaction() { return self::$active; } }
class CSF_Scheduling_Schema { public static function table( $name ) { return 'fixture_' . $name; } public static function settings() { return array( 'revision' => 1 ); } }
class CSF_Scheduling_Audit {
	public static function uuid( $value ) { return is_string( $value ) && preg_match( '/^[a-f0-9-]{36}$/D', $value ); }
	public static function now() { return gmdate( 'Y-m-d H:i:s' ); }
}
class CSF_Scheduling_Access { public static $allowed = true; public static function can( $cap ) { return self::$allowed; } }
class CSF_Scheduling_Permissions { public static function authorize( $kind, $body ) { return CSF_Scheduling_Access::$allowed; } }
class CSF_Scheduling_Resource_Hours { const COMMANDS = array(); public static function covers( $d, $a, $z, $p ) { return true; } }
class LifecycleDatabase {
	public $rows = array();
	public $last_error = '';
	public $insert_id = 0;
	public $future = false;
	public $fail = false;
	public function prepare( $sql, ...$args ) {
		foreach ( $args as $arg ) { $sql = preg_replace( '/%[sd]/', "'" . $arg . "'", $sql, 1 ); }
		return $sql;
	}
	public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
	public function get_var( $sql ) { $this->last_error = $this->fail ? 'synthetic read error' : ''; return $this->future ? '1' : null; }
	public function get_row( $sql, $format ) {
		preg_match( '/FROM `([^`]+)` WHERE uuid=\x27([^\x27]+)\x27/', $sql, $m );
		return $this->rows[ $m[1] ?? '' ][ $m[2] ?? '' ] ?? null;
	}
	public function get_results( $sql, $format ) {
		preg_match( '/FROM `([^`]+)`/', $sql, $m );
		return array_values( $this->rows[ $m[1] ] ?? array() );
	}
	public function update( $table, $data, $where ) {
		foreach ( $this->rows[ $table ] as $uuid => $row ) {
			if ( $row['id'] === $where['id'] ) { $this->rows[ $table ][ $uuid ] = array_merge( $row, $data ); return 1; }
		}
		return 0;
	}
	public function insert( $table, $data ) {
		$data['id'] = ++$this->insert_id + 100;
		$this->rows[ $table ][ $data['uuid'] ] = $data;
		return 1;
	}
}
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-calendar.php';
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-time.php';

require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-ui.php';
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-conflict.php';
$checks = 0;
function ar_check( $ok, $message ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $message ); } ++$checks; }
function ar_expect( $reason, $run ) { $got = null; try { $run(); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } ar_check( $reason === $got, 'Expected ' . var_export( $reason, true ) . ', got ' . var_export( $got, true ) ); }
$uuid = '00000000-0000-4000-8000-000000000001';
$rider = '00000000-0000-4000-8000-000000000002';
$resource = '00000000-0000-4000-8000-000000000003';
$data = array( 'label' => 'Synthetic school horse', 'source' => 'school', 'resource_uuid' => $resource, 'suitable_services' => array( 'lesson' ), 'suitable_riders' => array(), 'training_enrolled' => false );
$policy = array( 'timezone' => 'UTC', 'services' => array( 'lesson' => array() ), 'training_horse_cap' => 1, 'workload' => array( 'daily_minutes' => 120, 'consecutive_minutes' => 60, 'break_minutes' => 15 ) );
$settings = array( 'policy_json' => json_encode( $policy ) );
$wpdb = new LifecycleDatabase();
$wpdb->rows['fixture_resources'][$resource] = array( 'uuid' => $resource, 'id' => 3, 'kind' => 'horse', 'label' => 'Synthetic resource', 'active' => 1 );
$body = array( 'uuid' => $uuid, 'expected' => 0, 'data' => $data );
ar_check( CSF_Scheduling_UI::editable( 'horse_saved', $body ), 'Legacy form accepted' );
CSF_Scheduling_Calendar::apply( 'horse_saved', $body, $settings );
$saved = json_decode( $wpdb->rows['fixture_horses'][$uuid]['body_json'], true );
ar_check( false === $saved['any_rider'], 'Legacy save stays restricted' );
ar_check( ! CSF_Scheduling_Calendar::rider_suitable( $saved, $rider ), 'Unknown rider blocked by default' );
$body['expected'] = 1;
foreach ( array( 'true', 1, null, array() ) as $invalid ) {
 $body['data']['any_rider'] = $invalid;
 ar_check( ! CSF_Scheduling_UI::editable( 'horse_saved', $body ), 'Malformed option rejected by presentation' );
 ar_expect( 'invalid_body', fn() => CSF_Scheduling_Calendar::apply( 'horse_saved', $body, $settings ) );
}
$body['data']['any_rider'] = true;
ar_check( CSF_Scheduling_UI::editable( 'horse_saved', $body ), 'New form accepted' );
CSF_Scheduling_Calendar::apply( 'horse_saved', $body, $settings );
$saved = json_decode( $wpdb->rows['fixture_horses'][$uuid]['body_json'], true );
ar_check( true === $saved['any_rider'] && array() === $saved['suitable_riders'], 'Any rider persists without a list' );
$history = array_values( $wpdb->rows['fixture_history'] );
$record = json_decode( end( $history )['body_json'], true );
ar_check( false === $record['before']['any_rider'] && true === $record['after']['any_rider'], 'Change audited before and after' );
// Rider is introduced AFTER the school horse was saved; no horse update follows.
$wpdb->rows['fixture_riders'][$rider] = array( 'uuid' => $rider, 'id' => 2, 'body_json' => '{}' );
$wpdb->rows['fixture_availability'][$resource] = array( 'uuid' => $resource, 'body_json' => '{}' );
$start = new DateTimeImmutable( '2027-01-01T09:00:00+00:00' ); $end = $start->modify( '+30 minutes' );
$booking = array( 'state' => 'requested', 'service_type' => 'lesson', 'riders' => array( $rider ), 'rides' => array( array( 'horse' => $uuid, 'rider' => $rider, 'start' => '2027-01-01T09:00+00:00', 'end' => '2027-01-01T09:30+00:00' ) ) );
$service = array( 'requires_travel' => false, 'horse_source' => 'school' );
$validate = new ReflectionMethod( CSF_Scheduling_Calendar::class, 'validate_allocations' );
$run = fn() => $validate->invoke( null, $booking, $service, $policy, $start, $end, array(), 0 );
ar_expect( null, $run );
$original = $wpdb->rows['fixture_horses'][$uuid]['body_json'];
foreach ( array( array( 'any_rider', false, 'horse_unsafe' ), array( 'archived', true, 'horse_archived' ), array( 'suitable_services', array(), 'horse_unsafe' ) ) as list( $key, $value, $reason ) ) {
 $modified = $saved; $modified[$key] = $value; $wpdb->rows['fixture_horses'][$uuid]['body_json'] = json_encode( $modified ); ar_expect( $reason, $run );
}
$wpdb->rows['fixture_horses'][$uuid]['body_json'] = $original;
$limited = $policy; $limited['workload']['consecutive_minutes'] = 15;
ar_expect( 'welfare_conflict', fn() => $validate->invoke( null, $booking, $service, $limited, $start, $end, array(), 0 ) );
unset( $wpdb->rows['fixture_availability'][$resource] );
ar_expect( 'outside_hours', $run );
$wpdb->rows['fixture_availability'][$resource] = array( 'uuid' => $resource, 'body_json' => '{}' );
$wpdb->rows['fixture_restrictions']['block'] = array( 'uuid' => '00000000-0000-4000-8000-000000000099', 'entity_uuid' => $uuid, 'body_json' => json_encode( array( 'start' => '2027-01-01T08:00+00:00', 'end' => '2027-01-01T10:00+00:00' ) ) );
ar_expect( 'welfare_conflict', $run );
ar_check( CSF_Scheduling_Calendar::rider_suitable( array( 'suitable_riders' => array( $rider ) ), $rider ), 'Existing explicit list remains valid' );
ar_check( ! CSF_Scheduling_Calendar::rider_suitable( array( 'suitable_riders' => array(), 'any_rider' => 'true' ), $rider ), 'Stored malformed value cannot broaden eligibility' );
echo $checks . " any-rider checks passed\n";

function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function wp_nonce_field( $v ) {}
function checked( $a, $b, $echo = true ) { return $a === $b ? ' checked="checked"' : ''; }
function selected( $a, $b, $echo = true ) { return $a === $b ? ' selected="selected"' : ''; }
$ctx = array( 'school_resources' => array( $resource => 'Synthetic resource' ), 'services' => array( 'lesson' => 'Lesson' ), 'riders' => array( $rider => 'New rider' ) );
foreach ( array( false, true ) as $enabled ) {
 $form = array( 'uuid' => $uuid, 'expected' => 0, 'data' => $data + array( 'any_rider' => $enabled ) );
 ob_start(); CSF_Scheduling_UI::setup_form( 'horse_saved', $form, $ctx ); $html = ob_get_clean();
 ar_check( str_contains( $html, 'Any rider — no individual rider list required' ), 'Option rendered' );
 ar_check( (bool) preg_match( '/data-field="any_rider"[^>]*checked="checked"/', $html ) === $enabled, 'Rendered selection matches stored value' );
}
echo "Horse form rendering passed for both modes\n";

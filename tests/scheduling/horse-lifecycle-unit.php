<?php
/** Real lifecycle code, isolated in-memory database double; not an isolation test. */
define( 'ABSPATH', '/synthetic-unit-only/' );
define( 'ARRAY_A', 'ARRAY_A' );
function esc_html( $value ) { return $value; }
function sanitize_text_field( $value ) { return strip_tags( $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function get_current_user_id() { return 1; }
function wp_generate_uuid4() { static $id = 10; return sprintf( '00000000-0000-4000-8000-%012d', ++$id ); }
class CSF_Scheduling_Store { public static $active = true; public static function in_calendar_transaction() { return self::$active; } }
class CSF_Scheduling_Schema { public static function table( $name ) { return 'fixture_' . $name; } }
class CSF_Scheduling_Audit {
	public static function uuid( $value ) { return is_string( $value ) && preg_match( '/^[a-f0-9-]{36}$/D', $value ); }
	public static function now() { return gmdate( 'Y-m-d H:i:s' ); }
}
class CSF_Scheduling_Access { public static $allowed = true; public static function can( $cap ) { return self::$allowed; } }
class CSF_Scheduling_Permissions { public static function authorize( $kind, $body ) { return CSF_Scheduling_Access::$allowed; } }
class CSF_Scheduling_Resource_Hours { const COMMANDS = array(); }
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
$uuid = '00000000-0000-4000-8000-000000000001';
$data = array( 'label' => 'Synthetic horse', 'source' => 'client', 'resource_uuid' => null, 'suitable_services' => array(), 'suitable_riders' => array(), 'training_enrolled' => false );
$policy = array( 'timezone' => 'UTC', 'services' => array(), 'training_horse_cap' => 1 );
$wpdb = new LifecycleDatabase();
$wpdb->rows['fixture_horses'][ $uuid ] = array( 'id' => 1, 'uuid' => $uuid, 'entity_uuid' => $uuid, 'revision' => 1, 'body_json' => json_encode( $data ) );
$body = array( 'uuid' => $uuid, 'expected' => 1, 'archived' => true, 'reason' => 'Synthetic archive review', 'confirm_label' => $data['label'] );
$settings = array( 'policy_json' => json_encode( $policy ) );
$checks = 0;
function life_check( $ok, $message ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $message ); } ++$checks; }
function life_reject( $body, $settings, $reason ) {
	global $wpdb;
	$before = $wpdb->rows;
	$got = null;
	try { CSF_Scheduling_Calendar::apply( 'horse_status_saved', $body, $settings ); } catch ( RuntimeException $error ) { $got = $error->getMessage(); }
	life_check( $got === $reason, 'Expected ' . $reason . ', got ' . var_export( $got, true ) );
	life_check( $wpdb->rows === $before, 'Rejected lifecycle command changed rows' );
}
CSF_Scheduling_Access::$allowed = false;
life_reject( $body, $settings, 'forbidden' );
CSF_Scheduling_Access::$allowed = true;
CSF_Scheduling_Store::$active = false;
life_reject( $body, $settings, 'forbidden' );
CSF_Scheduling_Store::$active = true;
$bad = $body; $bad['confirm_label'] = 'Wrong horse'; life_reject( $bad, $settings, 'invalid_transition' );
$bad = $body; $bad['expected'] = 0; life_reject( $bad, $settings, 'stale_appointment' );
$bad = $body; $bad['reason'] = ''; life_reject( $bad, $settings, 'invalid_body' );
$wpdb->future = true; life_reject( $body, $settings, 'horse_future_bookings' ); $wpdb->future = false;
$wpdb->fail = true; life_reject( $body, $settings, 'write_failed' ); $wpdb->fail = false;
$result = CSF_Scheduling_Calendar::apply( 'horse_status_saved', $body, $settings );
$archived = json_decode( $wpdb->rows['fixture_horses'][ $uuid ]['body_json'], true );
life_check( $result['archived'] && $result['entity_revision'] === 2, 'Archive result' );
life_check( $archived === $data + array( 'archived' => true ), 'Archive preserves all horse data' );
$history = array_values( $wpdb->rows['fixture_history'] );
$record = json_decode( $history[0]['body_json'], true );
life_check( $record['before'] === $data && $record['after'] === $archived && $record['reason'] === $body['reason'], 'Archive before/after/reason retained' );
$bad = $body; $bad['expected'] = 2; life_reject( $bad, $settings, 'invalid_transition' );
// Actual booking validation must reject archived horses before suitability/workload.
$validate = new ReflectionMethod( CSF_Scheduling_Calendar::class, 'validate_allocations' );
$start = new DateTimeImmutable( '2027-01-01T09:00:00+00:00' ); $end = $start->modify( '+30 minutes' );
$booking = array( 'state' => 'confirmed', 'riders' => array(), 'rides' => array( array( 'horse' => $uuid, 'rider' => null, 'start' => '2027-01-01T09:00+00:00', 'end' => '2027-01-01T09:30+00:00' ) ) );
$got = null;
try { $validate->invoke( null, $booking, array( 'requires_travel' => false ), array(), $start, $end, array(), 0 ); } catch ( RuntimeException $error ) { $got = $error->getMessage(); }
life_check( $got === 'horse_archived', 'Actual booking gate rejects archived horse' );
$restore = $body; $restore['expected'] = 2; $restore['archived'] = false;
$saved_row = $wpdb->rows['fixture_horses'][ $uuid ];
$unsafe = $archived; $unsafe['suitable_services'] = array( 'missing-service' );
$wpdb->rows['fixture_horses'][ $uuid ]['body_json'] = json_encode( $unsafe );
life_reject( $restore, $settings, 'horse_unsafe' );
$unsafe = $archived; $unsafe['suitable_riders'] = array( '00000000-0000-4000-8000-000000000099' );
$wpdb->rows['fixture_horses'][ $uuid ]['body_json'] = json_encode( $unsafe );
life_reject( $restore, $settings, 'horse_unsafe' );
$unsafe = $archived; $unsafe['source'] = 'school'; $unsafe['resource_uuid'] = '00000000-0000-4000-8000-000000000099';
$wpdb->rows['fixture_horses'][ $uuid ]['body_json'] = json_encode( $unsafe );
life_reject( $restore, $settings, 'horse_unsafe' );
$unsafe = $archived; $unsafe['training_enrolled'] = true;
$wpdb->rows['fixture_horses'][ $uuid ]['body_json'] = json_encode( $unsafe );
$limited = $policy; $limited['training_horse_cap'] = 0;
life_reject( $restore, array( 'policy_json' => json_encode( $limited ) ), 'capacity' );
$wpdb->rows['fixture_horses'][ $uuid ] = $saved_row;
$restriction = array( 'start' => gmdate( 'Y-m-d\TH:i+00:00', time() - 86400 ), 'end' => gmdate( 'Y-m-d\TH:i+00:00', time() + 86400 ), 'reason' => 'welfare' );
$wpdb->rows['fixture_restrictions']['block'] = array( 'uuid' => '00000000-0000-4000-8000-000000000098', 'entity_uuid' => $uuid, 'body_json' => json_encode( $restriction ) );
life_reject( $restore, $settings, 'welfare_conflict' );
unset( $wpdb->rows['fixture_restrictions'] );
$result = CSF_Scheduling_Calendar::apply( 'horse_status_saved', $restore, $settings );
life_check( ! $result['archived'] && $result['entity_revision'] === 3, 'Restore result' );
life_check( json_decode( $wpdb->rows['fixture_horses'][ $uuid ]['body_json'], true ) === $data + array( 'archived' => false ), 'Restore preserves suitability and classification' );
life_check( count( $wpdb->rows['fixture_history'] ) === 2, 'Restore adds history without removing archive history' );
echo $checks . " isolated horse lifecycle checks passed\n";

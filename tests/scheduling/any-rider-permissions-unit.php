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
class CSF_Scheduling_Access { public static $allowed = array(); public static function can( $cap ) { return in_array( $cap, self::$allowed, true ); } }
class CSF_Scheduling_Identity { const COMMANDS = array(); }
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
	public function get_var( $sql ) { return $this->get_row( $sql, ARRAY_A )['body_json'] ?? null; }
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
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-permissions.php';
$wpdb = new LifecycleDatabase();
$uuid = '00000000-0000-4000-8000-000000000001';
$data = array( 'label' => 'Synthetic horse', 'source' => 'school', 'resource_uuid' => null, 'suitable_services' => array(), 'suitable_riders' => array(), 'training_enrolled' => false );
$wpdb->rows['fixture_horses'][$uuid] = array( 'body_json' => json_encode( $data ) );
$body = array( 'uuid' => $uuid, 'expected' => 1, 'data' => $data + array( 'any_rider' => true ) );
$checks = 0;
function ap_check( $ok ) { global $checks; if ( ! $ok ) { throw new RuntimeException( 'permission regression' ); } ++$checks; }
CSF_Scheduling_Access::$allowed = array( 'resource_manage' );
ap_check( ! CSF_Scheduling_Permissions::authorize( 'horse_saved', $body ) );
$body['data']['any_rider'] = false;
ap_check( CSF_Scheduling_Permissions::authorize( 'horse_saved', $body ) );
CSF_Scheduling_Access::$allowed = array( 'suitability_manage' );
$body['data']['any_rider'] = true;
ap_check( CSF_Scheduling_Permissions::authorize( 'horse_saved', $body ) );
$wpdb->rows['fixture_horses'][$uuid]['body_json'] = json_encode( $body['data'] );
CSF_Scheduling_Access::$allowed = array( 'resource_manage' );
ap_check( CSF_Scheduling_Permissions::authorize( 'horse_saved', $body ) );
$body['data']['any_rider'] = false;
ap_check( ! CSF_Scheduling_Permissions::authorize( 'horse_saved', $body ) );
echo $checks . " any-rider authorization checks passed\n";


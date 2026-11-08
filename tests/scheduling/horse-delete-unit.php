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
	public $future = false; public $dependency = ''; public $fail_delete = false;
	public $fail = false;
	public function prepare( $sql, ...$args ) {
		foreach ( $args as $arg ) { $sql = preg_replace( '/%[sd]/', "'" . $arg . "'", $sql, 1 ); }
		return $sql;
	}
	public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
	public function get_var( $sql ) { $this->last_error = $this->fail ? 'synthetic read error' : ''; return $this->future || ( $this->dependency && str_contains( $sql, $this->dependency ) ) ? '1' : null; }
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
	public function delete( $table, $where ) { if ( $this->fail_delete ) { return false; } foreach ( $this->rows[$table] ?? array() as $uuid => $row ) { if ( ! array_diff_assoc( $where, $row ) ) { unset( $this->rows[$table][$uuid] ); return 1; } } return 0; }
	public function insert( $table, $data ) {
		$data['id'] = ++$this->insert_id + 100;
		$this->rows[ $table ][ $data['uuid'] ] = $data;
		return 1;
	}
}
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-calendar.php';
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-time.php';
$uuid = '00000000-0000-4000-8000-000000000001';
$data = array( 'label' => 'Synthetic unused mistake', 'source' => 'client', 'resource_uuid' => null, 'suitable_services' => array(), 'suitable_riders' => array(), 'training_enrolled' => false );
$wpdb = new LifecycleDatabase(); $wpdb->rows['fixture_horses'][$uuid] = array( 'id' => 1, 'uuid' => $uuid, 'entity_uuid' => $uuid, 'revision' => 1, 'body_json' => json_encode( $data ) );
$body = array( 'uuid' => $uuid, 'expected' => 1, 'reason' => 'Synthetic duplicate entry', 'confirm_label' => $data['label'], 'irreversible' => true );
$settings = array( 'policy_json' => '{}' ); $checks = 0;
function hd( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$checks; }
function hd_run( $body ) { global $wpdb, $settings; $snapshot = $wpdb->rows; try { return CSF_Scheduling_Calendar::apply( 'horse_deleted', $body, $settings ); } catch ( Throwable $e ) { $wpdb->rows = $snapshot; throw $e; } }
function hd_reject( $body, $reason ) { global $wpdb; $before = $wpdb->rows; $got = null; try { hd_run( $body ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } hd( $got === $reason, 'Expected ' . $reason . ', got ' . ( $got ?? 'success' ) ); hd( $before === $wpdb->rows, 'double rollback preserves all rows' ); }
CSF_Scheduling_Access::$allowed = false; hd_reject( $body, 'forbidden' ); CSF_Scheduling_Access::$allowed = true;
CSF_Scheduling_Store::$active = false; hd_reject( $body, 'forbidden' ); CSF_Scheduling_Store::$active = true;
$bad = $body; $bad['irreversible'] = false; hd_reject( $bad, 'invalid_body' );
$bad = $body; $bad['confirm_label'] = 'Other horse'; hd_reject( $bad, 'invalid_transition' );
$bad = $body; $bad['expected'] = 0; hd_reject( $bad, 'stale_appointment' );
$bad = $body; $bad['reason'] = ''; hd_reject( $bad, 'invalid_body' );
foreach ( array( 'fixture_details', 'fixture_history', 'fixture_restrictions', 'fixture_slots', "<>'horse_saved'" ) as $table ) { $wpdb->dependency = $table; hd_reject( $body, 'horse_delete_in_use' ); } $wpdb->dependency = '';
$wpdb->fail = true; hd_reject( $body, 'write_failed' ); $wpdb->fail = false;
$wpdb->fail_delete = true; hd_reject( $body, 'write_failed' ); $wpdb->fail_delete = false;
$wpdb->rows['fixture_horses'][$uuid]['body_json'] = json_encode( $data + array( 'archived' => true ) ); hd_reject( $body, 'horse_delete_in_use' ); $wpdb->rows['fixture_horses'][$uuid]['body_json'] = json_encode( $data );
$wpdb->rows['fixture_history']['setup'] = array( 'id' => 99, 'uuid' => 'setup', 'entity_uuid' => $uuid, 'body_json' => json_encode( array( 'kind' => 'horse_saved', 'before' => null, 'after' => $data ) ) );
$result = hd_run( $body ); hd( $result['deleted'] && ! isset( $wpdb->rows['fixture_horses'][$uuid] ), 'unused roster row removed only after all guards' );
$tombstone = json_decode( $wpdb->rows['fixture_details'][$uuid]['body_json'], true ); hd( $tombstone['record_type'] === 'horse_tombstone' && $tombstone['label'] === $data['label'], 'named UUID tombstone retained' );
hd( isset( $wpdb->rows['fixture_history']['setup'] ) && count( $wpdb->rows['fixture_history'] ) === 2, 'setup history preserved and deletion history appended' );
$got = null; try { CSF_Scheduling_Calendar::apply( 'horse_saved', array( 'uuid' => $uuid, 'expected' => 0, 'data' => $data ), $settings ); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } hd( $got === 'horse_deleted', 'deleted UUID cannot be silently recreated' );
hd_reject( $body, 'not_found' );
echo $checks . " isolated unused-horse deletion checks passed
";

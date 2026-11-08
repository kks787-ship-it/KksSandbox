<?php
/** Isolated guard tests; no WordPress bootstrap, database, network or writes. */
define( 'ABSPATH', '/synthetic-unit-only/' );
define( 'ARRAY_A', 'ARRAY_A' );
function esc_html( $value ) { return $value; }
class CSF_Scheduling_Store {
	public static $active = true;
	public static function in_calendar_transaction() { return self::$active; }
}
class CSF_Scheduling_Schema {
	public static function table( $name ) { return 'fixture_' . $name; }
}
class CSF_Scheduling_Audit {
	public static function uuid( $value ) { return is_string( $value ) && preg_match( '/^[a-f0-9-]{36}$/D', $value ); }
}
class HorseCorrectionDatabase {
	public $last_error = '';
	public $dependency = null;
	public $failure = null;
	public $queries = array();
	public function prepare( $sql, ...$args ) {
		foreach ( $args as $arg ) {
			$sql = preg_replace( '/%[sd]/', "'" . $arg . "'", $sql, 1 );
		}
		return $sql;
	}
	public function esc_like( $value ) { return addcslashes( $value, '_%\\' ); }
	public function get_var( $sql ) {
		$this->queries[] = $sql;
		$this->last_error = $this->failure && str_contains( $sql, $this->failure ) ? 'synthetic read failure' : '';
		return $this->dependency && str_contains( $sql, $this->dependency ) ? '42' : null;
	}
	public function get_row( $sql, $format ) {
		$this->queries[] = $sql;
		return array( 'id' => 7, 'kind' => 'horse' );
	}
}
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-calendar.php';
$guard = new ReflectionMethod( CSF_Scheduling_Calendar::class, 'unused_horse_mapping' );
$uuid = '00000000-0000-4000-8000-000000000001';
$resource = '00000000-0000-4000-8000-000000000002';
$horse = array( 'uuid' => $uuid, 'id' => 5 );
$client = array( 'source' => 'client', 'resource_uuid' => null );
$school = array( 'source' => 'school', 'resource_uuid' => $resource );
$checks = 0;
function hc_expect( $expected, $callback ) {
	global $checks;
	$reason = null;
	try { $callback(); } catch ( RuntimeException $error ) { $reason = $error->getMessage(); }
	if ( $expected !== $reason ) { throw new RuntimeException( 'Expected ' . var_export( $expected, true ) . ', got ' . var_export( $reason, true ) ); }
	++$checks;
}
foreach ( array( array( $client, $school ), array( $school, $client ) ) as list( $before, $after ) ) {
	$wpdb = new HorseCorrectionDatabase();
	$run = static fn() => $guard->invoke( null, $horse, $before, $after );
	hc_expect( null, $run );
	foreach ( array( 'fixture_details', 'fixture_history', 'fixture_restrictions', 'fixture_slots', 'fixture_allocations' ) as $table ) {
		$wpdb = new HorseCorrectionDatabase();
		$wpdb->dependency = $table;
		hc_expect( 'horse_mapping_in_use', $run );
		$wpdb = new HorseCorrectionDatabase();
		$wpdb->failure = $table;
		hc_expect( 'write_failed', $run );
	}
	$wpdb = new HorseCorrectionDatabase();
	CSF_Scheduling_Store::$active = false;
	hc_expect( 'forbidden', $run );
	if ( $wpdb->queries ) { throw new RuntimeException( 'Unlocked call touched database' ); }
	++$checks;
	CSF_Scheduling_Store::$active = true;
}
echo $checks . " isolated horse mapping guard checks passed\n";

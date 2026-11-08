<?php
/** Pure date/grouping test with a scoped-reader double; no browser/access acceptance claim. */
define( 'ABSPATH', '/synthetic/' );
class WP_Error { public function __construct( ...$args ) {} }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
class CSF_Scheduling_Access { public static $allowed = true; public static function can() { return self::$allowed; } }
class CSF_Scheduling_Schema { public static function settings() { return array( 'policy_json' => '{"timezone":"America/Los_Angeles"}' ); } }
class CSF_Scheduling_Calendar { public static $called = array(); public static $rows = array(); public static function view( ...$args ) { self::$called = $args; return self::$rows; } }
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-dashboard.php';
$n = 0; function dash_check( $ok, $label ) { global $n; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$n; }
foreach ( array( array( '2027-01-04T01:00Z', '2026-12-28' ), array( '2027-01-04T09:00Z', '2027-01-04' ), array( '2027-03-14T10:30Z', '2027-03-08' ), array( '2027-11-07T09:30Z', '2027-11-01' ) ) as $case ) {
 dash_check( CSF_Scheduling_Dashboard::monday( new DateTimeImmutable( $case[0] ), 'America/Los_Angeles' ) === $case[1], 'facility Monday handles UTC boundary/year/DST' );
}
CSF_Scheduling_Calendar::$rows = array( array( 'uuid' => 'a', 'visit_uuid' => 'family', 'start' => '2027-03-09T10:00-08:00', 'state' => 'confirmed' ), array( 'uuid' => 'b', 'visit_uuid' => 'family', 'start' => '2027-03-09T10:30-08:00', 'state' => 'canceled' ), array( 'uuid' => 'c', 'visit_uuid' => null, 'start' => '2027-03-09T12:00-08:00', 'state' => 'requested' ) );
$week = CSF_Scheduling_Dashboard::week( new DateTimeImmutable( '2027-03-14T10:30Z' ) );
dash_check( CSF_Scheduling_Calendar::$called === array( '2027-03-08', 'week' ), 'uses authoritative week reader without an unscoped alternate query' );
dash_check( count( $week['days'] ) === 7 && isset( $week['days']['2027-03-14'] ), 'Mon through Sun retained including empty days' );
dash_check( $week['today'] === '2027-03-14', 'today is facility-local' );
dash_check( count( $week['days']['2027-03-09'] ) === 2 && count( $week['days']['2027-03-09']['family'] ) === 2, 'groups family while preserving individual canceled child' );
CSF_Scheduling_Access::$allowed = false; CSF_Scheduling_Calendar::$called = array();
dash_check( is_wp_error( CSF_Scheduling_Dashboard::week() ) && ! CSF_Scheduling_Calendar::$called, 'denied access never queries schedule' );
echo $n . " isolated weekly dashboard checks passed\n";

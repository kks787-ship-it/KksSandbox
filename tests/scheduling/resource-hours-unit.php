<?php
/** Isolated recurring-hours/closure checks against real domain code; no database. */
define( 'ABSPATH', '/synthetic-unit-only/' );
function esc_html( $value ) { return $value; }
$root = dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/';
require $root . 'class-csf-scheduling-calendar.php';
require $root . 'class-csf-scheduling-time.php';
require $root . 'class-csf-scheduling-resource-hours.php';
$checks = 0;
function rh_check( $ok, $message ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $message ); } ++$checks; }
$p = array( 'timezone' => 'America/Los_Angeles', 'service_hours' => array_fill( 0, 7, array( array( '08:00', '18:00' ) ) ) );
$p['service_hours'][0] = array();
$weekly = array( 'mode' => 'custom', 'timezone' => $p['timezone'], 'from' => '2027-01-18', 'periods' => array( array( 'weekday' => 1, 'start' => '09:00', 'end' => '12:00' ), array( 'weekday' => 1, 'start' => '13:00', 'end' => '17:00' ) ) );
CSF_Scheduling_Resource_Hours::validate( $weekly, $p );
$d = array( 'intervals' => array( array( 'start' => '2027-01-01T00:00-08:00', 'end' => '2027-12-01T00:00-08:00' ) ), 'weekly' => $weekly );
$covers = static fn( $doc, $a, $z, $policy = null ) => CSF_Scheduling_Resource_Hours::covers( $doc, new DateTimeImmutable( $a ), new DateTimeImmutable( $z ), $policy ?? $p );
rh_check( $covers( $d, '2027-01-25T09:00-08:00', '2027-01-25T10:00-08:00' ), 'split Monday opens configured interval' );
rh_check( ! $covers( $d, '2027-01-25T11:45-08:00', '2027-01-25T13:15-08:00' ), 'split-shift break remains unavailable' );
rh_check( ! $covers( $d, '2027-01-26T09:00-08:00', '2027-01-26T10:00-08:00' ), 'unconfigured weekday remains closed' );
rh_check( $covers( $d, '2027-01-12T09:00-08:00', '2027-01-12T10:00-08:00' ), 'legacy dated availability preserved before effective date' );
$inherited = $d; $inherited['weekly']['mode'] = 'inherit'; $inherited['weekly']['periods'] = array();
rh_check( $covers( $inherited, '2027-01-26T09:00-08:00', '2027-01-26T10:00-08:00' ), 'inherited weekday uses facility hours' );
rh_check( ! $covers( $inherited, '2027-01-24T09:00-08:00', '2027-01-24T10:00-08:00' ), 'inherited closed facility day remains closed' );
$changed = $p; $changed['timezone'] = 'UTC';
rh_check( ! $covers( $d, '2027-01-25T09:00-08:00', '2027-01-25T10:00-08:00', $changed ), 'timezone change fails closed until review' );
foreach ( array( array( 'weekday' => 1, 'start' => '11:00', 'end' => '14:00' ), array( 'weekday' => 1, 'start' => '08:01', 'end' => '09:00' ) ) as $bad ) {
	$proposal = $weekly; $proposal['periods'][] = $bad; $reason = null;
	try { CSF_Scheduling_Resource_Hours::validate( $proposal, $p ); } catch ( RuntimeException $error ) { $reason = $error->getMessage(); }
	rh_check( 'resource_hours_invalid' === $reason, 'overlap/non-grid proposal rejected' );
}
$closure = array( 'start' => '2027-01-25T10:00-08:00', 'end' => '2027-01-25T11:00-08:00', 'recurrence' => array( 'timezone' => $p['timezone'], 'from' => '2027-01-25', 'until' => '2027-02-08', 'weekday' => 1, 'start' => '10:00', 'end' => '11:00' ), 'reopen_dates' => array() );
$blocks = static fn( $doc, $a, $z ) => CSF_Scheduling_Resource_Hours::blocks( $doc, new DateTimeImmutable( $a ), new DateTimeImmutable( $z ) );
rh_check( $blocks( $closure, '2027-02-01T10:15-08:00', '2027-02-01T10:30-08:00' ), 'weekly closure recurs' );
rh_check( ! $blocks( $closure, '2027-02-15T10:15-08:00', '2027-02-15T10:30-08:00' ), 'repeat end is bounded' );
$closure['reopen_dates'][] = '2027-02-01';
rh_check( ! $blocks( $closure, '2027-02-01T10:15-08:00', '2027-02-01T10:30-08:00' ), 'single-date reopening takes effect' );
rh_check( $blocks( $closure, '2027-02-08T10:15-08:00', '2027-02-08T10:30-08:00' ), 'single reopening preserves other dates and inclusive final occurrence' );
$closure['released'] = true;
rh_check( ! $blocks( $closure, '2027-02-08T10:15-08:00', '2027-02-08T10:30-08:00' ), 'explicit series release removes only this closure' );
$gap = CSF_Scheduling_Resource_Hours::window( '2027-03-14', '02:00', '03:00', $p['timezone'] );
rh_check( null === $gap, 'missing opening boundary stays closed' );
$gap = CSF_Scheduling_Resource_Hours::window( '2027-03-14', '02:00', '03:00', $p['timezone'], true );
rh_check( strtotime( $gap['end'] ) - strtotime( $gap['start'] ) === 23 * 3600, 'missing closure boundary protects full 23-hour day' );
$open = CSF_Scheduling_Resource_Hours::window( '2027-11-07', '01:00', '02:00', $p['timezone'] );
$block = CSF_Scheduling_Resource_Hours::window( '2027-11-07', '01:00', '02:00', $p['timezone'], true );
rh_check( strtotime( $open['end'] ) - strtotime( $open['start'] ) === 3600 && strtotime( $block['end'] ) - strtotime( $block['start'] ) === 7200, 'fold opening narrows while closure covers both occurrences' );
echo $checks . " isolated recurring-hours/closure checks passed\n";

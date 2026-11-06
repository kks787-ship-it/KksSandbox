<?php
/** Server-only routine arithmetic on disposable synthetic records.
 *
 * @package CSF_Scheduling_Tests
 */

if ( 'local' !== wp_get_environment_type() || 'fixture_' !== $GLOBALS['wpdb']->prefix || ! CSF_Scheduling_Access::writer() ) {
	throw new RuntimeException( 'Disposable fixture only.' );
}
$checks = 0;
$assert = static function ( $ok, $label ) use ( &$checks ) {
	if ( ! $ok ) {
		throw new RuntimeException( $label );
	}
	++$checks;
};
$p = CSF_Scheduling_Settings::pilot();
$rows = CSF_Scheduling_Calendar::documents( 'riders' );
$horses = CSF_Scheduling_Calendar::documents( 'horses' );
$resources = $GLOBALS['wpdb']->get_results( 'SELECT * FROM `' . CSF_Scheduling_Schema::table( 'resources' ) . '` ORDER BY id', ARRAY_A );
$b = array(
	'uuid' => wp_generate_uuid4(), 'expected' => 0, 'title' => 'Synthetic arithmetic', 'service_type' => 'pilot_private', 'state' => 'requested',
	'start' => array( 'local' => '2027-01-26T10:00', 'choice' => null ), 'end' => null, 'hold_expires' => null,
	'resources' => array_column( array_values( array_filter( $resources, static fn( $r ) => in_array( $r['kind'], array( 'arena', 'instructor' ), true ) ) ), 'uuid' ),
	'riders' => array(), 'rides' => array(), '_routine' => true, 'single_rider' => $rows[0]['uuid'], 'single_horse' => $horses[0]['uuid'],
);
foreach ( array( 30, 45, 60, 120 ) as $duration ) {
	$p['services']['pilot_private']['duration_minutes'] = $duration;
	$derived = CSF_Scheduling_Booking::derive( $b, $p );
	$assert( $duration * 60 === strtotime( $derived['end'] ) - strtotime( $derived['start'] ), 'Elapsed duration ' . $duration );
	$assert( 1 === count( $derived['riders'] ) && 1 === count( $derived['rides'] ) && $derived['rides'][0]['end'] === $derived['end'], 'Duration does not infer multiple riders ' . $duration );
	$assert( ! isset( $derived['_routine'], $derived['single_horse'], $derived['single_rider'] ), 'Canonical command strips presentation fields' );
}
foreach ( array( array( '2027-03-14T01:30', '2027-03-14T01:30-08:00', '2027-03-14T04:30-07:00' ), array( '2027-11-07T01:30', '2027-11-07T01:30-07:00', '2027-11-07T02:30-08:00' ), array( '2027-11-07T01:30', '2027-11-07T01:30-08:00', '2027-11-07T03:30-08:00' ) ) as $case ) {
	$b['start'] = array( 'local' => $case[0], 'choice' => $case[1] );
	$derived = CSF_Scheduling_Booking::derive( $b, $p );
	$assert( $case[2] === $derived['end'] && 7200 === strtotime( $derived['end'] ) - strtotime( $derived['start'] ), 'DST explicit occurrence preserves elapsed duration' );
}
foreach ( array( array( '2027-03-14T02:30', null, 'nonexistent_time' ), array( '2027-11-07T01:30', null, 'ambiguous_time' ), array( '2027-01-26T10:07', null, 'lesson_grid' ) ) as $case ) {
	$b['start'] = array( 'local' => $case[0], 'choice' => $case[1] );
	$reason = '';
	try {
		CSF_Scheduling_Booking::derive( $b, $p );
	} catch ( RuntimeException $e ) {
		$reason = $e->getMessage();
	}
	$assert( $case[2] === $reason, 'Invalid local start fails specifically: ' . $case[2] );
}
echo 'ROUTINE_DOMAIN_CHECKS=' . $checks . "\n";

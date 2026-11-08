<?php
/** Real pure visit planner, synthetic data only; no WordPress or database. */
define( 'ABSPATH', '/synthetic-unit-only/' );
$root = dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/';
require $root . 'class-csf-scheduling-audit.php';
require $root . 'class-csf-scheduling-visit-plan.php';
$checks = 0;
function vp_check( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$checks; }
function vp_id( $n ) { return sprintf( '00000000-0000-4000-8000-%012d', $n ); }
function vp_lesson( $n, $start, $minutes, $visit = 100 ) {
	return array( 'uuid' => vp_id( $n ), 'visit_uuid' => null === $visit ? null : vp_id( $visit ), 'start' => $start, 'end' => $start + $minutes * 60, 'resources' => array(
		array( 'uuid' => vp_id( 200 ), 'kind' => 'instructor', 'mandatory_before' => 0, 'mandatory_after' => 0, 'shareable' => true ),
		array( 'uuid' => vp_id( 201 ), 'kind' => 'arena', 'mandatory_before' => 0, 'mandatory_after' => 0, 'shareable' => true ),
	) );
}
function vp_plan( $lessons, $before = 15, $after = 15 ) { return CSF_Scheduling_Visit_Plan::calculate( $lessons, $before, $after ); }
function vp_bad( $lessons, $label, $before = 15, $after = 15 ) { $reason = null; try { vp_plan( $lessons, $before, $after ); } catch ( InvalidArgumentException $e ) { $reason = $e->getMessage(); } vp_check( 'visit_plan_invalid' === $reason, $label ); }
$t = strtotime( '2027-01-25T10:00:00-08:00' );
$a = vp_lesson( 1, $t, 30 ); $b = vp_lesson( 2, $t + 1800, 30 );
$original = array( $a, $b ); $plan = vp_plan( $original );
vp_check( array( $a, $b ) === $original, 'input unchanged' );
vp_check( 2 === count( $plan['lessons'] ) && array() === $plan['conflicts'], 'two itemized siblings without false conflict' );
foreach ( array( 0, 1 ) as $r ) {
	$x = $plan['lessons'][0]['reservations'][$r]; $y = $plan['lessons'][1]['reservations'][$r];
	vp_check( $x['start'] === $t - 900 && $x['end'] === $t + 1800 && $y['start'] === $x['end'] && $y['end'] === $t + 4500, '09:45 to 11:15 shared envelope on each resource' );
	vp_check( $x['shared_after'] === $b['uuid'] && $y['shared_before'] === $a['uuid'], 'shared edge retains exact sibling IDs' );
}
vp_check( $plan['lessons'][0]['teaching_end'] === $a['end'] && $plan['lessons'][1]['teaching_start'] === $b['start'], 'teaching times unchanged' );
$long = array( vp_lesson( 1, $t, 120 ), vp_lesson( 2, $t + 7200, 120 ), vp_lesson( 3, $t + 14400, 60 ) );
$p = vp_plan( $long );
vp_check( ! $p['conflicts'] && $p['lessons'][0]['reservations'][0]['start'] === $t - 900 && $p['lessons'][2]['reservations'][0]['end'] === $t + 18900, '120+120+60 has five hours teaching and 30 outer minutes' );
vp_check( $p['lessons'][1]['reservations'][0]['pre_minutes'] === 0 && $p['lessons'][1]['reservations'][0]['post_minutes'] === 0, 'middle lesson has no optional internal buffers' );
$p = vp_plan( array( $b, $a ) );
vp_check( $p['lessons'][0]['uuid'] === $b['uuid'] && ! $p['conflicts'] && $p['lessons'][0]['reservations'][0]['pre_minutes'] === 0, 'input order preserved without relying on it for adjacency' );
$other = $b; $other['visit_uuid'] = vp_id( 101 );
vp_check( 2 === count( vp_plan( array( $a, $other ) )['conflicts'] ), 'different visit retains both resource buffers' );
$other['visit_uuid'] = null; $none = $a; $none['visit_uuid'] = null;
vp_check( 2 === count( vp_plan( array( $none, $other ) )['conflicts'] ), 'two unlinked lessons never share implicitly' );
$p = vp_plan( array( $b ) );
vp_check( $p['lessons'][0]['reservations'][0]['start'] === $b['start'] - 900 && $p['lessons'][0]['reservations'][0]['end'] === $b['end'] + 900, 'cancel first restores surviving pre buffer without shifting teaching' );
$p = vp_plan( array( $a ) );
vp_check( $p['lessons'][0]['reservations'][0]['end'] === $a['end'] + 900, 'cancel last restores preceding post buffer' );
$p = vp_plan( array( $long[0], $long[2] ) );
vp_check( ! $p['conflicts'] && $p['lessons'][0]['reservations'][0]['post_minutes'] === 15 && $p['lessons'][1]['reservations'][0]['pre_minutes'] === 15, 'cancel middle splits visit at gap' );
$changed = $b; $changed['resources'][0]['uuid'] = vp_id( 202 );
$p = vp_plan( array( $a, $changed ) );
vp_check( 1 === count( $p['conflicts'] ) && $p['conflicts'][0]['resource_uuid'] === vp_id( 201 ), 'different instructor does not waive shared arena turnover' );
$changed = $b; $changed['resources'][1]['uuid'] = vp_id( 202 );
vp_check( 1 === count( vp_plan( array( $a, $changed ) )['conflicts'] ), 'different arena does not waive instructor turnover' );
$changed = $b; $changed['resources'][1]['shareable'] = false;
vp_check( 2 === count( vp_plan( array( $a, $changed ) )['conflicts'] ), 'incompatible turnover prevents optional sharing' );
$changed = $b; $changed['resources'][0]['mandatory_before'] = 20;
$p = vp_plan( array( $a, $changed ) );
vp_check( $p['lessons'][1]['reservations'][0]['pre_minutes'] === 20 && 1 === count( $p['conflicts'] ), 'mandatory travel survives sharing and creates conflict' );
$changed = $a; $changed['resources'][0]['mandatory_after'] = 30;
vp_check( 1 === count( vp_plan( array( $changed, $b ) )['conflicts'] ), 'mandatory post turnover survives sharing' );
$p = vp_plan( array( $a ), 5, 25 );
vp_check( $p['lessons'][0]['reservations'][0]['start'] === $t - 300 && $p['lessons'][0]['reservations'][0]['end'] === $a['end'] + 1500, 'defaults are parameters, not permanent policy' );
$changed = $b; $changed['resources'] = array_reverse( $changed['resources'] );
vp_check( ! vp_plan( array( $a, $changed ) )['conflicts'], 'resource order does not change compatibility' );
$changed = $b; $changed['start'] -= 60;
vp_check( 2 === count( vp_plan( array( $a, $changed ) )['conflicts'] ), 'occupied overlap cannot be merged' );
$changed = $b; $changed['start'] += 60;
vp_check( 2 === count( vp_plan( array( $a, $changed ) )['conflicts'] ), 'one-minute gap does not imply shared visit arrival' );
$changed = vp_lesson( 2, $a['end'] + 1800, 30, 101 );
vp_check( ! vp_plan( array( $a, $changed ) )['conflicts'], 'half-open independent buffers can meet at boundary' );
$changed = $b; $changed['resources'] = array( $changed['resources'][0] ); $alone = $a; $alone['resources'] = array( $alone['resources'][0] );
vp_check( 1 === count( vp_plan( array( $alone, $changed ) )['conflicts'] ), 'missing location context cannot authorize sharing' );
$fold = strtotime( '2027-11-07T01:30:00-07:00' );
$p = vp_plan( array( vp_lesson( 1, $fold, 30 ), vp_lesson( 2, strtotime( '2027-11-07T01:00:00-08:00' ), 30 ) ) );
vp_check( ! $p['conflicts'] && $p['lessons'][1]['teaching_end'] - $p['lessons'][0]['teaching_start'] === 3600, 'DST repeated local hour uses actual elapsed UTC adjacency' );
vp_check( array( 'lessons' => array(), 'conflicts' => array() ) === vp_plan( array() ), 'empty active visit is an empty plan' );
vp_bad( array( $a, $a ), 'duplicate appointment rejected' );
$changed = $b; $changed['uuid'] = vp_id( 3 ); vp_bad( array( $a, $b, $changed ), 'branching adjacency rejected' );
foreach ( array( 'visit_uuid' => 'family-name', 'start' => (string) $t, 'end' => $t, 'extra' => true ) as $key => $value ) { $changed = $a; $changed[$key] = $value; vp_bad( array( $changed ), 'invalid lesson ' . $key ); }
foreach ( array( 'mandatory_before' => -1, 'mandatory_after' => '15', 'shareable' => 1, 'kind' => 'horse', 'uuid' => 'bad', 'extra' => true ) as $key => $value ) { $changed = $a; $changed['resources'][0][$key] = $value; vp_bad( array( $changed ), 'invalid resource ' . $key ); }
$changed = $a; $changed['resources'][] = $changed['resources'][0]; vp_bad( array( $changed ), 'duplicate resource rejected' );
$changed = $b; $changed['resources'][0]['kind'] = 'location'; vp_bad( array( $a, $changed ), 'resource kind contradiction rejected' );
vp_bad( array( $a ), 'negative default rejected', -1 );
vp_bad( array( $a ), 'unbounded default rejected', 15, 1441 );
vp_bad( array( $a ), 'string default rejected', '15' );
vp_bad( array_fill( 0, 101, $a ), 'oversized plan rejected' );
// Deterministic varied-length chains exercise recalculation beyond the two examples.
for ( $size = 2; $size <= 20; ++$size ) {
	$chain = array(); $cursor = $t;
	for ( $i = 0; $i < $size; ++$i ) { $minutes = 15 * ( 1 + ( $i * 7 + $size ) % 8 ); $chain[] = vp_lesson( $i + 1, $cursor, $minutes ); $cursor += $minutes * 60; }
	$p = vp_plan( $chain );
	vp_check( ! $p['conflicts'] && $p['lessons'][0]['reservations'][0]['start'] === $t - 900 && $p['lessons'][$size - 1]['reservations'][0]['end'] === $cursor + 900, 'varied chain retains exactly the outer envelope' );
	$valid = true;
	foreach ( $p['lessons'] as $i => $lesson ) {
		foreach ( $lesson['reservations'] as $r ) { $valid = $valid && $r['start'] <= $chain[$i]['start'] && $r['end'] >= $chain[$i]['end'] && $r['pre_minutes'] === ( 0 === $i ? 15 : 0 ) && $r['post_minutes'] === ( $size - 1 === $i ? 15 : 0 ); }
	}
	vp_check( $valid, 'every teaching interval remains covered with only outer optional buffers' );
	$chain[1]['resources'][0]['mandatory_before'] = 1;
	vp_check( count( vp_plan( $chain )['conflicts'] ) >= 1, 'even a one-minute mandatory boundary cannot be waived' );
}
echo $checks . " isolated family visit planning checks passed\n";

<?php
/** Pure adapter and in-memory sink tests; no DB, WordPress mail or network. */
define( 'ABSPATH', '/test/site/' );
function esc_html( $value ) { return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' ); }
$environment = 'local'; function wp_get_environment_type() { global $environment; return $environment; }
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-audit.php';
require dirname( __DIR__, 2 ) . '/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-email-sink.php';
function eid( $n ) { return sprintf( '00000000-0000-4000-8000-%012d', $n ); }
$checks = 0; function ec( $ok, $label ) { global $checks; if ( ! $ok ) { throw new RuntimeException( $label ); } ++$checks; }
function er( $fn, $reason ) { $got = null; try { $fn(); } catch ( RuntimeException $e ) { $got = $e->getMessage(); } ec( $got === $reason, 'Expected ' . $reason . ', got ' . ( $got ?? 'success' ) ); }
$event = array( 'record_type' => 'booking_notice', 'source_command' => eid( 1 ), 'email_state' => 'held_no_delivery_authorization', 'recipient_ids' => array( 1, 2 ), 'recipient_items' => array( 1 => array( eid( 10 ) ), 2 => array( eid( 11 ) ) ), 'changes' => array( array( 'uuid' => eid( 10 ), 'kind' => 'NEW', 'urgent' => false, 'reason' => 'PRIVATE' ), array( 'uuid' => eid( 11 ), 'kind' => 'CANCELED', 'urgent' => true ) ) );
$before = $event; $j = CSF_Scheduling_Email_Test_Adapter::job( $event, 1 );
ec( $event === $before, 'sink job never mutates durable held envelope' ); ec( count( $j['message']['changes'] ) === 1 && $j['message']['changes'][0]['uuid'] === eid( 10 ), 'recipient subset enforced' ); ec( ! str_contains( json_encode( $j ), 'PRIVATE' ), 'free text stripped' );
ec( $j['key'] === CSF_Scheduling_Email_Test_Adapter::job( $event, 1 )['key'], 'stable recipient event idempotency key' );
ec( $j['key'] !== CSF_Scheduling_Email_Test_Adapter::job( $event, 2 )['key'], 'recipient keys differ' );
er( fn() => CSF_Scheduling_Email_Test_Adapter::job( $event, 3 ), 'email_invalid' );
$token = str_repeat( 'a', 32 ); $other = str_repeat( 'b', 32 );
$c = CSF_Scheduling_Email_Test_Adapter::claim( $j, 0, $token, 100 );
ec( $c['attempts'] === 1 && $c['revision'] === 1 && $c['state'] === 'claimed', 'claim increments attempt and revision' );
er( fn() => CSF_Scheduling_Email_Test_Adapter::claim( $c, 0, $other, 101 ), 'email_stale' );
er( fn() => CSF_Scheduling_Email_Test_Adapter::claim( $c, 1, $other, 101 ), 'email_not_claimable' );
$sink = new CSF_Scheduling_Email_Sink();
er( fn() => CSF_Scheduling_Email_Test_Adapter::attempt( $c, $other, 101, $sink ), 'email_claim_lost' ); ec( ! $sink->messages, 'lost claim does not reach sink' );
$done = CSF_Scheduling_Email_Test_Adapter::attempt( $c, $token, 101, $sink );
ec( $done['state'] === 'sink_accepted' && count( $sink->messages ) === 1, 'acceptance means sink only, never delivered' );
er( fn() => CSF_Scheduling_Email_Test_Adapter::claim( $done, 2, $other, 102 ), 'email_not_claimable' );
ec( $sink->messages[0]['idempotency_key'] === $j['key'], 'stable key accompanies sink submission' );
$replay = CSF_Scheduling_Email_Test_Adapter::attempt( $c, $token, 102, $sink ); ec( $replay['state'] === 'sink_accepted' && count( $sink->messages ) === 1, 'sink deduplicates stable key after lost acceptance response' );
$changed = $c; $changed['message']['text'] = 'changed'; $conflict = CSF_Scheduling_Email_Test_Adapter::attempt( $changed, $token, 102, $sink ); ec( $conflict['state'] === 'uncertain' && count( $sink->messages ) === 1, 'same key with changed payload cannot cause second acceptance' );
$lost = CSF_Scheduling_Email_Test_Adapter::claim( $c, 1, $other, 160 ); ec( $lost['state'] === 'uncertain' && $lost['attempts'] === 1, 'expired claim not reclaimed or resent' );
er( fn() => CSF_Scheduling_Email_Test_Adapter::claim( $lost, 2, $other, 200 ), 'email_not_claimable' );
$late = CSF_Scheduling_Email_Test_Adapter::finish( $c, 1, $token, 'accepted', 160 ); ec( $late['state'] === 'uncertain', 'late result requires review' );
foreach ( array( 'throw', 'unknown', 'uncertain' ) as $outcome ) { $s = new CSF_Scheduling_Email_Sink( array( $outcome ) ); $r = CSF_Scheduling_Email_Test_Adapter::attempt( $c, $token, 101, $s ); ec( $r['state'] === 'uncertain', 'ambiguous result never becomes retry' ); }
$r = $j; $now = 100; $s = new CSF_Scheduling_Email_Sink( array_fill( 0, 3, 'rejected_transient' ) );
for ( $i = 1; $i <= 3; ++$i ) { $r = CSF_Scheduling_Email_Test_Adapter::claim( $r, $r['revision'], $token, $now ); $r = CSF_Scheduling_Email_Test_Adapter::attempt( $r, $token, $now, $s ); ec( $r['state'] === ( $i < 3 ? 'retry' : 'failed' ), 'retry bounded at attempt ' . $i ); if ( $i < 3 ) { er( fn() => CSF_Scheduling_Email_Test_Adapter::claim( $r, $r['revision'], $other, $now ), 'email_not_claimable' ); $now = $r['retry_at']; } }
ec( count( $s->messages ) === 3 && $r['attempts'] === 3, 'three attempts maximum' );
$permanent = CSF_Scheduling_Email_Test_Adapter::attempt( $c, $token, 101, new CSF_Scheduling_Email_Sink( array( 'rejected_permanent' ) ) ); ec( $permanent['state'] === 'failed', 'permanent rejection terminal' );
$departed = $event; $departed['recipient_departures'] = array( 1 => array( eid( 10 ) ) ); $departure_job = CSF_Scheduling_Email_Test_Adapter::job( $departed, 1 ); ec( $departure_job['message']['changes'][0]['kind'] === 'REMOVED_FROM_ASSIGNMENT', 'sink preserves minimal departure classification without new schedule details' );
$environment = 'development'; er( fn() => CSF_Scheduling_Email_Test_Adapter::job( $event, 1 ), 'email_fixture_only' );
$environment = 'production'; er( fn() => CSF_Scheduling_Email_Test_Adapter::attempt( $c, $token, 101, $sink ), 'email_fixture_only' );
echo $checks . " isolated email sink adapter checks passed; no delivery\n";

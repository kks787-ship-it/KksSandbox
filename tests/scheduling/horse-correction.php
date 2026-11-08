<?php
/** Unused-horse mapping regressions. Disposable synthetic fixture only. */
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) {
	WP_CLI::error( 'Disposable fixture required.' );
}
global $wpdb;
wp_set_current_user( 1 );
$GLOBALS['horse_correction_checks'] = 0;
function hc_check( $ok, $message ) {
	if ( ! $ok ) { throw new RuntimeException( $message ); }
	++$GLOBALS['horse_correction_checks'];
	WP_CLI::log( 'PASS ' . $message );
}
function hc_revision() { return (int) CSF_Scheduling_Schema::settings()['revision']; }
function hc_rows() {
	global $wpdb;
	$out = array();
	foreach ( CSF_Scheduling_Schema::definitions() as $name => $definition ) {
		$table = CSF_Scheduling_Schema::table( $name );
		$out[ $name ] = $wpdb->get_results( "SELECT * FROM `$table` ORDER BY 1", ARRAY_A );
	}
	return $out;
}
function hc_save( $uuid, $expected, $data, $revision = null, $command = null ) {
	return CSF_Scheduling_Store::calendar( 'horse_saved', compact( 'uuid', 'expected', 'data' ), $revision ?? hc_revision(), $command ?? wp_generate_uuid4() );
}
function hc_denied( $uuid, $expected, $data, $reason ) {
	$before = hc_rows();
	$result = hc_save( $uuid, $expected, $data );
	hc_check( is_wp_error( $result ) && ( $result->get_error_data()['reason'] ?? $result->get_error_code() ) === $reason, 'reject correction: ' . $reason );
	hc_check( $before === hc_rows(), 'failed correction preserves all scheduling rows' );
}
function hc_resource( $kind = 'horse' ) {
	$uuid = wp_generate_uuid4();
	$result = CSF_Scheduling_Store::resource( array( 'uuid' => $uuid, 'kind' => $kind, 'label' => 'Synthetic correction ' . $kind ), hc_revision(), wp_generate_uuid4() );
	hc_check( ! is_wp_error( $result ), 'create unused correction resource' );
	return $uuid;
}
$uuid = wp_generate_uuid4();
$client = array( 'label' => 'Synthetic mistaken classification', 'source' => 'client', 'resource_uuid' => null, 'suitable_services' => array(), 'suitable_riders' => array(), 'training_enrolled' => false );
hc_check( ! is_wp_error( hc_save( $uuid, 0, $client ) ), 'create mistaken client classification' );
$school = $client;
$school['source'] = 'school';
$school['resource_uuid'] = hc_resource();
$revision = hc_revision();
$command = wp_generate_uuid4();
$result = hc_save( $uuid, 1, $school, $revision, $command );
hc_check( ! is_wp_error( $result ) && $result['uuid'] === $uuid && $result['entity_revision'] === 2, 'correct unused client to school without replacing horse identity' );
$before = hc_rows();
hc_check( hc_save( $uuid, 1, $school, $revision, $command ) === $result && hc_rows() === $before, 'exact replay returns committed mapping without duplicate history or revision' );
// Outside a transaction, history intentionally filters to visible appointments. Query
// the synthetic horse's own rows directly to verify the stored before/after evidence.
$table = CSF_Scheduling_Schema::table( 'history' );
$history = $wpdb->get_results( $wpdb->prepare( "SELECT body_json FROM `$table` WHERE entity_uuid=%s ORDER BY id", $uuid ), ARRAY_A );
hc_check( count( $history ) === 2 && json_decode( $history[1]['body_json'], true )['before'] === $client && json_decode( $history[1]['body_json'], true )['after'] === $school, 'classification correction retains original document and audited before/after' );
hc_denied( $uuid, 1, $client, 'stale_appointment' );
$bad = $school;
$bad['resource_uuid'] = hc_resource( 'arena' );
hc_denied( $uuid, 2, $bad, 'horse_unsafe' );
$second = wp_generate_uuid4();
hc_check( ! is_wp_error( hc_save( $second, 0, $client ) ), 'create second unused horse' );
hc_denied( $second, 1, $school, 'horse_unsafe' );
hc_check( ! is_wp_error( hc_save( $uuid, 2, $client ) ), 'correct unused school to client with null school resource' );
$bad = $client;
$bad['resource_uuid'] = $school['resource_uuid'];
hc_denied( $uuid, 3, $bad, 'invalid_body' );
$inject = static fn( $query ) => str_contains( $query, 'JOIN `fixture_csf_sched_details` d ON d.uuid=a.uuid' ) ? 'SELECT * FROM fixture_deliberately_missing' : $query;
add_filter( 'query', $inject );
hc_denied( $uuid, 3, $school, 'write_failed' );
remove_filter( 'query', $inject );
$fixture = json_decode( file_get_contents( '/test/calendar-fixture.json' ), true );
$used = CSF_Scheduling_Calendar::find( 'horses', $fixture['horse'] );
$used_data = json_decode( $used['body_json'], true );
$used_data['source'] = 'client';
$used_data['resource_uuid'] = null;
hc_denied( $used['uuid'], (int) $used['revision'], $used_data, 'horse_mapping_in_use' );
wp_set_current_user( $fixture['staff'] );
hc_denied( $uuid, 3, $school, 'forbidden' );
wp_set_current_user( 1 );
// A block on the proposed school resource must never be bypassed by remapping.
$block = wp_generate_uuid4();
$restriction = array( 'target_uuid' => $school['resource_uuid'], 'start' => '2027-01-01T00:00-08:00', 'end' => '2027-01-02T00:00-08:00', 'reason' => 'welfare' );
$result = CSF_Scheduling_Store::calendar( 'restriction_added', array( 'uuid' => $block, 'expected' => 0, 'data' => $restriction ), hc_revision(), wp_generate_uuid4() );
hc_check( ! is_wp_error( $result ), 'protect proposed resource' );
hc_denied( $uuid, 3, $school, 'horse_mapping_in_use' );
$result = CSF_Scheduling_Store::calendar( 'restriction_released', array( 'uuid' => $block, 'expected' => 1, 'reason' => 'Synthetic reviewed release' ), hc_revision(), wp_generate_uuid4() );
hc_check( ! is_wp_error( $result ), 'release synthetic block while retaining history' );
hc_check( ! is_wp_error( hc_save( $uuid, 3, $school ) ), 'released block history does not prevent reconciled correction' );
// Seed a synthetic canceled appointment snapshot with the real row shape. Remapping
// must not mutate its canceled booking or detail snapshot.
$past_horse=wp_generate_uuid4();hc_check(!is_wp_error(hc_save($past_horse,0,$client)),'Create historical-only correction horse');
$appointments=CSF_Scheduling_Schema::table('appointments');$details=CSF_Scheduling_Schema::table('details');$histories=CSF_Scheduling_Schema::table('history');
$sample=$wpdb->get_row("SELECT a.* FROM `$appointments` a JOIN `$details` d ON d.uuid=a.uuid WHERE JSON_LENGTH(d.body_json,'$.rides')>0 LIMIT 1",ARRAY_A);$sample_detail=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$details` WHERE uuid=%s",$sample['uuid']),ARRAY_A);
$past_uuid=wp_generate_uuid4();unset($sample['id'],$sample_detail['id']);$sample['uuid']=$past_uuid;$sample['state']='canceled';
hc_check(1===$wpdb->insert($appointments,$sample),'Seed synthetic canceled appointment');
$sample_detail['uuid']=$past_uuid;$sample_detail['entity_uuid']=$past_uuid;$past_data=json_decode($sample_detail['body_json'],true);$past_data['uuid']=$past_uuid;foreach($past_data['rides'] as &$turn){$turn['horse']=$past_horse;}unset($turn);$sample_detail['body_json']=wp_json_encode($past_data);
hc_check(1===$wpdb->insert($details,$sample_detail),'Seed historical horse reference');
$past_resource=hc_resource();$new_school=$client;$new_school['source']='school';$new_school['resource_uuid']=$past_resource;
$preserved=$wpdb->get_results($wpdb->prepare("SELECT * FROM `$details` WHERE uuid=%s",$past_uuid),ARRAY_A);$preserved_booking=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$appointments` WHERE uuid=%s",$past_uuid),ARRAY_A);
$revision=hc_revision();$command=wp_generate_uuid4();$mapped=hc_save($past_horse,1,$new_school,$revision,$command);
hc_check(!is_wp_error($mapped),'Canceled appointment history permits reconciled remapping');
hc_check($preserved===$wpdb->get_results($wpdb->prepare("SELECT * FROM `$details` WHERE uuid=%s",$past_uuid),ARRAY_A)&&$preserved_booking===$wpdb->get_row($wpdb->prepare("SELECT * FROM `$appointments` WHERE uuid=%s",$past_uuid),ARRAY_A),'Remapping leaves historical booking and detail bytes intact');
$before=hc_rows();hc_check(hc_save($past_horse,1,$new_school,$revision,$command)===$mapped&&$before===hc_rows(),'Historical correction replay is idempotent');
$delete_used=array('uuid'=>$past_horse,'expected'=>2,'reason'=>'Synthetic rejection','confirm_label'=>$client['label'],'irreversible'=>true);
$before=hc_rows();$deleted=CSF_Scheduling_Store::calendar('horse_deleted',$delete_used,hc_revision(),wp_generate_uuid4());hc_check(is_wp_error($deleted)&&$before===hc_rows(),'Remapped historical horse remains undeletable');
// Ordinary edits are still supported on a used horse; only identity remapping is gated.
$original = json_decode( $used['body_json'], true );
hc_check( ! is_wp_error( hc_save( $used['uuid'], (int) $used['revision'], $original ) ), 'ordinary used-horse save remains available' );
hc_check( CSF_Scheduling_Schema::VERSION === 4, 'horse correction runs under lifecycle schema v4' );
// Conditional deletion: current transaction, exact replay and UUID tombstone.
$unused_delete = wp_generate_uuid4(); hc_check( ! is_wp_error( hc_save( $unused_delete, 0, $client ) ), 'create synthetic unused deletion entry' );
$delete = array( 'uuid' => $unused_delete, 'expected' => 1, 'reason' => 'Synthetic mistaken entry', 'confirm_label' => $client['label'], 'irreversible' => true );
$before = hc_rows(); $fail = static fn( $q ) => str_contains( $q, 'INSERT INTO `fixture_csf_sched_commands`' ) ? 'SELECT * FROM fixture_delete_missing' : $q;
add_filter( 'query', $fail ); $failed = CSF_Scheduling_Store::calendar( 'horse_deleted', $delete, hc_revision(), wp_generate_uuid4() ); remove_filter( 'query', $fail );
hc_check( is_wp_error( $failed ) && $before === hc_rows(), 'late ledger failure restores horse, tombstone and history together' );
$rev = hc_revision(); $cmd = wp_generate_uuid4(); $deleted = CSF_Scheduling_Store::calendar( 'horse_deleted', $delete, $rev, $cmd );
hc_check( ! is_wp_error( $deleted ) && ! CSF_Scheduling_Calendar::find( 'horses', $unused_delete ), 'delete only proven-unused synthetic roster entry' );
$before = hc_rows(); hc_check( CSF_Scheduling_Store::calendar( 'horse_deleted', $delete, $rev, $cmd ) === $deleted && $before === hc_rows(), 'exact deletion replay creates no duplicate audit/tombstone' );
hc_denied( $unused_delete, 0, $client, 'horse_deleted' );
$delete['uuid'] = $used['uuid']; $delete['expected'] = (int) CSF_Scheduling_Calendar::find( 'horses', $used['uuid'] )['revision']; $delete['confirm_label'] = $original['label'];
$before = hc_rows(); $blocked = CSF_Scheduling_Store::calendar( 'horse_deleted', $delete, hc_revision(), wp_generate_uuid4() );
hc_check( is_wp_error( $blocked ) && $before === hc_rows(), 'historical used horse cannot be deleted' );
WP_CLI::success( $GLOBALS['horse_correction_checks'] . ' unused horse correction checks' );

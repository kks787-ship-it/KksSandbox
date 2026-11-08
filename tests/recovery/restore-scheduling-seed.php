<?php
// Only the offline container fixture loads this seed; never invoke against a farm.
if (getenv('CSF_SYNTHETIC_RESTORE') !== 'container-only' || !is_file('/.dockerenv') || ABSPATH !== '/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com/' || !is_dir('/fixture')) { throw new RuntimeException('Synthetic recovery only'); }
global $wpdb;
wp_set_current_user(1);
require_once ABSPATH . 'wp-admin/includes/plugin.php';
foreach (array('csf-core/csf-core.php','two-factor/two-factor.php','csf-mfa/csf-mfa.php','csf-scheduling/csf-scheduling.php') as $plugin) {
    $result=activate_plugin($plugin);if(is_wp_error($result)){throw new RuntimeException('Fixture activation failed');}
}
$result=CSF_Scheduling_Schema::migrate();if(is_wp_error($result)){throw new RuntimeException('Fixture migration failed');}
Two_Factor_Core::get_providers();
$key=Two_Factor_Totp::generate_key();Two_Factor_Totp::get_instance()->set_user_totp_key(1,$key);
update_user_meta(1,'_two_factor_enabled_providers',array('Two_Factor_Totp','Two_Factor_Backup_Codes'));
update_user_meta(1,'_two_factor_provider','Two_Factor_Totp');
Two_Factor_Backup_Codes::get_instance()->generate_codes(get_userdata(1));
WP_Session_Tokens::get_instance(1)->create(time()+3600);
update_user_meta(1,'synthetic_nullable_auth_reference',null);
foreach(CSF_Scheduling_Schema::definitions() as $name=>$definition){
    $table=CSF_Scheduling_Schema::table($name);
    if((int)$wpdb->get_var("SELECT COUNT(*) FROM `$table`"))continue;
    $row=array();
    foreach($definition['columns'] as $column=>$spec){
        if($spec[1]){$row[$column]=null;}
        elseif(str_contains($spec[0],'int')){$row[$column]=1;}
        elseif(str_starts_with($spec[0],'datetime')){$row[$column]=str_contains($column,'end')?'2027-01-25 10:30:00.000000':'2027-01-25 10:00:00.000000';}
        elseif(str_starts_with($spec[0],'char(36)')){$row[$column]='11111111-1111-4111-8111-111111111111';}
        elseif(str_ends_with($column,'_json')){$row[$column]=wp_json_encode(array('label'=>'Pilot recovery','fixture'=>true));}
        elseif(str_contains($column,'hash')){$row[$column]=str_repeat('a',64);}
        else{$row[$column]='pilot';}
    }
    if($name==='resources'){$row['kind']='instructor';$row['label']='Pilot Instructor';}
    if($name==='appointments'){$row['title']='Pilot confirmed lesson';$row['state']='confirmed';$row['timezone']='America/Los_Angeles';$row['service_type']='private_lesson';}
    if(false===$wpdb->insert($table,$row)){throw new RuntimeException('Fixture row failed: '.$name);}
}
$row=$wpdb->get_row('SELECT * FROM '.CSF_Scheduling_Schema::table('appointments').' WHERE id=1',ARRAY_A);
$row['id']=2;$row['uuid']='22222222-2222-4222-8222-222222222222';$row['state']='canceled';$row['title']='Pilot canceled lesson';$row['canceled_by']=1;
if(false===$wpdb->insert(CSF_Scheduling_Schema::table('appointments'),$row)){throw new RuntimeException('Cancellation fixture failed');}
// Schema-4 metadata must survive exact source/data restore, including held transports.
$sample_appointment='11111111-1111-4111-8111-111111111111';
$sample_visit='33333333-3333-4333-8333-333333333333';
$sample_notice='44444444-4444-4444-8444-444444444444';
$window=array('resource_uuid'=>$sample_appointment,'start'=>1800870300,'end'=>1800873900,'pre_minutes'=>15,'post_minutes'=>15);
$metadata=array('record_type'=>'family_visit','same_family'=>true,'state'=>'confirmed','hold_expires_utc'=>null,'active_members'=>array($sample_appointment),'all_members'=>array($sample_appointment));
foreach(array($sample_visit=>$metadata,$sample_notice=>array('record_type'=>'booking_notice','source_command'=>$sample_notice,'entity_uuid'=>$sample_visit,'recipient_ids'=>array(1),'recipient_items'=>array(1=>array($sample_appointment)),'changes'=>array(array('uuid'=>$sample_appointment,'kind'=>'NEW','instructors'=>array($sample_appointment),'urgent'=>false)),'email_state'=>'held_no_delivery_authorization','push_state'=>'not_configured')) as $id=>$body){
    if(false===$wpdb->insert(CSF_Scheduling_Schema::table('details'),array('uuid'=>$id,'entity_uuid'=>$sample_visit,'body_json'=>wp_json_encode($body),'revision'=>1,'created_by'=>1,'created_at_utc'=>'2027-01-25 10:00:00.000000'))){throw new RuntimeException('Schema-four fixture metadata failed');}
}
$detail=$wpdb->get_row('SELECT * FROM '.CSF_Scheduling_Schema::table('details').' WHERE id=1',ARRAY_A);
$trimmed=$window;$trimmed['start']+=900;$trimmed['pre_minutes']=0;
$body=json_decode($detail['body_json'],true);$body['visit_uuid']=$sample_visit;$body['resource_reservations']=array($trimmed);$body['buffer_overrides']=array(array('resource_uuid'=>$sample_appointment,'segment'=>'pre','approved_by'=>1,'reason'=>'Synthetic recovery exception','before'=>$window,'after'=>$trimmed));
$wpdb->update(CSF_Scheduling_Schema::table('details'),array('body_json'=>wp_json_encode($body)),array('id'=>1));
$horse=$wpdb->get_row('SELECT * FROM '.CSF_Scheduling_Schema::table('horses').' WHERE id=1',ARRAY_A);$body=json_decode($horse['body_json'],true);$body['status']='archived';
$wpdb->update(CSF_Scheduling_Schema::table('horses'),array('body_json'=>wp_json_encode($body)),array('id'=>1));
// Exercise physical foreign-key/check preservation, beyond the app's logical links.
$wpdb->query('ALTER TABLE '.CSF_Scheduling_Schema::table('allocations').' ADD CONSTRAINT fixture_resource_reference FOREIGN KEY(resource_id) REFERENCES '.CSF_Scheduling_Schema::table('resources').'(id)');
$wpdb->query('ALTER TABLE '.CSF_Scheduling_Schema::table('resources').' ADD CONSTRAINT fixture_revision_check CHECK(revision>=0)');
$phase=getenv('CSF_SYNTHETIC_PHASE');
if($phase==='partial'){
    $wpdb->update(CSF_Scheduling_Schema::table('settings'),array('schema_version'=>0),array('id'=>1));
    foreach(array('authorization','participants','slots','history','details','restrictions','availability') as $name){$wpdb->query('DROP TABLE '.CSF_Scheduling_Schema::table($name));}
}elseif($phase==='damaged'){$wpdb->query('DROP TABLE '.CSF_Scheduling_Schema::table('authorization'));}
echo 'SYNTHETIC_SCHEDULING_SEEDED';

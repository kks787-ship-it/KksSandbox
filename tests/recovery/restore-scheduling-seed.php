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
// Exercise physical foreign-key/check preservation, beyond the app's logical links.
$wpdb->query('ALTER TABLE '.CSF_Scheduling_Schema::table('allocations').' ADD CONSTRAINT fixture_resource_reference FOREIGN KEY(resource_id) REFERENCES '.CSF_Scheduling_Schema::table('resources').'(id)');
$wpdb->query('ALTER TABLE '.CSF_Scheduling_Schema::table('resources').' ADD CONSTRAINT fixture_revision_check CHECK(revision>=0)');
$phase=getenv('CSF_SYNTHETIC_PHASE');
if($phase==='partial'){
    $wpdb->update(CSF_Scheduling_Schema::table('settings'),array('schema_version'=>0),array('id'=>1));
    foreach(array('authorization','participants','slots','history','details','restrictions','availability') as $name){$wpdb->query('DROP TABLE '.CSF_Scheduling_Schema::table($name));}
}elseif($phase==='damaged'){$wpdb->query('DROP TABLE '.CSF_Scheduling_Schema::table('authorization'));}
echo 'SYNTHETIC_SCHEDULING_SEEDED';

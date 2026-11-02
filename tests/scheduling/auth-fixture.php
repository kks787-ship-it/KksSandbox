<?php
// Trusted synthetic adapter ONLY in the disposable fixture. Never deploy this file.
if ( ! defined('ABSPATH') || ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { return; }
add_filter('csf_scheduling_mfa_verified',static fn($value,$id)=>$id>0 && (bool)get_userdata($id),10,2);
function fixture_staff($id,$denies=array()) {
 $overrides=array_fill_keys(array_keys(CSF_Scheduling_Permissions::registry()),'grant');
 foreach($denies as$p){$overrides[$p]='deny';}
 return CSF_Scheduling_Store::permissions('team_member_saved',array('user_id'=>$id,'member'=>array('group'=>'read_only','overrides'=>$overrides,'resources'=>array(),'active'=>true,'verified'=>true),'sensitive_confirmed'=>true),(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());
}
function fixture_member($id,$active,$revision=null,$command=null,$readonly=false) {
 $overrides=$readonly?array():array_fill_keys(array_keys(CSF_Scheduling_Permissions::registry()),'grant');
 return CSF_Scheduling_Store::permissions('team_member_saved',array('user_id'=>$id,'member'=>array('group'=>'read_only','overrides'=>$overrides,'resources'=>array(),'active'=>$active,'verified'=>true),'sensitive_confirmed'=>true),$revision??(int)CSF_Scheduling_Schema::settings()['revision'],$command??wp_generate_uuid4());
}

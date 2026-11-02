<?php
// Trusted synthetic adapter ONLY in the disposable fixture. Never deploy this file.
if ( ! defined('ABSPATH') || ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { return; }
add_filter('csf_scheduling_mfa_verified',static fn($value,$id)=>$id>0 && (bool)get_userdata($id),10,2);
add_filter('csf_scheduling_mfa_enrolled',static fn($value,$id)=>true===apply_filters('csf_scheduling_mfa_verified',false,$id),10,2);
function fixture_staff($id,$denies=array()) {
 $overrides=array_fill_keys(array_keys(CSF_Scheduling_Permissions::registry()),'grant');
 foreach($denies as$p){$overrides[$p]='deny';}
 return CSF_Scheduling_Store::permissions('team_member_saved',array('user_id'=>$id,'member'=>array('group'=>'read_only','overrides'=>$overrides,'resources'=>array(),'active'=>true,'verified'=>true),'sensitive_confirmed'=>true),(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());
}
function fixture_member($id,$active,$revision=null,$command=null,$readonly=false) {
 $overrides=$readonly?array():array_fill_keys(array_keys(CSF_Scheduling_Permissions::registry()),'grant');
 return CSF_Scheduling_Store::permissions('team_member_saved',array('user_id'=>$id,'member'=>array('group'=>'read_only','overrides'=>$overrides,'resources'=>array(),'active'=>$active,'verified'=>true),'sensitive_confirmed'=>true),$revision??(int)CSF_Scheduling_Schema::settings()['revision'],$command??wp_generate_uuid4());
}
// Test data construction only. Production master changes must use independent real-browser approvals.
function fixture_master($id){
 if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture only');}
 global$wpdb;$s=CSF_Scheduling_Permissions::state();$old=$s['master'];if($old!==null&&$old!==$id){$s['members'][$old]['active']=false;}
 $s['master']=$id;$s['members'][$id]=array('group'=>'read_only','overrides'=>array(),'resources'=>array(),'active'=>true,'verified'=>true);$wpdb->update(CSF_Scheduling_Schema::table('authorization'),array('registry_json'=>wp_json_encode($s)),array('id'=>1));CSF_Scheduling_Access::reconcile();if($old!==null&&$old!==$id){WP_Session_Tokens::get_instance($old)->destroy_all();}return array('synthetic_fixture_master'=>$id);
}

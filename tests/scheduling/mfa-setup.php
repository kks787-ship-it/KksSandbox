<?php
// Synthetic identities, generated secrets and domain data only, on the disposable fixture.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture required');}
global $wpdb;
wp_set_current_user(1);
$result=CSF_Scheduling_Schema::migrate();if(is_wp_error($result)){throw new RuntimeException('Migration failed');}
$policy=CSF_Scheduling_Policy::defaults();$policy['timezone']='America/Los_Angeles';$wpdb->update(CSF_Scheduling_Schema::table('settings'),array('policy_json'=>wp_json_encode($policy)),array('id'=>1));
$trainer=wp_create_user('mfa_trainer','fixture-only','mfa-trainer@example.invalid');$outside=wp_create_user('mfa_outside','fixture-only','mfa-outside@example.invalid');
$totp=Two_Factor_Totp::get_instance();$back=Two_Factor_Backup_Codes::get_instance();$private=array();
foreach(array(1,$trainer,$outside)as$id){
 $totp->set_user_totp_key($id,$totp::generate_key());update_user_meta($id,'_two_factor_enabled_providers',array('Two_Factor_Totp','Two_Factor_Backup_Codes'));update_user_meta($id,'_two_factor_provider','Two_Factor_Totp');
 $private[$id]=$back->generate_codes(get_userdata($id));
}
$s=CSF_Scheduling_Permissions::initial();$s['master']=1;
foreach(array(1,$trainer)as$id){$s['members'][$id]=array('group'=>$id===1?'read_only':'head_trainer','overrides'=>array(),'resources'=>array(),'active'=>true,'verified'=>true);}
$wpdb->update(CSF_Scheduling_Schema::table('authorization'),array('registry_json'=>wp_json_encode($s)),array('id'=>1));
if(is_wp_error(CSF_Scheduling_Access::reconcile())){throw new RuntimeException('Mirror fixture failed');}
file_put_contents('/test/mfa-private.json',wp_json_encode(array('trainer'=>$trainer,'outside'=>$outside,'backup'=>$private)));chmod('/test/mfa-private.json',0600);
global$checks;$checks=0;function mcheck($ok,$text){global$checks;if(!$ok){throw new RuntimeException($text);}echo"PASS $text\n";$checks++;}
mcheck(CSF_MFA_Session::ready(),'pinned provider and adapter active in isolated fixture');
mcheck(CSF_Scheduling_Permissions::enrolled(1)&&CSF_Scheduling_Permissions::enrolled($trainer),'enrollment available separately for verified identities');
mcheck(!CSF_Scheduling_Permissions::mfa(1)&&!CSF_Scheduling_Access::can('calendar_all'),'CLI --user is never a browser MFA session');
mcheck(!CSF_Scheduling_Permissions::identity_gate(1),'legacy CLI master bootstrap fails closed pending a separately designed identity ceremony');
mcheck(array_keys(apply_filters('two_factor_providers',array_fill_keys(array('Two_Factor_Totp','Two_Factor_Backup_Codes','Two_Factor_Email','Two_Factor_Dummy'),'fixture')) )===array('Two_Factor_Totp','Two_Factor_Backup_Codes'),'email and dummy cannot become approved factors');
$before=CSF_MFA_Session::meta($trainer,'_two_factor_totp_key');update_user_meta($trainer,'_two_factor_enabled_providers',array('Two_Factor_Email'));mcheck(!CSF_Scheduling_Permissions::enrolled($trainer),'email-only enrollment denied');update_user_meta($trainer,'_two_factor_enabled_providers',array('Two_Factor_Totp','Two_Factor_Backup_Codes'));
$wpdb->insert($wpdb->usermeta,array('user_id'=>$trainer,'meta_key'=>'_two_factor_totp_key','meta_value'=>'INVALID'));mcheck(null===CSF_MFA_Session::meta($trainer,'_two_factor_totp_key')&&!CSF_Scheduling_Permissions::enrolled($trainer),'duplicate credential meta fails closed');$wpdb->delete($wpdb->usermeta,array('user_id'=>$trainer,'meta_key'=>'_two_factor_totp_key','meta_value'=>'INVALID'));
mcheck($before===CSF_MFA_Session::meta($trainer,'_two_factor_totp_key'),'synthetic credential restored after malformed state test');
echo 'MFA_SETUP_ASSERTIONS='.$checks."\n";

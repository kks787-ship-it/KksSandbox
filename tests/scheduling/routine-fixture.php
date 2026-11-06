<?php
// Fast setup in the disposable fixture only. Never deploy or use on a farm.
if(!defined('WP_CLI')||!WP_CLI||ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Local fixture only');}
require '/repo/tests/scheduling/auth-fixture.php';
add_filter('csf_scheduling_mfa_verified',static fn()=>true,PHP_INT_MAX);
add_filter('csf_scheduling_mfa_enrolled',static fn()=>true,PHP_INT_MAX);
wp_set_current_user(1);
$call=static function($kind,$body){$r=CSF_Scheduling_Store::calendar($kind,$body,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException(wp_json_encode($r));return$r;};
$save=static function($kind,$data,$id=null)use($call){$id=$id??wp_generate_uuid4();$call($kind,array('uuid'=>$id,'expected'=>0,'data'=>$data));return$id;};
$r=CSF_Scheduling_Store::configure(CSF_Scheduling_Settings::pilot(),(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Policy seed');
$ids=array();foreach(array('instructor','arena','horse')as$kind){$id=wp_generate_uuid4();$r=CSF_Scheduling_Store::resource(array('uuid'=>$id,'kind'=>$kind,'label'=>'Synthetic '.$kind),(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Resource seed');$save('availability_saved',array('resource_uuid'=>$id,'intervals'=>array(array('start'=>'2027-01-25T08:00-08:00','end'=>'2027-02-01T18:00-08:00'))),$id);$ids[$kind]=$id;}
$rider=$save('rider_saved',array('label'=>'Synthetic rider','adult'=>false,'release_verified'=>false,'guardian_verified'=>false,'emergency_verified'=>false));
$horse=$save('horse_saved',array('label'=>'Synthetic horse','source'=>'school','resource_uuid'=>$ids['horse'],'suitable_services'=>array('pilot_private','pilot_group'),'suitable_riders'=>array($rider),'training_enrolled'=>false));
echo wp_json_encode(array('riders'=>array($rider),'horses'=>array($horse),'instructor'=>$ids['instructor'],'arena'=>$ids['arena']));

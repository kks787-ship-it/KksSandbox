<?php
// Disposable local fixture only. Never a farm provisioning or authentication path.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local')throw new RuntimeException('Fixture only');
ob_start();require '/repo/tests/scheduling/resource-hours-domain.php';$ids=json_decode(ob_get_clean(),true);
$policy=json_decode(CSF_Scheduling_Schema::settings()['policy_json'],true);$policy['services']['pilot_private']['label']='Student — 30 Minutes';$policy['services']['pilot_ground_60']['label']='Student — 60 Minutes';
$r=CSF_Scheduling_Store::configure($policy,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic lesson labels');
$doc=CSF_Scheduling_Calendar::find('riders',$ids['riders'][0]);$data=json_decode($doc['body_json'],true);$data['release_verified']=true;$data['emergency_verified']=true;$data['guardian_verified']=true;
$r=CSF_Scheduling_Store::calendar('rider_saved',array('uuid'=>$doc['uuid'],'expected'=>(int)$doc['revision'],'data'=>$data),(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic rider evidence');
$ids['policy_without_services']=$policy;unset($ids['policy_without_services']['services']);file_put_contents('/test/ux-fixture.json',wp_json_encode($ids));echo wp_json_encode($ids);

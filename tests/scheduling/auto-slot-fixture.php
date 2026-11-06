<?php
// Local-only synthetic selection cases; never a protected-farm setup path.
if(!defined('WP_CLI')||!WP_CLI||ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture only');}
ob_start();require '/repo/tests/scheduling/routine-fixture.php';$ids=json_decode(ob_get_clean(),true);
$p=CSF_Scheduling_Settings::pilot();$p['service_hours'][0]=array(array('00:00','04:00'));
foreach(array(60,120)as$minutes){$p['services']['pilot_ground_'.$minutes]=array('enabled'=>true,'capacity'=>1,'duration_minutes'=>$minutes,'requires_travel'=>false,'horse_source'=>'none','required_resources'=>array('instructor','arena'));}
$r=CSF_Scheduling_Store::configure($p,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic policy');
foreach(CSF_Scheduling_Calendar::documents('availability')as$doc){$call('availability_saved',array('uuid'=>$doc['uuid'],'expected'=>(int)$doc['revision'],'data'=>array('resource_uuid'=>$doc['entity_uuid'],'intervals'=>array(array('start'=>'2027-01-25T00:00-08:00','end'=>'2027-02-01T18:00-08:00'),array('start'=>'2027-03-14T00:00-08:00','end'=>'2027-03-14T04:00-07:00'),array('start'=>'2027-11-07T00:00-07:00','end'=>'2027-11-07T04:00-08:00')))));}
$id=wp_generate_uuid4();$r=CSF_Scheduling_Store::resource(array('uuid'=>$id,'kind'=>'horse','label'=>'Synthetic unsuitable resource'),(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic resource');$save('availability_saved',array('resource_uuid'=>$id,'intervals'=>array(array('start'=>'2027-01-25T00:00-08:00','end'=>'2027-02-01T18:00-08:00'))),$id);
$ids['unsuitable']=$save('horse_saved',array('label'=>'Synthetic unsuitable horse','source'=>'school','resource_uuid'=>$id,'suitable_services'=>array(),'suitable_riders'=>array(),'training_enrolled'=>false));
echo wp_json_encode($ids);

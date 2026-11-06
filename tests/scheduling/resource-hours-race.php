<?php
// Concurrent workers only within the disposable local WordPress fixture.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local')throw new RuntimeException('Fixture only');
require '/repo/tests/scheduling/auth-fixture.php';add_filter('csf_scheduling_mfa_verified',static fn()=>true,PHP_INT_MAX);add_filter('csf_scheduling_mfa_enrolled',static fn()=>true,PHP_INT_MAX);wp_set_current_user(1);
$mode=getenv('CSF_RESOURCE_RACE');$path='/test/resource-hours-race.json';
if($mode==='setup'){
 $ids=json_decode(file_get_contents('/test/resource-hours-fixture.json'),true);$a=wp_generate_uuid4();$c=wp_generate_uuid4();
 $booking=array('uuid'=>$a,'expected'=>0,'title'=>'Synthetic concurrent availability booking','service_type'=>'pilot_ground_60','state'=>'confirmed','start'=>'2027-01-25T14:00-08:00','end'=>'2027-01-25T15:00-08:00','hold_expires'=>null,'resources'=>array($ids['instructor'],$ids['arena']),'riders'=>array(),'rides'=>array());
 $closure=array('uuid'=>$c,'expected'=>0,'impact_acknowledged'=>true,'data'=>array('target_uuid'=>$ids['arena'],'start'=>$booking['start'],'end'=>$booking['end'],'category'=>'unavailable','note'=>'Synthetic concurrent closure','repeat_until'=>null));
 file_put_contents($path,wp_json_encode(array('revision'=>(int)CSF_Scheduling_Schema::settings()['revision'],'booking'=>$booking,'closure'=>$closure)));exit;
}
$v=json_decode(file_get_contents($path),true);
if(in_array($mode,array('booking','closure'),true)){
 touch('/test/resource-race-'.$mode.'.ready');$until=microtime(true)+10;while(!file_exists('/test/resource-race-booking.ready')||!file_exists('/test/resource-race-closure.ready')){if(microtime(true)>$until)throw new RuntimeException('Concurrent barrier timeout');usleep(10000);}
 $r=CSF_Scheduling_Store::calendar($mode==='booking'?'appointment_saved':'resource_closure_added',$v[$mode],$v['revision'],wp_generate_uuid4());
 file_put_contents('/test/resource-race-'.$mode.'.result',wp_json_encode(is_wp_error($r)?array('error'=>$r->get_error_data()['reason']??$r->get_error_code()):array('success'=>true)));exit;
}
$a=json_decode(file_get_contents('/test/resource-race-booking.result'),true);$b=json_decode(file_get_contents('/test/resource-race-closure.result'),true);
if(((int)!empty($a['success'])+(int)!empty($b['success']))!==1||(($a['error']??$b['error']??'')!=='stale_revision'))throw new RuntimeException('Race must commit once and reject stale peer: '.wp_json_encode(array($a,$b)));
$loser=empty($a['success'])?'booking':'closure';$r=CSF_Scheduling_Store::calendar($loser==='booking'?'appointment_saved':'resource_closure_added',$v[$loser],(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());
if($loser==='booking'){if(!is_wp_error($r))throw new RuntimeException('Closed resource permitted booking retry');}
else{if(is_wp_error($r)||$r['affected_appointments']!==1||CSF_Scheduling_Calendar::find('appointments',$v['booking']['uuid'])['state']!=='confirmed')throw new RuntimeException('Reviewed closure retry lost confirmed appointment');}
echo "RESOURCE_CLOSURE_BOOKING_RACE_ONE_COMMIT_ONE_STALE_REVALIDATED_PASS\n";

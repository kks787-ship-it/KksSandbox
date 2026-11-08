<?php
// Synthetic fixture only, CLI setup never grants a real browser MFA session.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local')throw new RuntimeException('Fixture required');
ob_start();require '/repo/tests/scheduling/auto-slot-fixture.php';$ids=json_decode(ob_get_clean(),true);
$p=json_decode(CSF_Scheduling_Schema::settings()['policy_json'],true);$p['service_hours']=array_fill(0,7,[['06:00','22:00']]);$p['services']['pilot_ground_30']=$p['services']['pilot_ground_60'];$p['services']['pilot_ground_30']['duration_minutes']=30;
// Keep this synthetic facility at 08:xx so its genuine future-only override is
// valid regardless of runner time. Other calendar suites cover America/Los_Angeles DST.
$offset=8-(int)gmdate('G');if($offset < -12)$offset+=24;
$p['timezone']=$offset===0?'Etc/GMT':'Etc/GMT'.($offset>0?'-':'+').abs($offset);
$r=CSF_Scheduling_Store::configure($p,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Fixture policy');
$zone=new DateTimeZone($p['timezone']);$day=(new DateTimeImmutable('now',$zone))->format('Y-m-d');
foreach(CSF_Scheduling_Calendar::documents('availability')as$doc)$call('availability_saved',['uuid'=>$doc['uuid'],'expected'=>(int)$doc['revision'],'data'=>['resource_uuid'=>$doc['entity_uuid'],'intervals'=>[['start'=>(new DateTimeImmutable($day.' 00:00',$zone))->modify('-1 day')->format('Y-m-d\TH:iP'),'end'=>(new DateTimeImmutable($day.' 00:00',$zone))->modify('+10 days')->format('Y-m-d\TH:iP')]]]]);
$trainer=(int)get_user_by('login','mfa_trainer')->ID;$member=CSF_Scheduling_Permissions::state()['members'][$trainer];$member['group']='instructor';$member['resources']=[$ids['instructor']];$member['overrides']=[];
$r=CSF_Scheduling_Store::permissions('team_member_saved',['user_id'=>$trainer,'member'=>$member,'sensitive_confirmed'=>true],(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic assigned-only member');
$make=static function($clock,$minutes,$title)use($day,$zone,$ids){$start=new DateTimeImmutable($day.' '.$clock,$zone);return ['uuid'=>wp_generate_uuid4(),'expected'=>0,'title'=>$title,'service_type'=>'pilot_ground_'.$minutes,'state'=>'confirmed','start'=>$start->format('Y-m-d\TH:iP'),'end'=>$start->modify('+'.$minutes.' minutes')->format('Y-m-d\TH:iP'),'hold_expires'=>null,'resources'=>[$ids['instructor'],$ids['arena']],'riders'=>[$ids['riders'][0]],'rides'=>[]];};
$group=['uuid'=>wp_generate_uuid4(),'expected'=>0,'same_family'=>true,'items'=>[['kind'=>'appointment_saved','body'=>$make('10:00',30,'Synthetic sibling A')],['kind'=>'appointment_saved','body'=>$make('10:30',30,'Synthetic sibling B')]]];$call('family_visit_saved',$group);
$lesson=$make('14:00',60,'Synthetic changed lesson');$event=wp_generate_uuid4();$r=CSF_Scheduling_Store::calendar('appointment_saved',$lesson,(int)CSF_Scheduling_Schema::settings()['revision'],$event);if(is_wp_error($r))throw new RuntimeException('Synthetic lesson');
$owner=CSF_Scheduling_Permissions::state()['members'][1];$owner['overrides']['buffer_override']='grant';$r=CSF_Scheduling_Store::permissions('team_member_saved',['user_id'=>1,'member'=>$owner,'sensitive_confirmed'=>true],(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());if(is_wp_error($r))throw new RuntimeException('Synthetic override grant');
$call('buffer_overridden',['uuid'=>$lesson['uuid'],'expected'=>1,'resource_uuid'=>$ids['instructor'],'segment'=>'pre','minutes'=>0,'reason'=>'Synthetic reviewed buffer exception']);
$ids+=['trainer'=>$trainer,'day'=>$day,'lesson'=>$lesson,'event'=>$event,'group'=>$group['uuid']];file_put_contents('/test/dashboard-notice-fixture.json',wp_json_encode($ids));echo wp_json_encode($ids);

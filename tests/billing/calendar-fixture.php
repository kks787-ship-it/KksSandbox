<?php
/** Real scheduler command probes, only within money-fixture's authenticated disposable scope. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||ABSPATH!=='/tmp/csf-combined-restored/'||DB_NAME!=='billing_restore'||!preg_match('/^money_probe_[a-f0-9]{16}_$/D',$wpdb->prefix)){throw new RuntimeException('Synthetic scheduler only');}
define('CSF_BILLING_BOOKING_ENABLED',true);define('CSF_BILLING_BOOKING_POLICY_JSON',wp_json_encode(array('version'=>'synthetic-only','approved'=>true,'products'=>array('11111111-1111-4111-8111-111111111111'=>array('lesson')),'release_initiators'=>array('facility'))));
function calendar_probe_ok($r){if(is_wp_error($r))throw new CSF_Billing_Failure('synthetic_scheduler_'.($r->get_error_data()['reason']??$r->get_error_code()));return $r;}
function calendar_probe_rev(){return (int)CSF_Scheduling_Schema::settings()['revision'];}
function calendar_probe_save($kind,$data,$uuid=null){$uuid=$uuid??wp_generate_uuid4();calendar_probe_ok(CSF_Scheduling_Store::calendar($kind,array('uuid'=>$uuid,'expected'=>0,'data'=>$data),calendar_probe_rev(),wp_generate_uuid4()));return $uuid;}
if($action==='calendar_setup'){
 calendar_probe_ok(CSF_Scheduling_Schema::migrate());
 // Scratch registry and scratch-only mirror, not grants on the restored/farm facility.
 $s=CSF_Scheduling_Permissions::initial();$s['master']=1;$s['members'][1]=array('group'=>'read_only','overrides'=>array(),'resources'=>array(),'active'=>true,'verified'=>true);
 $wpdb->update(CSF_Scheduling_Schema::table('authorization'),array('registry_json'=>wp_json_encode($s)),array('id'=>1));
 $mirror=array();foreach(CSF_Scheduling_Permissions::registry() as $permission=>$label)$mirror['csf_dr_'.$permission]=true;
 add_user_meta(1,$wpdb->prefix.'capabilities',$mirror,true);
 $p=CSF_Scheduling_Policy::defaults();$p['timezone']='UTC';$p['service_hours']=array_fill(0,7,array(array('06:00','22:00')));$p['training_horse_cap']=5;$p['academy']=array('enabled'=>false);$p['workload']=array('daily_minutes'=>300,'consecutive_minutes'=>120,'break_minutes'=>15);$p['travel_buffer_minutes']=15;
 $p['services']['lesson']=array('enabled'=>true,'capacity'=>1,'duration_minutes'=>60,'horse_source'=>'school','requires_travel'=>false,'required_resources'=>array('instructor','arena','horse'));
 calendar_probe_ok(CSF_Scheduling_Store::configure($p,calendar_probe_rev(),wp_generate_uuid4()));
 $resources=array();foreach(array('instructor','arena','horse') as $kind){$uuid=wp_generate_uuid4();calendar_probe_ok(CSF_Scheduling_Store::resource(array('uuid'=>$uuid,'kind'=>$kind,'label'=>'Synthetic '.$kind),calendar_probe_rev(),wp_generate_uuid4()));calendar_probe_save('availability_saved',array('resource_uuid'=>$uuid,'intervals'=>array(array('start'=>gmdate('Y-m-d\T00:00+00:00',time()-10*86400),'end'=>gmdate('Y-m-d\T23:00+00:00',time()+10*86400)))),$uuid);$resources[$kind]=$uuid;}
 $rider=calendar_probe_save('rider_saved',array('label'=>'Synthetic rider','adult'=>true,'release_verified'=>false,'guardian_verified'=>false,'emergency_verified'=>false));
 $horse=calendar_probe_save('horse_saved',array('label'=>'Synthetic horse','source'=>'school','resource_uuid'=>$resources['horse'],'suitable_services'=>array('lesson'),'suitable_riders'=>array($rider),'training_enrolled'=>false));
 $start=gmdate('Y-m-d\T12:00+00:00',time()+86400);$end=gmdate('Y-m-d\T13:00+00:00',time()+86400);
 $body=array('uuid'=>wp_generate_uuid4(),'expected'=>0,'title'=>'Synthetic funded lesson','service_type'=>'lesson','state'=>'confirmed','start'=>$start,'end'=>$end,'hold_expires'=>null,'resources'=>array($resources['instructor'],$resources['arena']),'riders'=>array($rider),'rides'=>array(array('horse'=>$horse,'rider'=>$rider,'start'=>$start,'end'=>$end)));
 $result=array('body'=>$body,'revision'=>calendar_probe_rev());
}elseif($action==='calendar_command'){
 $fault=null;$mode=$input['fault']??'';
 if($mode==='history'){$target='INSERT INTO `'.CSF_Scheduling_Schema::table('history').'`';$fault=static fn($sql)=>str_starts_with($sql,$target)?'SELECT * FROM nonexistent_synthetic_history_fault':$sql;}
 if($mode==='lost_commit'){$fault=static function($sql)use(&$fault){if($sql==='COMMIT'){remove_filter('query',$fault);global $wpdb;$wpdb->query('COMMIT');return 'SELECT * FROM nonexistent_synthetic_ack_fault';}return $sql;};}
 if($fault)add_filter('query',$fault);
 try{
  if($input['transition']==='cancel')$r=CSF_Scheduling_Store::calendar('appointment_canceled',$input['body'],$input['revision'],$input['command']);
  else{$c=$store->snapshot(static fn($s)=>$s->row('certificates',$input['id']));$number=CSF_Billing_Config::open($c['number_sealed']);$r=CSF_Billing_Booking::submit($input['body'],$input['revision'],$input['command'],$input['transition'],$number);}
  $result=is_wp_error($r)?array('committed'=>false,'reason'=>$r->get_error_data()['reason']??$r->get_error_code()):array('committed'=>true,'result'=>$r);
 }finally{if($fault)remove_filter('query',$fault);}
}elseif($action==='calendar_state'){
 $a=CSF_Scheduling_Calendar::find('appointments',$input['booking']);$d=CSF_Scheduling_Calendar::find('details',$input['booking']);
 $result=array('revision'=>calendar_probe_rev(),'appointment'=>$a?array('state'=>$a['state'],'revision'=>(int)$a['revision']):null,'detail'=>$d?json_decode($d['body_json'],true):null,'appointments'=>(int)$wpdb->get_var('SELECT COUNT(*) FROM '.CSF_Scheduling_Schema::table('appointments')));
}elseif($action==='calendar_age'){
 // Simulate elapsed time in synthetic rows only; no application clock/MFA bypass.
 $b=$input['body'];$a=CSF_Scheduling_Calendar::find('appointments',$b['uuid']);if(!$a)throw new RuntimeException('Missing synthetic appointment');
 $d=CSF_Scheduling_Calendar::find('details',$b['uuid']);$detail=json_decode($d['body_json'],true);
 foreach(array('start','end') as $field){$b[$field]=(new DateTimeImmutable($b[$field]))->modify('-2 days')->format('Y-m-d\TH:iP');$detail[$field]=$b[$field];foreach($b['rides'] as &$ride)$ride[$field]=(new DateTimeImmutable($ride[$field]))->modify('-2 days')->format('Y-m-d\TH:iP');unset($ride);}
 $detail['rides']=$b['rides'];foreach($detail['resource_reservations'] as &$window){$window['start']-=172800;$window['end']-=172800;}unset($window);
 $wpdb->query('START TRANSACTION');try{
  foreach(array('appointments','allocations','slots','participants') as $name){$table=CSF_Scheduling_Schema::table($name);$where=$name==='appointments'?'id':'appointment_id';if(false===$wpdb->query($wpdb->prepare("UPDATE $table SET start_utc=DATE_SUB(start_utc,INTERVAL 2 DAY),end_utc=DATE_SUB(end_utc,INTERVAL 2 DAY) WHERE $where=%d",$a['id'])))throw new RuntimeException('Synthetic aging failed');}
  $wpdb->update(CSF_Scheduling_Schema::table('details'),array('body_json'=>wp_json_encode($detail)),array('uuid'=>$b['uuid']));$wpdb->query('COMMIT');
 }catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
 $b['expected']=(int)$a['revision'];$result=array('body'=>$b,'revision'=>calendar_probe_rev());
}else throw new RuntimeException('Unknown synthetic calendar action');

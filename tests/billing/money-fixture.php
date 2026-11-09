<?php
/** Real service/SQL concurrency fixture behind a genuine restored MFA session; never deploy. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||ABSPATH!=='/tmp/csf-combined-restored/'||DB_NAME!=='billing_restore'){throw new RuntimeException('Isolated money fixture only');}
CSF_Billing_Access::require_cap('csf_billing_manage');
if(!wp_verify_nonce($_SERVER['HTTP_X_WP_NONCE']??'','wp_rest')){wp_send_json(array('error'=>'nonce'),403);}
$input=json_decode(file_get_contents('php://input'),true);$token=$input['token']??'';
if(!is_string($token)||!preg_match('/^[a-f0-9]{16}$/D',$token)){throw new RuntimeException('Fixture prefix');}
global $wpdb;$previous=$wpdb->prefix;$wpdb->prefix='money_probe_'.$token.'_';$store=new CSF_Billing_Store();$service=new CSF_Billing_Service($store);
try{
 $action=$input['action']??'';
 if($action==='setup'){
  foreach(CSF_Billing_Schema::statements() as $sql){if(false===$wpdb->query($sql)){throw new RuntimeException('Fresh fixture only');}}
  $wpdb->query('CREATE TABLE '.$wpdb->prefix.'test_bookings (uuid char(36) PRIMARY KEY, body_json longtext NOT NULL) ENGINE=InnoDB');
  $ids=array();foreach(array('workflow','race','replay','fulfillment','booking') as $label){
   $id=wp_generate_uuid4();$ids[$label]=$id;
   $store->command(hash('sha256','seed:'.$id),array('synthetic'=>$id),static function($s)use($id){
    $order=wp_generate_uuid4();$s->insert('orders',array('id'=>$order,'session_hash'=>str_repeat('a',64),'status'=>'issued','capture_id'=>'SYNTHETIC'.strtoupper(bin2hex(random_bytes(8))),'revision'=>1,'document'=>wp_json_encode(array('id'=>$order,'fulfillment'=>CSF_Billing_Fulfillment::snapshot(CSF_Billing_Fulfillment::selection(array('buyer_email'=>'buyer@fixture.invalid'))),'product'=>array('id'=>'11111111-1111-4111-8111-111111111111','minutes_per_ride'=>60,'label'=>'Synthetic management fixture'),'amount_minor'=>101,'currency'=>'USD','terms'=>array('version'=>'synthetic','text'=>'Fixture only'),'accepted_at'=>'2026-11-08T00:00:00Z')),'created_at'=>'2026-11-08 00:00:00'));
    $number='CSF-'.strtoupper(bin2hex(random_bytes(16)));$v=CSF_Billing_Value::initial(101,3,array('method'=>'proportional_floor_v1','version'=>'synthetic','approved'=>true));
    $s->insert('certificates',array('id'=>$id,'order_id'=>$order,'number_hash'=>hash('sha256',$number),'number_sealed'=>CSF_Billing_Config::seal($number),'value_json'=>wp_json_encode($v),'purchased'=>3,'redeemed'=>0,'status'=>'active','revision'=>1));return array('seeded'=>true);
   });
  }$result=$ids;
 }elseif($action==='cash'){$result=$service->cash($input['command'],$input['id'],$input['revision'],$input['transition'],$input['request'],$input['amount'],$input['evidence'],$input['attested']);}
 elseif($action==='redeem'){$result=$service->redeem($input['command'],$input['id'],$input['rides'],$input['revision'],$input['ride']);}
 elseif($action==='correct'){$result=$service->correct($input['command'],$input['id'],$input['original'],$input['revision'],'mistaken_ride');}

 elseif($action==='rotate'){$result=$service->rotate($input['command'],$input['id'],$input['revision'],true);}
 elseif($action==='pdf'){$row=$store->snapshot(static fn($s)=>$s->row('certificates',$input['id']));$r=CSF_Billing_PDF::document($store,$row['order_id'],null);$bytes=CSF_Billing_Config::open($r['sealed']);$result=array('artifact_id'=>$r['artifact_id'],'sha256'=>$r['sha256'],'valid'=>str_starts_with($bytes,'%PDF-1.4')&&hash('sha256',$bytes)===$r['sha256']);}
 elseif($action==='booking_sql'){
  // This tests actual joined SQL atomicity, not the full scheduler's availability/authorization path.
  define('CSF_BILLING_BOOKING_ENABLED',true);define('CSF_BILLING_BOOKING_POLICY_JSON',wp_json_encode(array('version'=>'synthetic-only','approved'=>true,'products'=>array('11111111-1111-4111-8111-111111111111'=>array('lesson')),'release_initiators'=>array('facility'))));
  $calendarFlag=new ReflectionProperty(CSF_Scheduling_Store::class,'calendar_transaction');$calendarFlag->setAccessible(true);
  $wpdb->query('START TRANSACTION');$calendarFlag->setValue(null,true);
  try{
   $body=$input['body'];$before=$wpdb->get_var($wpdb->prepare('SELECT body_json FROM '.$wpdb->prefix.'test_bookings WHERE uuid=%s FOR UPDATE',$body['uuid']));$before=$before?json_decode($before,true):null;
   $row=$store->join_calendar(static fn($s)=>$s->row('certificates',$input['id']));
   CSF_Billing_Booking::context($input['transition']==='cancel'?null:array('transition'=>$input['transition'],'number_hash'=>$row['number_hash'],'command'=>$input['command']));$detail=$body;
   CSF_Billing_Booking::participate($input['transition']==='cancel'?'appointment_canceled':'appointment_saved',$body,$before,$detail,'UTC');
   if(false===$wpdb->replace($wpdb->prefix.'test_bookings',array('uuid'=>$body['uuid'],'body_json'=>wp_json_encode($detail))))throw new RuntimeException('Booking fixture write');
   if(!empty($input['fail']))throw new CSF_Billing_Failure('synthetic_outer_failure');
   if(false===$wpdb->query('COMMIT'))throw new RuntimeException('Booking fixture commit');$result=array('state'=>$detail['billing_credit_state']);
  }catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}finally{$calendarFlag->setValue(null,false);CSF_Billing_Booking::context(null);}
 }
 elseif($action==='state'){$result=$store->snapshot(static function($s)use($input){$row=$s->row('certificates',$input['id']);return array('order_id'=>$row['order_id'],'revision'=>(int)$row['revision'],'redeemed'=>(int)$row['redeemed'],'value'=>CSF_Billing_Value::read($row),'reserved'=>CSF_Billing_Booking::slots($s,$input['id']));});}
 elseif($action==='cleanup'){foreach(array('guard','orders','certificates','commands','audit','throttle') as $table){$wpdb->query('DROP TABLE '.$store->table($table));}$wpdb->query('DROP TABLE '.$wpdb->prefix.'test_bookings');$result=array('removed'=>true);}
 else{throw new RuntimeException('Unknown fixture action');}
 wp_send_json(array('ok'=>true,'result'=>$result));
}catch(Throwable $e){wp_send_json(array('ok'=>false,'error'=>$e instanceof CSF_Billing_Failure?$e->getMessage():'fixture_transaction_failed'),409);}finally{$wpdb->prefix=$previous;}

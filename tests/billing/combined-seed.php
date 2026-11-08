<?php
/** Construct clearly synthetic recovery records only; not evidence of provider verification. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||DB_NAME!=='wp_mdf4x'||!is_dir('/fixture')){throw new RuntimeException('Offline container only');}
require_once ABSPATH.'wp-admin/includes/plugin.php';$r=activate_plugin('csf-billing/csf-billing.php');if(is_wp_error($r)){throw new RuntimeException('Fixture billing activation');}
global $wpdb;foreach(CSF_Billing_Schema::statements() as $sql){if(false===$wpdb->query($sql)){throw new RuntimeException('Fixture billing schema');}}
$store=new CSF_Billing_Store();
foreach(array('issued','frozen','capture_uncertain','create_uncertain','prepared','awaiting_approval','approved') as $index=>$state){
 $id=wp_generate_uuid4();$certificate=wp_generate_uuid4();$capture='SYNTHETICCAPTURE'.$index;$provider='SYNTHETICORDER'.$index;
 $terms=array('version'=>'synthetic','text'=>'Synthetic recovery terms only.','sha256'=>hash('sha256','Synthetic recovery terms only.'),'approved'=>true,'legal_reviewed'=>true);
 $doc=array('id'=>$id,'product'=>array('id'=>wp_generate_uuid4(),'revision'=>1,'label'=>'Synthetic recovery package','minutes_per_ride'=>60,'approved'=>true,'rides'=>2,'amount_minor'=>12345,'currency'=>'USD','payment_mode'=>'one_time'),'amount_minor'=>12345,'currency'=>'USD','terms'=>$terms,'accepted_at'=>'2026-11-08T00:00:00Z','create_request_id'=>wp_generate_uuid4(),'capture_request_id'=>wp_generate_uuid4());
 $store->command(hash('sha256','seed:'.$id),array('synthetic'=>$id),static function($s)use($id,$state,$doc,$provider,$capture){
  $s->insert('orders',array('id'=>$id,'session_hash'=>str_repeat('a',64),'provider_order_id'=>in_array($state,array('prepared','create_uncertain'),true)?null:$provider,'capture_id'=>in_array($state,array('issued','frozen'),true)?$capture:null,'status'=>$state,'revision'=>1,'document'=>wp_json_encode($doc),'created_at'=>'2026-11-08 00:00:00'));return array('seeded'=>true);
 });
 if(!in_array($state,array('issued','frozen'),true)){continue;}
 $number='CSF-'.strtoupper(bin2hex(random_bytes(16)));
 $store->command(hash('sha256','issue:'.$capture),array('kind'=>'issue','order_id'=>$id,'capture_id'=>$capture),static function($s)use($id,$certificate,$state,$number){
  $s->insert('certificates',array('id'=>$certificate,'order_id'=>$id,'number_hash'=>hash('sha256',$number),'number_sealed'=>CSF_Billing_Config::seal($number),'purchased'=>2,'redeemed'=>0,'status'=>$state==='frozen'?'frozen':'active','revision'=>1));$s->audit('certificate_issued',0,$certificate,array('order_id'=>$id,'rides'=>2));return array('certificate_id'=>$certificate);
 });
 // Synthetic ledger histories use Store transactions; no access/MFA bypass or provider proof.
 if($state==='issued'){
  for($turn=0;$turn<2;$turn++){
   $command=wp_generate_uuid4();$ride=wp_generate_uuid4();
   $store->command(hash('sha256','redeem:'.$command),array('synthetic_debit'=>$command),static function($s)use($certificate,$command,$ride,$turn){
    $s->insert('commands',array('id'=>hash('sha256','ride:'.$certificate.':'.$ride),'payload_hash'=>hash('sha256',$certificate.':'.$ride),'result'=>'{}','created_at'=>'2026-11-08 00:00:00'));
    $s->update('certificates',$certificate,array('redeemed'=>1,'revision'=>$turn===0?2:4));
    $s->audit('rides_redeemed',0,$certificate,array('rides'=>1,'command'=>$command,'ride_reference'=>$ride));
    return array('certificate_id'=>$certificate,'command'=>$command,'rides'=>1);
   });
   if($turn===0){$store->command(hash('sha256','correct:'.$command),array('synthetic_correction'=>$command),static function($s)use($certificate,$command){
    $s->insert('commands',array('id'=>hash('sha256','reversed:'.$command),'payload_hash'=>hash('sha256',$command),'result'=>'{}','created_at'=>'2026-11-08 00:00:00'));
    $s->update('certificates',$certificate,array('redeemed'=>0,'revision'=>3));
    $s->audit('ride_debit_corrected',0,$certificate,array('original_command'=>$command,'rides'=>1,'reason'=>'mistaken_ride'));return array('certificate_id'=>$certificate);
   });}
  }
 }
 if($state==='frozen'){$store->command(hash('sha256','capture_hold:'.$capture),array('synthetic_hold'=>$capture),static fn()=>array('capture_id'=>$capture,'provider_order_id'=>$provider,'provider_event_id'=>'SYNTHETIC-REFUND'));}
}
echo 'SYNTHETIC_COMBINED_33_TABLES_SEEDED';

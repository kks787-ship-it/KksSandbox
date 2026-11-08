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
  $ids=array();foreach(array('workflow','race','replay') as $label){
   $id=wp_generate_uuid4();$ids[$label]=$id;
   $store->command(hash('sha256','seed:'.$id),array('synthetic'=>$id),static function($s)use($id){
    $v=CSF_Billing_Value::initial(101,3,array('method'=>'proportional_floor_v1','version'=>'synthetic','approved'=>true));
    $s->insert('certificates',array('id'=>$id,'order_id'=>wp_generate_uuid4(),'number_hash'=>hash('sha256',$id),'number_sealed'=>CSF_Billing_Config::seal('SYNTHETIC-'.$id),'value_json'=>wp_json_encode($v),'purchased'=>3,'redeemed'=>0,'status'=>'active','revision'=>1));return array('seeded'=>true);
   });
  }$result=$ids;
 }elseif($action==='cash'){$result=$service->cash($input['command'],$input['id'],$input['revision'],$input['transition'],$input['request'],$input['amount'],$input['evidence'],$input['attested']);}
 elseif($action==='redeem'){$result=$service->redeem($input['command'],$input['id'],$input['rides'],$input['revision'],$input['ride']);}
 elseif($action==='correct'){$result=$service->correct($input['command'],$input['id'],$input['original'],$input['revision'],'mistaken_ride');}
 elseif($action==='state'){$result=$store->snapshot(static function($s)use($input){$row=$s->row('certificates',$input['id']);return array('revision'=>(int)$row['revision'],'redeemed'=>(int)$row['redeemed'],'value'=>CSF_Billing_Value::read($row));});}
 elseif($action==='cleanup'){foreach(array('guard','orders','certificates','commands','audit','throttle') as $table){$wpdb->query('DROP TABLE '.$store->table($table));}$result=array('removed'=>true);}
 else{throw new RuntimeException('Unknown fixture action');}
 wp_send_json(array('ok'=>true,'result'=>$result));
}catch(CSF_Billing_Failure $e){wp_send_json(array('ok'=>false,'error'=>$e->getMessage()),409);}finally{$wpdb->prefix=$previous;}

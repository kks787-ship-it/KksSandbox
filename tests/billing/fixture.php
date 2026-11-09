<?php
/** Synthetic provider only; NEVER install this file on a farm or public site. */
if (!defined('ABSPATH') || ABSPATH !== '/tmp/csf-billing-fixture/site/' || DB_NAME !== 'csf_gift_fixture' || wp_get_environment_type() !== 'local' || !defined('CSF_BILLING_FIXTURE_MOCK')) { return; }
add_filter('pre_wp_mail',static fn()=>true);
add_filter('pre_http_request',static function($pre,$args,$url){
 if(!str_starts_with($url,'https://api-m.sandbox.paypal.com/')){return new WP_Error('fixture_external_blocked','External requests disabled in fixture.');}
 $path=parse_url($url,PHP_URL_PATH);$state=get_option('gift_fixture_provider',array());$body=array();
 if($path==='/v1/oauth2/token'){$body=array('access_token'=>'synthetic-token','token_type'=>'Bearer');}
 elseif($path==='/v2/checkout/orders'&&$args['method']==='POST'){
  $order=json_decode($args['body'],true);$id=strtoupper(substr(hash('sha256',$order['purchase_units'][0]['custom_id']),0,17));$order['id']=$id;$order['status']='CREATED';$state[$id]=$order;update_option('gift_fixture_provider',$state,false);if(is_file('/tmp/csf-billing-fixture/create-response-lost')){return new WP_Error('fixture_lost_response','Synthetic lost create response.');}$body=$order;
 }elseif(preg_match('~^/v2/checkout/orders/([A-Z0-9]+)(/capture)?$~',$path,$m)&&isset($state[$m[1]])){
  $id=$m[1];if(!empty($m[2])){
   if($state[$id]['status']!=='APPROVED'){return array('response'=>array('code'=>422),'body'=>'{}','headers'=>array());}
   $state[$id]['status']='COMPLETED';$state[$id]['purchase_units'][0]['payments']['captures']=array(array('id'=>'CAP'.$id,'status'=>'COMPLETED','final_capture'=>true,'amount'=>$state[$id]['purchase_units'][0]['amount'],'supplementary_data'=>array('related_ids'=>array('order_id'=>$id))));update_option('gift_fixture_provider',$state,false);if(is_file('/tmp/csf-billing-fixture/capture-response-lost')){return new WP_Error('fixture_lost_capture','Synthetic lost capture response.');}
  }$body=$state[$id];
 }elseif(preg_match('~^/v2/payments/captures/CAP([A-Z0-9]+)$~',$path,$m)&&isset($state[$m[1]]['purchase_units'][0]['payments']['captures'][0])){$body=$state[$m[1]]['purchase_units'][0]['payments']['captures'][0];}
 elseif($path==='/v1/notifications/verify-webhook-signature'){$verification=json_decode($args['body'],true);$body=array('verification_status'=>($verification['transmission_sig']??'')==='synthetic-valid-signature'?'SUCCESS':'FAILURE');}
 else{return new WP_Error('fixture_provider_mismatch','Unexpected synthetic provider call.');}
 return array('response'=>array('code'=>200),'body'=>wp_json_encode($body),'headers'=>array());
},10,3);
add_action('init',static function(){
 if(isset($_GET['gift_fixture_approve'])){
  $id=sanitize_text_field(wp_unslash($_GET['gift_fixture_approve']));$state=get_option('gift_fixture_provider',array());if(isset($state[$id])&&$state[$id]['status']==='CREATED'){$state[$id]['status']='APPROVED';update_option('gift_fixture_provider',$state,false);}wp_send_json(array('synthetic_approval'=>isset($state[$id])));
 }
});
add_filter('rest_post_dispatch',static function($response){$response->header('X-Fixture-Worker',(string)getmypid());return $response;});
add_filter('query',static function($sql){
 if(file_exists('/tmp/csf-billing-fixture/audit-failure')&&str_starts_with($sql,'INSERT INTO `gift_fixture_csf_billing_audit`')){return 'INSERT INTO gift_fixture_intentionally_absent VALUES (1)';}
 if(file_exists('/tmp/csf-billing-fixture/race-delay')&&str_starts_with($sql,'UPDATE `gift_fixture_csf_billing_certificates`')){usleep(500000);}
 return $sql;
});

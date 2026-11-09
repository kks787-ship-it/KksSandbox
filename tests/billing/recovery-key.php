<?php
/** Read-only key/ciphertext qualification on the isolated restored database; no values printed. */
if(DB_NAME!=='csf_gift_restore'||wp_get_environment_type()!=='local'||!defined('WP_CLI')||CSF_Billing_Config::enabled()){throw new RuntimeException('Closed isolated restore required');}
global $wpdb;$checks=0;$rows=$wpdb->get_results('SELECT number_sealed,number_hash FROM gift_fixture_csf_billing_certificates',ARRAY_A);
if(!$rows){throw new RuntimeException('Populated certificate fixture required');}
foreach($rows as $row){
 $plain=CSF_Billing_Config::open($row['number_sealed']);
 if(!preg_match('/^CSF-[A-F0-9]{32}$/D',$plain)||!hash_equals($row['number_hash'],hash('sha256',$plain))){throw new RuntimeException('Restored key/certificate mismatch');}++$checks;
 $raw=base64_decode($row['number_sealed'],true);$bad=$raw;$bad[28]=chr(ord($bad[28])^1);
 try{CSF_Billing_Config::open(base64_encode($bad));throw new RuntimeException('Tampered ciphertext accepted');}catch(CSF_Billing_Failure $e){++$checks;}
 $wrong=openssl_decrypt(substr($raw,28),'aes-256-gcm',random_bytes(32),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'csf-billing-v1');
 if(false!==$wrong){throw new RuntimeException('Wrong restored key accepted');}++$checks;
}
// Replay a restored issuance receipt without provider traffic or a second certificate.
$store=new CSF_Billing_Store();$orders=$wpdb->get_results("SELECT id,capture_id FROM gift_fixture_csf_billing_orders WHERE capture_id IS NOT NULL",ARRAY_A);
foreach($orders as $order){
 $existing=$wpdb->get_var($wpdb->prepare('SELECT id FROM gift_fixture_csf_billing_certificates WHERE order_id=%s',$order['id']));
 $result=$store->command(hash('sha256','issue:'.$order['capture_id']),array('kind'=>'issue','order_id'=>$order['id'],'capture_id'=>$order['capture_id']),static function(){throw new RuntimeException('Restored receipt attempted reissuance');});
 if(($result['certificate_id']??null)!==$existing){throw new RuntimeException('Restored issuance receipt mismatch');}++$checks;
}
// Closed recovery must deny even an authenticated payment replay before provider transport.
$calls=0;$probe=static function($v)use(&$calls){++$calls;return new WP_Error('unexpected_network','Blocked');};add_filter('pre_http_request',$probe,1);
try{(new CSF_Billing_Service(new CSF_Billing_Store()))->capture(wp_generate_uuid4(),wp_generate_uuid4(),str_repeat('0',64));throw new RuntimeException('Closed restore accepted payment');}catch(CSF_Billing_Failure $e){if($e->getMessage()!=='sandbox_disabled'){throw $e;}++$checks;}finally{remove_filter('pre_http_request',$probe,1);}
if($calls!==0){throw new RuntimeException('Recovery attempted provider traffic');}++$checks;
echo 'RESTORED_KEY_AND_CLOSED_PAYMENT_ASSERTIONS='.$checks;

<?php
/** Additional recovery states in a fresh synthetic clone only; no real provider or identity changes. */
if(ABSPATH!=='/tmp/csf-billing-fixture/site/'||DB_NAME!=='csf_gift_fixture'||wp_get_environment_type()!=='local'||!defined('WP_CLI')||!defined('CSF_BILLING_FIXTURE_MOCK')){throw new RuntimeException('Synthetic source required');}
$catalog=CSF_Billing_Config::catalog();$product=$catalog['products'][0];$terms=$catalog['terms'];$consent=array('accepted'=>true,'terms_version'=>$terms['version'],'terms_hash'=>$terms['sha256']);$session=hash('sha256',random_bytes(32));$service=new CSF_Billing_Service(new CSF_Billing_Store());
foreach(array('prepared','awaiting_approval','create_uncertain','capture_uncertain') as $state){
 $id=$service->prepare(wp_generate_uuid4(),$session,$product,$terms,$consent)['order_id'];
 if($state==='prepared'){continue;}
 if($state==='create_uncertain'){
  file_put_contents('/tmp/csf-billing-fixture/create-response-lost','synthetic recovery fault');
  try{$service->create(wp_generate_uuid4(),$id,$session);throw new RuntimeException('Expected uncertainty');}catch(CSF_Billing_Failure $e){if($e->getMessage()!=='provider_outcome_uncertain'){throw $e;}}finally{unlink('/tmp/csf-billing-fixture/create-response-lost');}continue;
 }
 $provider=$service->create(wp_generate_uuid4(),$id,$session)['provider_order_id'];if($state==='awaiting_approval'){continue;}
 $mock=get_option('gift_fixture_provider');$mock[$provider]['status']='APPROVED';update_option('gift_fixture_provider',$mock,false);
 file_put_contents('/tmp/csf-billing-fixture/capture-response-lost','synthetic recovery fault');
 try{$service->capture(wp_generate_uuid4(),$id,$session);throw new RuntimeException('Expected capture uncertainty');}catch(CSF_Billing_Failure $e){if($e->getMessage()!=='provider_outcome_uncertain'){throw $e;}}finally{unlink('/tmp/csf-billing-fixture/capture-response-lost');}
}
echo 'SYNTHETIC_OUTSTANDING_PAYMENT_STATES_SEEDED';

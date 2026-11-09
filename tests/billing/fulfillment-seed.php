<?php
/** Synthetic historical artifacts inside the existing isolated seed transaction only. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||DB_NAME!=='wp_mdf4x'){throw new RuntimeException('Isolated seed only');}
$hash=hash('sha256',$number);$aid=hash('sha256','pdf:'.$certificate.':'.$hash);
$pdf=CSF_Billing_PDF::render(array('status'=>'issued','certificate_number'=>$number,'presentation'=>$doc['fulfillment'],'package'=>$doc['product']['label'],'purchased'=>2,'amount_minor'=>$doc['amount_minor'],'terms'=>$doc['terms']));
$artifact=array('version'=>1,'certificate_id'=>$certificate,'number_hash'=>$hash,'sha256'=>hash('sha256',$pdf),'sealed'=>CSF_Billing_Config::seal($pdf));
$s->insert('commands',array('id'=>$aid,'payload_hash'=>hash('sha256',CSF_Billing_Domain::json($artifact)),'result'=>CSF_Billing_Domain::json($artifact),'created_at'=>gmdate('Y-m-d H:i:s')));
$s->audit('certificate_pdf_persisted',0,$certificate,array('artifact_id'=>$aid,'number_hash'=>$hash,'sha256'=>$artifact['sha256']));
foreach(array('buyer','administration') as $audience){
 $did=hash('sha256','dispatch:'.$aid.':'.$audience);$attempt=wp_generate_uuid4();
 $message=array('to'=>'synthetic@fixture.invalid','subject'=>'Synthetic certificate','order_id'=>$id);
 if($audience==='buyer'){$message['pdf_artifact']=$aid;$message['pdf_sha256']=$artifact['sha256'];$message['terms']=$doc['terms'];$message['instructions']=$doc['fulfillment']['instructions'];}
 $dispatch=array('version'=>1,'dispatch_id'=>$did,'certificate_id'=>$certificate,'number_hash'=>$hash,'order_id'=>$id,'audience'=>$audience,'state'=>$audience==='buyer'?'sink_recorded':'uncertain','attempt'=>$attempt,'message_sealed'=>CSF_Billing_Config::seal(CSF_Billing_Domain::json($message)),'delivered'=>false);
 $s->insert('commands',array('id'=>hash('sha256','dispatch_state:'.$did),'payload_hash'=>hash('sha256',$did),'result'=>CSF_Billing_Domain::json($dispatch),'created_at'=>gmdate('Y-m-d H:i:s')));
 if($audience==='buyer'){$s->insert('commands',array('id'=>hash('sha256','dispatch_sink_receipt:'.$did),'payload_hash'=>hash('sha256',$dispatch['message_sealed']),'result'=>CSF_Billing_Domain::json(array('dispatch_id'=>$did,'attempt'=>$attempt,'sink_accepted'=>true,'delivered'=>false)),'created_at'=>gmdate('Y-m-d H:i:s')));}
}

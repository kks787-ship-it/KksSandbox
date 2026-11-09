<?php
/** Synthetic history linked to an existing synthetic scheduler appointment, not an actual sale. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||DB_NAME!=='wp_mdf4x'){throw new RuntimeException('Isolated seed only');}
$appointment=$wpdb->get_row('SELECT * FROM TXGEh_csf_sched_appointments WHERE state="confirmed" LIMIT 1',ARRAY_A);
if(!$appointment)throw new RuntimeException('Synthetic confirmed appointment required');
$booking=$appointment['uuid'];$detail=$wpdb->get_row($wpdb->prepare('SELECT * FROM TXGEh_csf_sched_details WHERE uuid=%s',$booking),ARRAY_A);
if(!$detail)throw new RuntimeException('Synthetic appointment detail required');
// Earlier independently canceled synthetic appointment: reserve then reviewed release.
$canceled=$wpdb->get_row('SELECT * FROM TXGEh_csf_sched_appointments WHERE state="canceled" LIMIT 1',ARRAY_A);if(!$canceled)throw new RuntimeException('Canceled fixture missing');
$store->command(hash('sha256','synthetic_cancel:'.$certificate),array('synthetic'=>$certificate),static function($s)use($certificate,$canceled){
 global $wpdb;$bid=$canceled['uuid'];$r=array('state'=>'reserved','slots'=>array(1),'value_minor'=>6173,'policy'=>array('version'=>'synthetic','approved'=>true,'products'=>array(),'release_initiators'=>array('facility')),'revision'=>1,'completion_command'=>null);
 $s->audit('booking_credit_reserve',0,$certificate,array('booking_uuid'=>$bid,'reservation'=>$r,'initiator'=>null));$r['state']='released';$r['revision']=2;$s->audit('booking_credit_cancel',0,$certificate,array('booking_uuid'=>$bid,'reservation'=>$r,'initiator'=>'facility'));
 $s->insert('commands',array('id'=>hash('sha256','booking_reservations:'.$certificate),'payload_hash'=>hash('sha256',$certificate),'result'=>CSF_Billing_Domain::json(array($bid=>$r)),'created_at'=>gmdate('Y-m-d H:i:s')));
 $wpdb->insert('TXGEh_csf_sched_details',array('uuid'=>$bid,'entity_uuid'=>$bid,'body_json'=>wp_json_encode(array('billing_certificate_id'=>$certificate,'billing_credit_state'=>'released')),'revision'=>1,'created_by'=>1,'created_at_utc'=>gmdate('Y-m-d H:i:s')));return array('released'=>true);
});
$store->command(hash('sha256','synthetic_booking:'.$certificate),array('synthetic'=>$certificate),static function($s)use($certificate,$booking,$detail){
 global $wpdb;$reservation=array('state'=>'reserved','slots'=>array(1),'value_minor'=>6173,'policy'=>array('version'=>'synthetic','approved'=>true,'products'=>array(),'release_initiators'=>array('facility')),'revision'=>1,'completion_command'=>null);
 $projection=json_decode($s->row('commands',hash('sha256','booking_reservations:'.$certificate))['result'],true);$projection[$booking]=$reservation;$s->update('commands',hash('sha256','booking_reservations:'.$certificate),array('result'=>CSF_Billing_Domain::json($projection)));
 $s->audit('booking_credit_reserve',0,$certificate,array('booking_uuid'=>$booking,'reservation'=>$reservation,'initiator'=>null));
 $d=json_decode($detail['body_json'],true);$d['billing_certificate_id']=$certificate;$d['billing_credit_state']='reserved';
 if(1!==$wpdb->update('TXGEh_csf_sched_details',array('body_json'=>wp_json_encode($d)),array('uuid'=>$booking)))throw new RuntimeException('Synthetic binding failed');return array('reserved'=>true);
});
// Actual compatible ledger receipts for historical completion, then an audited correction.
$completion=wp_generate_uuid4();$synthCorrection=wp_generate_uuid4();
$store->command(hash('sha256','synthetic_lifecycle:'.$certificate),array('synthetic'=>$certificate),static function($s)use($certificate,$booking,$completion,$synthCorrection){
 $key=hash('sha256','booking_reservations:'.$certificate);$all=json_decode($s->row('commands',$key)['result'],true);$r=$all[$booking];
 $receipt=array('certificate_id'=>$certificate,'command'=>$completion,'rides'=>1,'slots'=>array(1),'value_minor'=>6173,'booking_uuid'=>$booking);
 foreach(array(hash('sha256','redeem:'.$completion)=>CSF_Billing_Domain::json($receipt),hash('sha256','ride:'.$certificate.':'.$completion)=>'{}') as $id=>$result)$s->insert('commands',array('id'=>$id,'payload_hash'=>hash('sha256',$completion),'result'=>$result,'created_at'=>gmdate('Y-m-d H:i:s')));
 $s->audit('rides_redeemed',0,$certificate,$receipt+array('ride_reference'=>$completion));$r['state']='used';$r['revision']=2;$r['completion_command']=$completion;$s->audit('booking_credit_complete',0,$certificate,array('booking_uuid'=>$booking,'reservation'=>$r,'initiator'=>null));
 $s->insert('commands',array('id'=>hash('sha256','reversed:'.$completion),'payload_hash'=>hash('sha256',$completion),'result'=>'{}','created_at'=>gmdate('Y-m-d H:i:s')));
 $s->audit('ride_debit_corrected',0,$certificate,array('original_command'=>$completion,'rides'=>1,'slots'=>array(1),'value_minor'=>6173,'reason'=>'mistaken_ride'));
 $r['state']='reserved';$r['revision']=3;$s->audit('booking_credit_correct',0,$certificate,array('booking_uuid'=>$booking,'reservation'=>$r,'initiator'=>null));$all[$booking]=$r;$s->update('commands',$key,array('result'=>CSF_Billing_Domain::json($all)));return array('corrected'=>true);
});
$rotation=wp_generate_uuid4();$newNumber='CSF-'.strtoupper(bin2hex(random_bytes(16)));
$store->command(hash('sha256','rotate:'.$rotation),array('synthetic'=>$rotation),static function($s)use($certificate,$rotation,$newNumber){
 $r=$s->row('certificates',$certificate);$hash=hash('sha256',$newNumber);
 $s->insert('commands',array('id'=>hash('sha256','retired_number:'.$r['number_hash']),'payload_hash'=>hash('sha256',$certificate),'result'=>CSF_Billing_Domain::json(array('certificate_id'=>$certificate,'number_hash'=>$r['number_hash'],'replacement_hash'=>$hash,'command'=>$rotation)),'created_at'=>gmdate('Y-m-d H:i:s')));
 $s->update('certificates',$certificate,array('number_hash'=>$hash,'number_sealed'=>CSF_Billing_Config::seal($newNumber),'revision'=>(int)$r['revision']+1));
 $s->audit('certificate_number_rotated',0,$certificate,array('command'=>$rotation,'previous_hash'=>$r['number_hash'],'number_hash'=>$hash,'revision'=>(int)$r['revision']+1));return array('certificate_id'=>$certificate,'revision'=>(int)$r['revision']+1,'delivery'=>'held');
});

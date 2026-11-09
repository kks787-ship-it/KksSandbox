<?php
/** Synthetic history linked to an existing synthetic scheduler appointment, not an actual sale. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||DB_NAME!=='wp_mdf4x'){throw new RuntimeException('Isolated seed only');}
$appointment=$wpdb->get_row('SELECT * FROM TXGEh_csf_sched_appointments WHERE state="confirmed" LIMIT 1',ARRAY_A);
if(!$appointment)throw new RuntimeException('Synthetic confirmed appointment required');
$booking=$appointment['uuid'];$detail=$wpdb->get_row($wpdb->prepare('SELECT * FROM TXGEh_csf_sched_details WHERE uuid=%s',$booking),ARRAY_A);
if(!$detail)throw new RuntimeException('Synthetic appointment detail required');
$store->command(hash('sha256','synthetic_booking:'.$certificate),array('synthetic'=>$certificate),static function($s)use($certificate,$booking,$detail){
 global $wpdb;$reservation=array('state'=>'reserved','slots'=>array(1),'value_minor'=>6173,'policy'=>array('version'=>'synthetic','approved'=>true,'products'=>array(),'release_initiators'=>array('facility')),'revision'=>1,'completion_command'=>null);
 $s->insert('commands',array('id'=>hash('sha256','booking_reservations:'.$certificate),'payload_hash'=>hash('sha256',$certificate),'result'=>CSF_Billing_Domain::json(array($booking=>$reservation)),'created_at'=>gmdate('Y-m-d H:i:s')));
 $s->audit('booking_credit_reserve',0,$certificate,array('booking_uuid'=>$booking,'reservation'=>$reservation,'initiator'=>null));
 $d=json_decode($detail['body_json'],true);$d['billing_certificate_id']=$certificate;$d['billing_credit_state']='reserved';
 if(1!==$wpdb->update('TXGEh_csf_sched_details',array('body_json'=>wp_json_encode($d)),array('uuid'=>$booking)))throw new RuntimeException('Synthetic binding failed');return array('reserved'=>true);
});
$rotation=wp_generate_uuid4();$newNumber='CSF-'.strtoupper(bin2hex(random_bytes(16)));
$store->command(hash('sha256','rotate:'.$rotation),array('synthetic'=>$rotation),static function($s)use($certificate,$rotation,$newNumber){
 $r=$s->row('certificates',$certificate);$hash=hash('sha256',$newNumber);
 $s->insert('commands',array('id'=>hash('sha256','retired_number:'.$r['number_hash']),'payload_hash'=>hash('sha256',$certificate),'result'=>CSF_Billing_Domain::json(array('certificate_id'=>$certificate,'number_hash'=>$r['number_hash'],'replacement_hash'=>$hash,'command'=>$rotation)),'created_at'=>gmdate('Y-m-d H:i:s')));
 $s->update('certificates',$certificate,array('number_hash'=>$hash,'number_sealed'=>CSF_Billing_Config::seal($newNumber),'revision'=>(int)$r['revision']+1));
 $s->audit('certificate_number_rotated',0,$certificate,array('command'=>$rotation,'previous_hash'=>$r['number_hash'],'number_hash'=>$hash,'revision'=>(int)$r['revision']+1));return array('certificate_id'=>$certificate,'revision'=>(int)$r['revision']+1,'delivery'=>'held');
});

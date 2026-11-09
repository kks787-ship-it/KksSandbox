<?php
/** Synthetic participant tests; scheduler/DB transaction is simulated here, not SQL proof. */
require __DIR__.'/fulfillment.php';$beforeCount=$count;
define('CSF_BILLING_BOOKING_ENABLED',true);
define('CSF_BILLING_BOOKING_POLICY_JSON',json_encode(['version'=>'synthetic-only','approved'=>true,'products'=>[$product['id']=>['lesson']],'release_initiators'=>['facility']]));
$GLOBALS['bookingTables']=$fs->tables;
class CSF_Billing_Store extends FixtureStore {
 public function __construct(){$this->tables=&$GLOBALS['bookingTables'];}
 public function join_calendar($callback){CSF_Billing_Domain::need(CSF_Scheduling_Store::$active,'outer_transaction_required');return $callback($this);}
}
class CSF_Scheduling_Store {public static $active=false;}
class CSF_Scheduling_Time {public static function parse($v,$zone){return new DateTimeImmutable($v,new DateTimeZone($zone));}}
function participant($body,$transition,$previous=null,$fail=false){
 $saved=$GLOBALS['bookingTables'];$detail=$body;
 CSF_Scheduling_Store::$active=true;
 CSF_Billing_Booking::context($transition);
 try{CSF_Billing_Booking::participate($body['state']==='canceled'?'appointment_canceled':'appointment_saved',$body,$previous,$detail,'UTC');if($fail)throw new CSF_Billing_Failure('simulated_scheduler_audit_failure');return $detail;}
 catch(Throwable $e){$GLOBALS['bookingTables']=$saved;throw $e;}
 finally{CSF_Scheduling_Store::$active=false;CSF_Billing_Booking::context(null);}
}
$bs=new CSF_Billing_Store();$svc=new CSF_Billing_Service($bs);$cert=$bs->tables['certificates'][$cid];
$body=['uuid'=>wp_generate_uuid4(),'expected'=>0,'title'=>'Synthetic booking','service_type'=>'lesson','state'=>'confirmed','start'=>gmdate('Y-m-d\TH:i:s\Z',time()+86400),'end'=>gmdate('Y-m-d\TH:i:s\Z',time()+90000),'hold_expires'=>null,'resources'=>['synthetic'],'riders'=>['synthetic-rider'],'rides'=>[['horse'=>'synthetic-horse']]];
$ctx=['transition'=>'reserve','number_hash'=>$cert['number_hash'],'command'=>wp_generate_uuid4()];
$saved=$bs->tables;rejected(fn()=>participant($body,$ctx,null,true),'outer scheduler failure rolls back participant');check($saved===$bs->tables,'no reservation after failed booking');
$detail=participant($body,$ctx);$cert=$bs->tables['certificates'][$cid];
check($cert['redeemed']===0&&count(CSF_Billing_Booking::slots($bs,$cid))===1,'booking reserves without consuming');
rejected(fn()=>$svc->cash(wp_generate_uuid4(),$cid,(int)$cert['revision'],'reserve',wp_generate_uuid4(),12345,wp_generate_uuid4(),true),'cash cannot spend reserved value');
rejected(fn()=>participant($body,$ctx,$detail),'second reservation same booking denied');
rejected(fn()=>participant($body,null,$detail),'ordinary edit cannot strip reservation');
$complete=['transition'=>'complete','number_hash'=>null,'command'=>wp_generate_uuid4()];
rejected(fn()=>participant($body,$complete,$detail),'cannot complete future lesson');
// Simulate elapsed time in this isolated in-memory fixture; no production clock override exists.
$body['start']=gmdate('Y-m-d\TH:i:s\Z',time()-7200);$body['end']=gmdate('Y-m-d\TH:i:s\Z',time()-3600);$detail['start']=$body['start'];$detail['end']=$body['end'];
$used=participant($body,$complete,$detail);$cert=$bs->tables['certificates'][$cid];
check($cert['redeemed']===1&&!CSF_Billing_Booking::slots($bs,$cid),'completion moves reserved to used once');
rejected(fn()=>participant($body,$complete,$used),'second completion denied');
rejected(fn()=>$svc->correct(wp_generate_uuid4(),$cid,$complete['command'],(int)$cert['revision'],'mistaken_ride'),'ordinary correction cannot detach booking');
$correct=['transition'=>'correct','number_hash'=>null,'command'=>wp_generate_uuid4()];
$corrected=participant($body,$correct,$used);$cert=$bs->tables['certificates'][$cid];
check($cert['redeemed']===0&&count(CSF_Billing_Booking::slots($bs,$cid))===1,'audited correction returns original reserved value');
$cancel=['uuid'=>$body['uuid'],'state'=>'canceled','initiator'=>'client'];rejected(fn()=>participant($cancel,null,$corrected),'unapproved client cancellation release held');
$cancel['initiator']='facility';$released=participant($cancel,null,$corrected);
check(!CSF_Billing_Booking::slots($bs,$cid)&&$bs->tables['certificates'][$cid]['redeemed']===0,'approved cancellation releases without use');
$body['uuid']=wp_generate_uuid4();$body['start']=gmdate('Y-m-d\TH:i:s\Z',time()+86400);$body['end']=gmdate('Y-m-d\TH:i:s\Z',time()+90000);$ctx['command']=wp_generate_uuid4();participant($body,$ctx);
$cert=$bs->tables['certificates'][$cid];$debit=$svc->redeem(wp_generate_uuid4(),$cid,1,(int)$cert['revision'],wp_generate_uuid4());
check($debit['slots']===[1],'ordinary completed ride uses unreserved ordinal');
$other=$body;$other['uuid']=wp_generate_uuid4();rejected(fn()=>participant($other,$ctx),'reserved plus used cannot overspend');
$wpdb->allow=false;rejected(fn()=>CSF_Billing_Booking::authorize($ctx),'revoked billing capability cannot replay');$wpdb->allow=true;
echo 'BOOKING_PARTICIPANT_ASSERTIONS='.($count-$beforeCount)."; simulated transaction only\n";

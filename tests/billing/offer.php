<?php
/** Synthetic buyer consent/privacy and atomic participant tests; not a real scheduler proof. */
require __DIR__.'/fulfillment.php';$before=$count;
define('CSF_BILLING_BOOKING_ENABLED',true);define('CSF_BILLING_BOOKING_POLICY_JSON',json_encode(array('version'=>'synthetic','approved'=>true,'products'=>array($product['id']=>array('lesson')),'release_initiators'=>array('facility'))));
class CSF_Scheduling_Store {public static function preview($b,$r,$c){return array('preview'=>true);}public static function calendar($k,$b,$r,$c,$p){return array('body'=>$b,'payment'=>$p);}}
$body=array('uuid'=>wp_generate_uuid4(),'expected'=>0,'title'=>'Private rider identity','service_type'=>'lesson','state'=>'confirmed','start'=>gmdate('c',time()+86400),'end'=>gmdate('c',time()+90000),'hold_expires'=>null,'resources'=>array('private-resource'),'riders'=>array('private-rider'),'rides'=>array(array('horse'=>'private-horse')));
$offer=wp_generate_uuid4();rejected(fn()=>CSF_Billing_Offer::create($fs,$offer,$oid,$body,1),'customer booking default-off');define('CSF_BILLING_CUSTOMER_BOOKING_ENABLED',true);
$created=CSF_Billing_Offer::create($fs,$offer,$oid,$body,1);check(CSF_Billing_Offer::create($fs,$offer,$oid,$body,1)===$created,'offer create replay');
$cert=$fs->tables['certificates'][$cid];$number=CSF_Billing_Config::open($cert['number_sealed']);
$view=CSF_Billing_Offer::view($fs,$offer,$oid,$session);check($view['state']==='awaiting_buyer_consent'&&!str_contains(json_encode($view),'private-')&&!isset($view['title']),'buyer view excludes rider resource and staff identities');
rejected(fn()=>CSF_Billing_Offer::view($fs,$offer,$oid,str_repeat('b',64)),'wrong buyer session');
rejected(fn()=>CSF_Billing_Offer::accept($fs,wp_generate_uuid4(),$offer,$oid,$session,'CSF-'.str_repeat('B',32),true),'wrong certificate');
rejected(fn()=>CSF_Billing_Offer::accept($fs,wp_generate_uuid4(),$offer,$oid,$session,$number,false),'explicit buyer consent required');
rejected(fn()=>CSF_Billing_Offer::consume($fs,$offer,$cert,$body,wp_generate_uuid4()),'staff cannot consume absent consent');
$command=wp_generate_uuid4();$accepted=CSF_Billing_Offer::accept($fs,$command,$offer,$oid,$session,$number,true);check(CSF_Billing_Offer::accept($fs,$command,$offer,$oid,$session,$number,true)===$accepted,'buyer consent exact replay');
check($cert===$fs->tables['certificates'][$cid]&&CSF_Billing_Booking::slots($fs,$cid)===array(),'consent neither consumes nor reserves value');
check(!str_contains(json_encode($fs->tables),$number)&&!str_contains(json_encode($fs->tables),'Private rider identity'),'offer and code encrypted or hashed at rest');
$changed=$body;$changed['end']=gmdate('c',time()+93600);rejected(fn()=>CSF_Billing_Offer::consume($fs,$offer,$cert,$changed,wp_generate_uuid4()),'changed lesson invalidates consent');
$consentKey=hash('sha256','booking_consent:'.$offer);$originalConsent=$fs->tables['commands'][$consentKey];$bad=json_decode($originalConsent['result'],true);$bad['terms_sha256']=str_repeat('0',64);$fs->tables['commands'][$consentKey]['result']=CSF_Billing_Domain::json($bad);rejected(fn()=>CSF_Billing_Offer::consume($fs,$offer,$cert,$body,wp_generate_uuid4()),'changed terms consent rejected');$fs->tables['commands'][$consentKey]=$originalConsent;
$saved=$fs->tables;rejected(fn()=>$fs->command(hash('sha256','synthetic offer failure'),array(),function($s)use($offer,$cert,$body){CSF_Billing_Offer::consume($s,$offer,$cert,$body,wp_generate_uuid4());throw new CSF_Billing_Failure('late_failure');}),'late transaction failure');check($saved===$fs->tables,'failed booking leaves consent available and no consumed marker');
CSF_Billing_Offer::consume($fs,$offer,$cert,$body,wp_generate_uuid4());rejected(fn()=>CSF_Billing_Offer::consume($fs,$offer,$cert,$body,wp_generate_uuid4()),'offer cannot fund another booking');
$wpdb->allow=false;rejected(fn()=>CSF_Billing_Offer::confirm($fs,$offer,$oid,1,wp_generate_uuid4()),'staff permission revoked');$wpdb->allow=true;
$svc->rotate(wp_generate_uuid4(),$cid,(int)$cert['revision'],true);rejected(fn()=>CSF_Billing_Offer::view($fs,$offer,$oid,$session),'rotated bearer invalidates unreviewed offer');
echo 'OFFER_ASSERTIONS='.($count-$before)."; synthetic scheduler preview only\n";

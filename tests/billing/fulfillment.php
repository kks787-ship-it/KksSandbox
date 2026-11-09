<?php
/** Synthetic issuance, private rendering and delivery-sink regression; no external I/O. */
define('CSF_BILLING_SYNTHETIC_SINK', true);
require __DIR__.'/service.php';
function esc_attr($s) {return htmlspecialchars($s,ENT_QUOTES,'UTF-8');}
$before=$count;
foreach(['pasture-v1','sunset-v1','classic-v1'] as $design) {
 check(CSF_Billing_Fulfillment::selection(['design'=>$design])['design']===$design,'compiled theme');
}
foreach(['<script>','https://attacker.invalid',null,[]] as $bad) {
 if ($bad===null) continue;
 rejected(fn()=>CSF_Billing_Fulfillment::selection(['design'=>$bad]),'reject untrusted design');
}
foreach(["a@example.invalid\r\nBcc: other@example.invalid",'no-email',[],str_repeat('a',255).'@example.invalid'] as $bad) rejected(fn()=>CSF_Billing_Fulfillment::selection(['buyer_email'=>$bad]),'reject bad address');
$fs=new FixtureStore();$svc=new CSF_Billing_Service($fs);$command=wp_generate_uuid4();
$choice=$consent+['design'=>'sunset-v1','buyer_email'=>'buyer@fixture.invalid'];
$prepared=$svc->prepare($command,$session,$product,$terms,$choice);$oid=$prepared['order_id'];
check($svc->prepare($command,$session,$product,$terms,$choice)===$prepared,'randomized envelopes preserve exact command replay');
rejected(fn()=>$svc->prepare($command,$session,$product,$terms,array_replace($choice,['design'=>'classic-v1'])),'design replay conflict');
rejected(fn()=>$svc->prepare($command,$session,$product,$terms,array_replace($choice,['buyer_email'=>'other@fixture.invalid'])),'recipient replay conflict');
$doc=json_decode($fs->tables['orders'][$oid]['document'],true);
check(!str_contains(json_encode($fs->tables),'buyer@fixture.invalid'),'contact never plaintext in ledger');
check(CSF_Billing_Config::open($doc['fulfillment']['contact_sealed'])==='buyer@fixture.invalid','authenticated contact envelope');
check(count($fs->tables['certificates'])===0,'prepare never issues');
$svc->create(wp_generate_uuid4(),$oid,$session);$provider_order['status']='APPROVED';$timeout_capture=false;
$issued=$svc->capture(wp_generate_uuid4(),$oid,$session);$cid=$issued['certificate_id'];
$intents=fn()=>array_filter($fs->tables['commands'],fn($r)=>isset($r['result'])&&is_string($r['result'])&&str_contains($r['result'],'delivery_not_qualified'));
check(count($intents())===2,'buyer and administration intents atomically issued');
$svc->capture(wp_generate_uuid4(),$oid,$session);check(count($intents())===2 && count($fs->tables['certificates'])===1,'payment replay neither reissues nor requeues');
$cert=$fs->tables['certificates'][$cid];
$receipt=['status'=>'issued','certificate_number'=>CSF_Billing_Config::open($cert['number_sealed']),'presentation'=>$doc['fulfillment'],'package'=>'<script>bad()</script>','purchased'=>2,'amount_minor'=>$product['amount_minor'],'terms'=>$terms];
$html=CSF_Billing_Fulfillment::render($receipt);
check(str_contains($html,'Signature pending authorized artwork setup'),'no forged signature');
check(str_contains($html,'#87551b'),'selected theme rendered');
check(str_contains($html,'SANDBOX TEST')&&!str_contains($html,'buyer@fixture.invalid'),'test warning and no buyer email');
check(!str_contains($html,'<script>')&&str_contains($html,'&lt;script&gt;'),'escape package text');
foreach(['prepared','frozen','capture_uncertain'] as $status) rejected(fn()=>CSF_Billing_Fulfillment::render(array_replace($receipt,['status'=>$status])),'no unpaid/frozen certificate');
$fs->fail_audit=true;
rejected(fn()=>CSF_Billing_Fulfillment::sink($fs,$cid,'buyer'),'sink audit failure rolls back');
check(!$fs->row('commands',hash('sha256','fulfillment_sink_payload:'.$cid.':buyer')),'sink rollback removes payload');
$fs->fail_audit=false;
$r=CSF_Billing_Fulfillment::sink($fs,$cid,'buyer');check($r['delivered']===false && $r['state']==='sink_recorded','sink never claims delivery');
$n=count($fs->tables['audit']);check(CSF_Billing_Fulfillment::sink($fs,$cid,'buyer')===$r && count($fs->tables['audit'])===$n,'sink replay deduplicates');
$sealed=json_decode($fs->row('commands',hash('sha256','fulfillment_sink_payload:'.$cid.':buyer'))['result'],true);
$msg=json_decode(CSF_Billing_Config::open($sealed['sealed']),true);
check($msg['terms']==$terms && $msg['recipient']==='buyer@fixture.invalid' && str_contains($msg['certificate_html'],$receipt['certificate_number']),'buyer bundle includes exact terms receipt and numbered certificate');
CSF_Billing_Fulfillment::sink($fs,$cid,'administration');
$sealed=json_decode($fs->row('commands',hash('sha256','fulfillment_sink_payload:'.$cid.':administration'))['result'],true);
$msg=json_decode(CSF_Billing_Config::open($sealed['sealed']),true);
check(!isset($msg['certificate_html'])&&!str_contains(json_encode($msg),$receipt['certificate_number'])&&!str_contains(json_encode($msg),'buyer@fixture.invalid'),'administration minimizes bearer and personal data');
check(!str_contains(json_encode($fs->tables),'buyer@fixture.invalid')&&!str_contains(json_encode($fs->tables),$receipt['certificate_number']),'no plaintext contact or certificate in stored sink');
// Transparent one-pixel synthetic fixture, not a person's signature.
$png='iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6XEYAAAAASUVORK5CYII=';
define('CSF_BILLING_SIGNATURE_JSON',json_encode(['authorized'=>true,'authorization_ref'=>'SYNTHETIC-ONLY','png_base64'=>$png,'sha256'=>hash('sha256',base64_decode($png))]));
$snapshot=CSF_Billing_Fulfillment::snapshot(CSF_Billing_Fulfillment::selection($choice));
check(!str_contains(json_encode($snapshot),$png),'signature bytes encrypted at rest');
$signed=CSF_Billing_Fulfillment::render(array_replace($receipt,['presentation'=>$snapshot]));
check(str_contains($signed,'data:image/png;base64,'.$png) && !str_contains($signed,'Signature pending'),'authorized synthetic raster rendered');
$damaged=$snapshot;$damaged['signature_sealed']='invalid';
rejected(fn()=>CSF_Billing_Fulfillment::render(array_replace($receipt,['presentation'=>$damaged])),'damaged signature envelope rejected');
echo 'PASS '.($count-$before)." fulfillment assertions; synthetic sink only\n";

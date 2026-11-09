<?php
/** Synthetic persisted PDF/dispatch/rotation regression; no real mail. */
require __DIR__.'/fulfillment.php';$before=$count;
$pdf=CSF_Billing_PDF::document($fs,$oid,$session);$bytes=CSF_Billing_Config::open($pdf['sealed']);
check(str_starts_with($bytes,'%PDF-1.4')&&str_ends_with($bytes,"%%EOF\n"),'real bounded PDF bytes');
check(hash('sha256',$bytes)===$pdf['sha256'],'PDF digest');
$again=CSF_Billing_PDF::document($fs,$oid,$session);check($again===$pdf,'persisted artifact stable on reprint');
check(!str_contains(json_encode($fs->tables),'%PDF-1.4'),'PDF encrypted at rest');
rejected(fn()=>CSF_Billing_PDF::document($fs,$oid,str_repeat('b',64)),'wrong session denied');
$claim=CSF_Billing_Dispatch::claim($fs,$oid,'buyer');check(CSF_Billing_Dispatch::claim($fs,$oid,'buyer')===$claim,'one durable claim');
rejected(fn()=>CSF_Billing_Dispatch::step($fs,$claim['dispatch_id'],wp_generate_uuid4(),true),'wrong claimant');
$accepted=CSF_Billing_Dispatch::step($fs,$claim['dispatch_id'],$claim['attempt'],true);check($accepted['state']==='sink_recorded'&&!$accepted['delivered'],'sink truth');
$receiptKey=hash('sha256','dispatch_sink_receipt:'.$claim['dispatch_id']);$saved=$fs->row('commands',$receiptKey);
CSF_Billing_Dispatch::step($fs,$claim['dispatch_id'],$claim['attempt'],true);check($saved===$fs->row('commands',$receiptKey),'sink exactly one receipt');
$admin=CSF_Billing_Dispatch::claim($fs,$oid,'administration');$uncertain=CSF_Billing_Dispatch::step($fs,$admin['dispatch_id'],$admin['attempt']);
check($uncertain['state']==='uncertain','unknown outcome remains uncertain');
check(CSF_Billing_Dispatch::step($fs,$admin['dispatch_id'],$admin['attempt'],true)['state']==='uncertain','uncertain not blindly retried');
$original=$fs->tables['certificates'][$cid];$oldNumber=CSF_Billing_Config::open($original['number_sealed']);$rotation=wp_generate_uuid4();
$fs->fail_audit=true;rejected(fn()=>$svc->rotate($rotation,$cid,(int)$original['revision'],true),'rotation audit failure');check($original===$fs->tables['certificates'][$cid],'rotation rolls back');$fs->fail_audit=false;
$result=$svc->rotate($rotation,$cid,(int)$original['revision'],true);check($svc->rotate($rotation,$cid,(int)$original['revision'],true)===$result,'rotation replay stable');
$new=$fs->tables['certificates'][$cid];check($new['number_hash']!==$original['number_hash']&&$new['value_json']===$original['value_json']&&$new['redeemed']===$original['redeemed'],'rotation preserves paid/ride balance');
check(!$fs->find('certificates','number_hash',hash('sha256',$oldNumber)),'old bearer invalidated');
check(CSF_Billing_Dispatch::step($fs,$admin['dispatch_id'],$admin['attempt'],true)['state']==='superseded','rotation prevents old dispatch');
$newPdf=CSF_Billing_PDF::document($fs,$oid,$session);check($newPdf['artifact_id']!==$pdf['artifact_id'],'replacement artifact same balance new bearer');
$wpdb->allow=false;rejected(fn()=>$svc->rotate($rotation,$cid,(int)$original['revision'],true),'revoked actor rotation replay');rejected(fn()=>CSF_Billing_Dispatch::claim($fs,$oid,'buyer'),'revoked actor dispatch replay');$wpdb->allow=true;
check(!str_contains(json_encode($fs->tables),$oldNumber),'no plaintext retired bearer');
// Printable output must fail closed rather than silently omit unsupported content.
$plainReceipt=array_replace($receipt,['presentation'=>$doc['fulfillment'],'package'=>'Synthetic printable package']);
rejected(fn()=>CSF_Billing_PDF::render(array_replace($plainReceipt,['package'=>'Non-ASCII café'])),'unsupported PDF text remains pending');
$long=$plainReceipt;$long['presentation']['instructions']=str_repeat('Long synthetic instructions. ',100);
rejected(fn()=>CSF_Billing_PDF::render($long),'PDF overflow remains pending');
$signed=$plainReceipt;$signed['presentation']=$snapshot;
if(!function_exists('imagecreatefromstring')||!function_exists('imagejpeg')){
 rejected(fn()=>CSF_Billing_PDF::render($signed),'signature renderer missing fails closed');
}else{
 // This fixture is an authorized synthetic pixel only, never Beth artwork.
 $signedBytes=CSF_Billing_PDF::render($signed);check(str_contains($signedBytes,'/Subtype /Image'),'authorized synthetic raster embedded');
}
echo 'DISPATCH_ROTATION_ASSERTIONS='.($count-$before)."\n";

<?php
/** Synthetic MIME envelope/attachment regression. No mail function is called. */
require __DIR__.'/dispatch.php';$before=$count;
$stored=json_decode($fs->row('commands',hash('sha256','dispatch_mime:'.$claim['dispatch_id']))['result'],true);$mime=CSF_Billing_Config::open($stored['sealed']);
$message=json_decode(CSF_Billing_Config::open(json_decode($fs->row('commands',hash('sha256','dispatch_state:'.$claim['dispatch_id']))['result'],true)['message_sealed']),true);
check(hash('sha256',$mime)===$stored['sha256'],'encrypted MIME digest');check(CSF_Billing_Mime::build($message,$claim['dispatch_id'],$bytes)===$mime,'MIME stable exact replay');
check(!str_contains(json_encode($fs->tables),'MIME-Version: 1.0'),'MIME never plaintext in ledger');
check(str_contains($mime,'filename="gift-certificate.pdf"')&&str_contains($mime,'filename="accepted-terms.txt"'),'fixed private attachment names');
foreach(array("test@fixture.invalid\r\nBcc: attacker@fixture.invalid",'real@example.com') as $bad)rejected(fn()=>CSF_Billing_Mime::build(array_replace($message,array('to'=>$bad)),$claim['dispatch_id'],$bytes),'recipient gate');
rejected(fn()=>CSF_Billing_Mime::build(array_replace($message,array('subject'=>'CSF-'.$oldNumber)),$claim['dispatch_id'],$bytes),'bearer excluded from subject');
rejected(fn()=>CSF_Billing_Mime::build($message,$claim['dispatch_id'],$bytes.'tamper'),'attachment tamper rejected');
$bad=$message;$bad['terms']['text'].='tampered';rejected(fn()=>CSF_Billing_Mime::build($bad,$claim['dispatch_id'],$bytes),'accepted terms hash bound');
$adminMessage=array('to'=>'administration@fixture.invalid','subject'=>'Test gift certificate sale','order_id'=>$oid,'amount_minor'=>12345,'currency'=>'USD','date'=>$message['date']);
$adminMime=CSF_Billing_Mime::build($adminMessage,hash('sha256','synthetic-admin'));check(!str_contains($adminMime,'filename=')&&!str_contains($adminMime,$oldNumber),'administration no attachments or bearer');
rejected(fn()=>CSF_Billing_Mime::build($adminMessage,hash('sha256','synthetic-admin'),$bytes),'admin cannot attach bearer');
$mimeKey=hash('sha256','dispatch_mime:'.$claim['dispatch_id']);check(isset($fs->tables['commands'][$mimeKey]),'one persisted MIME sink artifact');
echo 'MIME_ASSERTIONS='.($count-$before)."\n";
if(in_array('--mime-proof',$argv??array(),true))echo json_encode(array('mime'=>base64_encode($mime),'pdf_sha256'=>hash('sha256',$bytes),'terms_sha256'=>hash('sha256',$message['terms']['text'])))."\n";

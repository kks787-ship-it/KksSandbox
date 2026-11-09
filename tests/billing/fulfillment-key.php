<?php
/** Closed isolated restore key check. Never outputs any decrypted content. */
if(getenv('CSF_SYNTHETIC_RESTORE')!=='container-only'||!is_file('/.dockerenv')||ABSPATH!=='/tmp/csf-combined-restored/'||DB_NAME!=='billing_restore'||CSF_Billing_Config::enabled()){throw new RuntimeException('Closed isolated restore only');}
global $wpdb;$checks=0;$sealed=array();
foreach($wpdb->get_results('SELECT document FROM TXGEh_csf_billing_orders',ARRAY_A) as $row){
 $d=json_decode($row['document'],true);if(!isset($d['fulfillment']))continue;
 $f=$d['fulfillment'];if($f['contact_sealed']!==null){$email=CSF_Billing_Config::open($f['contact_sealed']);if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!str_ends_with($email,'.invalid'))throw new RuntimeException('Contact mismatch');$sealed[]=$f['contact_sealed'];$checks++;}
 if($f['signature_sealed']!==null){$s=json_decode(CSF_Billing_Config::open($f['signature_sealed']),true);if(($s['authorized']??false)!==true)throw new RuntimeException('Signature mismatch');$sealed[]=$f['signature_sealed'];$checks++;}
}
foreach($wpdb->get_results('SELECT result FROM TXGEh_csf_billing_commands',ARRAY_A) as $row){
 $r=json_decode($row['result'],true);
 if(isset($r['sealed'],$r['number_hash'],$r['sha256'])){$pdf=CSF_Billing_Config::open($r['sealed']);if(!str_starts_with($pdf,'%PDF-1.4')||!hash_equals($r['sha256'],hash('sha256',$pdf))||!preg_match('/Number: (CSF-[A-F0-9]{32})/',$pdf,$match)||!hash_equals($r['number_hash'],hash('sha256',$match[1])))throw new RuntimeException('PDF mismatch');$sealed[]=$r['sealed'];$checks++;}
 if(isset($r['sealed'],$r['dispatch_id'],$r['sha256'])){$mime=CSF_Billing_Config::open($r['sealed']);if(!hash_equals($r['sha256'],hash('sha256',$mime))||!str_contains($mime,'MIME-Version: 1.0'))throw new RuntimeException('MIME mismatch');$sealed[]=$r['sealed'];$checks++;}
 if(isset($r['body_sealed'])){$body=CSF_Billing_Config::open($r['body_sealed']);if(!hash_equals($r['body_sha256'],hash('sha256',$body))||!is_array(json_decode($body,true)))throw new RuntimeException('Offer mismatch');$sealed[]=$r['body_sealed'];$checks++;}
 if(isset($r['message_sealed'])){$m=json_decode(CSF_Billing_Config::open($r['message_sealed']),true);if(!str_ends_with($m['to'],'.invalid')||str_contains($m['subject'],'CSF-'))throw new RuntimeException('Message mismatch');$sealed[]=$r['message_sealed'];$checks++;}
}
foreach($sealed as $value){$raw=base64_decode($value,true);$raw[28]=chr(ord($raw[28])^1);try{CSF_Billing_Config::open(base64_encode($raw));throw new RuntimeException('Tamper accepted');}catch(CSF_Billing_Failure $e){$checks++;}}
if($checks<20)throw new RuntimeException('Fulfillment fixture missing');echo 'FULFILLMENT_KEYS='.$checks;

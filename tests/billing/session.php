<?php
/** Adversarial anonymous-session contract checks; no WordPress or database implied. */
require __DIR__ . '/domain.php';
require __DIR__ . '/../../wp-content/plugins/csf-billing/includes/class-csf-billing-session.php';
$fixture_home='https://fixture.invalid/';$fixture_tls=true;
function home_url($path='/'){global $fixture_home;return $fixture_home;}
function wp_parse_url($url){return parse_url($url);}
function is_ssl(){global $fixture_tls;return $fixture_tls;}
class SessionRequest {
 public $origin='https://fixture.invalid',$type='application/json',$method='POST',$body='{}';
 public function get_method(){return $this->method;}
 public function get_header($name){return $name==='origin'?$this->origin:$this->type;}
 public function get_body(){return $this->body;}
}
$cookie=CSF_Billing_Session::issue();
check(CSF_Billing_Session::validate($cookie)===$cookie,'valid signed session');
check(CSF_Billing_Session::digest($cookie)!==$cookie,'ledger keeps digest only');
check(CSF_Billing_Session::csrf($cookie)!==explode('.',$cookie)[2],'CSRF purpose separated');
check(CSF_Billing_Session::issue()!==$cookie,'independent random sessions');
foreach(array('',strtoupper($cookie),$cookie.'x',substr_replace($cookie,'z',0,1),substr($cookie,0,-1).(str_ends_with($cookie,'a')?'b':'a')) as $bad){rejected(fn()=>CSF_Billing_Session::validate($bad),'malformed or tampered cookie');}
foreach(array(time()-1,time()+7201) as $expiry){$body=str_repeat('a',64).'.'.$expiry;$signed=$body.'.'.hash_hmac('sha256',$fixture_home.'|cookie|'.$body,CSF_Billing_Config::key());rejected(fn()=>CSF_Billing_Session::validate($signed),'signed invalid lifetime');}
$fixture_home='https://other.invalid/';rejected(fn()=>CSF_Billing_Session::validate($cookie),'cookie site bound');$fixture_home='https://fixture.invalid/';
$r=new SessionRequest();CSF_Billing_Session::origin($r);check(true,'exact HTTPS JSON POST accepted');
foreach(array('origin'=>array('','null','https://fixture.invalid.attacker.invalid','http://fixture.invalid','https://fixture.invalid/'),'type'=>array('text/plain','application/jsonx'),'method'=>array('GET'),'body'=>array(str_repeat('x',16385))) as $key=>$values){foreach($values as $value){$bad=clone $r;$bad->$key=$value;rejected(fn()=>CSF_Billing_Session::origin($bad),'request boundary '.$key);}}
$fixture_tls=false;rejected(fn()=>CSF_Billing_Session::origin($r),'TLS required');
echo "PASS {$count} cumulative billing/session assertions\n";

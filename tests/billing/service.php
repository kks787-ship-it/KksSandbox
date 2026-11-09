<?php
/** Synthetic provider/store contract tests, not MySQL or PayPal qualification. */
require __DIR__ . '/domain.php';
require __DIR__ . '/../../wp-content/plugins/csf-billing/includes/class-csf-billing-paypal.php';
define('CSF_BILLING_VALUATION_JSON','{"method":"proportional_floor_v1","version":"synthetic","approved":true}');
define( 'CSF_BILLING_SANDBOX_ENABLED', true );
define( 'CSF_BILLING_CATALOG_JSON', json_encode( array( 'products' => array( $product ), 'terms' => $terms ) ) );
define( 'CSF_PAYPAL_SANDBOX_CLIENT_ID', 'synthetic-client-only' );
define( 'CSF_PAYPAL_SANDBOX_CLIENT_SECRET', 'synthetic-secret-only' );
define( 'CSF_PAYPAL_SANDBOX_MERCHANT_ID', 'TESTMERCHANT123' );
define( 'CSF_PAYPAL_SANDBOX_WEBHOOK_ID', 'SYNTHETICWEBHOOK123' );
function home_url($path='/'){return 'https://fixture.invalid'.$path;}
function wp_get_environment_type() { return defined('CSF_BILLING_SYNTHETIC_SINK') ? 'local' : 'development'; }
function wp_generate_uuid4() { $b = random_bytes(16); $b[6] = chr((ord($b[6]) & 15) | 64); $b[8] = chr((ord($b[8]) & 63) | 128); $h = bin2hex($b); return substr($h,0,8).'-'.substr($h,8,4).'-'.substr($h,12,4).'-'.substr($h,16,4).'-'.substr($h,20); }
class FixtureStore {
	public $tables = array( 'orders'=>array(), 'certificates'=>array(), 'commands'=>array(), 'audit'=>array() );
	public $fail_audit = false;
	public function command( $id, $payload, $callback, $guard = null ) {
		$before = $this->tables;
		try {
			if ( $guard ) { $guard(); }
			$hash = hash('sha256', CSF_Billing_Domain::json($payload));
			if ( isset($this->tables['commands'][$id]) ) {
				CSF_Billing_Domain::need( $hash === $this->tables['commands'][$id]['hash'], 'command_conflict' );
				return $this->tables['commands'][$id]['result'];
			}
			$result = $callback($this);
			$this->tables['commands'][$id] = array('hash'=>$hash,'result'=>$result);
			return $result;
		} catch (Throwable $e) { $this->tables=$before; throw $e; }
	}
	public function find($t,$f,$v) {foreach($this->tables[$t] as $row){if(($row[$f]??null)===$v){return $row;}}return null;}
	public function snapshot($callback,$guard=null){if($guard){$guard();}return $callback($this);}
	public function row($t,$id) {$r=$this->tables[$t][$id]??null;if($t==='commands'&&isset($r['hash'])){$r['payload_hash']=$r['hash'];$r['result']=json_encode($r['result']);}return $r;}
	public function insert($t,$data) {
		if ('certificates' === $t) {foreach($this->tables[$t] as $row) {CSF_Billing_Domain::need($row['order_id']!==$data['order_id']);}}
		$this->tables[$t][$data['id']]=$data;
	}
	public function update($t,$id,$data) {$this->tables[$t][$id]=array_replace($this->tables[$t][$id],$data);}
	public function audit($kind,$actor,$id,$details) {CSF_Billing_Domain::need(!$this->fail_audit,'fixture_audit_failure');$this->tables['audit'][]=compact('kind','actor','id','details');}
}
$http = array(); $provider_order = null; $timeout_capture = false; $verify_success = true;
function wp_remote_request( $url, $args ) {
	global $http, $provider_order, $timeout_capture, $verify_success;
	check(str_starts_with($url,'https://api-m.sandbox.paypal.com/'),'fixed sandbox origin');
	check($args['sslverify']===true && $args['redirection']===0,'TLS and no redirects');
	$http[]=array('url'=>$url,'method'=>$args['method']);
	$path=parse_url($url,PHP_URL_PATH);$body=array();
	if ($path==='/v1/oauth2/token') {$body=array('access_token'=>'synthetic-token','token_type'=>'Bearer');}
	elseif ($path==='/v2/checkout/orders' && $args['method']==='POST') {
		check(isset($args['headers']['PayPal-Request-Id']),'create request id');
		$provider_order=json_decode($args['body'],true);$provider_order['id']='TESTORDER12345';$provider_order['status']='CREATED';$body=$provider_order;
	} elseif ($path==='/v2/checkout/orders/TESTORDER12345') {$body=$provider_order;}
	elseif ($path==='/v2/checkout/orders/TESTORDER12345/capture') {
		check(isset($args['headers']['PayPal-Request-Id']),'capture request id');
		$provider_order['status']='COMPLETED';
		$provider_order['purchase_units'][0]['payments']['captures']=array(array('id'=>'TESTCAPTURE123','status'=>'COMPLETED','final_capture'=>true,'amount'=>$provider_order['purchase_units'][0]['amount'],'supplementary_data'=>array('related_ids'=>array('order_id'=>'TESTORDER12345'))));
		if($timeout_capture){return new stdClass();}
		$body=$provider_order;
	} elseif ($path==='/v2/payments/captures/TESTCAPTURE123') {$body=$provider_order['purchase_units'][0]['payments']['captures'][0];}
	elseif ($path==='/v1/notifications/verify-webhook-signature') {$body=array('verification_status'=>$verify_success?'SUCCESS':'FAILURE');}
	else {throw new RuntimeException('Unexpected network request');}
	return array('code'=>200,'body'=>json_encode($body));
}
function is_wp_error($r){return $r instanceof stdClass;}
function wp_remote_retrieve_response_code($r){return $r['code'];}
function wp_remote_retrieve_body($r){return $r['body'];}
$store=new FixtureStore();$service=new CSF_Billing_Service($store);$session=hash('sha256','synthetic-browser-session');$command=wp_generate_uuid4();
$prepared=$service->prepare($command,$session,$product,$terms,$consent);
check($prepared===$service->prepare($command,$session,$product,$terms,$consent),'prepare replay');
check(count($store->tables['orders'])===1,'no duplicated prepared order');
rejected(fn()=>$service->prepare($command,hash('sha256','other'),$product,$terms,$consent),'session replay mismatch');
$id=$prepared['order_id'];$create_command=wp_generate_uuid4();
$created=$service->create($create_command,$id,$session);
check($created['provider_order_id']==='TESTORDER12345','provider binding');
$before_http=count($http);
rejected(fn()=>$service->create($create_command,$id,$session),'claim replay cannot resend');
check(count($http)===$before_http,'no duplicate create request');
rejected(fn()=>$service->capture(wp_generate_uuid4(),$id,hash('sha256','other')),'different customer session');
rejected(fn()=>$service->capture(wp_generate_uuid4(),$id,$session),'unapproved cannot capture');
$provider_order['status']='APPROVED';$provider_order['id']='WRONGORDER123';
rejected(fn()=>$service->capture(wp_generate_uuid4(),$id,$session),'wrong provider order before capture');
$provider_order['id']='TESTORDER12345';$timeout_capture=true;
rejected(fn()=>$service->capture(wp_generate_uuid4(),$id,$session),'capture timeout');
check($store->tables['orders'][$id]['status']==='capture_uncertain','durable uncertain claim');
check(count($store->tables['certificates'])===0,'timeout alone never issues');
$issued=$service->capture(wp_generate_uuid4(),$id,$session);
check(count($store->tables['certificates'])===1,'reconcile verified completion issues once');
check($issued===$service->capture(wp_generate_uuid4(),$id,$session),'capture replay stable');
$captures=array_filter($http,fn($r)=>str_ends_with($r['url'],'/capture'));
check(count($captures)===1,'uncertain capture never resent');
$cert=$store->tables['certificates'][$issued['certificate_id']];
check($cert['purchased']===2 && $cert['redeemed']===0,'exact ride balance');
check(str_starts_with(CSF_Billing_Config::open($cert['number_sealed']),'CSF-'),'private certificate number encrypted');
class CSF_MFA_Session {public static $ok=true; public static function verified($unused,$id){return self::$ok;} public static function web_verified($id){return self::$ok;}}
class FixtureWpdb {
	public $usermeta='fixture_usermeta';public $allow=true;
	public function get_blog_prefix(){return 'fixture_';}
	public function prepare($sql,...$args){return $sql;}
	public function get_col($sql){return array(serialize(array('administrator'=>true,'csf_billing_redeem'=>$this->allow,'csf_billing_manage'=>$this->allow)));}
}
$wpdb=new FixtureWpdb();function get_current_user_id(){return 123;}
$redeem=wp_generate_uuid4();$ride=wp_generate_uuid4();$cid=$issued['certificate_id'];
$result=$service->redeem($redeem,$cid,1,1,$ride);
check($result['remaining']===1,'audited ride debit');
check($result===$service->redeem($redeem,$cid,1,1,$ride),'redeem replay stable');
check($store->tables['certificates'][$cid]['redeemed']===1,'redeem retry cannot double spend');
rejected(fn()=>$service->redeem($redeem,$cid,2,1,$ride),'changed command conflicts');
$wpdb->allow=false;rejected(fn()=>$service->redeem($redeem,$cid,1,1,$ride),'revoked actor cannot replay');$wpdb->allow=true;
CSF_MFA_Session::$ok=false;rejected(fn()=>$service->redeem(wp_generate_uuid4(),$cid,1,2,$ride),'MFA required');CSF_MFA_Session::$ok=true;
rejected(fn()=>$service->redeem(wp_generate_uuid4(),$cid,1,2,$ride),'duplicate ride reference');
$store->fail_audit=true;$before=$store->tables;
rejected(fn()=>$service->redeem(wp_generate_uuid4(),$cid,1,2,wp_generate_uuid4()),'audit failure rollback');
check($before===$store->tables,'mutation and receipt roll back with audit');$store->fail_audit=false;
$headers=array('auth_algo'=>'SHA256withRSA','cert_url'=>'https://api.sandbox.paypal.com/v1/notifications/certs/SYNTHETIC-CERT','transmission_id'=>'synthetic-transmission','transmission_sig'=>'synthetic-signature','transmission_time'=>'2026-11-08T00:00:00Z');
$event=json_encode(array('id'=>'SYNTHETIC-EVENT-1','event_type'=>'PAYMENT.CAPTURE.COMPLETED'));
$provider=new CSF_Billing_PayPal();check($provider->verify($event,$headers)['id']==='SYNTHETIC-EVENT-1','verification contract stub');
$verify_success=false;rejected(fn()=>$provider->verify($event,$headers),'provider signature rejection');
$headers['cert_url']='https://attacker.invalid/cert';$before_http=count($http);
rejected(fn()=>$provider->verify($event,$headers),'untrusted certificate URL');check($before_http===count($http),'no untrusted URL request');
$correction=wp_generate_uuid4();$corrected=$service->correct($correction,$cid,$redeem,2,'mistaken_ride');check($corrected['remaining']===2,'correction restores only original debit');check($corrected===$service->correct($correction,$cid,$redeem,2,'mistaken_ride'),'correction replay');rejected(fn()=>$service->correct(wp_generate_uuid4(),$cid,$redeem,3,'mistaken_ride'),'second correction rejected');
$verify_success=true;$headers['cert_url']='https://api.sandbox.paypal.com/v1/notifications/certs/SYNTHETIC-CERT';
$refund=array('id'=>'SYNTHETIC-REFUND-EVENT','event_type'=>'PAYMENT.CAPTURE.REFUNDED','resource'=>array('supplementary_data'=>array('related_ids'=>array('capture_id'=>'TESTCAPTURE123'))));
$frozen=$service->webhook(json_encode($refund),$headers);
check($frozen['frozen']===true && $store->tables['certificates'][$cid]['status']==='frozen','verified refund freezes credits');
$revision=$store->tables['certificates'][$cid]['revision'];
check($frozen===$service->webhook(json_encode($refund),$headers),'event replay stable');
check($revision===$store->tables['certificates'][$cid]['revision'],'event replay no second mutation');
$changed=$refund;$changed['summary']='different payload';rejected(fn()=>$service->webhook(json_encode($changed),$headers),'same event ID changed payload');
rejected(fn()=>$service->redeem(wp_generate_uuid4(),$cid,1,$revision,$ride),'frozen redemption prohibited');
$late=array('id'=>'SYNTHETIC-LATE-EVENT','event_type'=>'PAYMENT.CAPTURE.COMPLETED','resource'=>array('id'=>'TESTCAPTURE123'));
rejected(fn()=>$service->webhook(json_encode($late),$headers),'late completion cannot revive frozen credits');
check($store->tables['certificates'][$cid]['status']==='frozen','freeze retained after reordered completion');

// Verified dispute can precede local binding; its capture hold must survive that ordering.
$earlyStore=new FixtureStore();$earlyRow=$store->tables['orders'][$id];$earlyRow['status']='approved';unset($earlyRow['capture_id'],$earlyRow['provider_order_id']);$earlyStore->tables['orders'][$id]=$earlyRow;
$earlyService=new CSF_Billing_Service($earlyStore);
$dispute=array('id'=>'SYNTHETIC-EARLY-DISPUTE','event_type'=>'CUSTOMER.DISPUTE.CREATED','resource'=>array('disputed_transactions'=>array(array('seller_transaction_id'=>'TESTCAPTURE123'))));
$held=$earlyService->webhook(json_encode($dispute),$headers);check($held['unmapped_count']===1 && $held['review_required']===true,'unmapped dispute retained for private review');
$earlyStore->tables['orders'][$id]['provider_order_id']='TESTORDER12345';
rejected(fn()=>$earlyService->reconcile($id),'early dispute blocks later issuance');check(count($earlyStore->tables['certificates'])===0,'no credit issued after early negative evidence');
$pendingStore=new FixtureStore();$pendingStore->tables['orders'][$id]=$earlyStore->tables['orders'][$id];$pendingStore->tables['orders'][$id]['status']='capture_uncertain';$pendingService=new CSF_Billing_Service($pendingStore);
$provider_order['purchase_units'][0]['payments']['captures'][0]['status']='PENDING';
$pending=array('id'=>'SYNTHETIC-PENDING-EVENT','event_type'=>'PAYMENT.CAPTURE.PENDING','resource'=>array('id'=>'TESTCAPTURE123'));
check($pendingService->webhook(json_encode($pending),$headers)['pending']===true,'pending capture acknowledged without issuance');check(count($pendingStore->tables['certificates'])===0,'pending capture has no rides');
$provider_order['purchase_units'][0]['payments']['captures'][0]['status']='COMPLETED';
$completed=array('id'=>'SYNTHETIC-COMPLETE-LATER','event_type'=>'PAYMENT.CAPTURE.COMPLETED','resource'=>array('id'=>'TESTCAPTURE123'));
$pendingService->webhook(json_encode($completed),$headers);check(count($pendingStore->tables['certificates'])===1,'pending to verified completion issues exactly once');
$provider_order['purchase_units'][0]['payments']['captures'][0]['status']='REFUNDED';
rejected(fn()=>$pendingService->reconcile($id),'issuance replay rechecks current capture status');
$provider_order['purchase_units'][0]['payments']['captures'][0]['status']='COMPLETED';
$dispute['id']='SYNTHETIC-RESOLVED-FIRST';$dispute['event_type']='CUSTOMER.DISPUTE.RESOLVED';$pendingService->webhook(json_encode($dispute),$headers);
check(array_values($pendingStore->tables['certificates'])[0]['status']==='frozen','resolved dispute does not automatically release credits');
// A lost create response can only be linked to the original immutable order.
$recoveryStore=new FixtureStore();$recoveryRow=$earlyRow;$recoveryRow['status']='create_uncertain';$recoveryRow['revision']=2;unset($recoveryRow['provider_order_id']);$recoveryStore->tables['orders'][$id]=$recoveryRow;
$recoveryService=new CSF_Billing_Service($recoveryStore);$recoverCommand=wp_generate_uuid4();
$provider_order['purchase_units'][0]['payee']['merchant_id']='WRONGMERCHANT';
rejected(fn()=>$recoveryService->recover_created($recoverCommand,$id,'TESTORDER12345',2),'manual recovery rejects wrong merchant');
$provider_order['purchase_units'][0]['payee']['merchant_id']='TESTMERCHANT123';
$linked=$recoveryService->recover_created($recoverCommand,$id,'TESTORDER12345',2);
check($linked['status']==='approved' && count($recoveryStore->tables['certificates'])===0,'manual recovery links without issuing');
check($linked===$recoveryService->recover_created($recoverCommand,$id,'TESTORDER12345',2),'manual recovery exact replay');
rejected(fn()=>$recoveryService->recover_created($recoverCommand,$id,'TESTORDER12345',3),'manual recovery changed replay rejected');
$beforeCapture=count(array_filter($http,fn($r)=>str_ends_with($r['url'],'/capture')));
$recoveryService->capture(wp_generate_uuid4(),$id,$session);
check(count($recoveryStore->tables['certificates'])===1,'recovered completed order reconciles once');
check(count(array_filter($http,fn($r)=>str_ends_with($r['url'],'/capture')))===$beforeCapture,'recovered completion never recaptured');
// Already-approved lost-create recovery must still be capturable exactly once.
$approvedStore=new FixtureStore();$approvedStore->tables['orders'][$id]=$recoveryRow;$approvedService=new CSF_Billing_Service($approvedStore);
$provider_order['status']='APPROVED';unset($provider_order['purchase_units'][0]['payments']);$timeout_capture=false;
$approvedService->recover_created(wp_generate_uuid4(),$id,'TESTORDER12345',2);
$approvedService->capture(wp_generate_uuid4(),$id,$session);
check(count($approvedStore->tables['certificates'])===1,'recovered already-approved order can capture');
// A separately completed verified provider order can arrive while local approval is pending.
$externalStore=new FixtureStore();$externalRow=$recoveryRow;$externalRow['status']='awaiting_approval';$externalRow['provider_order_id']='TESTORDER12345';$externalStore->tables['orders'][$id]=$externalRow;
$externalService=new CSF_Billing_Service($externalStore);$beforeCapture=count(array_filter($http,fn($r)=>str_ends_with($r['url'],'/capture')));
$externalService->capture(wp_generate_uuid4(),$id,$session);
check(count($externalStore->tables['certificates'])===1,'verified external completion reconciles local pending approval');
check(count(array_filter($http,fn($r)=>str_ends_with($r['url'],'/capture')))===$beforeCapture,'external completion is never recaptured');
echo "PASS {$count} cumulative synthetic billing assertions; no real database or PayPal service used\n";

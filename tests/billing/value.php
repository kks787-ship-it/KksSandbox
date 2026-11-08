<?php
/** Synthetic service/value regression suite; provider and authorization are mocks here. */
require __DIR__.'/service.php';
$start=$count;
foreach(array('proportional_floor_v1','front_remainder_v1') as $method){
 $p=array('method'=>$method,'version'=>'synthetic','approved'=>true);
 foreach(array(101,10000,100000000) as $paid){foreach(array(1,2,3,100) as $rides){$a=CSF_Billing_Value::allocation($paid,$rides,$p);check(array_sum($a)===$paid&&count($a)===$rides&&max($a)-min($a)<=1,'all paid cents allocated proportionally');}}
}
$p=array('method'=>'proportional_floor_v1','version'=>'synthetic','approved'=>true);
$moneyStore=new FixtureStore();$moneyService=new CSF_Billing_Service($moneyStore);$moneyId=wp_generate_uuid4();
$row=array('id'=>$moneyId,'purchased'=>3,'redeemed'=>0,'status'=>'active','revision'=>1,'value_json'=>CSF_Billing_Domain::json(CSF_Billing_Value::initial(101,3,$p)));
$moneyStore->tables['certificates'][$moneyId]=$row;
$first=wp_generate_uuid4();$one=$moneyService->redeem($first,$moneyId,1,1,wp_generate_uuid4());$two=$moneyService->redeem(wp_generate_uuid4(),$moneyId,1,2,wp_generate_uuid4());
check($one['value_minor']===33&&$two['value_minor']===34,'immutable proportional cent allocation');
$moneyService->correct(wp_generate_uuid4(),$moneyId,$first,3,'mistaken_ride');$v=CSF_Billing_Value::read($moneyStore->tables['certificates'][$moneyId]);check(CSF_Billing_Value::remaining($v)===67&&$v['used_slots']===array(1),'correction restores original 33 cents rather than recomputing rounded average');
$request=wp_generate_uuid4();$cmd=wp_generate_uuid4();$evidence=wp_generate_uuid4();
$cash=$moneyService->cash($cmd,$moneyId,4,'reserve',$request,67,$evidence,true);
check($cash===$moneyService->cash($cmd,$moneyId,4,'reserve',$request,67,$evidence,true),'cash request replay exact');
rejected(fn()=>$moneyService->redeem(wp_generate_uuid4(),$moneyId,1,5,wp_generate_uuid4()),'cash reserve prevents competing ride debit');
rejected(fn()=>$moneyService->cash(wp_generate_uuid4(),$moneyId,5,'record_paid',$request,66,wp_generate_uuid4(),true),'amount cannot drift');
rejected(fn()=>$moneyService->cash(wp_generate_uuid4(),$moneyId,5,'record_paid',$request,67,wp_generate_uuid4(),false),'attestation required');
$moneyService->cash(wp_generate_uuid4(),$moneyId,5,'uncertain',$request,67,wp_generate_uuid4(),true);
check(CSF_Billing_Value::read($moneyStore->tables['certificates'][$moneyId])['pending']['state']==='uncertain','unknown payout remains reserved');
$paidCommand=wp_generate_uuid4();$paidEvidence=wp_generate_uuid4();$beforeHttp=count($http);
$paid=$moneyService->cash($paidCommand,$moneyId,6,'record_paid',$request,67,$paidEvidence,true);
check($paid['remaining_minor']===0&&$paid['payment_executed']===false&&count($http)===$beforeHttp,'cash recording consumes balance without provider calls');
check($paid===$moneyService->cash($paidCommand,$moneyId,6,'record_paid',$request,67,$paidEvidence,true),'recorded payout replay never duplicates debit');
rejected(fn()=>$moneyService->cash(wp_generate_uuid4(),$moneyId,7,'record_paid',$request,67,$paidEvidence,true),'different command cannot repeat payout');
rejected(fn()=>$moneyService->redeem(wp_generate_uuid4(),$moneyId,1,7,wp_generate_uuid4()),'cash exhausted value prevents remaining ride use');
$wpdb->allow=false;rejected(fn()=>$moneyService->cash($paidCommand,$moneyId,6,'record_paid',$request,67,$paidEvidence,true),'revoked cash authority cannot replay');$wpdb->allow=true;
CSF_MFA_Session::$ok=false;rejected(fn()=>$moneyService->cash($paidCommand,$moneyId,6,'record_paid',$request,67,$paidEvidence,true),'cash replay requires current MFA');CSF_MFA_Session::$ok=true;
// New certificate tests cancellation, rollback, stale revisions, pending approval and malformed values.
$other=wp_generate_uuid4();$row['id']=$other;$moneyStore->tables['certificates'][$other]=$row;
$r=wp_generate_uuid4();$moneyService->cash(wp_generate_uuid4(),$other,1,'reserve',$r,101,wp_generate_uuid4(),true);
$moneyStore->fail_audit=true;rejected(fn()=>$moneyService->cash(wp_generate_uuid4(),$other,2,'cancel_unpaid',$r,101,wp_generate_uuid4(),true),'failed cash audit rolls back release');$moneyStore->fail_audit=false;
check(CSF_Billing_Value::read($moneyStore->tables['certificates'][$other])['pending']['id']===$r,'failed release preserves reservation');
$moneyService->cash(wp_generate_uuid4(),$other,2,'cancel_unpaid',$r,101,wp_generate_uuid4(),true);
rejected(fn()=>$moneyService->cash(wp_generate_uuid4(),$other,3,'reserve',$r,101,wp_generate_uuid4(),true),'reservation tombstone prevents reuse');
$moneyService->redeem(wp_generate_uuid4(),$other,1,3,wp_generate_uuid4());
rejected(fn()=>$moneyService->cash(wp_generate_uuid4(),$other,3,'reserve',wp_generate_uuid4(),68,wp_generate_uuid4(),true),'stale revision cannot cash and ride spend');
$bad=$row;$v=json_decode($bad['value_json'],true);$v['policy']['approved']=false;$bad['value_json']=json_encode($v);rejected(fn()=>CSF_Billing_Value::debit($bad,1),'unapproved allocation blocks rides');
$v['cash_paid_minor']=102;$bad['value_json']=json_encode($v);rejected(fn()=>CSF_Billing_Value::read($bad),'negative value fails closed');
unset($bad['value_json']);rejected(fn()=>CSF_Billing_Value::read($bad),'legacy rows require reconciliation not invented value');
echo 'VALUE_WORKFLOW_ASSERTIONS='.($count-$start)."\n";

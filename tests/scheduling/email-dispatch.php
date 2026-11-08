<?php
/** Durable outbox checks on synthetic data in the exact disposable database only. */
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture required');}
wp_set_current_user(1);require_once '/repo/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-email-dispatch.php';
global $wpdb;$n=0;
$check=static function($ok,$label)use(&$n){if(!$ok)throw new RuntimeException($label);++$n;};
$rev=static fn()=>(int)CSF_Scheduling_Schema::settings()['revision'];
$save=static fn($b)=>CSF_Scheduling_Store::calendar('email_sink_transition',$b,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4());
$t=CSF_Scheduling_Schema::table('details');
$rows=$wpdb->get_results("SELECT * FROM `$t` ORDER BY id DESC",ARRAY_A);$event=null;
foreach($rows as$row){$e=json_decode($row['body_json'],true);if(($e['record_type']??null)==='booking_notice'&&in_array(1,$e['recipient_ids'],true)){$event=$e;break;}}
$check($event!==null,'real committed booking event exists');
// Duplicate only a synthetic event envelope to give each scenario an independent key.
$body=static function()use($event,$wpdb,$t){$e=$event;$e['source_command']=wp_generate_uuid4();$wpdb->insert($t,['uuid'=>$e['source_command'],'entity_uuid'=>$e['entity_uuid'],'revision'=>1,'body_json'=>wp_json_encode($e),'created_by'=>1,'created_at_utc'=>CSF_Scheduling_Audit::now()]);return ['event'=>$e['source_command'],'recipient'=>1,'expected'=>0,'action'=>'queue','token'=>str_repeat('a',32),'outcome'=>''];};
$read=static function($b)use($wpdb,$t){$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$t` WHERE uuid=%s",CSF_Scheduling_Email_Dispatch::uuid($b['event'],$b['recipient'])),ARRAY_A);return $r?json_decode($r['body_json'],true)['job']:null;};
$claim=static function(&$b)use($save,$check){$r=$save($b);$check(!is_wp_error($r)&&$r['job']['state']==='queued','queue persists');$b['expected']=$r['expected'];$b['action']='claim';$r=$save($b);$check(!is_wp_error($r)&&$r['job']['state']==='claimed','claim persists');$b['expected']=$r['expected'];return$r;};
$b=$body();$revision=$rev();$command=wp_generate_uuid4();$r=CSF_Scheduling_Store::calendar('email_sink_transition',$b,$revision,$command);
$check(!is_wp_error($r),'first durable queue');$check($r===CSF_Scheduling_Store::calendar('email_sink_transition',$b,$revision,$command),'queue ledger replay');$check(is_wp_error($save($b)),'second queue cannot duplicate key');
$b=$body();$claim($b);$sink=new CSF_Scheduling_Email_Sink();$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);$check(!is_wp_error($r)&&$read($b)['state']==='sink_accepted'&&count($sink->messages)===1,'worker persists accepted sink outcome');
$check(is_wp_error(CSF_Scheduling_Email_Dispatch::run($b,$sink))&&count($sink->messages)===1,'restart with old claim never resends');
$b=$body();$claim($b);$sink=new CSF_Scheduling_Email_Sink(['throw']);$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);$check(!is_wp_error($r)&&$read($b)['state']==='uncertain','ambiguous outcome durable terminal');
$b=$body();$claim($b);$sink=new CSF_Scheduling_Email_Sink(['rejected_transient']);$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);$check(!is_wp_error($r)&&$read($b)['state']==='retry'&&$read($b)['retry_at']>time(),'explicit rejection alone schedules bounded retry');$b['expected']=$r['expected'];$b['action']='claim';$check(is_wp_error($save($b)),'retry cannot run before backoff');
// Fail the ledger after the begin write: transaction rollback must prevent submission.
$b=$body();$claim($b);$sink=new CSF_Scheduling_Email_Sink();$fail=static fn($q)=>str_contains($q,'INSERT INTO `fixture_csf_sched_commands`')?'SELECT * FROM fixture_dispatch_missing':$q;
add_filter('query',$fail);$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);remove_filter('query',$fail);$check(is_wp_error($r)&&!$sink->messages&&$read($b)['state']==='claimed','late begin ledger failure rolls back and never sends');
// Finish failure after a successful sink call must leave submitting; no resend authority.
$count=0;$fail_finish=static function($q)use(&$count){if(str_contains($q,'INSERT INTO `fixture_csf_sched_commands`')&&++$count===2)return'SELECT * FROM fixture_dispatch_missing';return$q;};
add_filter('query',$fail_finish);$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);remove_filter('query',$fail_finish);$check(is_wp_error($r)&&count($sink->messages)===1&&$read($b)['state']==='submitting','failed finish preserves uncertain submission boundary');
$check(is_wp_error(CSF_Scheduling_Email_Dispatch::run($b,new CSF_Scheduling_Email_Sink())),'fresh process cannot replay submission');
// Exact fixture-only clock adjustment exercises expired submitting records without sleeping.
$uuid=CSF_Scheduling_Email_Dispatch::uuid($b['event'],1);$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$t` WHERE uuid=%s",$uuid),ARRAY_A);$doc=json_decode($row['body_json'],true);$doc['job']['lease_until']=time()-1;$wpdb->update($t,['body_json'=>wp_json_encode($doc)],['id'=>$row['id']]);$b['expected']=(int)$row['revision'];$b['action']='expire';$r=$save($b);$check(!is_wp_error($r)&&$read($b)['state']==='uncertain','expired submitting becomes terminal uncertain');
// Enrollment revocation between claim and submission suppresses, without changing MFA or membership.
$b=$body();$claim($b);$sink=new CSF_Scheduling_Email_Sink();$deny=static fn()=>false;add_filter('csf_scheduling_mfa_enrolled',$deny,PHP_INT_MAX);$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);remove_filter('csf_scheduling_mfa_enrolled',$deny,PHP_INT_MAX);$check(!is_wp_error($r)&&$read($b)['state']==='suppressed'&&!$sink->messages,'current enrollment checked immediately before submitting');
$check($event['email_state']==='held_no_delivery_authorization','source event remains held');
$b=$body();$claim($b);file_put_contents("/test/email-dispatch-race.json",wp_json_encode($b));
echo "EMAIL_DISPATCH_DATABASE_PASS checks=$n\n";

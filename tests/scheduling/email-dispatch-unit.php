<?php
/** Current-recipient regression checks, independent of transport/runtime. */
define('ABSPATH','/test/site/');define('DB_NAME','csf_fixture');
function wp_get_environment_type(){return 'local';}
$root=dirname(__DIR__,2).'/wp-content/plugins/csf-scheduling/includes/';
require $root.'class-csf-scheduling-permissions.php';require $root.'class-csf-scheduling-email-dispatch.php';
$n=0;function ed_check($ok,$label){global$n;if(!$ok)throw new RuntimeException($label);++$n;}
$m=static fn($group,$resources=[])=>['active'=>true,'verified'=>true,'group'=>$group,'resources'=>$resources,'overrides'=>[]];
$s=['master'=>99,'groups'=>CSF_Scheduling_Permissions::presets(),'members'=>[1=>$m('operations'),2=>$m('instructor',['a']),3=>$m('instructor',['b'])]];
$e=['recipient_ids'=>[1,2],'recipient_items'=>[1=>['x'],2=>['x']],'changes'=>[['uuid'=>'x','previous_instructors'=>['a'],'instructors'=>['a']]]];$a=['x'=>['instructors'=>['a']]];
$check=static function($expected,$event,$id,$state,$appointments,$label){ed_check($expected===CSF_Scheduling_Email_Dispatch::permitted($event,$id,$state,$appointments),$label);};
$check(true,$e,1,$s,$a,'current oversight');$check(true,$e,2,$s,$a,'current assigned');$check(false,$e,3,$s,$a,'new recipient cannot join original event');
foreach(['active','verified']as$key){$bad=$s;$bad['members'][2][$key]=false;$check(false,$e,2,$bad,$a,'revoked '.$key);}
foreach(['calendar_assigned']as$key){$bad=$s;$bad['members'][2]['overrides'][$key]='deny';$check(false,$e,2,$bad,$a,'deny '.$key);}
$bad=$s;$bad['members'][1]['overrides']['calendar_all']='deny';$check(false,$e,1,$bad,$a,'removed oversight');
$check(false,$e,2,$s,['x'=>['instructors'=>['b']]],'old NEW notice suppressed after reassignment');
$check(false,$e,2,$s,['x'=>['resources'=>['a'],'instructors'=>[]]],'non-instructor resources cannot confer mail scope');
$e['recipient_departures'][2]=['x'];$check(true,$e,2,$s,['x'=>['instructors'=>['b']]],'minimal departure still permitted');
$bad=$s;$bad['members'][2]['resources']=[];$check(false,$e,2,$bad,$a,'removed resource binding suppresses departure');
$check(false,$e,2,$s,[],'missing appointment suppressed');
$e['recipient_items'][2][]='y';$check(false,$e,2,$s,$a,'partial group never silently expands or changes');
$u=CSF_Scheduling_Email_Dispatch::uuid('00000000-0000-4000-8000-000000000001',2);
ed_check($u===CSF_Scheduling_Email_Dispatch::uuid('00000000-0000-4000-8000-000000000001',2),'deterministic identity');
ed_check($u!==CSF_Scheduling_Email_Dispatch::uuid('00000000-0000-4000-8000-000000000001',1),'recipient isolation');
ed_check((bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-a[a-f0-9]{3}-[a-f0-9]{12}$/',$u),'valid stable UUID');
echo "EMAIL_DISPATCH_UNIT_PASS checks=$n\n";

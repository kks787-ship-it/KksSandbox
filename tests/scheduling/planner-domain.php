<?php
// Pure presentation rules, disposable fixture only; no alternate booking decisions.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture only');}
$n=0;$check=static function($ok)use(&$n){if(!$ok)throw new RuntimeException('Planner presentation assertion '.($n+1));++$n;};
$normal=CSF_Scheduling_Planner::ticks('2027-01-26','America/Los_Angeles');$gap=CSF_Scheduling_Planner::ticks('2027-03-14','America/Los_Angeles');$fold=CSF_Scheduling_Planner::ticks('2027-11-07','America/Los_Angeles');
$check(count($normal)===96);$check(count($gap)===92);$check(count($fold)===100);$check(count(array_unique(array_column($fold,'iso')))===100);
$check(count(array_filter($fold,static fn($t)=>$t['clock']==='01:30'))===2);$check(!array_filter($gap,static fn($t)=>str_starts_with($t['clock'],'02:')));
foreach(array($normal,$gap,$fold)as$ticks){$check(!array_filter($ticks,static fn($t)=>CSF_Scheduling_Time::parse($t['iso'],'America/Los_Angeles')->getTimestamp()!==$t['stamp']));}
try{CSF_Scheduling_Planner::ticks('2027-02-30','America/Los_Angeles');$check(false);}catch(RuntimeException$e){$check($e->getMessage()==='invalid_time');}
$p=CSF_Scheduling_Settings::pilot();$available=array('resource'=>array(array('start'=>'2027-01-26T09:00-08:00','end'=>'2027-01-26T12:00-08:00')));
$status=static fn($i,$restrictions=array(),$events=array(),$resource='resource')=>CSF_Scheduling_Planner::status($normal[$i],2,$resource,$p,$available,$restrictions,$events);
$check($status(28)==='Facility closed');$check($status(32)==='Resource unavailable');$check($status(36)==='');$check($status(48)==='Resource unavailable');
$event=array('left'=>strtotime('2027-01-26T10:00-08:00'),'right'=>strtotime('2027-01-26T10:30-08:00'),'reserved'=>true);
$check($status(40,array(),array($event))==='Reserved');$check($status(42,array(),array($event))==='');$event['reserved']=false;$check($status(40,array(),array($event))==='');
$restriction=array('entity_uuid'=>'resource','data'=>array('start'=>'2027-01-26T11:00-08:00','end'=>'2027-01-26T11:30-08:00','reason'=>'welfare'));
$check($status(44,array($restriction))==='Protective block: welfare');$restriction['data']['released']=true;$check($status(44,array($restriction))==='');
$check($status(40,array(),array(),'')==='');
$check(CSF_Scheduling_Booking::simple(array('expected'=>0,'riders'=>array(),'rides'=>array())));$check(!CSF_Scheduling_Booking::simple(array('expected'=>0,'riders'=>array('one','two'),'rides'=>array(array(),array()))));
echo 'PLANNER_DOMAIN_CHECKS='.$n."\n";

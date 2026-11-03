<?php
require '/repo/tests/workflows/fixture.php';
if(getenv('CSF_WORKFLOW_FIXTURE')!=='local-only')exit(2);
[$script,$ready,$action,$actor,$command,$revision]=$argv;
$db=database();$access=new FixtureAccess();$app=new CSF_Workflows($db,'wf_',$access);
file_put_contents($ready,'ready');
try{
 if($action==='assign')$result=$app->assign('barn-a',(int)$actor,$command,'student-race',(int)$revision);
 elseif($action==='review')$result=$app->review('barn-a',(int)$actor,$command,$revision,'reject');
 elseif($action==='deliver'){$GLOBALS['sink_db']=database();$result=$app->deliver_one(new FixtureSink(),99999);}
 else throw new RuntimeException('invalid');
 echo json_encode(['ok'=>true,'result'=>$result]);
}catch(Throwable$e){echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);}

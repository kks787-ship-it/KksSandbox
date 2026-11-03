<?php
require '/repo/tests/workflows/fixture.php';
if(getenv('CSF_WORKFLOW_FIXTURE')!=='local-only')throw new RuntimeException('Fixture guard');
$db=database();$db->set_charset('utf8mb4');
foreach(CSF_Workflows::definitions()as$name=>$definition)$db->query("CREATE TABLE wf_$name($definition) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin");
$db->query("CREATE TABLE wf_appointment_source(facility VARCHAR(64),command_id VARCHAR(64),data LONGTEXT,observed INT,PRIMARY KEY(facility,command_id)) ENGINE=InnoDB");
$db->query("INSERT INTO wf_facility VALUES('barn-a',0),('barn-b',0)");
$access=new FixtureAccess();$source=new FixtureAppointments();$app=new CSF_Workflows($db,'wf_',$access,$source);$n=0;
function check(bool $ok,string $label):void{global$n;if(!$ok)throw new RuntimeException($label);++$n;echo"PASS $label\n";}
function denied(callable $fn,string $label):void{try{$fn();}catch(Throwable $e){check(true,$label);return;}check(false,$label);}
function count_rows(mysqli $db,string $table):int{return(int)$db->query("SELECT COUNT(*) FROM wf_$table")->fetch_row()[0];}
$horse=['profile'=>['barn_name'=>'Pilot Horse','owner_observation'=>'Synthetic','tack_notes'=>'Fixture','clinical_plan'=>'PRIVATE','billing'=>'PRIVATE','welfare_hold'=>'FARM-HELD'],'links'=>[]];
seed($db,'barn-a','horse','horse-a',$horse);seed($db,'barn-a','horse','horse-b',$horse);seed($db,'barn-b','horse','horse-a',$horse);
seed($db,'barn-a','student','student-a',['education'=>['display_label'=>'Pilot Rider','goals'=>'Balance','experience'=>'Synthetic','focus'=>'Seat','medical'=>'PRIVATE'],'guardian'=>'PRIVATE','legal'=>'PRIVATE','emergency'=>'PRIVATE']);
seed($db,'barn-b','student','student-a',['education'=>['display_label'=>'Other facility']]);
denied(fn()=>$app->horse('barn-a',5,'horse-a'),'unlinked boarder denied');
$app->horse_links('barn-a',1,'link-two','horse-a',1,['5'=>['barn_name','owner_observation'],'6'=>['tack_notes']]);
check($app->horse('barn-a',5,'horse-a')===['barn_name'=>'Pilot Horse','owner_observation'=>'Synthetic'],'boarder projection contains permitted fields only');
check($app->horse('barn-a',6,'horse-a')===['tack_notes'=>'Fixture'],'same horse supports independently scoped second account');
denied(fn()=>$app->horse('barn-a',5,'horse-b'),'cross horse denied');denied(fn()=>$app->horse('barn-b',5,'horse-a'),'cross facility link denied');
denied(fn()=>$app->horse_links('barn-a',5,'self-link','horse-b',1,['5'=>['barn_name']]),'boarder cannot self assign');
denied(fn()=>$app->horse_links('barn-a',1,'ghost','horse-b',1,['99'=>['barn_name']]),'unknown target cannot become authorized by numeric ID');
denied(fn()=>$app->horse_links('barn-a',1,'unsafe-fields','horse-b',1,['5'=>['welfare_hold']]),'farm welfare field cannot be delegated');
$app->horse_edit('barn-a',5,'boarder-edit','horse-a',2,['barn_name'=>'🐴 Synthetic']);check($app->horse('barn-a',5,'horse-a')['barn_name']==='🐴 Synthetic','authorized profile write persists utf8mb4');
$before=count_rows($db,'audit');$r=$app->horse_edit('barn-a',5,'boarder-edit','horse-a',2,['barn_name'=>'🐴 Synthetic']);check($r['revision']===3&&count_rows($db,'audit')===$before,'exact replay does not duplicate mutation or audit');
denied(fn()=>$app->horse_edit('barn-a',5,'boarder-edit','horse-a',2,['barn_name'=>'tampered']),'replay payload tampering denied');
foreach(['clinical_plan','welfare_hold','billing','links']as$field)denied(fn()=>$app->horse_edit('barn-a',5,'bad-'.$field,'horse-a',3,[$field=>'overwrite']),'boarder cannot write '.$field);
$access->revoked[5]=true;denied(fn()=>$app->horse('barn-a',5,'horse-a'),'membership removal takes effect immediately');denied(fn()=>$app->horse_edit('barn-a',5,'boarder-edit','horse-a',2,['barn_name'=>'🐴 Synthetic']),'revocation denies old replay access');unset($access->revoked[5]);
$app->horse_links('barn-a',1,'unlink','horse-a',3,['6'=>['tack_notes']]);denied(fn()=>$app->horse('barn-a',5,'horse-a'),'removed horse link immediately denies');denied(fn()=>$app->horse_edit('barn-a',5,'boarder-edit','horse-a',2,['barn_name'=>'🐴 Synthetic']),'link removal denies old successful command replay');
$education=$app->student('barn-a',3,'student-a');check(!array_intersect_key($education,array_flip(['medical','guardian','legal','emergency'])),'educational projection excludes sensitive sections');check($app->student('barn-a',4,'student-a')===$education,'authorized nonprimary instructor sees same education');denied(fn()=>$app->student('barn-a',5,'student-a'),'boarder cannot read student profiles');denied(fn()=>$app->student('barn-b',3,'student-a'),'educator cannot cross facility membership');
$access->no_proof[3]=true;denied(fn()=>$app->student('barn-a',3,'student-a'),'missing current proof cannot use educational access');unset($access->no_proof[3]);denied(fn()=>$app->horse('barn-a',0,'horse-a'),'anonymous identity denied before profile access');
$a=$app->assign('barn-a',3,'primary-a','student-a',0);check($a['primary']===3&&$a['state']==='pending_review'&&entity($db,'primary','student-a')['data']['primary']===3,'self reassignment effective immediately before review');
check((int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE event='primary.changed'")->fetch_row()[0]===2,'both managers notified of committed change');
$app->note('barn-a',4,'note-a','student-a','Synthetic lesson observation');check(count($app->notes('barn-a',3,'student-a'))===1&&count($app->notes('barn-a',4,'student-a'))===1,'all authorized instructors read notes independently of authorship/primary');
$row=$db->query("SELECT recipient FROM wf_outbox WHERE command_id='note-a'")->fetch_row();check((int)$row[0]===3,'note author differs from effective primary notification recipient');
$access->denies[4]=['education_write'];denied(fn()=>$app->note('barn-a',4,'note-a','student-a','Synthetic lesson observation'),'revoked write privilege denies old note replay while membership remains active');check(count($app->notes('barn-a',4,'student-a'))===1,'write revocation does not invent a primary-based read restriction');unset($access->denies[4]);
$r=$app->review('barn-a',1,'approve-a','primary-a','approve');check($r['state']==='approved'&&$r['primary']===3,'approve retains effective primary');
$app->assign('barn-a',4,'primary-b','student-a',2);$r=$app->review('barn-a',2,'reject-b','primary-b','reject');check($r['state']==='rejected'&&$r['primary']===3,'unchanged rejected revision restores preceding primary');
$app->assign('barn-a',4,'primary-c','student-a',4);$app->assign('barn-a',3,'primary-d','student-a',5);$r=$app->review('barn-a',1,'reject-stale','primary-c','reject');check($r['state']==='conflict_review'&&entity($db,'primary','student-a')['data']['primary']===3,'stale rejection cannot overwrite later assignment');
denied(fn()=>$app->assign('barn-a',4,'stale-assign','student-a',0),'stale concurrent writer denied');
$before=[count_rows($db,'entity'),count_rows($db,'outbox'),count_rows($db,'audit'),count_rows($db,'command')];$access->calls=0;$access->revoke_at=6;
try{$app->note('barn-a',4,'revoked-during-command','student-a','Should roll back');}catch(Throwable$e){}$access->revoke_at=null;unset($access->revoked[4]);
$after=[count_rows($db,'entity'),count_rows($db,'outbox'),count_rows($db,'audit'),count_rows($db,'command')];check($before===$after,'authorization change rolls back notes, audit, command and outbox');
function appointment_source(mysqli$db,string$id,string$status,?int$instructor,string$event='new'):void{$json=json_encode(['id'=>$id,'status'=>$status,'instructor'=>$instructor,'event'=>$event]);$s=$db->prepare("INSERT INTO wf_appointment_source VALUES('barn-a',?,?,0)");$s->bind_param('ss',$id,$json);$s->execute();}
$before=count_rows($db,'outbox');denied(fn()=>$app->appointment('barn-a',1,'missing-source'),'no self-attested committed appointment accepted');check(count_rows($db,'outbox')===$before,'uncommitted source produces no notification');
appointment_source($db,'draft','draft',3);$app->appointment('barn-a',1,'draft');check(count_rows($db,'outbox')===$before,'draft creates no notification');
appointment_source($db,'failed','confirmed',3);$source->fail=true;denied(fn()=>$app->appointment('barn-a',1,'failed'),'injected source failure rolls back');$source->fail=false;check((int)$db->query("SELECT observed FROM wf_appointment_source WHERE command_id='failed'")->fetch_row()[0]===0&&count_rows($db,'outbox')===$before,'source mutation and outbox participant share rollback');
foreach(['new','rescheduled','confirmed','canceled']as$event){appointment_source($db,'lesson-'.$event,$event==='canceled'?'canceled':'confirmed',3,$event);$app->appointment('barn-a',1,'lesson-'.$event);}
check((int)$db->query("SELECT COUNT(DISTINCT event) FROM wf_outbox WHERE event LIKE 'appointment.%'")->fetch_row()[0]===4,'new rescheduled confirmed canceled event types distinct');
$before=count_rows($db,'outbox');$app->appointment('barn-a',1,'lesson-new');check(count_rows($db,'outbox')===$before,'appointment replay does not duplicate notifications');
appointment_source($db,'unassigned','requested',null);$app->appointment('barn-a',1,'unassigned');check((int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE command_id='unassigned' AND recipient=1")->fetch_row()[0]===1&&(int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE command_id='unassigned'")->fetch_row()[0]===1,'unassigned lesson routes only to operations review');
// Sink worker checks delivery-time revocation/opt-in and contains no body/contact values.
$sink=new FixtureSink();$access->no_consent[2]=true;$access->revoked[4]=true;
for($i=0;$i<100;$i++)if($app->deliver_one($sink,1000)==='empty')break;
check(count($sink->messages)>0,'fixture sink receives eligible messages');check((int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE recipient IN(2,4) AND state='sent'")->fetch_row()[0]===0,'revoked or nonconsenting recipients not delivered');
check(!str_contains(json_encode($sink->messages),'Synthetic lesson observation')&&!str_contains(json_encode($sink->messages),'PRIVATE'),'notification envelope excludes notes and private content');
$sink->sink=false;denied(fn()=>$app->deliver_one($sink,1000),'non-sink transport always disabled');$sink->sink=true;
appointment_source($db,'retry-case','confirmed',3);$app->appointment('barn-a',1,'retry-case');$sink->mode='reject';do{$state=$app->deliver_one($sink,2000);}while($state==='suppressed');check($state==='retry','known unaccepted delivery is retryable');$sink->mode='accept';
for($i=0;$i<100;$i++)if($app->deliver_one($sink,10000)==='empty')break;
check((int)$db->query("SELECT COUNT(*) FROM wf_attempt WHERE state='retry'")->fetch_row()[0]>=1,'attempt history retained');
appointment_source($db,'lookup-outage','requested',null);$app->appointment('barn-a',1,'lookup-outage');$access->lookup_failure=true;$before=count($sink->messages);
check($app->deliver_one($sink,15000)==='retry'&&count($sink->messages)===$before,'recipient lookup outage sends nothing and remains retryable');$access->lookup_failure=false;$app->deliver_one($sink,16000);
appointment_source($db,'uncertain-case','confirmed',3);$app->appointment('barn-a',1,'uncertain-case');$sink->mode='unknown';do{$state=$app->deliver_one($sink,20000);}while($state==='suppressed');check($state==='uncertain','unknown acceptance is not blindly retried');$unknown=(int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE state='uncertain'")->fetch_row()[0];$sink->mode='accept';for($i=0;$i<100;$i++)if($app->deliver_one($sink,30000)==='empty')break;
check((int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE state='uncertain'")->fetch_row()[0]===$unknown,'uncertain item awaits explicit reconciliation');
check((int)$db->query("SELECT COUNT(*) FROM wf_audit WHERE event='note.committed'")->fetch_row()[0]===1,'failed note has no success audit');
// Database-side failure after mutations must roll back every participant.
seed($db,'barn-a','student','student-failure',['education'=>['display_label'=>'Failure fixture']]);
$before=[count_rows($db,'entity'),count_rows($db,'outbox'),count_rows($db,'audit'),count_rows($db,'command')];
$db->query("CREATE TRIGGER wf_fail BEFORE INSERT ON wf_outbox FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='synthetic outbox failure'");
denied(fn()=>$app->assign('barn-a',3,'outbox-failure','student-failure',0),'outbox insert failure is not acknowledged');$db->query('DROP TRIGGER wf_fail');
check($before===[count_rows($db,'entity'),count_rows($db,'outbox'),count_rows($db,'audit'),count_rows($db,'command')],'outbox failure rolls back assignment change audit and command');
// An expired worker lease has an unknown outcome and must not automatically resend.
$db->query("UPDATE wf_outbox SET state='leased',lease='synthetic',lease_until=1 WHERE id=(SELECT x.id FROM(SELECT id FROM wf_outbox WHERE state='sent' LIMIT 1)x)");
$unknown=(int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE state='uncertain'")->fetch_row()[0];$app->deliver_one($sink,40000);
check((int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE state='uncertain'")->fetch_row()[0]===$unknown+1,'expired lease becomes uncertain without resend');
// Actual two-worker races start behind a held database row lock.
function race(mysqli$db,array$jobs,string$lockSql):array{
 $token=bin2hex(random_bytes(5));$db->begin_transaction();$db->query($lockSql);$children=[];
 foreach($jobs as$i=>$job){$ready='/tmp/wf-'.$token.'-'.$i;$pipes=[];$proc=proc_open(array_merge([PHP_BINARY,'/repo/tests/workflows/race.php',$ready],$job),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$children[]=[$proc,$pipes,$ready];}
 $end=microtime(true)+10;foreach($children as$c){while(!is_file($c[2])){if(microtime(true)>$end)throw new RuntimeException('race startup');usleep(10000);}}
 usleep(150000);$db->commit();$results=[];
 foreach($children as[$proc,$pipes,$ready]){$out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($proc);unlink($ready);if($code!==0)throw new RuntimeException('race process '.$error.$out);$results[]=json_decode($out,true,512,JSON_THROW_ON_ERROR);}
 return$results;
}
seed($db,'barn-a','student','student-race',['education'=>['display_label'=>'Race fixture']]);
$rows=race($db,[['assign','3','race-one','0'],['assign','4','race-two','0']],"SELECT revision FROM wf_facility WHERE id='barn-a' FOR UPDATE");
check(count(array_filter($rows,fn($r)=>$r['ok']))===1&&entity($db,'primary','student-race')['revision']===1,'two simultaneous self assignments yield exactly one primary/revision');
$before=(int)$db->query("SELECT COUNT(*) FROM wf_audit WHERE subject='student-race'")->fetch_row()[0];
$rows=race($db,[['assign','3','race-replay','1'],['assign','3','race-replay','1']],"SELECT revision FROM wf_facility WHERE id='barn-a' FOR UPDATE");
check($rows[0]['ok']&&$rows[1]['ok']&&$rows[0]['result']===$rows[1]['result']&&(int)$db->query("SELECT COUNT(*) FROM wf_audit WHERE subject='student-race'")->fetch_row()[0]===$before+1,'simultaneous exact replay commits one audit and one change');
$rows=race($db,[['review','1','race-review','race-replay'],['assign','4','race-after-review','2']],"SELECT revision FROM wf_facility WHERE id='barn-a' FOR UPDATE");
$current=entity($db,'primary','student-race');
check($rows[0]['ok']&&($rows[1]['ok']?($current['data']['primary']===4&&$rows[0]['result']['state']==='conflict_review'):($rows[0]['result']['state']==='rejected'&&$current['revision']===3)),'simultaneous rejection never overwrites a successful newer assignment');
for($i=0;$i<100;$i++)if($app->deliver_one($sink,90000)==='empty')break;
check((int)$db->query("SELECT COUNT(*) FROM wf_outbox WHERE state IN('pending','retry')")->fetch_row()[0]===0,'worker-race queue starts without unrelated events');
$db->query("CREATE TABLE wf_sink(id CHAR(64) PRIMARY KEY,requests INT NOT NULL) ENGINE=InnoDB");
appointment_source($db,'delivery-race','requested',null);$app->appointment('barn-a',1,'delivery-race');
$rows=race($db,[['deliver','1','unused','0'],['deliver','1','unused','0']],"SELECT id FROM wf_outbox WHERE command_id='delivery-race' FOR UPDATE");
check(count(array_filter($rows,fn($r)=>$r['result']==='sent'))===1&&(int)$db->query('SELECT SUM(requests) FROM wf_sink')->fetch_row()[0]===1,'two simultaneous workers claim and deliver one event once');
$revision=(int)$db->query("SELECT revision FROM wf_facility WHERE id='barn-a'")->fetch_row()[0];$db->begin_transaction();$db->query("UPDATE wf_facility SET revision=revision+10 WHERE id='barn-a'");
denied(fn()=>$app->assign('barn-a',3,'nested-forbidden','student-race',3),'externally active transaction is rejected before implicit commit');
check((int)$db->query("SELECT revision FROM wf_facility WHERE id='barn-a'")->fetch_row()[0]===$revision+10,'rejection does not roll back caller-owned transaction');$db->rollback();
check((int)$db->query("SELECT revision FROM wf_facility WHERE id='barn-a'")->fetch_row()[0]===$revision,'caller retains rollback authority and no implicit commit occurred');
echo"WORKFLOWS_CHECKS=$n\n";

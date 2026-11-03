<?php
// Synthetic fixtures only; never included by plugin or copied to a farm.
require_once '/repo/wp-content/plugins/csf-ranch-workflows/includes/class-csf-workflows.php';
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
function database():mysqli{return new mysqli('fixture-db','root','fixture-only','ranch_fixture');}
final class FixtureAccess implements CSF_Workflows_Access {
 public array $no_proof=[];public array $denies=[];public bool $lookup_failure=false;public array $revoked=[];public array $no_consent=[];public int $calls=0;public ?int $revoke_at=null;
 public function actor(string $f,int $id):array{
  ++$this->calls;if($this->revoke_at===$this->calls)$this->revoked[$id]=true;
  $permissions=match($id){1,2=>['horse_links','horse_view_all','education_view','primary_review','appointment_notify'],3,4=>['education_view','education_write','primary_self_assign'],5,6=>[],default=>[]};
  return ['id'=>$id,'facility'=>$f,'active'=>$f==='barn-a'&&$id>=1&&$id<=6&&!isset($this->revoked[$id]),'verified'=>true,'fresh_proof'=>!isset($this->no_proof[$id]),'permissions'=>array_values(array_diff($permissions,$this->denies[$id]??[]))];
 }
 public function managers(string $f,bool $operations_only=false):array{return$operations_only?[1]:[1,2];}
 public function recipient(string $f,int $id,string $event,string $subject):array{
  if($this->lookup_failure)throw new RuntimeException('synthetic lookup outage');
  $allowed=match(true){$event==='horse.access'=>in_array($id,[5,6],true),str_starts_with($event,'note.')||$event==='primary.restore'=>in_array($id,[3,4],true),$event==='primary.changed'=>in_array($id,[1,2],true),str_starts_with($event,'primary.')=>in_array($id,[3,4],true),str_starts_with($event,'appointment.')=>in_array($id,[1,2,3,4],true),default=>false};
  return ['active'=>!isset($this->revoked[$id]),'verified'=>true,'allowed'=>$allowed,'contact_verified'=>true,'opt_in'=>!isset($this->no_consent[$id])];
 }
}
final class FixtureSink implements CSF_Workflows_Sink {
 public array $messages=[];public string $mode='accept';public bool $sink=true;
 public function is_sink():bool{return$this->sink;}
 public function deliver(string $key,int $recipient,array $message):bool{
  if($this->mode==='unknown')throw new RuntimeException('private provider error');
  if($this->mode==='reject')return false;
  if(isset($GLOBALS['sink_db'])){$db=$GLOBALS['sink_db'];$s=$db->prepare('INSERT INTO wf_sink VALUES(?,1) ON DUPLICATE KEY UPDATE requests=requests+1');$s->bind_param('s',$key);$s->execute();}
  $this->messages[$key]=[$recipient,$message];return true;
 }
}
final class FixtureAppointments implements CSF_Workflows_Appointments {
 public bool $fail=false;
 public function change(mysqli $db,string $f,string $command):?array{
  $stmt=$db->prepare('SELECT data FROM wf_appointment_source WHERE facility=? AND command_id=?');$stmt->bind_param('ss',$f,$command);$stmt->execute();$row=$stmt->get_result()->fetch_assoc();
  if(!$row)return null;$data=json_decode($row['data'],true,512,JSON_THROW_ON_ERROR);
  // This synthetic source change and its notification participant share one transaction.
  $stmt=$db->prepare('UPDATE wf_appointment_source SET observed=observed+1 WHERE facility=? AND command_id=?');$stmt->bind_param('ss',$f,$command);$stmt->execute();
  if($this->fail)throw new RuntimeException('injected failure');return$data;
 }
}
function seed(mysqli $db,string $f,string $kind,string $id,array $data,int $revision=1):void{
 $json=json_encode($data,JSON_THROW_ON_ERROR);$s=$db->prepare('INSERT INTO wf_entity(facility,kind,id,revision,data) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE revision=VALUES(revision),data=VALUES(data)');$s->bind_param('sssis',$f,$kind,$id,$revision,$json);$s->execute();
}
function entity(mysqli $db,string $kind,string $id,string $f='barn-a'):array{
 $s=$db->prepare('SELECT revision,data FROM wf_entity WHERE facility=? AND kind=? AND id=?');$s->bind_param('sss',$f,$kind,$id);$s->execute();$r=$s->get_result()->fetch_assoc();return['revision'=>(int)$r['revision'],'data'=>json_decode($r['data'],true)];
}

<?php
// Synthetic fixture only. Exercises the real policy lock, history, ledger and rollback.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local')throw new RuntimeException('Fixture only');
ob_start();require '/repo/tests/scheduling/auto-slot-fixture.php';$ids=json_decode(ob_get_clean(),true);
$checks=0;$check=static function($value,$message)use(&$checks){if(!$value)throw new RuntimeException($message);++$checks;};
$policy=static fn()=>json_decode(CSF_Scheduling_Schema::settings()['policy_json'],true);
$configure=static fn($p,$revision=null,$command=null)=>CSF_Scheduling_Store::configure($p,$revision??(int)CSF_Scheduling_Schema::settings()['revision'],$command??wp_generate_uuid4());
$p=$policy();$check(!is_wp_error(CSF_Scheduling_Policy::validate($p)),'Legacy six-field services valid');
ob_start();CSF_Scheduling_Settings::render();$html=ob_get_clean();foreach(array_keys($p['services'])as$i=>$id){$name=preg_quote('settings[services]['.$i.'][sort_order]','/');$found=preg_match('/name="'.$name.'" type="number" value="([0-9]+)"/',$html,$match);$check($found&&$match[1]===(string)($i*10),'Legacy order retained in native form '.$id);}

foreach(CSF_Scheduling_Catalog::examples()as$e){$id=$e['classification']==='student_lesson'?'catalog_student_'.$e['duration_minutes']:'lesson_'.str_replace('-','',wp_generate_uuid4());$p['services'][$id]=array('enabled'=>false,'capacity'=>null,'duration_minutes'=>$e['duration_minutes'],'horse_source'=>null,'requires_travel'=>false,'required_resources'=>null,'label'=>$e['label'],'category'=>$e['category'],'classification'=>$e['classification'],'sort_order'=>0);}
$check(count(CSF_Scheduling_Catalog::examples())===7,'Seven owner examples');
$check(!is_wp_error(CSF_Scheduling_Policy::validate($p)),'Disabled unknown requirements accepted');
$check(count(CSF_Scheduling_Catalog::choices($p['services']))===5,'Drafts not available for bookings');
$examples=array_slice($p['services'],5,null,true);$unknown=0;foreach($examples as$s){if($s['duration_minutes']===null)++$unknown;}$check($unknown===5,'Only 30/60 duration assumed');
$id=array_key_first($examples);$x=$p;$x['services'][$id]['enabled']=true;$check(is_wp_error(CSF_Scheduling_Policy::validate($x)),'Incomplete enabled service rejected');
foreach(array('label'=>array(),'category'=>array(),'classification'=>array(),'sort_order'=>null,'unexpected'=>1)as$field=>$value){$x=$p;$x['services'][$id][$field]=$value;$check(is_wp_error(CSF_Scheduling_Policy::validate($x)),'Malformed catalog field '.$field);}
$check(!is_wp_error($configure($p)),'Save disabled catalog in transaction');
$id='pilot_ground_60';$body=array('uuid'=>wp_generate_uuid4(),'expected'=>0,'title'=>'Synthetic catalog history','service_type'=>$id,'state'=>'confirmed','start'=>'2027-01-27T10:00-08:00','end'=>'2027-01-27T11:00-08:00','hold_expires'=>null,'resources'=>array($ids['instructor'],$ids['arena']),'riders'=>array(),'rides'=>array());
$check(!is_wp_error(CSF_Scheduling_Store::calendar('appointment_saved',$body,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4())),'Create legacy-ID appointment');
$booked=CSF_Scheduling_Catalog::booked($body['uuid'],$id,$p);$check($booked['label']==='Pilot Ground 60','Booked legacy label captured');
$p=$policy();$p['services'][$id]['label']='Student Lesson — 60 Minutes';$p['services'][$id]['category']='Student';$p['services'][$id]['sort_order']=1;$p['services'][$id]['classification']='unspecified';
$revision=(int)CSF_Scheduling_Schema::settings()['revision'];$command=wp_generate_uuid4();$result=$configure($p,$revision,$command);$check(!is_wp_error($result),'Friendly rename succeeds without changing ID');
$check($configure($p,$revision,$command)===$result,'Exact replay idempotent');
$check(CSF_Scheduling_Catalog::booked($body['uuid'],$id,$p)===$booked,'Old booked snapshot retained');
$check(CSF_Scheduling_Catalog::choices($p['services'])[$id]==='Student Lesson — 60 Minutes','New choices use friendly label');
$old=$policy();foreach(array('duration_minutes'=>30,'enabled'=>false,'capacity'=>2,'horse_source'=>'school','required_resources'=>array('arena'),'requires_travel'=>true,'classification'=>'working_student_paid')as$field=>$value){$x=$old;$x['services'][$id][$field]=$value;$impact=CSF_Scheduling_Catalog::impact($old,$x);$check($impact['blocked']&&$impact['changes'][0]['future']===1,'Readable future impact '.$field);$r=$configure($x);$check(is_wp_error($r)&&($r->get_error_data()['reason']??'')==='service_in_use','Locked rule change rejected '.$field);$check($policy()===$old,'Rejected rule unchanged '.$field);}
$body['expected']=1;$body['title']='Edited synthetic catalog history';$check(!is_wp_error(CSF_Scheduling_Store::calendar('appointment_saved',$body,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4())),'Edit appointment normally');
$check(CSF_Scheduling_Catalog::booked($body['uuid'],$id,$p)===$booked,'Ordinary edit preserves booked classification/label');
// Fail appended catalog-history insert: policy, history and ledger all roll back.
global$wpdb;$snap=static function()use($wpdb){$out=array();foreach(array('settings','history','commands','audit')as$n){$t=CSF_Scheduling_Schema::table($n);$out[$n]=$wpdb->get_results("SELECT * FROM `$t` ORDER BY id",ARRAY_A);}return wp_json_encode($out);};
$before=$snap();$x=$old;$x['services'][$id]['label']='Must not commit';$inject=static fn($q)=>str_starts_with($q,'INSERT INTO `'.CSF_Scheduling_Schema::table('history').'`')?'SELECT * FROM catalog_missing_table':$q;
$wpdb->suppress_errors(true);add_filter('query',$inject);$r=$configure($x);remove_filter('query',$inject);$check(is_wp_error($r)&&$snap()===$before,'History failure rolls back entire policy command');
wp_set_current_user(0);$check(is_wp_error($configure($old)),'Anonymous policy write denied');$check(CSF_Scheduling_Catalog::booked($body['uuid'],$id,$p)===null,'Anonymous snapshot hidden');wp_set_current_user(1);
// Save new catalog types with explicit no-horse requirements for browser duration tests.
foreach(array(30,60)as$minutes){$p=$policy();$new='catalog_student_'.$minutes;$p['services'][$new]=array('enabled'=>true,'capacity'=>1,'duration_minutes'=>$minutes,'horse_source'=>'none','requires_travel'=>false,'required_resources'=>array('instructor','arena'),'label'=>'Student Lesson — '.$minutes.' Minutes','category'=>'Student','sort_order'=>$minutes,'classification'=>'student_lesson');$check(!is_wp_error($configure($p)),'Configured portable student '.$minutes);}
// Historical disable, legacy-label fallback and disabled-type rejection.
$p=$policy();$past=$body;$past['uuid']=wp_generate_uuid4();$past['expected']=0;$past['service_type']='pilot_ground_120';$past['state']='requested';$past['start']='2027-01-28T10:00-08:00';$past['end']='2027-01-28T12:00-08:00';
$check(!is_wp_error(CSF_Scheduling_Store::calendar('appointment_saved',$past,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4())),'Legacy-history fixture created');
$ht=CSF_Scheduling_Schema::table('history');$wpdb->query($wpdb->prepare("UPDATE `$ht` SET body_json=JSON_REMOVE(body_json,'$.service_snapshot') WHERE entity_uuid=%s",$past['uuid']));
$p['services']['pilot_ground_120']['label']='Renamed legacy two hour';$check(!is_wp_error($configure($p)),'Legacy label renamed');
$check(CSF_Scheduling_Catalog::booked($past['uuid'],'pilot_ground_120',$p)['label']==='Pilot Ground 120','Pre-upgrade history resolves original label');
$cancel=array('uuid'=>$past['uuid'],'expected'=>1,'initiator'=>'staff','reason'=>'Synthetic historical disable','notice'=>'2026-01-01T09:00-08:00','effective'=>'2026-01-01T09:00-08:00');
$check(!is_wp_error(CSF_Scheduling_Store::calendar('appointment_canceled',$cancel,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4())),'Cancellation preserves legacy snapshot');
$p=$policy();$p['services']['pilot_ground_120']['enabled']=false;$check(!is_wp_error($configure($p)),'No active future bookings permits disable');
$check(!isset(CSF_Scheduling_Catalog::choices($p['services'])['pilot_ground_120']),'Disabled type excluded from new bookings');
$check(CSF_Scheduling_Catalog::booked($past['uuid'],'pilot_ground_120',$p)['label']==='Pilot Ground 120','Disabled historical label preserved');
$past['uuid']=wp_generate_uuid4();$check(is_wp_error(CSF_Scheduling_Store::calendar('appointment_saved',$past,(int)CSF_Scheduling_Schema::settings()['revision'],wp_generate_uuid4())),'Disabled type cannot be newly booked');
$check(CSF_Scheduling_Schema::inspect()['ready']&&count(CSF_Scheduling_Schema::definitions())===15,'Same physical 15-table schema');
file_put_contents('/test/catalog-domain-count',strval($checks));echo wp_json_encode($ids);

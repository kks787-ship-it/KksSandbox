<?php
// CLI-only synthetic presentation harness. No WordPress load, DB, secrets or network.
if(PHP_SAPI!=='cli'||getenv('CSF_DAYPILOT_FIXTURE')!=='synthetic-only')exit(2);
define('ABSPATH','/fixture/');
$input=json_decode(file_get_contents(__DIR__.'/.artifacts/input.json'),true,512,JSON_THROW_ON_ERROR);
if($input['fixture']!=='invented-daypilot-comparison')exit(2);
function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function esc_attr($s){return esc_html($s);}
function esc_url($s){return esc_html($s);}
function wp_json_encode($x){return json_encode($x);}
function is_wp_error($x){return false;}
function admin_url($s){return '/fixture-navigation';}
function add_query_arg($a,$s){return $s.'?'.http_build_query($a);}
function selected($a,$b,$echo=false){return $a===$b?'selected':'';}
function wp_date($f,$t=null,$z=null){return(new DateTimeImmutable('@'.($t??1794240000)))->setTimezone($z??new DateTimeZone('America/Los_Angeles'))->format($f);}
class CSF_Scheduling_Access{public static function can($cap=null){return !empty($GLOBALS['fixture_model']['appointments'])||!empty($GLOBALS['fixture_model']['resources']);}}
class CSF_Scheduling_Calendar{public static function documents($table){return $GLOBALS['fixture_docs'][$table]??array();}}
require __DIR__.'/../../wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-time.php';
require __DIR__.'/../../wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-planner.php';
$out=array('native'=>array(),'ticks'=>array());
foreach($input['dates']as$date){$out['ticks'][$date]=CSF_Scheduling_Planner::ticks($date,$input['zone']);}
foreach($input['scopes']as$scope=>$model){
 $GLOBALS['fixture_model']=$model;$ctx=array('resource_rows'=>array(),'horse_rows'=>array(),'resources'=>array(),'horses'=>array());$details=array();$rows=array();
 $GLOBALS['fixture_docs']=array('availability'=>array(),'restrictions'=>array());
 foreach($model['resources']as$r){$ctx['resources'][$r['id']]=$r['label'];$ctx['resource_rows'][]=array('uuid'=>$r['id'],'kind'=>$r['kind'],'label'=>$r['label'],'active'=>1);$GLOBALS['fixture_docs']['availability'][]=array('entity_uuid'=>$r['id'],'data'=>array('intervals'=>array(array('start'=>'2026-01-01T00:00-08:00','end'=>'2028-01-01T00:00-08:00'))));}
 foreach($model['closures']as$c){$GLOBALS['fixture_docs']['restrictions'][]=array('entity_uuid'=>$c['resource'],'data'=>array('start'=>$c['start'],'end'=>$c['end'],'reason'=>'Fictional arena closure'));}
 foreach($model['appointments']as$a){$rows[]=$a;$details[$a['uuid']]=array('resources'=>$a['resources'],'rides'=>array(),'service_type'=>'synthetic');}
 $policy=array('timezone'=>$input['zone'],'service_hours'=>array_fill(0,7,array(array('08:00','18:00'))),'services'=>array('synthetic'=>array('requires_travel'=>false)),'travel_buffer_minutes'=>0);
 foreach($input['dates']as$date){foreach(array_merge(array('day','week','resources'),array_map(static fn($r)=>'filter-'.$r['id'],array_values(array_filter($model['resources'],static fn($r)=>$r['kind']==='arena'))))as$view){if($view==='week'&&$date!==$input['week'])continue;
  $policy['service_hours']=array_fill(0,7,array(array(in_array($date,array('2026-11-01','2027-03-14'),true)?'00:00':'08:00',in_array($date,array('2026-11-01','2027-03-14'),true)?'04:00':'18:00')));
  ob_start();CSF_Scheduling_Planner::render($date,$view==='week'?'week':'day',str_starts_with($view,'filter-')?substr($view,7):'',$rows,$details,$ctx,$policy);$out['native'][$scope][$date][$view]=ob_get_clean();
 }}
}
echo json_encode($out,JSON_THROW_ON_ERROR);

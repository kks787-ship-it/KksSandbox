<?php
/** Isolated presentation fixture. Never load into WordPress or a farm. */
define( 'ABSPATH', '/synthetic-directory-fixture/' );
function esc_html($v){return htmlspecialchars((string)$v,ENT_QUOTES);}
function esc_attr($v){return esc_html($v);}
function wp_json_encode($v){return json_encode($v);}
function checked($a,$b,$echo=false){return $a==$b?' checked':'';}
function selected($a,$b,$echo=false){return $a==$b?' selected':'';}
function wp_generate_uuid4(){static $i=100;return sprintf('00000000-0000-4000-8000-%012d',++$i);}
function wp_nonce_field($v){echo '<input type="hidden" name="_wpnonce" value="fixture-nonce">';}
class CSF_Scheduling_Access {public static function can($cap=''){return !getenv('DIRECTORY_READ_ONLY');}}
class CSF_Scheduling_Schema {public static function settings(){return ['revision'=>8,'policy_json'=>'{"timezone":"America/Los_Angeles"}'];}}
class CSF_Scheduling_Calendar {public static function find($t,$id){return null;}public static function documents($t){return [];}public static function horse_mapping_bookings($id){return [];}}
require getenv('DIRECTORY_UI_SOURCE') ?: __DIR__.'/../../wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-ui.php';
$ctx=['resource_rows'=>[], 'rider_rows'=>[], 'horse_rows'=>[], 'all_services'=>['private'=>'Student lesson — 60 minutes'],'services'=>['private'=>'Student lesson — 60 minutes'],'school_resources'=>[], 'riders'=>[], 'targets'=>[]];
foreach(['Main arena','West paddock','Instructor Willow']as$i=>$name){$id=wp_generate_uuid4();$ctx['resource_rows'][]=['uuid'=>$id,'label'=>$name,'kind'=>$i===2?'instructor':'arena','active'=>true,'revision'=>2];$ctx['targets'][$id]=$name;}
foreach(['Alex Example','Jamie Example']as$name){$id=wp_generate_uuid4();$ctx['rider_rows'][]=['uuid'=>$id,'revision'=>3,'data'=>['label'=>$name,'adult'=>true,'release_verified'=>false,'guardian_verified'=>false,'emergency_verified'=>false]];$ctx['riders'][$id]=$name;}
foreach(['Clover','Juniper','Willow','Maple <script>alert(1)</script>']as$i=>$name){$id=wp_generate_uuid4();$ctx['horse_rows'][]=['uuid'=>$id,'revision'=>5,'data'=>['label'=>$name,'source'=>'client','resource_uuid'=>null,'suitable_services'=>['private'],'suitable_riders'=>[],'training_enrolled'=>false,'archived'=>$i===2]];$ctx['targets'][$id]=$name;}
echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Digital Ranch synthetic directory preview</title><style>body{font:15px/1.5 system-ui;background:#eef2f0;margin:0;padding:24px}.csf-calendar{margin:auto}button,input,select{font:inherit}button{cursor:pointer}h1{font-size:24px}</style>';
foreach(['staff','directory']as$file)echo '<style>'.file_get_contents(__DIR__.'/../../wp-content/plugins/csf-scheduling/assets/'.$file.'.css').'</style>';
echo '</head><body><main class="csf-calendar"><h1>Digital Ranch · Farm records</h1><p>Private development preview · fictional records · no saves reach a server</p>';
CSF_Scheduling_UI::setup($ctx);
echo '</main><script>window.CSFStaff={url:"/no-writes",nonce:"fixture",services:{},workload:{},timezone:"America/Los_Angeles"};</script>';
foreach(['staff','directory']as$file)echo '<script>'.file_get_contents(__DIR__.'/../../wp-content/plugins/csf-scheduling/assets/'.$file.'.js').'</script>';
echo '<script>document.addEventListener("submit",e=>{e.preventDefault();window.lastSubmission={kind:e.target.dataset.kind,body:JSON.parse(e.target.elements.body.value)};e.target.querySelector(".csf-feedback").textContent="Synthetic preview only: nothing saved.";e.target.querySelector(".csf-save").disabled=false;});</script></body></html>';

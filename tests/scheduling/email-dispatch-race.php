<?php
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture required');}
wp_set_current_user(1);require_once '/repo/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-email-dispatch.php';
$id=getenv('CSF_DISPATCH_WORKER');if(!in_array($id,['1','2'],true))throw new RuntimeException('worker');
$b=json_decode(file_get_contents('/test/email-dispatch-race.json'),true);touch('/test/dispatch-ready-'.$id);
$deadline=microtime(true)+20;while(!file_exists('/test/dispatch-go')){if(microtime(true)>$deadline)throw new RuntimeException('barrier timeout');usleep(10000);}
$sink=new CSF_Scheduling_Email_Sink();$r=CSF_Scheduling_Email_Dispatch::run($b,$sink);
file_put_contents('/test/dispatch-outcome-'.$id,wp_json_encode(['error'=>is_wp_error($r),'messages'=>count($sink->messages),'result'=>$r]));

<?php
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){WP_CLI::error('Disposable fixture required.');}
$case=getenv('CSF_PERMISSION_RACE');$worker=(int)getenv('CSF_PERMISSION_WORKER');$f=json_decode(file_get_contents('/test/permission-race-'.$case.'.json'),true);
wp_set_current_user($case==='revocation'?$f['trainer']:($worker===1?1:$f['manager']));
if($case==='revocation'){
 $waiting=static function($q){if(str_contains($q,'WHERE id=1 FOR UPDATE')){file_put_contents('/test/permission-preauthorized','ready');$end=microtime(true)+30;while(!file_exists('/test/permission-revoked')){if(microtime(true)>$end){throw new RuntimeException('Barrier timeout');}usleep(10000);}}return$q;};add_filter('query',$waiting);
 $r=CSF_Scheduling_Store::calendar('appointment_saved',$f['body'],$f['revision'],wp_generate_uuid4());remove_filter('query',$waiting);
}else{
 file_put_contents('/test/permission-ready-'.$worker,'ready');$end=microtime(true)+30;while(!file_exists('/test/permission-go')){if(microtime(true)>$end){WP_CLI::error('Barrier timeout');}usleep(10000);}
 $r=CSF_Scheduling_Store::permissions('team_group_saved',$f['bodies'][$worker-1],$f['revision'],wp_generate_uuid4());
}
$reason=is_wp_error($r)?($r->get_error_data()['reason']??$r->get_error_code()):'committed';file_put_contents('/test/permission-outcome-'.$case.'-'.$worker,json_encode(['reason'=>$reason]));echo$reason."\n";

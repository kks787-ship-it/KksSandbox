<?php
/** Separate PHP workers race real InnoDB booking commands, then retry stale revisions. */
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){WP_CLI::error('Disposable fixture required.');}
wp_set_current_user(1);$f=json_decode(file_get_contents('/test/calendar-fixture.json'),true);$phase=getenv('CSF_RACE_PHASE');$worker=getenv('CSF_RACE_WORKER');
$spec=json_decode(file_get_contents('/test/race-'.$phase.'.json'),true);$b=$spec['bodies'][(int)$worker-1]??$spec['body'];$b['uuid']=wp_generate_uuid4();$b['title']='Race '.$phase.' worker '.$worker;$revision=$spec['revision'];$cmd=wp_generate_uuid4();
file_put_contents('/test/ready-'.$phase.'-'.$worker,'ready');$deadline=microtime(true)+20;while(!file_exists('/test/go-'.$phase)){if(microtime(true)>$deadline){WP_CLI::error('Barrier timeout');}usleep(10000);}
$result=CSF_Scheduling_Store::calendar('appointment_saved',$b,$revision,$cmd);
if(is_wp_error($result)&&($result->get_error_data()['reason']??'')==='stale_revision'){$revision=(int)CSF_Scheduling_Schema::settings()['revision'];$cmd=wp_generate_uuid4();$result=CSF_Scheduling_Store::calendar('appointment_saved',$b,$revision,$cmd);}
$reason=is_wp_error($result)?($result->get_error_data()['reason']??$result->get_error_code()):'committed';file_put_contents('/test/outcome-'.$phase.'-'.$worker,json_encode(array('reason'=>$reason,'uuid'=>$b['uuid'])));echo $reason."\n";

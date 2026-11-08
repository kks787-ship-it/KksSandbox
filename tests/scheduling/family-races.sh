#!/usr/bin/env bash
set -euo pipefail
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
"${WP[@]}" eval '
if(ABSPATH!=="/test/site/"||DB_NAME!=="csf_fixture"||wp_get_environment_type()!=="local"){WP_CLI::error("Fixture required");}
wp_set_current_user(1);$f=json_decode(file_get_contents("/test/family-race.json"),true);
$r=CSF_Scheduling_Store::configure($f["policy"],(int)CSF_Scheduling_Schema::settings()["revision"],wp_generate_uuid4());if(is_wp_error($r)){WP_CLI::error(wp_json_encode($r));}
$f["revision"]=(int)CSF_Scheduling_Schema::settings()["revision"];file_put_contents("/test/family-race.json",wp_json_encode($f));'
CSF_FAMILY_WORKER=1 "${WP[@]}" eval-file /repo/tests/scheduling/family-race.php > /test/family-worker-1.log 2>&1 &
FIRST=$!
CSF_FAMILY_WORKER=2 "${WP[@]}" eval-file /repo/tests/scheduling/family-race.php > /test/family-worker-2.log 2>&1 &
SECOND=$!
for attempt in $(seq 1 100); do
 if [[ -f /test/family-ready-1 && -f /test/family-ready-2 ]]; then break; fi
 sleep .1
done
touch /test/family-go
wait "$FIRST"
wait "$SECOND"
"${WP[@]}" eval '
wp_set_current_user(1);$f=json_decode(file_get_contents("/test/family-race.json"),true);
$out=[];foreach([1,2]as$n){$out[]=json_decode(file_get_contents("/test/family-outcome-".$n),true)["reason"];}
$sorted=$out;sort($sorted);if($sorted!==["committed","resource_conflict"]){WP_CLI::error(wp_json_encode($out));}
foreach($f["bodies"]as$i=>$b){$present=0;foreach($b["items"]as$item){$present+=(bool)CSF_Scheduling_Calendar::find("appointments",$item["body"]["uuid"]);}if($present!==($out[$i]==="committed"?2:0)){WP_CLI::error("Partial or duplicate family commit");}}
$r=CSF_Scheduling_Store::configure($f["original_policy"],(int)CSF_Scheduling_Schema::settings()["revision"],wp_generate_uuid4());if(is_wp_error($r)){WP_CLI::error(wp_json_encode($r));}
echo "TWO_WORKER_FAMILY_PASS_ONE_COMPLETE_VISIT_ONE_REJECTION\n";'

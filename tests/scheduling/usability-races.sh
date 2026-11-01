#!/usr/bin/env bash
set -euo pipefail
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
for CASE in horse instructor rider incomplete; do
 CSF_RACE_PHASE="$CASE" "${WP[@]}" eval 'if(DB_NAME!=="csf_fixture"||ABSPATH!=="/test/site/"){WP_CLI::error("Fixture required");}wp_set_current_user(1);$f=json_decode(file_get_contents("/test/usability-fixture.json"),true);$case=getenv("CSF_RACE_PHASE");$day=["horse"=>27,"instructor"=>28,"rider"=>29,"incomplete"=>30][$case];$a="2027-01-$day"."T09:00-08:00";$m="2027-01-$day"."T09:30-08:00";$z="2027-01-$day"."T10:00-08:00";$one=["uuid"=>wp_generate_uuid4(),"expected"=>0,"title"=>"Race","service_type"=>"group","state"=>"confirmed","start"=>$a,"end"=>$z,"hold_expires"=>null,"resources"=>[$f["instructor"],$f["arena"]],"riders"=>array_slice($f["riders"],0,2),"rides"=>[["horse"=>$f["horses"][0],"rider"=>$f["riders"][0],"start"=>$a,"end"=>$m],["horse"=>$f["horses"][0],"rider"=>$f["riders"][1],"start"=>$m,"end"=>$z]]];$two=$one;$two["resources"]=[$case==="instructor"?$f["instructor"]:$f["instructor2"],$f["arena2"]];$two["riders"]=$case==="rider"?$one["riders"]:array_slice($f["riders"],4,2);foreach($two["rides"]as$index=>&$ride){$ride["horse"]=$f["horses"][$case==="horse"?0:1];$ride["rider"]=$two["riders"][$index];}unset($ride);if($case==="incomplete"){array_pop($two["rides"]);}file_put_contents("/test/race-$case.json",wp_json_encode(["revision"=>(int)CSF_Scheduling_Schema::settings()["revision"],"bodies"=>[$one,$two]]));'
 CSF_RACE_PHASE="$CASE" CSF_RACE_WORKER=1 "${WP[@]}" eval-file /repo/tests/scheduling/booking-race.php > "/test/race-$CASE-1.log" 2>&1 &
 W1=$!
 CSF_RACE_PHASE="$CASE" CSF_RACE_WORKER=2 "${WP[@]}" eval-file /repo/tests/scheduling/booking-race.php > "/test/race-$CASE-2.log" 2>&1 &
 W2=$!
 for attempt in $(seq 1 100); do
  [[ -f "/test/ready-$CASE-1" && -f "/test/ready-$CASE-2" ]] && break
  sleep .1
 done
 touch "/test/go-$CASE"
 wait "$W1"
 wait "$W2"
 CSF_RACE_PHASE="$CASE" "${WP[@]}" eval '$case=getenv("CSF_RACE_PHASE");$a=json_decode(file_get_contents("/test/outcome-$case-1"),true);$b=json_decode(file_get_contents("/test/outcome-$case-2"),true);$got=[$a["reason"],$b["reason"]];sort($got);$reason=["horse"=>"resource_conflict","instructor"=>"resource_conflict","rider"=>"rider_conflict","incomplete"=>"horse_unsafe"][$case];if($got!==["committed",$reason]){WP_CLI::error(json_encode($got));}global$wpdb;$t=CSF_Scheduling_Schema::table("appointments");if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `$t` WHERE title LIKE %s AND state=%s","Race ".$case." worker %","confirmed"))!==1){WP_CLI::error("Expected exactly one booking");}echo "TWO_WORKER_GROUP_".strtoupper($case)."_PASS_ONE_COMMIT_ONE_REJECTION\n";'
done

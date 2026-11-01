#!/usr/bin/env bash
set -euo pipefail
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
# First race has an empty appointment calendar. Second keeps existing appointments.
"${WP[@]}" eval 'if(ABSPATH!=="/test/site/"||DB_NAME!=="csf_fixture"){WP_CLI::error("Fixture required");}global $wpdb;foreach(["appointments","allocations","slots","participants","details","history","restrictions"]as$n){$t=CSF_Scheduling_Schema::table($n);$wpdb->query("DELETE FROM `$t`");}'
for PHASE in empty populated; do
 CSF_RACE_PHASE="$PHASE" "${WP[@]}" eval 'wp_set_current_user(1);$f=json_decode(file_get_contents("/test/calendar-fixture.json"),true);$phase=getenv("CSF_RACE_PHASE");$day=$phase==="empty"?18:19;$a="2027-01-$day"."T09:00-08:00";$z="2027-01-$day"."T10:00-08:00";$b=["uuid"=>wp_generate_uuid4(),"expected"=>0,"title"=>"Race","service_type"=>"lesson","state"=>"confirmed","start"=>$a,"end"=>$z,"hold_expires"=>null,"resources"=>[$f["instructor"],$f["arena"]],"riders"=>[$f["rider"]],"rides"=>[["horse"=>$f["horse"],"rider"=>$f["rider"],"start"=>$a,"end"=>$z]]];file_put_contents("/test/race-$phase.json",wp_json_encode(["revision"=>(int)CSF_Scheduling_Schema::settings()["revision"],"body"=>$b]));'
 CSF_RACE_PHASE="$PHASE" CSF_RACE_WORKER=1 "${WP[@]}" eval-file /repo/tests/scheduling/booking-race.php > "/test/race-$PHASE-1.log" 2>&1 &
 WORKER1=$!
 CSF_RACE_PHASE="$PHASE" CSF_RACE_WORKER=2 "${WP[@]}" eval-file /repo/tests/scheduling/booking-race.php > "/test/race-$PHASE-2.log" 2>&1 &
 WORKER2=$!
 for attempt in $(seq 1 100); do
  if [[ -f "/test/ready-$PHASE-1" && -f "/test/ready-$PHASE-2" ]]; then break; fi
  sleep .1
 done
 touch "/test/go-$PHASE"
 wait "$WORKER1"
 wait "$WORKER2"
 CSF_RACE_PHASE="$PHASE" "${WP[@]}" eval '$phase=getenv("CSF_RACE_PHASE");$a=json_decode(file_get_contents("/test/outcome-$phase-1"),true);$b=json_decode(file_get_contents("/test/outcome-$phase-2"),true);$got=[$a["reason"],$b["reason"]];sort($got);if($got!==["committed","resource_conflict"]){WP_CLI::error(json_encode($got));}global $wpdb;$t=CSF_Scheduling_Schema::table("appointments");$count=(int)$wpdb->get_var("SELECT COUNT(*) FROM `$t` WHERE state=\"confirmed\"");if($count!==($phase==="empty"?1:2)){WP_CLI::error("Unexpected committed appointment count");}echo "TWO_WORKER_BOOKING_".strtoupper($phase)."_PASS_ONE_COMMIT_ONE_CONFLICT\n";'
done

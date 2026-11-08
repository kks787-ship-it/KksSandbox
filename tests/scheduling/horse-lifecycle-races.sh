#!/usr/bin/env bash
# Real two-worker booking versus archive race on disposable synthetic records only.
set -euo pipefail
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
"${WP[@]}" eval '
if(ABSPATH!=="/test/site/"||DB_NAME!=="csf_fixture"||wp_get_environment_type()!=="local"){WP_CLI::error("Fixture required");}
wp_set_current_user(1);$f=json_decode(file_get_contents("/test/calendar-fixture.json"),true);
$rev=static fn()=>(int)CSF_Scheduling_Schema::settings()["revision"];
$check=static function($r){if(is_wp_error($r)){WP_CLI::error(wp_json_encode($r));}};
$resource=wp_generate_uuid4();$horse=wp_generate_uuid4();
$check(CSF_Scheduling_Store::resource(["uuid"=>$resource,"kind"=>"horse","label"=>"Synthetic lifecycle race resource"],$rev(),wp_generate_uuid4()));
$source=json_decode(CSF_Scheduling_Calendar::find("horses",$f["horse"])["body_json"],true);
$availability=json_decode(CSF_Scheduling_Calendar::find("availability",$source["resource_uuid"])["body_json"],true);$availability["resource_uuid"]=$resource;
$check(CSF_Scheduling_Store::calendar("availability_saved",["uuid"=>$resource,"expected"=>0,"data"=>$availability],$rev(),wp_generate_uuid4()));
$source["resource_uuid"]=$resource;$source["label"]="Synthetic lifecycle race horse";unset($source["archived"]);
$check(CSF_Scheduling_Store::calendar("horse_saved",["uuid"=>$horse,"expected"=>0,"data"=>$source],$rev(),wp_generate_uuid4()));
$a="2027-02-02T09:00-08:00";$z="2027-02-02T10:00-08:00";
$b=["uuid"=>wp_generate_uuid4(),"expected"=>0,"title"=>"Synthetic archive race","service_type"=>"lesson","state"=>"confirmed","start"=>$a,"end"=>$z,"hold_expires"=>null,"resources"=>[$f["instructor"],$f["arena"]],"riders"=>[$f["rider"]],"rides"=>[["horse"=>$horse,"rider"=>$f["rider"],"start"=>$a,"end"=>$z]]];
file_put_contents("/test/lifecycle-race.json",wp_json_encode(["revision"=>$rev(),"horse"=>$horse,"appointment_saved"=>$b,"horse_status_saved"=>["uuid"=>$horse,"expected"=>1,"archived"=>true,"reason"=>"Synthetic concurrent archive","confirm_label"=>$source["label"]]]));'
CSF_LIFECYCLE_WORKER=1 "${WP[@]}" eval-file /repo/tests/scheduling/horse-lifecycle-race.php > /test/lifecycle-worker-1.log 2>&1 &
FIRST=$!
CSF_LIFECYCLE_WORKER=2 "${WP[@]}" eval-file /repo/tests/scheduling/horse-lifecycle-race.php > /test/lifecycle-worker-2.log 2>&1 &
SECOND=$!
for attempt in $(seq 1 100); do
 if [[ -f /test/lifecycle-ready-1 && -f /test/lifecycle-ready-2 ]]; then break; fi
 sleep .1
done
touch /test/lifecycle-go
wait "$FIRST"
wait "$SECOND"
"${WP[@]}" eval '
wp_set_current_user(1);$f=json_decode(file_get_contents("/test/lifecycle-race.json"),true);
$a=json_decode(file_get_contents("/test/lifecycle-outcome-1"),true)["reason"];$b=json_decode(file_get_contents("/test/lifecycle-outcome-2"),true)["reason"];
if(!in_array([$a,$b],[["committed","horse_future_bookings"],["horse_archived","committed"]],true)){WP_CLI::error(wp_json_encode([$a,$b]));}
$horse=json_decode(CSF_Scheduling_Calendar::find("horses",$f["horse"])["body_json"],true);$appointment=CSF_Scheduling_Calendar::find("appointments",$f["appointment_saved"]["uuid"]);
if((bool)($horse["archived"]??false)===(bool)$appointment){WP_CLI::error("Archive/book invariant failed");}
echo "TWO_WORKER_ARCHIVE_BOOKING_PASS_ONE_COMMIT_ONE_REJECTION\n";'

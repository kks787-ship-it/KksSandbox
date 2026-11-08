#!/usr/bin/env bash
set -euo pipefail
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
CSF_DISPATCH_WORKER=1 "${WP[@]}" eval-file /repo/tests/scheduling/email-dispatch-race.php >/test/dispatch-worker-1.log 2>&1 &
FIRST=$!
CSF_DISPATCH_WORKER=2 "${WP[@]}" eval-file /repo/tests/scheduling/email-dispatch-race.php >/test/dispatch-worker-2.log 2>&1 &
SECOND=$!
for attempt in $(seq 1 100); do
 if [[ -f /test/dispatch-ready-1 && -f /test/dispatch-ready-2 ]]; then break; fi
 sleep .1
done
touch /test/dispatch-go
wait "$FIRST"
wait "$SECOND"
"${WP[@]}" eval '
$a=json_decode(file_get_contents("/test/dispatch-outcome-1"),true);$b=json_decode(file_get_contents("/test/dispatch-outcome-2"),true);
if($a["messages"]+$b["messages"]!==1||$a["error"]===$b["error"]){WP_CLI::error(wp_json_encode([$a,$b]));}
echo "TWO_WORKER_DISPATCH_PASS_ONE_SUBMISSION\n";'

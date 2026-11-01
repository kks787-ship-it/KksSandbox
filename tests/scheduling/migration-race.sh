#!/usr/bin/env bash
set -euo pipefail
WP=(php /repo/.wp-local/wp-cli.phar --path=/test/site --allow-root)
# A true two-worker migration race starts from an empty owned schema, on this fixture only.
"${WP[@]}" --user=1 eval 'if (DB_NAME!=="csf_fixture" || wp_get_environment_type()!=="local") { WP_CLI::error("Fixture required"); } global $wpdb; foreach(CSF_Scheduling_Schema::definitions() as $name=>$def){$t=CSF_Scheduling_Schema::table($name);$wpdb->query("DROP TABLE `$t`");}'
"${WP[@]}" eval-file /repo/tests/scheduling/concurrent.php > /test/worker1.log 2>&1 &
WORKER1=$!
"${WP[@]}" eval-file /repo/tests/scheduling/concurrent.php > /test/worker2.log 2>&1 &
WORKER2=$!
wait "$WORKER1"
wait "$WORKER2"
"${WP[@]}" eval 'global $wpdb; $t=CSF_Scheduling_Schema::table("audit"); if (!CSF_Scheduling_Schema::inspect()["ready"] || (int)$wpdb->get_var("SELECT COUNT(*) FROM `$t`")!==1) { WP_CLI::error("Migration race duplicated or lost audit"); } echo "TWO_WORKER_MIGRATION_PASS_ONE_AUDIT\n";'

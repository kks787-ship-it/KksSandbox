<?php
/** Populated v2 -> v3 and quarantined legacy membership. Disposable fixture only. */
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){WP_CLI::error('Disposable fixture required.');}
global$wpdb;$wpdb->suppress_errors(true);wp_set_current_user(1);
$original=[];foreach(CSF_Scheduling_Schema::definitions()as$n=>$d){$x=CSF_Scheduling_Schema::table($n);$original[$n]=$wpdb->get_results("SELECT * FROM `$x` ORDER BY 1",ARRAY_A);}file_put_contents('/test/v3-before-permission-migration.json',wp_json_encode($original));$auth=CSF_Scheduling_Schema::table('authorization');$wpdb->query("DROP TABLE `$auth`");$t=CSF_Scheduling_Schema::table('settings');$wpdb->update($t,['schema_version'=>2,'staff_json'=>'[1]'],['id'=>1]);
$old=[];foreach(CSF_Scheduling_Schema::definitions(2)as$n=>$d){$x=CSF_Scheduling_Schema::table($n);$old[$n]=$wpdb->get_results("SELECT * FROM `$x` ORDER BY 1",ARRAY_A);}
if(count($old)!==14||empty($old['appointments'])||empty($old['horses'])){WP_CLI::error('Populated v2 calendar required.');}
$before=CSF_Scheduling_Schema::settings();if(CSF_Scheduling_Access::can()){WP_CLI::error('Unmigrated v2 unexpectedly authorized');}
$fail=static fn($q)=>str_contains($q,'INSERT INTO `fixture_csf_sched_audit`')?'SELECT * FROM missing_permission_migration_fixture':$q;add_filter('query',$fail);$r=CSF_Scheduling_Schema::migrate();remove_filter('query',$fail);if(!is_wp_error($r)||CSF_Scheduling_Schema::settings()['schema_version']!=='2'){WP_CLI::error('Migration audit failure acknowledged or changed version');}
$r=CSF_Scheduling_Schema::migrate();if(is_wp_error($r)||!CSF_Scheduling_Schema::inspect()['ready']){WP_CLI::error(wp_json_encode($r));}
foreach($old as$n=>$rows){$x=CSF_Scheduling_Schema::table($n);$now=$wpdb->get_results("SELECT * FROM `$x` ORDER BY 1",ARRAY_A);if($n==='settings'){$now[0]['schema_version']='2';}if($n==='audit'){$now=array_slice($now,0,count($rows));}if($now!==$rows){WP_CLI::error('Existing v2 '.$n.' changed');}}
$s=CSF_Scheduling_Permissions::state();if($s['master']!==null||$s['members'][1]['active']||$s['members'][1]['verified']||CSF_Scheduling_Access::can()){WP_CLI::error('Legacy manage inferred owner or active verified access');}
if(CSF_Scheduling_Schema::settings()['policy_json']!==$before['policy_json']||CSF_Scheduling_Schema::settings()['staff_json']!==$before['staff_json']){WP_CLI::error('Legacy policy/staff provenance lost');}
echo "POPULATED_V2_TO_V3_14_TABLE_DATA_PRESERVED_LEGACY_QUARANTINED_PASS\n";

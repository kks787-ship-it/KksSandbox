<?php
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){WP_CLI::error('Disposable fixture required.');}
require '/repo/.wp-local/permissions-v2-source/wp-content/plugins/csf-scheduling/csf-scheduling.php';
wp_set_current_user(1);$writes=[];$capture=static function($q)use(&$writes){if(preg_match('/^\s*(CREATE|ALTER|DROP|INSERT|UPDATE|DELETE|REPLACE)\b/i',$q)){$writes[]=$q;}return$q;};add_filter('query',$capture);$ready=CSF_Scheduling_Schema::inspect()['ready'];$r=CSF_Scheduling_Schema::migrate();remove_filter('query',$capture);if($ready||!is_wp_error($r)||$writes){WP_CLI::error('Old code accepted or changed v3 schema');}echo "PINNED_V2_CODE_DENIES_V3_NO_DESTRUCTIVE_DOWNGRADE_PASS\n";

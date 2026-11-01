<?php
if ('local'!==wp_get_environment_type()||ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'){WP_CLI::error('Disposable fixture only.');}
wp_set_current_user(1);
$result=CSF_Scheduling_Schema::migrate();
if(is_wp_error($result)){WP_CLI::error($result->get_error_code());}
WP_CLI::success('Concurrent migration/retry acknowledged.');

<?php
// Disposable local HTTP probe. Never deploy this file or use it with farm configuration.
if (!defined('ABSPATH') || ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local') {return;}
add_filter('pre_wp_mail', '__return_true'); // Test authentication must never send mail.
add_filter('query',static function($sql){
 if(!file_exists('/test/security-fault')){return$sql;}$needle=trim(file_get_contents('/test/security-fault'));
 if($needle==='commit_ack'&&$sql==='COMMIT'){$GLOBALS['wpdb']->dbh->query('COMMIT');file_put_contents('/test/security-fault-hit','yes');return 'SELECT * FROM fixture_security_absent';}
 if($needle!==''&&str_contains($sql,$needle)){file_put_contents('/test/security-fault-hit','yes');return 'SELECT * FROM fixture_security_absent';}return$sql;
});
add_action('two_factor_user_authenticated',static function($user){if(file_exists('/test/mfa-failure-mode')&&trim(file_get_contents('/test/mfa-failure-mode'))==='rotate'){file_put_contents('/test/mfa-failure-hit','rotate');Two_Factor_Totp::get_instance()->set_user_totp_key($user->ID,Two_Factor_Totp::generate_key());}},PHP_INT_MAX-1);
add_filter('query',static function($sql){
 $file='/test/mfa-failure-mode';if(!file_exists($file)){return$sql;}$mode=trim(file_get_contents($file));
 if($mode==='revoke'&&str_starts_with($sql,'UPDATE `fixture_usermeta`')&&str_contains($sql,'csf-mfa-proof-v1')){file_put_contents('/test/mfa-failure-hit',$mode);WP_Session_Tokens::get_instance(1)->destroy_all();return$sql;}
 $key=array('totp'=>'_two_factor_totp_last_successful_login','backup'=>'_two_factor_backup_codes','session'=>'session_tokens')[$mode]??'';
 if($key&&(str_starts_with($sql,'UPDATE `fixture_usermeta`')||str_starts_with($sql,'INSERT INTO `fixture_usermeta`'))&&str_contains($sql,"'".$key."'")){file_put_contents('/test/mfa-failure-hit',$mode);return 'UPDATE fixture_intentionally_absent SET id=1';}return$sql;
});
add_action('wp_ajax_fixture_mfa_probe', static function(){
 wp_send_json(array('id'=>get_current_user_id(),'mfa'=>CSF_Scheduling_Permissions::mfa(get_current_user_id()),'enrolled'=>CSF_Scheduling_Permissions::enrolled(get_current_user_id()),'calendar'=>CSF_Scheduling_Access::can('calendar_all'),'team'=>CSF_Scheduling_Access::can('team_assign'),'nonce'=>wp_create_nonce('csf_calendar_write'),'rest_nonce'=>wp_create_nonce('wp_rest'),'logout_url'=>html_entity_decode(wp_logout_url(),ENT_QUOTES,'UTF-8')));
});
add_action('rest_api_init',static function(){register_rest_route('fixture/v1','/mfa',array('methods'=>'GET','permission_callback'=>'__return_true','callback'=>static function(){return array('id'=>get_current_user_id(),'mfa'=>CSF_Scheduling_Permissions::mfa(get_current_user_id()),'calendar'=>CSF_Scheduling_Access::can('calendar_all'));}));});

add_action('template_redirect',static function(){
 if(!isset($_GET['fixture_security_probe'])){return;}
 wp_send_json(array('id'=>get_current_user_id(),'mfa'=>CSF_Scheduling_Permissions::mfa(get_current_user_id()),'web_mfa'=>CSF_MFA_Session::web_verified(get_current_user_id()),'admin'=>current_user_can('manage_options'),'promote'=>current_user_can('promote_users'),'calendar'=>CSF_Scheduling_Access::can('calendar_all'),'team'=>CSF_Scheduling_Access::can('team_assign'),'nonce'=>wp_create_nonce('csf_identity'),'rest_nonce'=>wp_create_nonce('wp_rest'),'logout_url'=>html_entity_decode(wp_logout_url(),ENT_QUOTES,'UTF-8')));
});
add_action('wp_ajax_fixture_identity',static function(){
 check_ajax_referer('csf_identity','nonce');$body=json_decode(wp_unslash($_POST['body']??''),true);$kind=sanitize_key($_POST['kind']??'');$r=CSF_Scheduling_Store::identity($kind,$body,(int)($_POST['revision']??-1),sanitize_text_field($_POST['command']??''));if(is_wp_error($r)){wp_send_json_error(array('code'=>$r->get_error_code(),'reason'=>$r->get_error_data()),403);}wp_send_json_success($r);
});

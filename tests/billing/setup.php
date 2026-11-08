<?php
/** Run only through WP-CLI on the disposable local fixture. Never on a farm. */
if(ABSPATH!=='/tmp/csf-billing-fixture/site/'||DB_NAME!=='csf_gift_fixture'||wp_get_environment_type()!=='local'||!defined('WP_CLI')){throw new RuntimeException('Isolated fixture required');}
global $wpdb;
foreach(CSF_Billing_Schema::statements() as $sql){if(false===$wpdb->query($sql)){throw new RuntimeException('Fixture schema failed');}}
(new CSF_Billing_Store())->ready();
$id=wp_create_user('gift_operator','synthetic-operator-only','operator@example.invalid');if(is_wp_error($id)){throw new RuntimeException('Synthetic operator already exists or creation failed');}
$user=get_userdata($id);foreach(array('csf_billing_audit','csf_billing_redeem','csf_billing_manage') as $cap){$user->add_cap($cap);}
$totp=Two_Factor_Totp::get_instance();$totp->set_user_totp_key($id,Two_Factor_Totp::generate_key());update_user_meta($id,'_two_factor_enabled_providers',array('Two_Factor_Totp','Two_Factor_Backup_Codes'));update_user_meta($id,'_two_factor_provider','Two_Factor_Totp');
Two_Factor_Backup_Codes::get_instance()->generate_codes($user);
if(!CSF_MFA_Session::ready()||!CSF_MFA_Session::enrolled(false,$id)||CSF_MFA_Session::verified(false,$id)){throw new RuntimeException('Real MFA fixture setup failed');}
$page=wp_insert_post(array('post_title'=>'Synthetic gift certificates','post_name'=>'gift-certificates','post_content'=>'[csf_gift_certificates]','post_type'=>'page','post_status'=>'publish'),true);if(is_wp_error($page)){throw new RuntimeException('Fixture page failed');}
update_option('permalink_structure','/%postname%/');flush_rewrite_rules(false);
file_put_contents('/tmp/csf-billing-fixture/operator.json',wp_json_encode(array('id'=>$id,'page'=>$page)));
echo "REAL_DB_SCHEMA_AND_OFFICIAL_MFA_FIXTURE_READY\n";

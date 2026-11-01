<?php
/** Disposable local MySQL/MariaDB integration and failure-injection checks. */
if ('local' !== wp_get_environment_type() || ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture') { WP_CLI::error('Only the Docker foundation fixture is allowed.'); }
global $wpdb;
$wpdb->suppress_errors(true);
$GLOBALS['csf_foundation_checks']=0;
function expect($ok,$message){if(!$ok){throw new RuntimeException($message);}++$GLOBALS['csf_foundation_checks'];WP_CLI::log('PASS '.$message);}
function failed($v,$message){expect(is_wp_error($v),$message);}
function as_user($id){wp_set_current_user(0);wp_set_current_user($id);}
function revision(){return (int)CSF_Scheduling_Schema::settings()['revision'];}
function count_rows($name){global $wpdb;$t=CSF_Scheduling_Schema::table($name);return (int)$wpdb->get_var("SELECT COUNT(*) FROM `$t`");}
function sql_write_failure($needle){return static function($query)use($needle){return str_contains($query,$needle)?'SELECT * FROM deliberately_absent_fixture_table':$query;};}
add_filter('pre_wp_mail',static function(){throw new RuntimeException('No mail is permitted.');});
as_user(1);
$page=wp_insert_post(array('post_type'=>'page','post_title'=>'Synthetic untouched page','post_status'=>'draft','post_content'=>'Preserve this fixture.'));
$menu=wp_create_nav_menu('Synthetic untouched menu');
$sentinel=array(get_post($page),wp_get_nav_menu_object($menu),get_option('csf_core_inquiry'),get_option('active_plugins'));
$roles=wp_roles()->roles;
$users_before=$wpdb->get_results("SELECT * FROM {$wpdb->usermeta} ORDER BY umeta_id",ARRAY_A);
$writes=array();$capture=static function($q)use(&$writes){if(preg_match('/^\s*(INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|REPLACE)\b/i',$q)){$writes[]=$q;}return $q;};
add_filter('query',$capture);
activate_plugin('csf-scheduling/csf-scheduling.php');
remove_filter('query',$capture);
expect(count($writes)===1 && str_contains($writes[0],'active_plugins'),'actual activation writes only WordPress active_plugins metadata');
expect($roles===wp_roles()->roles && $users_before===$wpdb->get_results("SELECT * FROM {$wpdb->usermeta} ORDER BY umeta_id",ARRAY_A),'activation grants no roles or user capabilities');
expect(!CSF_Scheduling_Schema::inspect()['ready'],'activation creates no tables or ready marker');
$real_db=$wpdb;$wpdb=(object)array('dbh'=>null);expect(CSF_Scheduling_Schema::inspect()['storage']==='mysql_required','unsupported storage driver fails closed');$wpdb=$real_db;
$writes=array();add_filter('query',$capture);CSF_Scheduling_Schema::statements();CSF_Scheduling_Schema::inspect();do_action('activate_csf-scheduling/csf-scheduling.php');remove_filter('query',$capture);
expect(!$writes,'activation hook and migration preview make zero plugin writes');
failed(CSF_Scheduling_Diagnostics::report(),'unprovisioned administrator denied private diagnostics');
as_user(0);failed(CSF_Scheduling_Schema::migrate(),'anonymous migration denied');as_user(1);
$ddl=CSF_Scheduling_Schema::statements();expect(count($ddl)===6,'preview specifies exact six-table schema');
expect(false!==$wpdb->query($ddl['resources']),'prepare interrupted migration fixture');
expect(!CSF_Scheduling_Schema::inspect()['ready'],'partial schema fails closed');
$wpdb->insert(CSF_Scheduling_Schema::table('resources'),array('uuid'=>wp_generate_uuid4(),'kind'=>'arena','label'=>'Synthetic unversioned data','active'=>1,'revision'=>1,'created_by'=>1,'created_at_utc'=>CSF_Scheduling_Audit::now()));failed(CSF_Scheduling_Schema::migrate(),'populated unversioned tables are not automatically adopted');$wpdb->query('DELETE FROM fixture_csf_sched_resources');
$block=sql_write_failure('CREATE TABLE IF NOT EXISTS `fixture_csf_sched_commands`');add_filter('query',$block);failed(CSF_Scheduling_Schema::migrate(),'DDL interruption does not acknowledge migration');remove_filter('query',$block);
expect(!CSF_Scheduling_Schema::inspect()['ready'],'DDL failure leaves no ready marker');
$block=sql_write_failure('INSERT INTO `fixture_csf_sched_settings`');add_filter('query',$block);failed(CSF_Scheduling_Schema::migrate(),'singleton seed failure leaves migration unready');remove_filter('query',$block);
$block=sql_write_failure('INSERT INTO `fixture_csf_sched_audit`');add_filter('query',$block);failed(CSF_Scheduling_Schema::migrate(),'migration audit failure rolls back ready marker');remove_filter('query',$block);
expect(!CSF_Scheduling_Schema::inspect()['ready']&&count_rows('audit')===0,'migration audit failure produces no false success event');
expect(true===CSF_Scheduling_Schema::migrate(),'retry completes partial migration on real InnoDB');
expect(CSF_Scheduling_Schema::inspect()['ready'],'physical columns indexes engine and version verified');
expect(count_rows('appointments')===0 && count_rows('resources')===0,'migration invents no appointments or resources');
expect(count_rows('audit')===1,'migration creates one audit event');
expect(true===CSF_Scheduling_Schema::migrate() && count_rows('audit')===1,'migration is idempotent without duplicate audit');
failed(CSF_Scheduling_Diagnostics::report(),'generic Administrator remains denied after migration');
$staff=wp_create_user('synthetic_staff','fixture-only','staff@example.invalid');
$editor=wp_create_user('synthetic_editor','fixture-only','editor@example.invalid');(new WP_User($editor))->set_role('editor');
$client=wp_create_user('synthetic_client','fixture-only','client@example.invalid');
foreach(array(0,$editor,$client)as$id){as_user($id);expect(!CSF_Scheduling_Access::can(),'ungranted identity denied '.$id);}
as_user(1);
failed(CSF_Scheduling_Access::provision(array(1,$staff),false,0,wp_generate_uuid4()),'owner identity acknowledgment required');
failed(CSF_Scheduling_Access::provision(array(999999),true,0,wp_generate_uuid4()),'unresolved staff ID rejected');
$cmd=wp_generate_uuid4();$provision=CSF_Scheduling_Access::provision(array(1,$staff),true,0,$cmd);expect(!is_wp_error($provision),'explicit owner-verified staff provisioning succeeds');
as_user(1);expect(CSF_Scheduling_Access::can('csf_schedule_manage'),'verified operator gets explicit scheduling capability');
$t=CSF_Scheduling_Schema::table('settings');$saved_staff=CSF_Scheduling_Schema::settings()['staff_json'];$wpdb->update($t,array('staff_json'=>'{"invalid_key":1}'),array('id'=>1));expect(!CSF_Scheduling_Access::can(),'malformed allowlist object cannot grant access');$wpdb->update($t,array('staff_json'=>$saved_staff),array('id'=>1));
as_user($staff);expect(CSF_Scheduling_Access::can(),'verified staff can view private diagnostics');
$d=CSF_Scheduling_Diagnostics::report();expect(!$d['foundation_ready']&&!$d['operational_calendar']&&!$d['appointment_writes_enabled'],'unset policies never appear operational');
$p=CSF_Scheduling_Policy::defaults();$p['timezone']='Europe/London';$p['service_hours']=array_fill(0,7,array());$p['service_hours'][0]=array(array('09:00','17:00'));$p['services']=array('coaching'=>array('enabled'=>true,'capacity'=>4,'duration_minutes'=>45,'horse_source'=>'client','requires_travel'=>true,'required_resources'=>array('instructor','location')));$p['training_horse_cap']=12;$p['academy']=array('enabled'=>false);$p['travel_buffer_minutes']=20;
expect(!is_wp_error(CSF_Scheduling_Policy::validate($p))&&!CSF_Scheduling_Policy::missing($p),'another barn may open Sundays with no Academy and a different cap/timezone');
$reordered=$p;$reordered['services']['coaching']=array_reverse($p['services']['coaching'],true);$reordered['workload']=array_reverse($p['workload'],true);expect(!is_wp_error(CSF_Scheduling_Policy::validate($reordered)),'JSON object key order does not change policy validity');
$bad=$p;$bad['academy']=array('enabled'=>true,'weekday'=>1,'start'=>'14:30','end'=>'17:00','season_start'=>'2026-02-30','season_end'=>'2026-04-01','capacity'=>4);failed(CSF_Scheduling_Policy::validate($bad),'impossible Academy season date rejected');
$bad=$p;$bad['services']['coaching']['required_resources']=array(array('instructor'));failed(CSF_Scheduling_Policy::validate($bad),'nested resource-kind input rejected without coercion');
foreach(array('timezone'=>'Not/AZone','training_horse_cap'=>'5','preferred_offsite_day'=>7,'travel_buffer_minutes'=>-1)as$key=>$value){$bad=$p;$bad[$key]=$value;failed(CSF_Scheduling_Policy::validate($bad),'reject malformed '.$key);}
$bad=$p;$bad['service_hours'][0]=array(array('17:00','09:00'));failed(CSF_Scheduling_Policy::validate($bad),'reject reversed public hours');
$bad=$p;$bad['service_hours'][0]=array(array('09:00','12:00'),array('11:00','13:00'));failed(CSF_Scheduling_Policy::validate($bad),'reject overlapping public hours');
$bad=$p;$bad['services']['coaching']['horse_source']='school';expect(in_array('workload',CSF_Scheduling_Policy::missing($bad),true),'school-horse workloads unset blocks readiness');
as_user($editor);failed(CSF_Scheduling_Store::configure($p,revision(),wp_generate_uuid4()),'editor cannot configure facility');
(new WP_User($editor))->add_cap('csf_schedule_manage');as_user($editor);failed(CSF_Scheduling_Store::configure($p,revision(),wp_generate_uuid4()),'capability alone cannot bypass staff allowlist');
as_user(1);
$cmd=wp_generate_uuid4();$rev=revision();$a=count_rows('audit');$result=CSF_Scheduling_Store::configure($p,$rev,$cmd);expect(!is_wp_error($result),'authorized audited policy replacement succeeds');
expect(count_rows('audit')===$a+1&&revision()===$rev+1,'policy and audit revision commit together');
expect(CSF_Scheduling_Store::configure($p,$rev,$cmd)===$result&&count_rows('audit')===$a+1,'same command replay returns original result once');
$bad=$p;$bad['training_horse_cap']=13;failed(CSF_Scheduling_Store::configure($bad,$rev,$cmd),'same UUID with altered payload rejected');
as_user($staff);failed(CSF_Scheduling_Store::configure($p,$rev,$cmd),'command replay is actor-bound');as_user(1);
failed(CSF_Scheduling_Store::configure($p,$rev,wp_generate_uuid4()),'stale policy revision rejected');
foreach(array('`fixture_csf_sched_audit`','`fixture_csf_sched_commands`')as$needle){$rev=revision();$a=count_rows('audit');$c=count_rows('commands');$block=sql_write_failure('INSERT INTO '.$needle);add_filter('query',$block);failed(CSF_Scheduling_Store::configure($bad,$rev,wp_generate_uuid4()),'failed '.$needle.' insert returns error');remove_filter('query',$block);expect(revision()===$rev&&count_rows('audit')===$a&&count_rows('commands')===$c,'failed command rolls back policy audit and result');}
$throw=static function($q){if(str_starts_with($q,'INSERT INTO `fixture_csf_sched_audit`')){throw new RuntimeException('SYNTHETIC_PRIVATE_BODY_NOT_FOR_ERRORS');}return $q;};$rev=revision();add_filter('query',$throw);$private_error=CSF_Scheduling_Store::configure($bad,$rev,wp_generate_uuid4());remove_filter('query',$throw);expect(is_wp_error($private_error)&&strpos(wp_json_encode($private_error->get_error_data()),'SYNTHETIC_PRIVATE')===false&&revision()===$rev,'unexpected exception bodies are redacted and transaction rolls back');
$resource=array('uuid'=>wp_generate_uuid4(),'kind'=>'arena','label'=>'Synthetic arena');$cmd=wp_generate_uuid4();$rev=revision();$rr=CSF_Scheduling_Store::resource($resource,$rev,$cmd);expect(!is_wp_error($rr)&&count_rows('resources')===1,'explicit private resource creation audited');
expect(CSF_Scheduling_Store::resource($resource,$rev,$cmd)===$rr&&count_rows('resources')===1,'resource replay creates no duplicate');
failed(CSF_Scheduling_Store::resource(array('uuid'=>wp_generate_uuid4(),'kind'=>'horse','label'=>'<script>x</script>'),revision(),wp_generate_uuid4()),'unsafe resource label rejected');
$report=CSF_Scheduling_Diagnostics::report();expect(!$report['foundation_ready'],'missing declared resources cannot appear ready');
foreach(array('instructor','location') as $kind){$v=CSF_Scheduling_Store::resource(array('uuid'=>wp_generate_uuid4(),'kind'=>$kind,'label'=>'Synthetic '.$kind),revision(),wp_generate_uuid4());expect(!is_wp_error($v),'create explicit '.$kind.' resource');}
$report=CSF_Scheduling_Diagnostics::report();expect($report['foundation_ready']&&!$report['operational_calendar'],'configured foundation still explicitly not a working calendar');
$audit=CSF_Scheduling_Schema::table('audit');$last=$wpdb->get_row("SELECT * FROM `$audit` ORDER BY id DESC LIMIT 1",ARRAY_A);$utc=new DateTimeImmutable($last['occurred_at_utc'],new DateTimeZone('UTC'));$local=new DateTimeImmutable($last['occurred_at_local'],new DateTimeZone($last['timezone']));expect($utc->format('U.u')===$local->format('U.u'),'audit local and UTC timestamps resolve to same instant');
$rev=revision();$oldcmd=wp_generate_uuid4();expect(!is_wp_error(CSF_Scheduling_Access::provision(array(1),true,$rev,$oldcmd)),'explicit staff removal succeeds');as_user($staff);expect(!CSF_Scheduling_Access::can(),'removed staff denied immediately');as_user(1);
$rev=revision();$grant_command=wp_generate_uuid4();$deny_cap=static function($check,$uid,$key)use($staff){return $uid===$staff&&str_ends_with($key,'capabilities')?false:$check;};add_filter('update_user_metadata',$deny_cap,10,3);failed(CSF_Scheduling_Access::provision(array(1,$staff),true,$rev,$grant_command),'capability mirror failure is reported, not silently acknowledged');remove_filter('update_user_metadata',$deny_cap,10);as_user($staff);expect(!CSF_Scheduling_Access::can(),'failed capability grant denies the affected staff identity');as_user(1);expect(!CSF_Scheduling_Diagnostics::report()['foundation_ready'],'incomplete capability reconciliation blocks readiness');expect(!is_wp_error(CSF_Scheduling_Access::provision(array(1,$staff),true,$rev,$grant_command)),'same provisioning command repairs capability mirror without duplicate event');as_user($staff);expect(CSF_Scheduling_Access::can(),'reconciled staff can access');as_user(1);
$t=CSF_Scheduling_Schema::table('resources');$wpdb->query("ALTER TABLE `$t` ENGINE=MyISAM");expect(!CSF_Scheduling_Schema::inspect()['ready'],'nontransactional engine rejected');failed(CSF_Scheduling_Schema::migrate(),'invalid engine is not silently converted');failed(CSF_Scheduling_Store::configure($p,revision(),wp_generate_uuid4()),'invalid engine blocks all foundation writes');$wpdb->query("ALTER TABLE `$t` ENGINE=InnoDB");
$wpdb->query("ALTER TABLE `$t` DROP INDEX kind_active");expect(!CSF_Scheduling_Schema::inspect()['ready'],'missing index fails closed');failed(CSF_Scheduling_Schema::migrate(),'missing index not silently repaired');$wpdb->query("ALTER TABLE `$t` ADD KEY kind_active(kind,active)");
$settings=CSF_Scheduling_Schema::table('settings');$wpdb->update($settings,array('schema_version'=>99),array('id'=>1));failed(CSF_Scheduling_Schema::migrate(),'newer schema cannot be downgraded');$wpdb->update($settings,array('schema_version'=>1),array('id'=>1));
$wpdb->query("ALTER TABLE `$t` ALTER COLUMN label SET DEFAULT 'unexpected'");expect(!CSF_Scheduling_Schema::inspect()['ready'],'unreviewed column default fails closed');$wpdb->query("ALTER TABLE `$t` ALTER COLUMN label DROP DEFAULT");
$app=CSF_Scheduling_Schema::table('appointments');$wpdb->query("DROP TABLE `$app`");failed(CSF_Scheduling_Schema::migrate(),'missing table in installed schema is not silently recreated');$wpdb->update($settings,array('schema_version'=>99),array('id'=>1));failed(CSF_Scheduling_Schema::migrate(),'newer partial schema cannot be downgraded');expect(null===$wpdb->get_var("SHOW TABLES LIKE '$app'"),'refused newer partial migration writes no table');$wpdb->query($ddl['appointments']);$wpdb->update($settings,array('schema_version'=>1),array('id'=>1));
expect(CSF_Scheduling_Schema::inspect()['ready'],'restored valid physical schema ready');
expect($sentinel[0]==get_post($page)&&$sentinel[1]==wp_get_nav_menu_object($menu)&&$sentinel[2]===get_option('csf_core_inquiry'),'foundation leaves pages menus and inquiry settings unchanged');
expect(!is_plugin_active('csf-emergency/csf-emergency.php'),'emergency remains inactive');
expect(count_rows('appointments')===0&&count_rows('allocations')===0,'foundation never confirms or allocates appointments');
$rows_before=array(count_rows('settings'),count_rows('resources'),count_rows('commands'),count_rows('audit'));deactivate_plugins('csf-scheduling/csf-scheduling.php');expect($rows_before===array(count_rows('settings'),count_rows('resources'),count_rows('commands'),count_rows('audit')),'deactivation retains all scheduling records');activate_plugin('csf-scheduling/csf-scheduling.php');expect($rows_before===array(count_rows('settings'),count_rows('resources'),count_rows('commands'),count_rows('audit')),'reactivation does not migrate or fabricate records');
WP_CLI::success($GLOBALS['csf_foundation_checks'].' scheduling foundation integration checks; database '.$wpdb->get_var('SELECT VERSION()'));

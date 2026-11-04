<?php
// Disposable WordPress with an in-memory mailer. Never copy this harness to the farm.
if(ABSPATH!=='/test/site/'||DB_NAME!=='csf_fixture'||wp_get_environment_type()!=='local'){throw new RuntimeException('Fixture required');}
remove_filter('pre_wp_mail','__return_true');
// The live probe is direct PHP-FPM, not WP-CLI; remove only proven CLI instrumentation.
foreach(array('wp_mail','pre_wp_mail','wp_mail_from','wp_mail_from_name','phpmailer_init','wp_mail_succeeded','wp_mail_failed','wp_mail_content_type','wp_mail_charset')as$hook){
 $object=$GLOBALS['wp_filter'][$hook]??null;
 foreach($object?$object->callbacks:array()as$priority=>$callbacks){foreach($callbacks as$callback){
  $fn=$callback['function'];
  try{$reflection=is_array($fn)?new ReflectionMethod($fn[0],$fn[1]):new ReflectionFunction($fn);$file=$reflection->getFileName();}
  catch(Throwable$error){continue;}
  if(is_string($file)&&str_starts_with($file,'phar:///repo/.wp-local/wp-cli.phar/')){remove_filter($hook,$fn,$priority);}
 }}
}
update_option('home','http://fixture.example.invalid');update_option('siteurl','http://fixture.example.invalid');
global $phpmailer,$dr_fake_sends,$dr_fake_to;
$dr_fake_sends=0;
if(!class_exists('WP_PHPMailer')){require_once ABSPATH.WPINC.'/PHPMailer/PHPMailer.php';require_once ABSPATH.WPINC.'/PHPMailer/SMTP.php';require_once ABSPATH.WPINC.'/PHPMailer/Exception.php';require_once ABSPATH.WPINC.'/class-wp-phpmailer.php';}
$phpmailer=new class extends WP_PHPMailer{public function send(){global$dr_fake_sends,$dr_fake_to;++$dr_fake_sends;$dr_fake_to=$this->getToAddresses();return true;}};
$case=getenv('CSF_TEST_NATIVE_CASE');
if(in_array($case,array('preview_case','dispatch_case'),true)){
 global$wpdb;
 $wpdb->update($wpdb->users,array('user_email'=>'CaseSensitive@example.invalid'),array('ID'=>1));clean_user_cache(1);
 $wpdb->update($wpdb->options,array('option_value'=>'CaseSensitive@example.invalid'),array('option_name'=>'admin_email'));
 wp_cache_delete('alloptions','options');wp_cache_delete('admin_email','options');
}
$template=file_get_contents('/repo/scripts/pilot/native_mail_runtime.php.txt');
// WP-CLI already bootstrapped this disposable site. Never reload its configuration.
$template=str_replace("require_once \$p['root'] . '/wp-load.php';",'/* Local fixture already bootstrapped. */',$template);
// Only this local guarded harness substitutes CLI and a fake mailer subclass.
if($case!=='wrong_sapi'){$template=str_replace("PHP_SAPI === 'fpm-fcgi'","PHP_SAPI === 'cli'",$template);}
$template=str_replace("get_class(\$mailer) === 'WP_PHPMailer'","(\$mailer instanceof WP_PHPMailer)",$template);
$parameters=array('root'=>'/test/site','url'=>'http://fixture.example.invalid','uid'=>posix_geteuid(),'mode'=>in_array($case,array('preview','preview_case','wrong_sapi'),true)?'preview':'dispatch',
 'dispatch_authorized'=>$case!=='no_approval','approval_reference'=>'synthetic-owner-approval','expected_binding'=>$case==='preview'||$case==='wrong_sapi'?'':json_decode(file_get_contents('/test/native-mail-preview.json'),true)['binding_fingerprint'],
 'deadline'=>time()+30,'message_references'=>array(str_repeat('a',32)));
if($case==='wrong_binding'){$parameters['expected_binding']=str_repeat('0',64);}
if($case==='unexpected_sender_hook'){add_filter('wp_mail_from',static function(){return'forged@example.invalid';});}
if($case==='unexpected_recipient_hook'){add_filter('wp_mail',static function($attrs){$attrs['to']=array('forged@example.invalid');return$attrs;});}
$program=str_replace('__PARAMETERS__',base64_encode(wp_json_encode($parameters)),$template);
eval('?>'.$program);
if($case==='preview'||$case==='preview_case'){
 if($out['state']!=='native-mail-runtime-qualified'||$dr_fake_sends!==0){throw new RuntimeException('Preview must not send');}
 file_put_contents('/test/native-mail-preview.json',wp_json_encode($out));chmod('/test/native-mail-preview.json',0600);
}elseif($case==='dispatch'||$case==='dispatch_case'){
 if($out['state']!=='native-mail-submission-results'||$dr_fake_sends!==1||$out['results'][0]['state']!=='submission_accepted'||$out['receipt_confirmed']){throw new RuntimeException('Dispatch must call fake native mailer once without receipt claim');}
 if($case==='dispatch_case'&&$dr_fake_to!==array(array('CaseSensitive@example.invalid',''))){throw new RuntimeException('Configured mailbox case changed');}
}else{if($out['state']!=='blocked'||$dr_fake_sends!==0){throw new RuntimeException('Blocked case sent or was not blocked');}}
if(str_contains(wp_json_encode($out),'fixture@example.invalid')){throw new RuntimeException('Address leaked in outcome');}
echo "\nNATIVE_MAIL_CASE_PASS=$case\n";

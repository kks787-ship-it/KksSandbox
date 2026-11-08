<?php
/** Real WordPress mail API with a final in-memory mailer; never farm execution. */
if ( ABSPATH !== '/test/site/' || DB_NAME !== 'csf_fixture' || wp_get_environment_type() !== 'local' ) { throw new RuntimeException( 'Fixture required' ); }
remove_filter( 'pre_wp_mail', '__return_true' );
foreach ( array( 'wp_mail', 'pre_wp_mail', 'wp_mail_from', 'wp_mail_from_name', 'phpmailer_init', 'wp_mail_succeeded', 'wp_mail_failed', 'wp_mail_content_type', 'wp_mail_charset' ) as $hook ) {
 $object = $GLOBALS['wp_filter'][$hook] ?? null;
 foreach ( $object ? $object->callbacks : array() as $priority => $callbacks ) { foreach ( $callbacks as $callback ) {
  $fn = $callback['function'];
  try { $r = is_array( $fn ) ? new ReflectionMethod( $fn[0], $fn[1] ) : new ReflectionFunction( $fn ); $file = $r->getFileName(); } catch ( Throwable $e ) { continue; }
  if ( is_string( $file ) && str_starts_with( $file, 'phar:///repo/.wp-local/wp-cli.phar/' ) ) { remove_filter( $hook, $fn, $priority ); }
 } }
}
if ( ! class_exists( 'WP_PHPMailer' ) ) {
 require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php'; require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php'; require_once ABSPATH . WPINC . '/PHPMailer/Exception.php'; require_once ABSPATH . WPINC . '/class-wp-phpmailer.php';
}
final class CSF_Scheduling_Email_Fixture_Mailer extends WP_PHPMailer {
 public $outcome = true; public $messages = array(); private $accepted = array();
 public function send() {
  $to = $this->getToAddresses();
  if ( $this->From !== 'fixture@example.invalid' || count( $to ) !== 1 || $to[0][0] !== 'SyntheticStaff@example.invalid' || $this->getCcAddresses() || $this->getBccAddresses() || $this->getAttachments() ) { throw new RuntimeException( 'Synthetic payload boundary' ); }
  $key = $this->getCustomHeaders()[0][1] ?? '';
  $message = array( $to, $this->Subject, $this->Body );
  if ( isset( $this->accepted[$key] ) ) { if ( $this->accepted[$key] !== $message ) { throw new RuntimeException( 'Conflicting key' ); } return true; }
  $this->messages[] = $message;
  if ( $this->outcome === 'throw' ) { throw new RuntimeException( 'Synthetic uncertain result' ); }
  if ( $this->outcome ) { $this->accepted[$key] = $message; }
  return $this->outcome;
 }
}
require_once '/repo/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-email-sink.php';
require_once '/repo/wp-content/plugins/csf-scheduling/includes/class-csf-scheduling-email-wp-mail.php';
$checks=0;$check=static function($ok,$label)use(&$checks){if(!$ok)throw new RuntimeException($label);++$checks;};
$event=array('record_type'=>'booking_notice','source_command'=>wp_generate_uuid4(),'email_state'=>'held_no_delivery_authorization','recipient_ids'=>array(1),'recipient_items'=>array(1=>array('00000000-0000-4000-8000-000000000001')),'changes'=>array(array('uuid'=>'00000000-0000-4000-8000-000000000001','kind'=>'NEW','urgent'=>true,'reason'=>'PRIVATE MUST NOT APPEAR')));
$job=CSF_Scheduling_Email_Test_Adapter::job($event,1);$token=str_repeat('a',32);$claim=CSF_Scheduling_Email_Test_Adapter::claim($job,0,$token,100);
global $phpmailer;$phpmailer=new CSF_Scheduling_Email_Fixture_Mailer(true);
$args=CSF_Scheduling_Email_WP_Mail::arguments($claim,'SyntheticStaff@example.invalid');
$check(str_contains($args['subject'],'URGENT CHANGE')&&!str_contains(wp_json_encode($args),'PRIVATE')&&!$args['attachments'],'Minimal urgent grouped envelope excludes private fields');
$done=CSF_Scheduling_Email_WP_Mail::attempt($claim,$token,101,'SyntheticStaff@example.invalid');
$check($done['state']==='sink_accepted'&&count($phpmailer->messages)===1,'wp_mail reaches fake native mailer once, never delivery: '.wp_json_encode(array('state'=>$done['state'],'messages'=>count($phpmailer->messages),'mailer_error'=>$phpmailer->ErrorInfo)));
$check($phpmailer->messages[0][0][0][0]==='SyntheticStaff@example.invalid','Recipient local-part case preserved');
CSF_Scheduling_Email_WP_Mail::attempt($claim,$token,102,'SyntheticStaff@example.invalid');$check(count($phpmailer->messages)===1,'Stable key deduplicates only within qualified synthetic sink');
$reject=static function($fn,$reason)use($check){$got=null;try{$fn();}catch(RuntimeException$e){$got=$e->getMessage();}$check($got===$reason,'Expected '.$reason);};
$reject(fn()=>CSF_Scheduling_Email_WP_Mail::attempt($claim,str_repeat('b',32),102,'SyntheticStaff@example.invalid'),'email_claim_lost');
$reject(fn()=>CSF_Scheduling_Email_WP_Mail::attempt($claim,$token,160,'SyntheticStaff@example.invalid'),'email_claim_lost');
$reject(fn()=>CSF_Scheduling_Email_WP_Mail::arguments($claim,'real@example.com'),'email_fixture_recipient');
$hook=static fn($atts)=>$atts;add_filter('wp_mail',$hook);$reject(fn()=>CSF_Scheduling_Email_WP_Mail::attempt($claim,$token,102,'SyntheticStaff@example.invalid'),'email_unqualified_hook');remove_filter('wp_mail',$hook);
foreach(array(false,'throw')as$outcome){$phpmailer=new CSF_Scheduling_Email_Fixture_Mailer(true);$phpmailer->outcome=$outcome;$done=CSF_Scheduling_Email_WP_Mail::attempt($claim,$token,101,'SyntheticStaff@example.invalid');$check($done['state']==='uncertain','False/exception native outcome must not auto-retry');}
$phpmailer=new WP_PHPMailer(true);$reject(fn()=>CSF_Scheduling_Email_WP_Mail::attempt($claim,$token,102,'SyntheticStaff@example.invalid'),'email_fixture_mailer_required');
$check($event['email_state']==='held_no_delivery_authorization','Original notice remains transport-held');
echo "EMAIL_WP_MAIL_FIXTURE_PASS checks=$checks\n";

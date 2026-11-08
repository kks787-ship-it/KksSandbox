<?php
/** Real SQL failure/rollback probes in a unique scratch prefix; disposable local fixture only. */
if(ABSPATH!=='/tmp/csf-billing-fixture/site/'||DB_NAME!=='csf_gift_fixture'||wp_get_environment_type()!=='local'||!defined('WP_CLI')){throw new RuntimeException('Isolated fixture required');}
global $wpdb,$checks;$original=$wpdb->prefix;$wpdb->prefix='gift_fixture_probe_'.bin2hex(random_bytes(5)).'_';$prefix=$wpdb->prefix.'csf_billing_';$checks=0;$tables=array('guard','orders','certificates','commands','audit','throttle');$old=$wpdb->suppress_errors(true);
function dbcheck($ok,$label){global $checks;if(!$ok){throw new RuntimeException($label);}++$checks;echo "PASS $label\n";}
function dbreject($callback,$label){try{$callback();}catch(CSF_Billing_Failure $e){dbcheck(true,$label);return;}throw new RuntimeException('Expected rejection: '.$label);}
try{
 foreach(CSF_Billing_Schema::statements() as $sql){dbcheck(false!==$wpdb->query($sql),'fresh DDL statement');}
 $store=new CSF_Billing_Store();$store->ready();dbcheck(true,'real engine and unique-index inspection');
 dbcheck(false===$wpdb->query(CSF_Billing_Schema::statements()[0]),'reapply refuses existing table');
 $wpdb->query("ALTER TABLE {$prefix}orders DROP INDEX provider_order");dbreject(fn()=>$store->ready(),'missing provider unique binding fails closed');
 $wpdb->query("ALTER TABLE {$prefix}orders ADD UNIQUE KEY provider_order(provider_order_id)");
 $wpdb->query("ALTER TABLE {$prefix}audit ENGINE=MyISAM");dbreject(fn()=>$store->ready(),'nontransactional audit fails closed');$wpdb->query("ALTER TABLE {$prefix}audit ENGINE=InnoDB");
 $wpdb->query("RENAME TABLE {$prefix}throttle TO {$prefix}missing");dbreject(fn()=>$store->ready(),'missing table fails closed');$wpdb->query("RENAME TABLE {$prefix}missing TO {$prefix}throttle");
 $wpdb->query("UPDATE {$prefix}guard SET schema_version=1");dbreject(fn()=>$store->snapshot(fn()=>true),'wrong schema version fails closed');$wpdb->query("UPDATE {$prefix}guard SET schema_version=2");
 $id=wp_generate_uuid4();$command=hash('sha256','synthetic-sql-command');
 $write=static function($store)use($id){$store->audit('synthetic_probe',0,$id,array('test'=>true));return array('id'=>$id);};
 $fault=static fn($sql)=>$sql==='COMMIT'?'SELECT * FROM nonexistent_synthetic_commit_fault':$sql;
 add_filter('query',$fault);dbreject(fn()=>$store->command($command,array('test'=>1),$write),'injected failed COMMIT rejects command');remove_filter('query',$fault);
 dbcheck((int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}audit")===0&&(int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}commands")===0,'failed COMMIT rolls audit and receipt back');
 $result=$store->command($command,array('test'=>1),$write);dbcheck($result===$store->command($command,array('test'=>1),$write),'real command replay exact');
 dbreject(fn()=>$store->command($command,array('test'=>2),$write),'changed command payload rejected');
 dbcheck((int)$wpdb->get_var("SELECT COUNT(*) FROM {$prefix}audit")===1,'exact replay never duplicates audit');
 dbcheck($wpdb->get_var("SELECT object_id FROM {$prefix}audit")===$id,'UUID audit field retains string type');
 echo "REAL_DATABASE_PROBE_ASSERTIONS=$checks\n";
}finally{
 remove_filter('query',$fault??static fn($v)=>$v);
 foreach(array_merge($tables,array('missing')) as $table){$wpdb->query("DROP TABLE IF EXISTS {$prefix}{$table}");}
 $wpdb->prefix=$original;$wpdb->suppress_errors($old);
}

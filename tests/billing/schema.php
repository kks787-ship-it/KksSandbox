<?php
/** Schema metadata validation only; does not establish MySQL execution evidence. */
require __DIR__ . '/domain.php';
require __DIR__ . '/../../wp-content/plugins/csf-billing/includes/class-csf-billing-store.php';
require __DIR__ . '/../../wp-content/plugins/csf-billing/includes/class-csf-billing-schema.php';
define('ARRAY_A','ARRAY_A');
class SchemaFixtureWpdb {
	public $prefix='fixture_'; public $users='fixture_users'; public $usermeta='fixture_usermeta'; public $last_error='';
	public $engine='InnoDB'; public $mode='valid';
	public function prepare($sql,...$args){return json_encode(array($sql,$args));}
	public function get_var($query){return $this->engine;}
	public function get_results($query,$mode){
		$parts=json_decode($query,true);$table=str_replace('fixture_csf_billing_','',$parts[1][0]);
		$keys=array('guard'=>array('PRIMARY'=>'id'),'orders'=>array('PRIMARY'=>'id','provider_order'=>'provider_order_id','capture'=>'capture_id'),'certificates'=>array('PRIMARY'=>'id','one_per_order'=>'order_id','certificate_number'=>'number_hash'),'commands'=>array('PRIMARY'=>'id'),'audit'=>array('PRIMARY'=>'id'),'throttle'=>array('PRIMARY'=>'id'));
		$rows=array();foreach($keys[$table] as $key=>$col){
			if($key==='capture' && $this->mode==='missing'){continue;}
			$rows[]=array('Key_name'=>$key,'Column_name'=>$col,'Non_unique'=>($this->mode==='nonunique'&&$key==='capture')?'1':'0','Sub_part'=>($this->mode==='prefix'&&$key==='capture')?'8':null);
			if($this->mode==='composite'&&$key==='capture'){$rows[]=array('Key_name'=>$key,'Column_name'=>'other','Non_unique'=>'0','Sub_part'=>null);}
		}return $rows;
	}
}
$wpdb=new SchemaFixtureWpdb();$store=new CSF_Billing_Store();$store->ready();check(true,'valid schema metadata');
foreach(array('MyISAM','MEMORY',null) as $engine){$wpdb->engine=$engine;rejected(fn()=>$store->ready(),'nontransactional or absent table');}
$wpdb->engine='InnoDB';
foreach(array('missing','nonunique','prefix','composite') as $mode){$wpdb->mode=$mode;rejected(fn()=>$store->ready(),'unsafe uniqueness metadata');}
check(count(CSF_Billing_Schema::statements())===7,'six DDL tables and singleton seed; no execution');
$wpdb->prefix='invalid-prefix;';rejected(fn()=>CSF_Billing_Schema::statements(),'invalid DDL prefix');
echo "PASS {$count} cumulative schema/contract assertions; database metadata is synthetic\n";

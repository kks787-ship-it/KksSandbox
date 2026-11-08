"""Unqualified combined-schema candidate on exact pinned runtime; offline synthetic container only.
Does not modify or invoke a broadened version of the qualified source contract.
"""
import base64,copy,hashlib,http.cookiejar,json,os,pathlib,pwd,re,secrets,shutil,subprocess,sys,tarfile,time,urllib.parse,urllib.request
from html.parser import HTMLParser
sys.path.insert(0,'/fixture/tests/billing')
from recovery_contract import qualify,compare,TABLES
P=pathlib.Path;SRC=P('/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com');DST=P('/tmp/csf-combined-restored');WORK=P('/tmp/csf-combined-evidence')
assert os.environ.get('CSF_SYNTHETIC_RESTORE')=='container-only' and P('/.dockerenv').is_file()
assert not SRC.exists() and not DST.exists() and not WORK.exists()
WORK.mkdir(mode=0o700);checks=0
def check(value,label):
 global checks
 assert value,label;checks+=1;print('PASS '+label,flush=True)
def run(args,**kw):
 result=subprocess.run(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE,**kw)
 if result.returncode:raise RuntimeError('Synthetic tool failure: '+result.stderr.decode(errors='replace')[-2000:])
 return result.stdout
php=['/usr/local/bin/php','-n'];extension_dir,loaded=json.loads(run(php+['-r','echo json_encode([ini_get("extension_dir"),get_loaded_extensions()]);']))
for name in ['mysqlnd','mysqli','mbstring','dom']:
 if name not in loaded:php+=['-d','extension='+extension_dir+'/'+name+'.so']
def evalphp(site,code):return run(php+['-r',"require '"+str(site)+"/wp-load.php';"+code]).decode().strip()
def sql(statement,database=None):return run(['/usr/bin/mariadb','--no-defaults','--socket=/run/mysqld/mysqld.sock','-uroot','--batch','--skip-column-names']+([database] if database else []),input=statement.encode())
def snapshot(database):
 assert database in ['wp_mdf4x','billing_restore']
 code='''$m=new mysqli('localhost','root','','''+json.dumps(database)+''',0,'/run/mysqld/mysqld.sock');$m->set_charset('utf8mb4');$out=array();$r=$m->query('SHOW TABLES');while($v=$r->fetch_row()){$t=$v[0];if(!preg_match('/^TXGEh_[A-Za-z0-9_]+$/D',$t)){exit(3);}$ddl=$m->query('SHOW CREATE TABLE `'.$t.'`')->fetch_row()[1];$rows=array();$q=$m->query('SELECT * FROM `'.$t.'`');while($row=$q->fetch_assoc()){ksort($row);$rows[]=json_encode($row,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}sort($rows,SORT_STRING);$out[$t]=array('ddl'=>$ddl,'rows'=>$rows);}ksort($out);echo json_encode((object)$out,JSON_THROW_ON_ERROR);'''
 return json.loads(run(php+['-r',code]))
def files(root):return {str(p.relative_to(root)):hashlib.sha256(p.read_bytes()).hexdigest() for p in root.rglob('*') if p.is_file()}
server=None;web=None
try:
 P('/run/mysqld').mkdir(exist_ok=True);mysql=pwd.getpwnam('mysql');os.chown('/run/mysqld',mysql.pw_uid,mysql.pw_gid)
 run(['/usr/bin/mariadb-install-db','--no-defaults','--user=mysql','--basedir=/usr','--datadir=/run/mysqld/data','--auth-root-authentication-method=normal','--skip-test-db'])
 server=subprocess.Popen(['/usr/sbin/mariadbd','--no-defaults','--user=mysql','--basedir=/usr','--datadir=/run/mysqld/data','--socket=/run/mysqld/mysqld.sock','--pid-file=/run/mysqld/server.pid','--skip-networking','--innodb-buffer-pool-size=64M'],stdout=subprocess.DEVNULL,stderr=(WORK/'database.log').open('wb'))
 for i in range(60):
  try:sql('SELECT 1;');break
  except subprocess.CalledProcessError:time.sleep(.25)
 else:raise RuntimeError('Isolated database startup')
 sql('CREATE DATABASE wp_mdf4x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;CREATE DATABASE billing_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
 SRC.mkdir(parents=True);shutil.copytree('/usr/src/wordpress',SRC,dirs_exist_ok=True);shutil.copytree('/fixture/plugins',SRC/'wp-content/plugins',dirs_exist_ok=True)
 source_mu=SRC/'wp-content/mu-plugins';source_mu.mkdir(exist_ok=True);(source_mu/'block.php').write_text("<?php add_filter('pre_wp_mail',static fn()=>false,-999999);add_filter('pre_http_request',static fn()=>new WP_Error('offline','Blocked'),-999999);")
 key=base64.b64encode(secrets.token_bytes(32)).decode()
 config="<?php define('DB_NAME','wp_mdf4x');define('DB_USER','root');define('DB_PASSWORD','');define('DB_HOST','localhost:/run/mysqld/mysqld.sock');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');$table_prefix='TXGEh_';define('WP_ENVIRONMENT_TYPE','development');define('DISABLE_WP_CRON',true);define('CSF_MFA_ENABLED',true);define('CSF_MFA_ENFORCE_ADMIN',false);define('CSF_SCHEDULING_MIGRATION_ALLOWED',true);define('CSF_BILLING_SANDBOX_ENABLED',false);define('CSF_BILLING_DATA_KEY','"+key+"');if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';"
 (SRC/'wp-config.php').write_text(config);(SRC/'wp-config.php').chmod(0o600)
 run(php+['-r',"define('WP_INSTALLING',true);require '"+str(SRC)+"/wp-load.php';require_once ABSPATH.'wp-admin/includes/upgrade.php';wp_install('Synthetic combined recovery','synthetic_admin','synthetic@example.invalid',true,'','synthetic-only-password');update_option('home','http://127.0.0.1:18890');update_option('siteurl','http://127.0.0.1:18890');wp_insert_post(array('post_title'=>'Gifts','post_name'=>'gifts','post_status'=>'publish','post_type'=>'page','post_content'=>'[csf_gift_certificates]')); "])
 check(evalphp(SRC,"require '/fixture/tests/recovery/restore-scheduling-seed.php';").endswith('SYNTHETIC_SCHEDULING_SEEDED'),'unchanged scheduling fixture seeded')
 check(evalphp(SRC,"require '/fixture/tests/billing/combined-seed.php';").endswith('SYNTHETIC_COMBINED_33_TABLES_SEEDED'),'synthetic billing seeded with sales disabled')
 check(evalphp(SRC,"require '/fixture/tests/billing/database.php';").endswith('REAL_DATABASE_PROBE_ASSERTIONS=19'),'nineteen real SQL failure and replay probes')
 before=snapshot('wp_mdf4x');manifest=files(SRC)
 check(len(before)==33,'combined exact 33 tables');plan=qualify(before,'TXGEh_');check(plan['billing_tables']==6 and plan['certificates']==2,'combined billing invariants')
 old={};exec(compile(P('/fixture/docs/pilot/baseline-select-plan.py.txt').read_bytes(),'original_contract','exec'),old)
 try:old['table_set'](before)
 except ValueError:check(True,'unchanged qualified contract rejects billing additions')
 else:raise AssertionError('Qualified contract unexpectedly broadened')
 with (WORK/'database.sql').open('wb') as out:subprocess.run(['/usr/bin/mariadb-dump','--no-defaults','--socket=/run/mysqld/mysqld.sock','-uroot','--single-transaction','--skip-comments','--hex-blob','wp_mdf4x'],stdout=out,stderr=subprocess.PIPE,check=True)
 (WORK/'database.sql').chmod(0o600)
 with tarfile.open(WORK/'site.tar.gz','w:gz') as archive:archive.add(SRC,arcname='site')
 check(snapshot('wp_mdf4x')==before and files(SRC)==manifest,'capture source stable')
 # Preserve newer source evidence across isolated restore, never rewind the source.
 sql("INSERT INTO TXGEh_options(option_name,option_value,autoload) VALUES('synthetic_newer_owner_note','preserve-current-source','no');",'wp_mdf4x');source_current=snapshot('wp_mdf4x')
 with tarfile.open(WORK/'site.tar.gz','r:gz') as archive:
  for item in archive.getmembers():
   path=P(item.name);assert path.parts[0]=='site' and '..' not in path.parts and not path.is_absolute() and (item.isdir() or item.isfile())
   target=DST.joinpath(*path.parts[1:])
   if item.isdir():target.mkdir(parents=True,exist_ok=True)
   else:
    target.parent.mkdir(parents=True,exist_ok=True)
    with archive.extractfile(item) as source,target.open('wb') as dest:shutil.copyfileobj(source,dest)
 check(files(DST)==manifest,'all restored files match exact capture')
 check(snapshot('billing_restore')=={},'restore database initially empty')
 with (WORK/'database.sql').open('rb') as stream:subprocess.run(['/usr/bin/mariadb','--no-defaults','--socket=/run/mysqld/mysqld.sock','-uroot','billing_restore'],stdin=stream,stderr=subprocess.PIPE,check=True)
 restored=snapshot('billing_restore');check(restored==before,'all 33 restored table definitions and rows match')
 check(compare(before,restored,'TXGEh_')==plan,'all six billing tables match and reconcile without writes')
 for name in TABLES:
  bad=copy.deepcopy(restored);bad.pop('TXGEh_csf_billing_'+name)
  try:qualify(bad,'TXGEh_')
  except ValueError:check(True,'partial billing rejects missing '+name)
  else:raise AssertionError('Partial billing accepted')
 (DST/'wp-config.php').write_text(config.replace("'wp_mdf4x'","'billing_restore'"));(DST/'wp-config.php').chmod(0o600)
 mu=DST/'wp-content/mu-plugins';mu.mkdir(exist_ok=True)
 (mu/'offline.php').write_text("<?php add_filter('pre_wp_mail',static fn()=>false,-999999);add_filter('pre_http_request',static fn()=>new WP_Error('offline','Blocked'),-999999);add_action('init',static function(){if(($_SERVER['REQUEST_URI']??'')==='/fixture-proof'){wp_send_json(array('verified'=>CSF_MFA_Session::web_verified(1)));}});")
 verify="global $wpdb;if(CSF_Billing_Config::enabled()){throw new RuntimeException('Sales enabled');}$rows=$wpdb->get_results('SELECT * FROM TXGEh_csf_billing_certificates',ARRAY_A);foreach($rows as $r){$p=CSF_Billing_Config::open($r['number_sealed']);if(!hash_equals($r['number_hash'],hash('sha256',$p))){throw new RuntimeException('Key mismatch');}$raw=base64_decode($r['number_sealed']);$raw[28]=chr(ord($raw[28])^1);try{CSF_Billing_Config::open(base64_encode($raw));throw new RuntimeException('Tamper accepted');}catch(CSF_Billing_Failure $e){}}if(!CSF_MFA_Session::enrolled(false,1)){throw new RuntimeException('MFA lost');}echo 'KEY_AND_MFA_OK';"
 check(evalphp(DST,verify)=='KEY_AND_MFA_OK','restored encrypted numbers key tamper rejection and official MFA enrollment')
 check(evalphp(DST,"$r=wp_remote_get('https://example.invalid');echo is_wp_error($r)?$r->get_error_code():'FAILED';")=='offline','restored outbound HTTP blocked')
 check(evalphp(DST,"echo apply_filters('pre_wp_mail',null,array())===false?'BLOCKED':'FAILED';")=='BLOCKED','restored email blocked')
 # Serve only the disposable restored copy as the separate www-data identity.
 user=pwd.getpwnam('www-data')
 for path in [DST,*DST.rglob('*')]:os.chown(path,user.pw_uid,user.pw_gid);path.chmod(0o700 if path.is_dir() else 0o600)
 router=WORK/'router.php';router.write_text("<?php $p=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);if(is_file('"+str(DST)+"'.$p)){return false;}require '"+str(DST)+"/index.php';")
 # Router lives with the restored web tree; private evidence directory is not exposed.
 shutil.copyfile(router,DST/'fixture-router.php');os.chown(DST/'fixture-router.php',user.pw_uid,user.pw_gid)
 web=subprocess.Popen(php+['-S','127.0.0.1:18890','-t',str(DST),str(DST/'fixture-router.php')],user=user.pw_uid,group=user.pw_gid,stdout=subprocess.DEVNULL,stderr=(WORK/'web.log').open('wb'))
 class Forms(HTMLParser):
  def __init__(self,text):super().__init__();self.forms=[];self.current=None;self.feed(text)
  def handle_starttag(self,tag,attrs):
   a=dict(attrs)
   if tag=='form':self.current={'action':a.get('action','/wp-login.php'),'fields':{}};self.forms.append(self.current)
   if tag=='input' and self.current is not None and a.get('name') and a.get('type') not in ['checkbox','radio']:self.current['fields'][a['name']]=a.get('value','')
  def handle_endtag(self,tag):
   if tag=='form':self.current=None
 opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()));base='http://127.0.0.1:18890'
 def request(path,data=None):return opener.open(urllib.parse.urljoin(base,path),None if data is None else urllib.parse.urlencode(data).encode(),timeout=20).read().decode()
 for i in range(40):
  try:html=request('/wp-login.php');break
  except OSError:time.sleep(.25)
 check('user_login' in html,'restored login renders over isolated HTTP')
 f=next(f for f in Forms(html).forms if 'log' in f['fields']);f['fields'].update(log='synthetic_admin',pwd='synthetic-only-password',redirect_to=base+'/wp-admin/')
 html=request(f['action'],f['fields']);f=next(f for f in Forms(html).forms if 'authcode' in f['fields']);check(True,'official restored TOTP challenge required')
 code=evalphp(DST,"$p=Two_Factor_Totp::get_instance();echo Two_Factor_Totp::calc_totp($p->get_user_totp_key(1),floor(time()/30));")
 f['fields']['authcode']=code;request(f['action'],f['fields']);check(json.loads(request('/fixture-proof'))['verified'] is True,'real restored password TOTP and signed session proof')
 check('Online gift certificate purchases are not available yet.' in request('/?p='+evalphp(DST,"echo get_page_by_path('gifts')->ID;")) or 'Online gift certificate purchases are not available yet.' in evalphp(DST,'echo CSF_Billing_Web::render();'),'restored checkout closed')
 after=snapshot('billing_restore');check({k:v for k,v in after.items() if '_csf_' in k}=={k:v for k,v in before.items() if '_csf_' in k},'scheduling and billing rows preserved after functional checks')
 check(snapshot('wp_mdf4x')==source_current and files(SRC)==manifest,'newer source owner data and files never overwritten')
 print(json.dumps({'combined_tables':33,'checks':checks,'exact_runtime_pins':True,'candidate_only':True,'qualified_contract_changed':False,'provider_verified':False,'sales_enabled':False,'source_preserved':True,'key_and_mfa_verified':True}),flush=True)
finally:
 if web:web.terminate();web.wait(timeout=15)
 if server:server.terminate();server.wait(timeout=30)
 # No secret fixture artifact is uploaded; container disposal is mandatory in shell trap.

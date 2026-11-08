"""Supplemental rootless recovery rehearsal. Never touches a farm or an existing restore DB."""
import hashlib, json, os, pathlib, shutil, subprocess, tarfile, tempfile
ROOT=pathlib.Path('/tmp/csf-billing-fixture'); REPO=pathlib.Path.cwd().resolve()
PHP=os.environ.get('CSF_BILLING_TEST_PHP',str(ROOT/'php'))
assert PHP in [str(ROOT/'php'),'/tmp/csf-gift-matrix/php8.1','/tmp/csf-gift-matrix/php8.3']
BIN=pathlib.Path('/tmp/csf-gift-matrix/jammy/root/usr/bin') if PHP.startswith('/tmp/csf-gift-matrix/') else pathlib.Path('/tmp/csf-billing-deb/root/usr/bin')
from recovery_contract import qualify,compare
assert (ROOT/'http-evidence.json').exists() and (ROOT/'site/wp-config.php').is_file()
assert REPO.name=='KksSandbox' and (REPO/'tests/billing/fixture.php').exists()
capture=pathlib.Path(tempfile.mkdtemp(prefix='recovery-',dir=ROOT));capture.chmod(0o700)
config=capture/'client.cnf';config.write_text('[client]\nhost=127.0.0.1\nport=13379\nuser=csffixture\npassword=synthetic-local-only\nprotocol=tcp\n');config.chmod(0o600)
snapshot=ROOT/'snapshot.php';snapshot.write_text('''<?php
$db=$argv[1];if(!in_array($db,array('csf_gift_fixture','csf_gift_restore'),true)){exit(2);}
$m=new mysqli('127.0.0.1','csffixture','synthetic-local-only',$db,13379);$m->set_charset('utf8mb4');$out=array();
$r=$m->query('SHOW TABLES');while($t=$r->fetch_row()){$name=$t[0];if(!preg_match('/^gift_fixture_[a-z0-9_]+$/D',$name)){exit(3);}$ddl=$m->query('SHOW CREATE TABLE `'.$name.'`')->fetch_row()[1];$rows=array();$q=$m->query('SELECT * FROM `'.$name.'`');while($row=$q->fetch_assoc()){ksort($row);$rows[]=json_encode($row,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}sort($rows,SORT_STRING);$out[$name]=array('ddl'=>$ddl,'rows'=>$rows);}ksort($out);echo json_encode((object)$out,JSON_THROW_ON_ERROR);
''')
def db_snapshot(name):return json.loads(subprocess.check_output([PHP,str(snapshot),name],text=True))
def digest(data):return hashlib.sha256(json.dumps(data,sort_keys=True,separators=(',',':')).encode()).hexdigest()
assert db_snapshot('csf_gift_restore')=={},'Restore target is not empty; do not overwrite it'
before=db_snapshot('csf_gift_fixture')
plan=qualify(before)
(capture/'before.json').write_text(json.dumps(before));(capture/'before.json').chmod(0o600)
dump=capture/'database.sql'
with dump.open('wb') as stream:subprocess.run([str(BIN/'mariadb-dump'),'--defaults-extra-file='+str(config),'--single-transaction','--skip-lock-tables','--skip-comments','--hex-blob','--no-tablespaces','csf_gift_fixture'],stdout=stream,check=True)
dump.chmod(0o600)
assert before==db_snapshot('csf_gift_fixture'),'Source drift during backup'
allowed=[(ROOT/'site').resolve(),(REPO/'wp-content/plugins/csf-billing').resolve(),(REPO/'wp-content/plugins/csf-mfa').resolve(),(REPO/'.wp-local/mfa-official/two-factor').resolve(),(REPO/'tests/billing/fixture.php').resolve()]
manifest={}
for directory,dirs,files in os.walk(ROOT/'site',followlinks=True):
 for name in files:
  path=pathlib.Path(directory)/name;real=path.resolve()
  assert any(real.is_relative_to(a) for a in allowed),'Unexpected linked source'
  manifest[path.relative_to(ROOT/'site').as_posix()]=hashlib.sha256(path.read_bytes()).hexdigest()
archive=capture/'site.tar.gz'
with tarfile.open(archive,'w:gz',dereference=True) as tar:
 tar.add(ROOT/'site',arcname='site');tar.add(ROOT/'config.php',arcname='fixture-secret-config.php')
archive.chmod(0o600)
recovered=capture/'recovered';recovered.mkdir(mode=0o700)
with tarfile.open(archive,'r:gz') as tar:
 for member in tar.getmembers():
  path=pathlib.PurePosixPath(member.name);assert not path.is_absolute() and '..' not in path.parts and (member.isdir() or member.isfile())
  target=recovered/path
  if member.isdir():target.mkdir(parents=True,exist_ok=True)
  else:
   target.parent.mkdir(parents=True,exist_ok=True)
   with tar.extractfile(member) as source,target.open('wb') as dest:shutil.copyfileobj(source,dest)
   target.chmod(0o600 if member.name.endswith('config.php') else 0o644)
restored_manifest={p.relative_to(recovered/'site').as_posix():hashlib.sha256(p.read_bytes()).hexdigest() for p in (recovered/'site').rglob('*') if p.is_file()}
assert restored_manifest==manifest,'Restored file mismatch'
with dump.open('rb') as stream:subprocess.run([str(BIN/'mariadb'),'--defaults-extra-file='+str(config),'csf_gift_restore'],stdin=stream,check=True)
restored=db_snapshot('csf_gift_restore');assert restored==before,'Restored table/row/DDL mismatch'
assert compare(before,restored)==plan
(capture/'restored.json').write_text(json.dumps(restored));(capture/'restored.json').chmod(0o600)
# Explicit isolation overlays after exact file/database equality has been established.
restore_config=recovered/'fixture-secret-config.php';text=restore_config.read_text().replace("DB_NAME !== 'csf_gift_fixture'","DB_NAME !== 'csf_gift_restore'").replace("define('CSF_BILLING_SANDBOX_ENABLED',true);","define('CSF_BILLING_SANDBOX_ENABLED',false);").replace("define('CSF_BILLING_WEBHOOK_QUALIFIED',true);","define('CSF_BILLING_WEBHOOK_QUALIFIED',false);")
text += "\ndefine('WP_HOME','https://127.0.0.1:18444');\ndefine('WP_SITEURL','https://127.0.0.1:18444');\n";restore_config.write_text(text)
wp_config=recovered/'site/wp-config.php';text=wp_config.read_text().replace("'csf_gift_fixture'","'csf_gift_restore'").replace("/tmp/csf-billing-fixture/config.php",str(restore_config));wp_config.write_text(text)
mu=recovered/'site/wp-content/mu-plugins/recovery-isolation.php';mu.write_text("<?php\nif(DB_NAME!=='csf_gift_restore'||wp_get_environment_type()!=='local'){throw new RuntimeException('Wrong recovery target');}\nadd_filter('pre_wp_mail',static fn()=>true);\nadd_filter('pre_http_request',static fn()=>new WP_Error('isolated_recovery','All external requests blocked'));\n")
code='''if(DB_NAME!=="csf_gift_restore"||CSF_Billing_Config::enabled()){throw new RuntimeException("Wrong recovery target");}global $wpdb;$rows=$wpdb->get_results("SELECT * FROM gift_fixture_csf_billing_certificates",ARRAY_A);foreach($rows as $row){$number=CSF_Billing_Config::open($row["number_sealed"]);if(!preg_match("/^CSF-[A-F0-9]{32}$/D",$number)||(int)$row["redeemed"]>(int)$row["purchased"]){throw new RuntimeException("Restored ledger or key invalid");}}if(!CSF_MFA_Session::ready()||!CSF_MFA_Session::enrolled(false,2)){throw new RuntimeException("Restored MFA enrollment missing");}echo "RESTORED_WORDPRESS_LEDGER_CIPHERTEXT_MFA_READY ".count($rows);'''
result=subprocess.check_output([PHP,'.wp-local/wp-cli.phar','--path='+str(recovered/'site'),'eval',code],text=True).strip()
assert 'RESTORED_WORDPRESS_LEDGER_CIPHERTEXT_MFA_READY' in result
assert {k:v for k,v in db_snapshot('csf_gift_restore').items() if '_csf_billing_' in k}=={k:v for k,v in before.items() if '_csf_billing_' in k},'Functional bootstrap changed financial data'
evidence={'capture_directory':str(capture),'archive_sha256':hashlib.sha256(archive.read_bytes()).hexdigest(),'sql_sha256':hashlib.sha256(dump.read_bytes()).hexdigest(),'file_count':len(manifest),'table_count':len(before),'exact_database_digest':digest(before),'exact_file_restore':True,'exact_database_restore':True,'wordpress_bootstrap':True,'ciphertext_decryption':True,'official_mfa_enrollment':True,'payments_and_webhooks_disabled':True,'outbound_mail_and_http_blocked':True,'scope':'Supplemental disposable native recovery only; not the approved target-bound recovery runner'}
evidence['billing_reconciliation']=plan
(capture/'evidence.json').write_text(json.dumps(evidence,indent=2))
previous=ROOT/'recovery-evidence.json'
if previous.exists():
 old=json.loads(previous.read_text());old_dir=pathlib.Path(old['capture_directory']);assert old_dir.parent==ROOT and old_dir.name.startswith('recovery-')
 if not (old_dir/'evidence.json').exists():(old_dir/'evidence.json').write_text(json.dumps(old,indent=2))
(ROOT/'recovery-evidence.json').write_text(json.dumps(evidence,indent=2));(ROOT/'restored-path.txt').write_text(str(recovered/'site'))
print(result);print('EXACT_RESTORE_FILES='+str(len(manifest))+' TABLES='+str(len(before)))

"""Supplemental rootless native matrix; requires the strictly isolated prepared fixture. Never deploy."""
import os,pathlib,subprocess,time,json,socket
base=pathlib.Path('/tmp/csf-gift-matrix');fixture=pathlib.Path('/tmp/csf-billing-fixture');repo=pathlib.Path.cwd()
assert repo.name=='KksSandbox' and (fixture/'operator.json').exists()
init=base/'accounts.sql';init.write_text("CREATE DATABASE IF NOT EXISTS csf_gift_fixture CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nCREATE USER IF NOT EXISTS 'csffixture'@'127.0.0.1' IDENTIFIED BY 'synthetic-local-only';\nGRANT ALL ON csf_gift_fixture.* TO 'csffixture'@'127.0.0.1';\n")
env=dict(os.environ,LD_LIBRARY_PATH=str(base/'jammy/root/usr/lib/x86_64-linux-gnu'))
evidence=[]
for db in os.environ.get('CSF_MATRIX_DATABASES','mariadb,mysql').split(','):
 assert db in ['mariadb','mysql']
 directory=base/(db+'-data');binary=base/('jammy/root/usr/sbin/mariadbd' if db=='mariadb' else 'mysql/root/usr/sbin/mysqld')
 args=[str(binary),'--no-defaults','--basedir='+str(binary.parent.parent),'--datadir='+str(directory),'--bind-address=127.0.0.1','--port=13379','--socket=','--skip-name-resolve','--pid-file='+str(base/(db+'.pid')),'--log-error='+str(base/(db+'-server.log')),'--init-file='+str(init),'--innodb-buffer-pool-size=64M']
 if db=='mysql':args+=['--mysqlx=OFF','--secure-file-priv=NULL']
 server=subprocess.Popen(args,env=env,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
 children=[]
 try:
  for attempt in range(100):
   if server.poll() is not None:raise RuntimeError(db+' stopped; inspect private server log')
   try:
    with socket.create_connection(('127.0.0.1',13379),timeout=.3):break
   except OSError:time.sleep(.2)
  else:raise RuntimeError('DB timeout')
  # Original synthetic backup only, never a farm export. Current plugins are local symlinks.
  capture=pathlib.Path(json.loads((fixture/'recovery-evidence.json').read_text())['capture_directory'])
  assert capture.parent==fixture and capture.name.startswith('recovery-')
  sql=(capture/'database.sql').read_bytes()
  import hashlib
  assert hashlib.sha256(sql).hexdigest()==json.loads((fixture/'recovery-evidence.json').read_text())['sql_sha256']
  # MariaDB emits a client-only sandbox directive that MySQL CLI does not recognize.
  sql=b'\n'.join(line for line in sql.splitlines() if not line.startswith(b'/*M!999999'))
  client=base/('jammy/root/usr/bin/mariadb' if db=='mariadb' else 'mysql/root/usr/bin/mysql')
  if os.environ.get('CSF_MATRIX_PROBES_ONLY')!='1':
   present=subprocess.check_output([str(client),'--defaults-extra-file='+str(fixture/'client.cnf'),'--batch','--skip-column-names','csf_gift_fixture','-e','SHOW TABLES'],env=env,text=True)
   assert not present.strip(),'Refuse to import over existing fixture tables'
   subprocess.run([str(client),'--defaults-extra-file='+str(fixture/'client.cnf'),'csf_gift_fixture'],input=sql,env=env,check=True)
  for version in ['8.1','8.3']:
   php=base/('php'+version);log=(base/(db+'-php'+version+'.log')).open('w')
   for port in ([] if os.environ.get('CSF_MATRIX_PROBES_ONLY')=='1' else [18080,18081]):children.append(subprocess.Popen([str(php),'-S','127.0.0.1:'+str(port),'-t',str(fixture/'site'),str(fixture/'router.php')],stdout=log,stderr=log))
   if os.environ.get('CSF_MATRIX_PROBES_ONLY')!='1':children.append(subprocess.Popen(['node',str(fixture/'tls.cjs')],stdout=log,stderr=log));time.sleep(1)
   testenv=dict(os.environ,CSF_BILLING_TEST_PHP=str(php))
   with (base/(db+'-php'+version+'-sql.log')).open('w') as out:subprocess.run([str(php),'.wp-local/wp-cli.phar','--path='+str(fixture/'site'),'eval-file','tests/billing/database.php'],stdout=out,stderr=subprocess.STDOUT,check=True)
   if os.environ.get('CSF_MATRIX_PROBES_ONLY')=='1':r=subprocess.CompletedProcess([],0)
   else:
    with (base/(db+'-php'+version+'-http.log')).open('w') as out:r=subprocess.run(['python3','tests/billing/http-integration.py'],env=testenv,stdout=out,stderr=subprocess.STDOUT)
   versions=subprocess.check_output([str(php),'.wp-local/wp-cli.phar','--path='+str(fixture/'site'),'eval','global $wpdb;echo PHP_VERSION." / ".$wpdb->get_var("SELECT VERSION()");'],text=True).strip()
   evidence.append({'cell':db+'-php'+version,'versions':versions,'exit_code':r.returncode});(base/('probe-evidence.json' if os.environ.get('CSF_MATRIX_PROBES_ONLY')=='1' else 'http-matrix-evidence.json')).write_text(json.dumps(evidence,indent=2));print(evidence[-1],flush=True)
   for child in children:child.terminate()
   for child in children:child.wait(timeout=10)
   children=[];log.close()
   if r.returncode:raise RuntimeError('Matrix test failure')
   # Respect the official provider's TOTP replay protection between fresh logins.
   if os.environ.get('CSF_MATRIX_PROBES_ONLY')!='1':time.sleep(31-time.time()%30)
 finally:
  for child in children:child.terminate()
  for child in children:child.wait(timeout=10)
  server.terminate();server.wait(timeout=30)

"""Rootless synthetic billing qualification; requires prepared native runtimes and a prior verified synthetic capture. Never a farm runner."""
import pathlib,subprocess,os,time,socket,json,tempfile,hashlib,fcntl
base=pathlib.Path('/tmp/csf-gift-matrix');fixture=pathlib.Path('/tmp/csf-billing-fixture');root=base/'jammy/root/usr';env=dict(os.environ,PYTHONDONTWRITEBYTECODE='1',LD_LIBRARY_PATH=str(root/'lib/x86_64-linux-gnu'),CSF_BILLING_TEST_PHP=str(base/'php8.3'))
lease=os.open(str(fixture/'recovery-run.lock'),os.O_CREAT|os.O_RDWR|os.O_NOFOLLOW,0o600);fcntl.flock(lease,fcntl.LOCK_EX|fcntl.LOCK_NB)
directory=pathlib.Path(tempfile.mkdtemp(prefix='recovery-qualification-',dir=base));directory.chmod(0o700)
subprocess.run([str(root/'bin/mariadb-install-db'),'--no-defaults','--basedir='+str(root),'--datadir='+str(directory),'--auth-root-authentication-method=normal','--skip-test-db'],env=env,stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL,check=True)
seed_capture=pathlib.Path(json.loads((fixture/'recovery-evidence.json').read_text())['capture_directory']);assert seed_capture.parent==fixture and seed_capture.name.startswith('recovery-')
assert hashlib.sha256((seed_capture/'database.sql').read_bytes()).hexdigest()==json.loads((fixture/'recovery-evidence.json').read_text())['sql_sha256']
init=base/'recovery-new-accounts.sql';init.write_text("CREATE DATABASE csf_gift_fixture CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nCREATE USER 'csffixture'@'127.0.0.1' IDENTIFIED BY 'synthetic-local-only';\nGRANT ALL ON csf_gift_fixture.* TO 'csffixture'@'127.0.0.1';\nCREATE DATABASE csf_gift_restore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nGRANT ALL ON csf_gift_restore.* TO 'csffixture'@'127.0.0.1';\n")
server=subprocess.Popen([str(root/'sbin/mariadbd'),'--no-defaults','--basedir='+str(root),'--datadir='+str(directory),'--bind-address=127.0.0.1','--port=13379','--socket=','--skip-name-resolve','--pid-file='+str(directory/'server.pid'),'--log-error='+str(directory/'server.log'),'--init-file='+str(init),'--innodb-buffer-pool-size=64M'],env=env)
try:
 for i in range(100):
  if server.poll() is not None:raise RuntimeError('Server stopped')
  try:
   with socket.create_connection(('127.0.0.1',13379),timeout=.3):
    if (directory/'server.pid').exists() and int((directory/'server.pid').read_text().strip())==server.pid:break
  except OSError:pass
  time.sleep(.2)
 else:raise RuntimeError('Owned isolated database did not become ready')
 identity=subprocess.check_output([str(root/'bin/mariadb'),'--defaults-extra-file='+str(fixture/'client.cnf'),'--batch','--skip-column-names','csf_gift_fixture','-e',"SELECT @@datadir; SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE();"],env=env,text=True).splitlines()
 assert pathlib.Path(identity[0]).resolve()==directory.resolve() and identity[1]=='0','Refuse wrong server or nonempty source database'
 with (seed_capture/'database.sql').open('rb') as stream:subprocess.run([str(root/'bin/mariadb'),'--defaults-extra-file='+str(fixture/'client.cnf'),'csf_gift_fixture'],stdin=stream,env=env,check=True)
 subprocess.run([str(base/'php8.3'),'.wp-local/wp-cli.phar','--path='+str(fixture/'site'),'eval-file','tests/billing/recovery-seed.php'],env=env,check=True)
 subprocess.run(['python3','tests/billing/recovery.py'],env=env,check=True)
 subprocess.run(['python3','tests/billing/recovery-contract-test.py'],env=env,check=True)
 subprocess.run([str(base/'php8.3'),'tests/billing/recovery-missing-key.php'],env=env,check=True)
 restored=(fixture/'restored-path.txt').read_text()
 subprocess.run([str(base/'php8.3'),'.wp-local/wp-cli.phar','--path='+restored,'eval-file','tests/billing/recovery-key.php'],env=env,check=True)
 if os.environ.get('CSF_BILLING_RECOVERY_BROWSER')=='1':
  subprocess.run(['node','tests/billing/recovery-browser.cjs'],env=dict(env,CSF_TEST_CHROMIUM='/tmp/csf-billing-deb/root/usr/lib/chromium/chromium-headless-shell'),check=True)
 before=json.loads((pathlib.Path(json.loads((fixture/'recovery-evidence.json').read_text())['capture_directory'])/'before.json').read_text())
 after=json.loads(subprocess.check_output([str(base/'php8.3'),str(fixture/'snapshot.php'),'csf_gift_fixture'],env=env,text=True))
 assert before==after,'Source changed during restore qualification'
 restore_after=json.loads(subprocess.check_output([str(base/'php8.3'),str(fixture/'snapshot.php'),'csf_gift_restore'],env=env,text=True))
 assert {k:v for k,v in restore_after.items() if '_csf_billing_' in k}=={k:v for k,v in before.items() if '_csf_billing_' in k},'Qualification mutated restored ledger'
 print('\nSOURCE_DATA_AND_RESTORED_LEDGER_PRESERVED_EXACTLY')
finally:
 server.terminate();server.wait(timeout=30)

"""Offline synthetic WordPress/MariaDB functional recovery; never invoke on a farm.

Container-only, no host mounts or external network; public code is exercised unchanged.
"""
import datetime as dt
import hashlib
import importlib.util
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import time

BASE=Path('/fixture');sys.path.insert(0,str(BASE/'scripts/recovery'))
import backup as b
import build_local_restore as builder


def run(args,**kwargs):return subprocess.run(args,check=True,capture_output=True,**kwargs).stdout

def main():
    assert os.environ.get('CSF_SYNTHETIC_RESTORE')=='container-only'
    assert not Path('/var/www/vhosts').exists()
    source=b.ROOT;source.mkdir(parents=True);shutil.copytree('/usr/src/wordpress',source,dirs_exist_ok=True)
    b.PARENT.mkdir(parents=True);Path('/run/mysqld').mkdir();os.chown('/run/mysqld',__import__('pwd').getpwnam('mysql').pw_uid,__import__('pwd').getpwnam('mysql').pw_gid)
    run(['/usr/bin/mariadb-install-db','--no-defaults','--user=mysql','--basedir=/usr','--datadir=/run/mysqld/data','--auth-root-authentication-method=normal','--skip-test-db'])
    server=subprocess.Popen(['/usr/sbin/mariadbd','--no-defaults','--user=mysql','--basedir=/usr','--datadir=/run/mysqld/data','--socket=/run/mysqld/mysqld.sock','--pid-file=/run/mysqld/server.pid','--skip-networking','--innodb-buffer-pool-size=64M','--innodb-log-file-size=32M'],stdout=subprocess.DEVNULL,stderr=open('/tmp/source-db.log','wb'))
    try:
        def sql(statement):return run(['/usr/bin/mariadb','--no-defaults','--socket=/run/mysqld/mysqld.sock','-uroot','--batch','--raw','--skip-column-names'],input=statement.encode())
        for i in range(60):
            try:sql('SELECT 1;');break
            except subprocess.CalledProcessError:time.sleep(.25)
        sql('CREATE DATABASE wp_mdf4x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
        config="<?php define('DB_NAME','wp_mdf4x');define('DB_USER','root');define('DB_PASSWORD','');define('DB_HOST','localhost:/run/mysqld/mysqld.sock');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');$table_prefix='TXGEh_';define('WP_ENVIRONMENT_TYPE','development');define('DISABLE_WP_CRON',true);define('WP_HOME','"+b.URL+"');define('WP_SITEURL','"+b.URL+"');define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';"
        (source/'wp-config.php').write_text(config)
        install="<?php define('WP_INSTALLING',true);require '"+str(source)+"/wp-load.php';require_once ABSPATH.'wp-admin/includes/upgrade.php';wp_install('Synthetic Farm','synthetic_admin','synthetic@example.invalid',true,'','synthetic-only-password');foreach(['lessons','contact'] as $slug){wp_insert_post(['post_title'=>ucfirst($slug),'post_name'=>$slug,'post_status'=>'publish','post_type'=>'page','post_content'=>'Synthetic recovery fixture']);}update_option('permalink_structure','/%postname%/');"
        Path('/tmp/install.php').write_text(install);run(['/usr/local/bin/php','/tmp/install.php'])
        # Capture parser requires a literal supported host. Runtime WP install used explicit fixture socket.
        (source/'wp-config.php').write_text(config.replace('localhost:/run/mysqld/mysqld.sock','localhost'))
        class DB(b.NativeDB):
            def run(self,binary,args,*extra,**kwargs):return super().run(binary,['--protocol=SOCKET','--socket=/run/mysqld/mysqld.sock',*args],*extra,**kwargs)
        budget=b.Budget(time.time()+240,240);db=DB({'user':'root','password':'','host':'localhost'},budget)
        protected=Path('/tmp/synthetic-protected');protected.write_bytes(b'synthetic private config')
        now=time.time();stamp=lambda v:dt.datetime.fromtimestamp(v,dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
        value={'mode':'capture','source_pin':b.IMPLEMENTATION,'run_token':'a'*32,'actor_ref':'synthetic','approval_ref':'local-fixture','start':stamp(now-5),'end':stamp(now+240),'custody':'local-only','config_reviewed':True,'wp_config_sha256':b.sha((source/'wp-config.php').read_bytes()),'plugins_sha256':b.sha(b'a:0:{}'),'protected':[{'path':str(protected),'name':'synthetic.fixture','sha256':b.sha(protected.read_bytes()),'scope_verified':True}],'db_secret':{'user':'root','password':'','host':'localhost'}}
        receipt=b.capture(value,db,budget);assert receipt['state']=='complete',receipt
        bundle=b.PARENT/('csf-gate-b-backup-'+'a'*32)
        binding={'bundle_id':bundle.name,'manifest_sha256':receipt['manifest_sha256'],'receipt_sha256':b.sha((bundle/'receipt.json').read_bytes()),'recovery_token':'b'*32}
        namespace={'__name__':'synthetic_payload','__file__':'synthetic_payload'};exec(compile(builder.public_payload(),'<synthetic-payload>','exec'),namespace)
        try:namespace['_R']['preflight'](namespace['_RESTORE_LIBRARY'],binding)
        except Exception:
            import traceback;traceback.print_exc();raise
        source_catalog=db.catalog();source_baseline=b.parse_rows(db.query(b.load_generator()(source_catalog)),source_catalog)
        command=builder.command(builder.public_payload(),binding)
        params={'actor_ref':'synthetic','approval_ref':'local-fixture','start':stamp(time.time()-5),'end':stamp(time.time()+240),'authorization':'one-isolated-functional-restore','login_test':'clone-admin'}
        result=subprocess.run(['/bin/bash','-c',command.decode()],input=json.dumps(params).encode(),capture_output=True,timeout=270)
        report=json.loads(result.stdout);print(json.dumps(report,sort_keys=True),flush=True)
        if report.get('restore_verified') is not True:
            # Synthetic-only diagnostic, no farm records or credentials are ever mounted here.
            for log in list((b.PARENT/('csf-gate-b-recovery-'+'b'*32)).rglob('*.log'))+list((b.PARENT/('csf-gate-b-recovery-'+'b'*32)).glob('software-modes.json')):
                print('SYNTHETIC LOG',log.relative_to(b.PARENT),log.read_text(errors='replace')[-5000:],flush=True)
        assert result.returncode==0 and report['restore_verified'] is True
        assert b.verify_receipt(bundle,binding['receipt_sha256'])
        assert not (b.PARENT/('csf-gate-b-recovery-'+'b'*32)/'jail/site/wp-config.php').exists()
        assert not (b.PARENT/('csf-gate-b-recovery-'+'b'*32)/'jail/work/login-input.json').exists()
        assert sql('SELECT COUNT(*) FROM wp_mdf4x.TXGEh_users;').strip()==b'1'
        assert b.inventory(source,b.Budget(time.time()+60,60))==json.loads((bundle/'file-attributes.json').read_text())['before']
        after=b.parse_rows(db.query(b.load_generator()(db.catalog())),db.catalog());assert source_baseline['rows']==after['rows']
        print('PASS synthetic source files/all database row hashes preserved; private clone credentials removed; full recovery verified',flush=True)
        interrupted=dict(binding);interrupted['recovery_token']='c'*32
        short=dict(params);short['start']=stamp(time.time()-1);short['end']=stamp(time.time()+2)
        failed=subprocess.run(['/bin/bash','-c',builder.command(builder.public_payload(),interrupted).decode()],input=json.dumps(short).encode(),capture_output=True,timeout=35)
        outcome=json.loads(failed.stdout);assert failed.returncode==2 and not outcome.get('restore_verified',False)
        remaining=[]
        for process in Path('/proc').iterdir():
            if not process.name.isdigit():continue
            try:
                status=(process/'status').read_text();name=status.split('Name:')[1].split()[0];state=status.split('State:')[1].split()[0]
                if name in ('mariadbd','php') and state!='Z':remaining.append(int(process.name))
            except FileNotFoundError:pass
        assert remaining==[server.pid],remaining
        assert b.verify_receipt(bundle,binding['receipt_sha256'])
        assert not (b.PARENT/('csf-gate-b-recovery-'+'c'*32)/'jail/work/login-input.json').exists()
        print('PASS forced deadline: nonzero/no recovery pass; no clone DB/PHP remains; exact source still verified',flush=True)
    finally:
        server.terminate();server.wait(timeout=15)

if __name__=='__main__':main()

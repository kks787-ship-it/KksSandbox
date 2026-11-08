"""Offline synthetic WordPress/MariaDB functional recovery; never invoke on a farm.

Container-only, no host mounts or external network; public code is exercised unchanged.
"""
import datetime as dt
import gzip
import uuid
import hashlib
import importlib.util
import json
import os
import re
from pathlib import Path
import shutil
import subprocess
import sys
import time

BASE=Path('/fixture');sys.path.insert(0,str(BASE/'scripts/recovery'))
import backup as b
import build_local_restore as builder


def run(args,**kwargs):
    result=subprocess.run(args,capture_output=True,**kwargs)
    if result.returncode:
        print('SYNTHETIC_TOOL_FAILURE',result.stdout.decode(errors='replace')[-1500:],result.stderr.decode(errors='replace')[-1500:],flush=True)
        result.check_returncode()
    return result.stdout

def main():
    assert os.environ.get('CSF_SYNTHETIC_RESTORE')=='container-only'
    assert Path('/.dockerenv').is_file()
    assert not Path('/var/www/vhosts').exists() or not list(Path('/var/www/vhosts').iterdir())
    phase=os.environ.get('CSF_SYNTHETIC_PHASE','pre');assert phase in ('pre','full','partial','damaged')
    source=b.ROOT;source.mkdir(parents=True);shutil.copytree('/usr/src/wordpress',source,dirs_exist_ok=True)
    shutil.copytree('/fixture/plugins',source/'wp-content/plugins',dirs_exist_ok=True)
    php=['/usr/local/bin/php','-n']
    extension_dir,loaded=json.loads(run(php+['-r','echo json_encode([ini_get("extension_dir"),get_loaded_extensions()]);']))
    for name in ('mysqlnd','mysqli','mbstring','dom'):
        if name not in loaded:php+=['-d','extension='+extension_dir+'/'+name+'.so']
    b.PARENT.mkdir(parents=True);Path('/run/mysqld').mkdir(exist_ok=True);os.chown('/run/mysqld',__import__('pwd').getpwnam('mysql').pw_uid,__import__('pwd').getpwnam('mysql').pw_gid)
    run(['/usr/bin/mariadb-install-db','--no-defaults','--user=mysql','--basedir=/usr','--datadir=/run/mysqld/data','--auth-root-authentication-method=normal','--skip-test-db'])
    server=subprocess.Popen(['/usr/sbin/mariadbd','--no-defaults','--user=mysql','--basedir=/usr','--datadir=/run/mysqld/data','--socket=/run/mysqld/mysqld.sock','--pid-file=/run/mysqld/server.pid','--skip-networking','--innodb-buffer-pool-size=64M','--innodb-log-file-size=32M'],stdout=subprocess.DEVNULL,stderr=open('/tmp/source-db.log','wb'))
    try:
        def sql(statement):return run(['/usr/bin/mariadb','--no-defaults','--socket=/run/mysqld/mysqld.sock','-uroot','--batch','--raw','--skip-column-names'],input=statement.encode())
        for i in range(60):
            try:sql('SELECT 1;');break
            except subprocess.CalledProcessError:time.sleep(.25)
        sql('CREATE DATABASE wp_mdf4x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
        config="<?php define('DB_NAME','wp_mdf4x');define('DB_USER','root');define('DB_PASSWORD','');define('DB_HOST','localhost:/run/mysqld/mysqld.sock');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');$table_prefix='TXGEh_';define('WP_ENVIRONMENT_TYPE','development');define('DISABLE_WP_CRON',true);define('CSF_MFA_ENABLED',true);define('CSF_MFA_ENFORCE_ADMIN',false);define('CSF_SCHEDULING_MIGRATION_ALLOWED',true);define('WP_HOME','"+b.URL+"');define('WP_SITEURL','"+b.URL+"');if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';"
        (source/'wp-config.php').write_text(config)
        install="<?php define('WP_INSTALLING',true);require '"+str(source)+"/wp-load.php';require_once ABSPATH.'wp-admin/includes/upgrade.php';wp_install('Synthetic Farm','synthetic_admin','synthetic@example.invalid',true,'','synthetic-only-password');foreach(['lessons','contact'] as $slug){wp_insert_post(['post_title'=>ucfirst($slug),'post_name'=>$slug,'post_status'=>'publish','post_type'=>'page','post_content'=>'Synthetic recovery fixture']);}update_option('permalink_structure','/%postname%/');"
        Path('/tmp/install.php').write_text(install);run(php+['/tmp/install.php'])
        if phase!='pre':
            Path('/tmp/seed.php').write_text("<?php require '"+str(source)+"/wp-load.php';require '/fixture/tests/recovery/restore-scheduling-seed.php';")
            assert run(php+['/tmp/seed.php']).endswith(b'SYNTHETIC_SCHEDULING_SEEDED')
        # Capture parser requires a literal supported host. Runtime WP install used explicit fixture socket.
        (source/'wp-config.php').write_text(config.replace('localhost:/run/mysqld/mysqld.sock','localhost'))
        class DB(b.NativeDB):
            def run(self,binary,args,*extra,**kwargs):return super().run(binary,['--protocol=SOCKET','--socket=/run/mysqld/mysqld.sock',*args],*extra,**kwargs)
        budget=b.Budget(time.time()+240,240);db=DB({'user':'root','password':'','host':'localhost'},budget)
        protected=Path('/tmp/synthetic-protected');protected.write_bytes(b'synthetic private config')
        now=time.time();stamp=lambda v:dt.datetime.fromtimestamp(v,dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')
        value={'mode':'capture','expected_phase':{'pre':'pre_scheduling','full':'scheduling_v4','partial':'partial_migration','damaged':'damaged_scheduling_v4'}[phase],'source_pin':b.IMPLEMENTATION,'run_token':'a'*32,'actor_ref':'synthetic','approval_ref':'local-fixture','start':stamp(now-5),'end':stamp(now+240),'custody':'local-only','config_reviewed':True,'wp_config_sha256':b.sha((source/'wp-config.php').read_bytes()),'plugins_sha256':b.sha(db.guard(None)['plugins'].encode()),'protected':[{'path':str(protected),'name':'synthetic.fixture','sha256':b.sha(protected.read_bytes()),'scope_verified':True}],'db_secret':{'user':'root','password':'','host':'localhost'}}
        receipt=b.capture(value,db,budget);assert receipt['state']=='complete',receipt
        bundle=b.PARENT/('csf-gate-b-backup-'+'a'*32)
        binding={'bundle_id':bundle.name,'manifest_sha256':receipt['manifest_sha256'],'receipt_sha256':b.sha((bundle/'receipt.json').read_bytes()),'recovery_token':'b'*32}
        namespace={'__name__':'synthetic_payload','__file__':'synthetic_payload'};exec(compile(builder.public_payload(),'<synthetic-payload>','exec'),namespace)
        try:namespace['_R']['preflight'](namespace['_RESTORE_LIBRARY'],binding)
        except Exception:
            import traceback;traceback.print_exc();raise
        source_catalog=db.catalog()
        # Simulate newer source owner/authentication data AFTER the backup. Restore must
        # never roll these later changes back, even though its clone uses the old snapshot.
        sql("INSERT INTO wp_mdf4x.TXGEh_usermeta(user_id,meta_key,meta_value) VALUES(1,'synthetic_newer_owner_note','preserve current owner data');")
        if phase!='pre':sql("UPDATE wp_mdf4x.TXGEh_usermeta SET meta_value='newer-owner-auth-revocation-epoch' WHERE user_id=1 AND meta_key='_csf_mfa_epoch';")
        source_baseline=b.parse_rows(db.query(b.load_generator()(source_catalog)),source_catalog)
        command=builder.command(builder.public_payload(),binding)
        params={'actor_ref':'synthetic','approval_ref':'local-fixture','start':stamp(time.time()-5),'end':stamp(time.time()+240),'authorization':'one-isolated-functional-restore','login_test':'clone-admin'}
        result=subprocess.run(['/bin/bash','-c',command.decode()],input=json.dumps(params).encode(),capture_output=True,timeout=270)
        report=json.loads(result.stdout);print(json.dumps(report,sort_keys=True),flush=True)
        if report.get('restore_verified') is not True and phase in ('pre','full'):
            # Synthetic-only diagnostic, no farm records or credentials are ever mounted here.
            for log in list((b.PARENT/('csf-gate-b-recovery-'+'b'*32)).rglob('*.log'))+list((b.PARENT/('csf-gate-b-recovery-'+'b'*32)).glob('software-modes.json')):
                print('SYNTHETIC LOG',log.relative_to(b.PARENT),log.read_text(errors='replace')[-5000:],flush=True)
        assert report['integrity_verified'] is True and report['pages_verified'] is True and report['login_verified'] is True and report['cleanup_verified'] is True
        if phase in ('pre','full'):assert result.returncode==0 and report['restore_verified'] is True
        else:assert result.returncode==2 and report['code']=='SCHEMA_STATE_REQUIRES_REVIEW' and report['restore_verified'] is False
        assert b.verify_receipt(bundle,binding['receipt_sha256'])
        assert not (b.PARENT/('csf-gate-b-recovery-'+'b'*32)/'jail/site/wp-config.php').exists()
        assert not (b.PARENT/('csf-gate-b-recovery-'+'b'*32)/'jail/work/login-input.json').exists()
        assert sql('SELECT COUNT(*) FROM wp_mdf4x.TXGEh_users;').strip()==b'1'
        assert b.inventory(source,b.Budget(time.time()+60,60))==json.loads((bundle/'file-attributes.json').read_text())['before']
        after=b.parse_rows(db.query(b.load_generator()(db.catalog())),db.catalog());assert source_baseline['rows']==after['rows']
        print('PASS phase='+phase+' tables='+str(len({c['table'] for c in source_catalog['columns']}))+' source current auth/owner rows preserved; clone login/logout and restored metadata verified; temporary credentials removed',flush=True)
        repeated=subprocess.run(['/bin/bash','-c',command.decode()],input=json.dumps(params).encode(),capture_output=True,timeout=30)
        assert repeated.returncode==2 and not json.loads(repeated.stdout).get('restore_verified',False)
        print('PASS completed restore destination never reused or overwritten',flush=True)
        if phase=='full':
            def altered(change,reseal=True):
                token=uuid.uuid4().hex;copy=b.PARENT/('csf-gate-b-backup-'+token);shutil.copytree(bundle,copy)
                change(copy)
                rec=json.loads((copy/'receipt.json').read_text());rec['run_token']=token
                if reseal:
                    manifest=''.join(b.sha((copy/name).read_bytes())+'  '+name+'\n' for name in sorted(b.MANIFEST_NAMES)).encode()
                    (copy/'SHA256SUMS').write_bytes(manifest);rec['manifest_sha256']=b.sha(manifest)
                (copy/'receipt.json').write_bytes(b.canonical(rec)+b'\n')
                return {'bundle_id':copy.name,'receipt_sha256':b.sha((copy/'receipt.json').read_bytes()),'manifest_sha256':rec['manifest_sha256'],'recovery_token':uuid.uuid4().hex}
            cases=[('manifest mismatch',lambda p:(p/'SHA256SUMS').write_bytes(b'broken manifest'),False),
                   ('truncated gzip',lambda p:(p/'database.sql.gz').write_bytes((p/'database.sql.gz').read_bytes()[:30]),True),
                   ('unexpected SQL table',lambda p:(p/'database.sql.gz').write_bytes(gzip.compress(gzip.decompress((p/'database.sql.gz').read_bytes())+b'CREATE TABLE TXGEh_unreviewed(id int);')),True)]
            for label,change,reseal in cases:
                invalid=altered(change,reseal)
                try:namespace['_R']['preflight'](namespace['_RESTORE_LIBRARY'],invalid)
                except Exception:pass
                else:raise AssertionError('Damaged backup accepted: '+label)
                assert not (b.PARENT/('csf-gate-b-recovery-'+invalid['recovery_token'])).exists()
                print('PASS '+label+' rejected before clone creation',flush=True)
            def duplicate_row(p):
                sql=gzip.decompress((p/'database.sql.gz').read_bytes())
                row=re.search(rb'INSERT INTO `TXGEh_csf_sched_resources` VALUES[^\n]+',sql)[0]
                (p/'database.sql.gz').write_bytes(gzip.compress(sql+b'\n'+row+b'\n'))
            bad=altered(duplicate_row)
            failure=subprocess.run(['/bin/bash','-c',builder.command(builder.public_payload(),bad).decode()],input=json.dumps(params).encode(),capture_output=True,timeout=90)
            bad_report=json.loads(failure.stdout)
            assert failure.returncode==2 and bad_report['code']=='DATABASE_IMPORT_BLOCKED' and bad_report['cleanup_verified'] is True and bad_report['restore_verified'] is False
            print('PASS database-import failure terminated clone services without modifying source',flush=True)
            stale=altered(lambda p:None)
            old=b.PARENT/stale['bundle_id'];rec=json.loads((old/'receipt.json').read_text());rec['started']=stamp(time.time()-90000);(old/'receipt.json').write_bytes(b.canonical(rec)+b'\n');stale['receipt_sha256']=b.sha((old/'receipt.json').read_bytes())
            assert namespace['_R']['preflight'](namespace['_RESTORE_LIBRARY'],stale)['backup_fresh_at_probe'] is False
            print('PASS stale snapshot explicitly reported; not a fresh deployment prerequisite',flush=True)
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

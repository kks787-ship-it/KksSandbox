"""Disposable local MariaDB10.6 snapshot/restore test. No network, host mounts or farm data."""
import re
import importlib.util
import json
from pathlib import Path
import secrets
import signal
import subprocess
import time

IMAGE='sha256:d29a34d05b1de1afa84c88bdad35cbba6dc1d1135ad2a95a8ceb56a710819e82'
NAME='csf-rbackup-fixture-'+secrets.token_hex(6)


def command(*args,input=None,timeout=20):
    result=subprocess.run(['docker',*args],input=input,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=timeout)
    if result.returncode:raise RuntimeError('Disposable local fixture operation failed: '+result.stderr.decode('utf-8',errors='replace'))
    return result.stdout


def sql(statement,database=None):
    return command('exec','-i',NAME,'mariadb','--no-defaults','--protocol=SOCKET','--default-character-set=utf8mb4','--batch','--raw','--skip-column-names','-uroot',*([database] if database else []),input=statement.encode())


def race(consistent):
    sql('UPDATE fixture_a SET version=1;UPDATE fixture_b SET version=1;','wp_mdf4x')
    flags=['--single-transaction'] if consistent else []
    args=['docker','exec',NAME,'mariadb-dump','--no-defaults','-uroot',*flags,'--default-character-set=utf8mb4','--quick','--skip-lock-tables','--skip-add-locks','--no-tablespaces','--hex-blob','--routines','--events','--triggers','--skip-comments','--skip-dump-date','--skip-extended-insert','--skip-disable-keys','--order-by-primary','--where=IF(id=10000,SLEEP(3)=0,TRUE)','wp_mdf4x']
    proc=subprocess.Popen(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE)
    lines=[]
    try:
        # Once first table rows emerge, the dump transaction is active. Pipe pressure
        # holds the dump within table A; its final fixture row also deliberately sleeps3s
        # so Docker buffering cannot race the writer past the second-table SELECT.
        # The delay/filter exists only in this synthetic test, never in the candidate.
        while True:
            line=proc.stdout.readline()
            if not line:raise RuntimeError('Fixture dump ended before expected snapshot marker')
            lines.append(line)
            if line.startswith(b'INSERT INTO `fixture_a`'):break
        sql('START TRANSACTION;UPDATE fixture_a SET version=2;UPDATE fixture_b SET version=2;COMMIT;','wp_mdf4x')
        rest=proc.stdout.read();error=proc.stderr.read();proc.wait(timeout=20);lines.append(rest)
        if proc.returncode or error:raise RuntimeError('Fixture dump failed')
        data=b''.join(lines)
    finally:
        if proc.poll() is None:proc.kill()
        proc.wait()
    a={int(x) for x in re.findall(rb'INSERT INTO `fixture_a` VALUES \(\d+,(\d+),',data)}
    b={int(x) for x in re.findall(rb'INSERT INTO `fixture_b` VALUES \(\d+,(\d+)\)',data)}
    if a!={1} or b!=({1} if consistent else {2}):raise RuntimeError('Snapshot consistency/negative-control assertion failed')
    return data


def main():
    def stop(*_):raise TimeoutError('Fixture limit')
    signal.signal(signal.SIGALRM,stop);signal.alarm(90);started=False
    try:
        command('run','--rm','--detach','--pull=never','--name',NAME,'--network','none','--read-only',
                '--tmpfs','/var/lib/mysql:rw,nosuid,size=512m','--tmpfs','/run/mysqld:rw,nosuid,size=16m',
                '--tmpfs','/tmp:rw,nosuid,size=64m','--memory','1g','--cpus','1','--pids-limit','128',
                '-e','MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=yes',IMAGE,'--skip-networking');started=True
        for attempt in range(30):
            try:
                if command('exec',NAME,'cat','/proc/1/comm').strip() not in (b'mariadbd',b'mysqld'):raise RuntimeError('Initialization still running')
                sql('SELECT 1;');break
            except RuntimeError:
                if attempt==29:raise
                time.sleep(1)
        sql('CREATE DATABASE wp_mdf4x CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;')
        sql("CREATE TABLE fixture_a(id INT PRIMARY KEY,version INT,payload TEXT) ENGINE=InnoDB;CREATE TABLE fixture_b(id INT PRIMARY KEY,version INT) ENGINE=InnoDB;INSERT INTO fixture_a SELECT seq,1,CONCAT(REPEAT('synthetic',16),CONVERT(0xF09F908E USING utf8mb4)) FROM seq_1_to_10000;INSERT INTO fixture_b VALUES(1,1);",'wp_mdf4x')
        data=race(True);print('PASS consistent single-transaction dump during committed concurrent update',flush=True)
        race(False);print('PASS negative control detects mixed snapshots without single-transaction',flush=True)
        # Routines/events dumps can emit USE for their original DB even with no objects.
        # Preserve the exact name in this isolated disposable daemon; never rewrite a dump.
        switches=re.findall(rb'\bUSE\s+`?([A-Za-z0-9_]+)',data,re.I)
        if any(name!=b'wp_mdf4x' for name in switches):raise RuntimeError('Unexpected fixture DB switch')
        sql('DROP TABLE fixture_a;DROP TABLE fixture_b;','wp_mdf4x')
        command('exec','-i',NAME,'mariadb','--no-defaults','--protocol=SOCKET','--default-character-set=utf8mb4','-uroot','wp_mdf4x',input=data)
        rows=sql('SELECT COUNT(*),MIN(version),MAX(version) FROM fixture_a;SELECT COUNT(*),MIN(version),MAX(version) FROM fixture_b;','wp_mdf4x')
        if b'10000\t1\t1' not in rows or b'1\t1\t1' not in rows:raise RuntimeError('Fixture artifact import mismatch')
        unicode=sql('SELECT HEX(RIGHT(payload,1)) FROM fixture_a WHERE id=1;','wp_mdf4x')
        if b'F09F908E' not in unicode:raise RuntimeError('Fixture UTF8MB4 import mismatch')
        print('PASS isolated synthetic import verifies 10000+1 snapshot rows and UTF8MB4',flush=True)
        source=Path(__file__).resolve().parents[2]/'scripts/recovery/backup.py'
        spec=importlib.util.spec_from_file_location('local_backup_fixture',source);b=importlib.util.module_from_spec(spec);spec.loader.exec_module(b)
        sql('DROP TABLE fixture_a;DROP TABLE fixture_b;','wp_mdf4x')
        for name in sorted(b.NATIVE):
            columns='synthetic_id INT PRIMARY KEY,option_name VARCHAR(100),option_value LONGBLOB' if name=='options' else 'synthetic_id INT PRIMARY KEY,payload VARBINARY(16)'
            sql('CREATE TABLE `'+b.PREFIX+name+'` ('+columns+') ENGINE=InnoDB;','wp_mdf4x')
            if name!='options':sql('INSERT INTO `'+b.PREFIX+name+'` VALUES(1,0xFF00),(2,NULL);','wp_mdf4x')
        sql("INSERT INTO TXGEh_options VALUES(1,'users_can_register','0');",'wp_mdf4x')
        db=b.NativeDB({'user':'root','password':'','host':'localhost'},b.Budget(time.time()+60,60))
        db.query=lambda statement:sql(statement,'wp_mdf4x')
        catalog=db.catalog();plan=b.load_generator()(catalog);before=b.parse_rows(db.query(plan),catalog)
        if len(before['rows'])!=23:raise RuntimeError('Native SELECT plan fixture row mismatch')
        print('PASS native twelve-table catalog, READ ONLY snapshot and byte framing execute',flush=True)
        sql("UPDATE TXGEh_options SET option_value='1' WHERE synthetic_id=1;",'wp_mdf4x')
        after=b.parse_rows(db.query(plan),db.catalog())
        policy=json.loads((source.parents[2]/'docs/pilot/baseline-contract.json').read_text())
        diff=b.drift(before,after,policy)
        if diff['counts']['critical']!=1 or not diff['review_required']:raise RuntimeError('Native critical drift fixture mismatch')
        print('PASS native critical option drift retained for review with unknown attribution',flush=True)

    finally:
        signal.alarm(0)
        if started:command('rm','--force',NAME,timeout=15)
        print('CLEANUP isolated local fixture only',flush=True)

if __name__=='__main__':main()

"""Recovery-qualified scheduling files ONLY. No activation, schema, account or MFA writes.

Library b, private binding and pinned public entries supplied by local build orchestration.
No target override, credentials, arbitrary file list or SQL accepted from remote stdin.
"""
import ctypes
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import stat
import subprocess
import tempfile
import time

PIN='2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1'
BASE='wp-content/plugins/csf-scheduling/'
PHP='/opt/plesk/php/8.3/bin/php'

class Blocked(Exception):pass

def require(ok):
    if not ok:raise Blocked()

def source_files(entries):
    require(isinstance(entries,list) and len(entries)==20)
    seen=set()
    for row in entries:
        require(set(row)=={'path','sha256','text'})
        name=row['path'];require(isinstance(name,str) and name.startswith(BASE))
        relative=name[len(BASE):]
        require(re.fullmatch(r'[a-zA-Z0-9_./-]+',relative) and all(p not in ('','..','.') for p in relative.split('/')))
        require(name not in seen);seen.add(name)
        require(hashlib.sha256(row['text'].encode()).hexdigest()==row['sha256'])
    require(sum(len(row['text'].encode()) for row in entries)<2*1024**2)
    require(BASE+'csf-scheduling.php' in seen)
    return {row['path'][len(BASE):]:row for row in entries}

def recovery(b,binding):
    require(set(binding)=={'bundle_id','manifest_sha256','receipt_sha256','recovery_token'})
    require(re.fullmatch('csf-gate-b-backup-[a-f0-9]{32}',binding['bundle_id']))
    require(re.fullmatch('[a-f0-9]{32}',binding['recovery_token']))
    for key in ('manifest_sha256','receipt_sha256'):require(re.fullmatch('[a-f0-9]{64}',binding[key]))
    parent=b.PARENT;require(parent.resolve(strict=True)==parent)
    source=parent/binding['bundle_id'];require(source.resolve(strict=True)==source)
    b.verify_receipt(source,binding['receipt_sha256'])
    require(b.sha((source/'SHA256SUMS').read_bytes())==binding['manifest_sha256'])
    receipt=json.loads((source/'receipt.json').read_text())
    require(0<=time.time()-b.utc(receipt['started'])<86400)
    drill=parent/('csf-gate-b-recovery-'+binding['recovery_token']);require(drill.resolve(strict=True)==drill)
    require(json.loads((drill/'private-input-binding.json').read_text())==binding)
    recovered=json.loads((drill/'restore-result.json').read_text())
    require(recovered['state']=='verified' and all(recovered[k] is True for k in ('restore_verified','integrity_verified','isolation_verified','pages_verified','login_verified','cleanup_verified')))
    return source

def target_guard(b,budget,source):
    root=b.ROOT;require(root.resolve(strict=True)==root)
    config=b.read_regular(root/'wp-config.php',16*1024**2,budget)[0]
    secret=b.validate_config(config,credentials=True)
    db=b.NativeDB(secret,budget)
    captured=json.loads((source/'target.json').read_text())
    before=db.guard(b.sha(captured['before_guard']['plugins'].encode()))
    require('csf-scheduling/' not in before['plugins'] and 'csf-mfa/' not in before['plugins'] and 'two-factor/' not in before['plugins'] and 'csf-emergency/' not in before['plugins'])
    parent=root/'wp-content/plugins';require(parent.resolve(strict=True)==parent and parent.is_dir())
    destination=parent/'csf-scheduling';require(not destination.exists() and not destination.is_symlink())
    require(b.free_bytes(parent)>2*1024**3)
    return db,before,parent,destination,hashlib.sha256(config).hexdigest()

def verify_tree(directory,entries):
    observed=set()
    for root,dirs,files in os.walk(directory,followlinks=False):
        require(all(not (Path(root)/d).is_symlink() for d in dirs))
        for name in files:
            path=Path(root)/name;require(path.is_file() and not path.is_symlink() and path.stat().st_nlink==1)
            relative=path.relative_to(directory).as_posix();require(relative in entries)
            require(hashlib.sha256(path.read_bytes()).hexdigest()==entries[relative]['sha256']);observed.add(relative)
    require(observed==set(entries))

def publish(temp,destination):
    # Linux no-replace rename: a concurrently created target must never be replaced.
    libc=ctypes.CDLL(None,use_errno=True);fn=libc.renameat2
    fn.argtypes=[ctypes.c_int,ctypes.c_char_p,ctypes.c_int,ctypes.c_char_p,ctypes.c_uint];fn.restype=ctypes.c_int
    require(fn(-100,os.fsencode(temp),-100,os.fsencode(destination),1)==0)

def run(b,binding,entries,mode):
    require(mode in ('preflight','stage-inactive'))
    entries=source_files(entries);budget=b.Budget(time.time()+115,115)
    source=recovery(b,binding);db,before,parent,destination,config_hash=target_guard(b,budget,source)
    for row in entries.values():
        budget.check()
        if row['path'].endswith('.php'):
            result=subprocess.run([PHP,'-n','-l'],input=row['text'].encode(),stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=min(10,budget.remaining()),env={'PATH':'/usr/bin:/bin'})
            require(result.returncode==0)
    if mode=='preflight':return {'state':'preflight-pass','files':20,'backup_fresh':True,'recovery_verified':True,'php_lint_passed':True,'writes':False}
    # Same-filesystem private staging; nothing under a plugin loader path until verified.
    require(source.parent.stat().st_dev==parent.stat().st_dev)
    temp=Path(tempfile.mkdtemp(prefix='csf-scheduling-files-',dir=source.parent));published=False
    try:
        owner=parent.stat()
        for relative,row in entries.items():
            budget.check();path=temp/relative;path.parent.mkdir(parents=True,exist_ok=True)
            fd=os.open(path,os.O_WRONLY|os.O_CREAT|os.O_EXCL|os.O_NOFOLLOW,0o644)
            with os.fdopen(fd,'wb') as output:output.write(row['text'].encode());output.flush();os.fsync(output.fileno())
            os.chown(path,owner.st_uid,owner.st_gid);os.chmod(path,0o644)
        verify_tree(temp,entries)
        for root,dirs,files in os.walk(temp):os.chown(root,owner.st_uid,owner.st_gid);os.chmod(root,0o755)
        require(db.guard(None)==before)
        require(b.sha(b.read_regular(b.ROOT/'wp-config.php',16*1024**2,budget)[0])==config_hash)
        publish(temp,destination);published=True
        verify_tree(destination,entries)
        # No automatic deactivation/removal if an independent owner change races publication.
        require(db.guard(None)==before)
        require(b.sha(b.read_regular(b.ROOT/'wp-config.php',16*1024**2,budget)[0])==config_hash)
        recovery(b,binding)
        return {'state':'staged-inactive','files':20,'hashes_verified':True,'php_lint_passed':True,'activation_performed':False,'database_writes':False,'configuration_writes':False,'backup_fresh':True,'recovery_verified':True}
    finally:
        if not published and temp.exists():shutil.rmtree(temp)

def main(b,binding,entries,mode):
    os.umask(0o077)
    try:result=run(b,binding,entries,mode);code=0
    except Exception:result={'state':'blocked','code':'STAGING_GUARD_FAILED','activation_performed':False,'database_writes':False};code=2
    print(json.dumps(result,sort_keys=True));return code

"""Local-only engineered R-Backup payload. No implicit execution, transfer or restore.

CLI requires private JSON on stdin and explicit --inspect or --capture. Tests inject a
synthetic scope/DB; CLI never accepts a target override. All errors are sanitized.
"""
import argparse
import contextlib
import datetime as dt
import gzip
import hashlib
import io
import json
import os
from pathlib import Path, PurePosixPath
import re
import resource
import signal
import stat
import subprocess
import sys
import tarfile
import tempfile
import time

ROOT = Path('/var/www/vhosts/cornerstonefarmomak.com/sandbox.cornerstonefarmomak.com')
PARENT = Path('/var/www/vhosts/cornerstonefarmomak.com/private')
URL = 'https://sandbox.cornerstonefarmomak.com'
DATABASE = 'wp_mdf4x'
PREFIX = 'TXGEh_'
IMPLEMENTATION = '2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1'
MAX_BYTES = 2 * 1024**3
MAX_DB = 1024**3
MAX_FILES = 100000
MANIFEST_NAMES = frozenset('database.sql.gz wp-content.tar.gz wordpress-root.tar.gz wp-config.php.recovery protected-configs.tar.gz target.json file-attributes.json before.catalog.json before.rows.ndjson after.catalog.json after.rows.ndjson before-files.sha256 after-files.sha256 capture-start-utc.txt capture-end-utc.txt drift-review.json'.split())
BASE = Path(__file__).resolve().parents[2]
NATIVE = {'commentmeta','comments','links','options','postmeta','posts','term_relationships','term_taxonomy','termmeta','terms','usermeta','users'}

class Stop(Exception):
    """No private diagnostic text crosses this exception boundary."""


def require(condition):
    if not condition:
        raise Stop('guard failed')


def canonical(value):
    return json.dumps(value, sort_keys=True, separators=(',', ':'), ensure_ascii=True, allow_nan=False).encode()


def sha(data):
    return hashlib.sha256(data).hexdigest()


def timestamp():
    return dt.datetime.now(dt.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ')


def utc(value):
    require(isinstance(value,str) and re.fullmatch(r'\d{4}-\d\d-\d\dT\d\d:\d\d:\d\dZ',value))
    return dt.datetime.strptime(value,'%Y-%m-%dT%H:%M:%SZ').replace(tzinfo=dt.timezone.utc).timestamp()


class Budget:
    def __init__(self, end, seconds=1770):
        self.end = end
        self.deadline = time.monotonic()+min(seconds,1770)
        self.disk_parent=None
    def check(self):
        require(time.time() < self.end and time.monotonic() < self.deadline)
        if self.disk_parent is not None:require(free_bytes(self.disk_parent)>=2*1024**3)
    def remaining(self):
        self.check()
        return max(.001,min(self.end-time.time(), self.deadline-time.monotonic()))


def safe_name(name):
    require(isinstance(name,str) and name and not any(ord(c)<32 or ord(c)==127 for c in name))
    require('\\' not in name and not name.startswith('/'))
    parts = name.rstrip('/').split('/')
    require(all(p not in ('','..') for p in parts))
    parts = [p for p in parts if p != '.']
    require(parts)
    return '/'.join(parts)


def validate_archive(path, allowed=None, max_bytes=MAX_BYTES, max_entries=MAX_FILES*2, budget=None, expected_files=None):
    """Read effective PAX/GNU headers, never extract. Reject unknown metadata/type overrides."""
    seen = {}; parents_with_children=set(); total = 0
    class BoundedTarInfo(tarfile.TarInfo):
        def _proc_pax(self,archive):
            if budget:budget.check()
            require(self.size<=1024*1024)
            return super()._proc_pax(archive)
        def _proc_gnulong(self,archive):
            if budget:budget.check()
            require(self.size<=1024*1024)
            return super()._proc_gnulong(archive)
        def _proc_sparse(self,archive):
            raise Stop('sparse metadata forbidden')
    with tarfile.open(path,'r:gz',tarinfo=BoundedTarInfo) as archive:
        # Stream iteration avoids loading an unbounded list of entries.
        for entry in archive:
            if budget: budget.check()
            name = safe_name(entry.name)
            require(name not in seen and len(seen)<max_entries)
            require(entry.type in (tarfile.REGTYPE,tarfile.AREGTYPE,tarfile.DIRTYPE))
            require(not entry.issparse() and not entry.linkname)
            require(set(entry.pax_headers) <= {'path','mtime','atime','ctime','uid','gid','uname','gname','size'})
            require(entry.size >= 0 and (entry.isfile() or entry.size==0))
            if allowed is not None: require(name in allowed)
            for parent in PurePosixPath(name).parents:
                if str(parent)!='.':
                    require(seen.get(str(parent))!='file');parents_with_children.add(str(parent))
            if entry.isfile(): require(name not in parents_with_children)
            seen[name] = 'file' if entry.isfile() else 'dir'
            total += entry.size
            require(total<=max_bytes)
            if entry.isfile() and expected_files is not None:
                require(name in expected_files and entry.size==expected_files[name]['size'])
                digest=hashlib.sha256()
                with archive.extractfile(entry) as stream:
                    while True:
                        if budget:budget.check()
                        chunk=stream.read(1024*1024)
                        if not chunk:break
                        digest.update(chunk)
                require(digest.hexdigest()==expected_files[name]['sha256'])
        require(allowed is None or set(seen)==set(allowed))
        end_of_members=archive.offset
        # Consume the gzip tail too: tar EOF alone does not validate CRC/truncation.
    with gzip.open(path,'rb') as stream:
        consumed = 0
        while True:
            if budget: budget.check()
            chunk=stream.read(1024*1024)
            if not chunk: break
            tail_start=max(0,end_of_members-consumed)
            if tail_start<len(chunk):require(not any(chunk[tail_start:]))
            consumed+=len(chunk)
            require(consumed<=max_bytes+max_entries*2048+10240)
    return {'entries':len(seen),'bytes':total}


def regular(path):
    path=Path(path)
    require(path.is_absolute() and path.resolve(strict=True)==path)
    info=path.lstat()
    require(stat.S_ISREG(info.st_mode) and info.st_nlink==1)
    return info


def stable_stat(info):
    return (info.st_dev,info.st_ino,info.st_mode,info.st_nlink,info.st_uid,info.st_gid,info.st_size,info.st_mtime_ns,info.st_ctime_ns)


def read_regular(path, limit, budget):
    before=regular(path);require(before.st_size<=limit);budget.check()
    fd=os.open(path,os.O_RDONLY|os.O_NOFOLLOW)
    try:
        require(stable_stat(os.fstat(fd))==stable_stat(before))
        with os.fdopen(fd,'rb',closefd=False) as stream:
            data=stream.read(limit+1)
        require(len(data)<=limit and stable_stat(os.fstat(fd))==stable_stat(before))
    finally: os.close(fd)
    require(stable_stat(regular(path))==stable_stat(before));budget.check()
    return data,before


def walk_error(_):
    raise Stop('unreadable scope')


def hash_regular(path,limit,budget):
    before=regular(path);require(before.st_size<=limit);budget.check()
    digest=hashlib.sha256();fd=os.open(path,os.O_RDONLY|os.O_NOFOLLOW)
    try:
        require(stable_stat(os.fstat(fd))==stable_stat(before))
        with os.fdopen(fd,'rb',closefd=False) as inp:
            while True:
                budget.check();chunk=inp.read(1024*1024)
                if not chunk:break
                digest.update(chunk)
        require(stable_stat(os.fstat(fd))==stable_stat(before))
    finally:os.close(fd)
    require(stable_stat(regular(path))==stable_stat(before))
    return digest.hexdigest(),before


def inventory(root,budget,max_bytes=MAX_BYTES):
    require(root.resolve(strict=True)==root and root.is_dir())
    device=root.stat().st_dev;result={};total=0
    for parent,dirs,files in os.walk(root,followlinks=False,onerror=walk_error):
        budget.check()
        for name in dirs:
            p=Path(parent)/name;i=p.lstat()
            require(stat.S_ISDIR(i.st_mode) and i.st_dev==device)
        for name in sorted(files):
            p=Path(parent)/name;relative=p.relative_to(root).as_posix();require(safe_name(relative)==relative and not relative.startswith(' '))
            digest,i=hash_regular(p,max_bytes-total,budget)
            require(i.st_dev==device)
            total+=i.st_size;require(total<=max_bytes and len(result)<MAX_FILES)
            result[relative]={'sha256':digest,'size':i.st_size,'mode':stat.S_IMODE(i.st_mode),'uid':i.st_uid,'gid':i.st_gid,'mtime_ns':i.st_mtime_ns}
    return dict(sorted(result.items()))


def validate_gzip(path,budget,limit=MAX_DB*2):
    total=0
    with gzip.open(path,'rb') as inp:
        while True:
            budget.check();chunk=inp.read(1024*1024)
            if not chunk:break
            total+=len(chunk);require(total<=limit)
    return total


def validate_config(data):
    """Conservative literal target check, never PHP evaluation or credential extraction.

    Opaque string tokens avoid treating commented/quoted defines as real calls. Conditional,
    dynamic, duplicate or unsupported target declarations fail closed for private review.
    """
    text=data.decode('utf-8');require(text.startswith('<?php'));text=text[5:]
    tokens=re.findall(r"'(?:[^'\\]|\\.)*'|\"(?:[^\"\\]|\\.)*\"|/\*[\s\S]*?\*/|//[^\n]*|\#[^\n]*|[A-Za-z_][A-Za-z0-9_]*|[^\s]",text)
    tokens=[t for t in tokens if not t.startswith(('/*','//','#'))]
    def literal(t):
        require(len(t)>=2 and t[0] in "'\"" and t[-1]==t[0])
        value=t[1:-1]
        if t[0]=='"':require('\\' not in value and '$' not in value)
        else:value=re.sub(r"\\(['\\])",r'\1',value)
        return value
    wanted={'DB_NAME':DATABASE,'DB_HOST':None,'WP_ENVIRONMENT_TYPE':'development'}
    observed={};depth=0;statement=[];prefixes=0
    for i,t in enumerate(tokens):
        if t=='{':depth+=1;statement=[]
        elif t=='}':depth-=1;statement=[]
        if t=='define' and i+5<len(tokens) and tokens[i+1]=='(' and tokens[i+2][0] in "'\"":
            name=literal(tokens[i+2])
            if name in wanted:
                require(depth==0 and not statement and name not in observed)
                require(tokens[i+3]==',' and tokens[i+5]==')')
                observed[name]=literal(tokens[i+4])
        if t=='table_prefix':
            require(depth==0 and i>0 and tokens[i-1]=='$' and tokens[i+1]=='=' and tokens[i+3]==';')
            require(statement==['$'])
            require(literal(tokens[i+2])==PREFIX);prefixes+=1
        if t in (';','{','}'):statement=[]
        else:statement.append(t)
    require(prefixes==1 and set(observed)==set(wanted))
    require(observed['DB_NAME']==DATABASE and observed['WP_ENVIRONMENT_TYPE']=='development')
    require(observed['DB_HOST'] in ('localhost','localhost:3306','127.0.0.1','127.0.0.1:3306'))


def private_write(path,data):
    fd=os.open(path,os.O_WRONLY|os.O_CREAT|os.O_EXCL|os.O_NOFOLLOW,0o600)
    with os.fdopen(fd,'wb') as out: out.write(data);out.flush();os.fsync(out.fileno())


def write_json(path,value): private_write(path,canonical(value)+b'\n')


def schema_digest(catalog):
    return sha(canonical({'columns':sorted(catalog['columns'],key=lambda r:(r['table'],r['ordinal'])),
                          'indexes':sorted(catalog['indexes'],key=lambda r:(r['table'],r['name'],r['sequence']))}))


def parse_rows(data,catalog):
    require(len(data)<=64*1024**2)
    values=[json.loads(line) for line in data.splitlines()];require(0<len(values)<=250001)
    header=values.pop(0)
    require(header.get('database')==DATABASE and header.get('prefix')==PREFIX and header.get('version')==1)
    seen=set()
    for r in values:
        require(set(r)=={'table','key_hash','value_hash','label'} and r['table'] in NATIVE)
        require(all(isinstance(r[k],str) and re.fullmatch('[0-9a-f]{64}',r[k]) for k in ('key_hash','value_hash')))
        require(r['label'] is None if r['table']!='options' else isinstance(r['label'],str))
        key=(r['table'],r['key_hash']);require(key not in seen);seen.add(key)
    return {'schema_digest':schema_digest(catalog),'header':header,'rows':values}


def drift(before,after,policy):
    old={(r['table'],r['key_hash']):r for r in before['rows']};new={(r['table'],r['key_hash']):r for r in after['rows']}
    changes=[];counts={'critical':0,'unclassified':0,'volatile_candidate':0}
    for key in sorted(old.keys()|new.keys()):
        a,b=old.get(key),new.get(key)
        if a==b:continue
        cats=[]
        for r in (a,b):
            if r is None:continue
            label=r['label'] or ''
            critical={n.replace('{prefix}',PREFIX) for n in policy['critical_options']}
            cat='critical' if r['table']!='options' or label in critical or label.startswith(tuple(policy['critical_option_prefixes'])) else 'volatile_candidate' if label in policy['volatile_candidate_options'] else 'unclassified'
            cats.append(cat)
        cat='critical' if 'critical' in cats else 'unclassified' if 'unclassified' in cats else 'volatile_candidate'
        counts[cat]+=1;changes.append({'table':key[0],'locator':key[1],'category':cat,'kind':'added' if a is None else 'removed' if b is None else 'modified'})
    schema_changed=before['schema_digest']!=after['schema_digest']
    return {'counts':counts,'schema_changed':schema_changed,'review_required':bool(changes) or schema_changed,'changes':changes,'attribution':'unknown'}


class NativeDB:
    """No shell, no WordPress bootstrap, no defaults inheritance, no credential tempfile."""
    def __init__(self,secret,budget,client='/usr/bin/mariadb',dump='/usr/bin/mariadb-dump'):
        require(set(secret)=={'user','password','host'})
        require(secret['host'] in ('localhost','127.0.0.1'))
        self.secret=secret;self.budget=budget;self.client=client;self.dumper=dump
    def run(self,binary,args,sql=None,output=None):
        # Linux inherited anonymous descriptor. No persistent credential file or environment.
        require(hasattr(os,'memfd_create'))
        fd=os.memfd_create('csf-client',0)
        def quote(v):
            require(isinstance(v,str) and not any(c in v for c in '\0\r\n'))
            return '"'+v.replace('\\','\\\\').replace('"','\\"')+'"'
        body='[client]\n'+''.join(k+'='+quote(self.secret[k])+'\n' for k in ('user','password','host'))+'port=3306\n'
        os.write(fd,body.encode());os.lseek(fd,0,0)
        try:
            # All I/O is anonymous and private; poll bounds even while dump output grows.
            with contextlib.ExitStack() as stack:
                stdout=stack.enter_context(os.fdopen(os.memfd_create('csf-output',0),'w+b'))
                stderr=stack.enter_context(os.fdopen(os.memfd_create('csf-error',0),'w+b'))
                stdin=stack.enter_context(os.fdopen(os.memfd_create('csf-input',0),'w+b'))
                if sql is not None: stdin.write(sql)
                stdin.seek(0)
                output_limit=MAX_DB*2 if output is not None else 64*1024**2
                def child_limits():
                    current=resource.getrlimit(resource.RLIMIT_FSIZE)[1]
                    bound=output_limit if current==resource.RLIM_INFINITY else min(current,output_limit)
                    resource.setrlimit(resource.RLIMIT_FSIZE,(bound,bound))
                proc=subprocess.Popen([binary,'--defaults-file=/proc/self/fd/'+str(fd),*args],
                                      stdin=stdin, stdout=output if output is not None else stdout,
                                      stderr=stderr, pass_fds=(fd,), preexec_fn=child_limits, env={'PATH':'/usr/bin:/bin','LC_ALL':'C','TZ':'UTC'})
                try:
                    while proc.poll() is None:
                        self.budget.check()
                        require(os.fstat(stderr.fileno()).st_size<=1024*1024)
                        observed=output if output is not None else stdout
                        require(os.fstat(observed.fileno()).st_size<(MAX_DB*2 if output is not None else 64*1024**2))
                        if output is not None:
                            require(free_bytes(PARENT)>=2*1024**3)
                        time.sleep(min(.05,self.budget.remaining()))
                    require(proc.returncode==0 and os.fstat(stderr.fileno()).st_size<=1024*1024)
                    observed=output if output is not None else stdout
                    require(os.fstat(observed.fileno()).st_size<(MAX_DB*2 if output is not None else 64*1024**2))
                    stdout.seek(0)
                    return stdout.read() if output is None else None
                finally:
                    if proc.poll() is None: proc.kill()
                    proc.wait()
        finally: os.close(fd)
    def query(self,sql):
        return self.run(self.client,['--batch','--raw','--skip-column-names','--default-character-set=utf8mb4','--database='+DATABASE],sql.encode())
    def catalog(self):
        columns=self.query("SELECT JSON_OBJECT('table',c.TABLE_NAME,'column',c.COLUMN_NAME,'ordinal',c.ORDINAL_POSITION,'type',c.COLUMN_TYPE,'nullable',c.IS_NULLABLE,'default',c.COLUMN_DEFAULT,'extra',c.EXTRA,'charset',c.CHARACTER_SET_NAME,'collation',c.COLLATION_NAME,'generated',c.GENERATION_EXPRESSION,'engine',t.ENGINE,'table_collation',t.TABLE_COLLATION,'pk_order',s.SEQ_IN_INDEX) FROM information_schema.COLUMNS c JOIN information_schema.TABLES t ON t.TABLE_SCHEMA=c.TABLE_SCHEMA AND t.TABLE_NAME=c.TABLE_NAME LEFT JOIN information_schema.STATISTICS s ON s.TABLE_SCHEMA=c.TABLE_SCHEMA AND s.TABLE_NAME=c.TABLE_NAME AND s.COLUMN_NAME=c.COLUMN_NAME AND s.INDEX_NAME='PRIMARY' WHERE c.TABLE_SCHEMA=DATABASE() ORDER BY c.TABLE_NAME,c.ORDINAL_POSITION;")
        indexes=self.query("SELECT JSON_OBJECT('table',TABLE_NAME,'name',INDEX_NAME,'sequence',SEQ_IN_INDEX,'column',COLUMN_NAME,'non_unique',NON_UNIQUE,'prefix_length',SUB_PART,'collation',COLLATION,'kind',INDEX_TYPE) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,INDEX_NAME,SEQ_IN_INDEX;")
        counters=self.query("SELECT JSON_OBJECT('table',TABLE_NAME,'next_id',AUTO_INCREMENT) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME;")
        return {'columns':[json.loads(x) for x in columns.splitlines()],'indexes':[json.loads(x) for x in indexes.splitlines()],'counters':[json.loads(x) for x in counters.splitlines()]}
    def guard(self,expected_plugins):
        rows=self.query("SELECT JSON_OBJECT('database',DATABASE(),'home',(SELECT option_value FROM TXGEh_options WHERE option_name='home'),'siteurl',(SELECT option_value FROM TXGEh_options WHERE option_name='siteurl'),'plugins',(SELECT option_value FROM TXGEh_options WHERE option_name='active_plugins'),'server_version',VERSION(),'bytes',(SELECT COALESCE(SUM(DATA_LENGTH+INDEX_LENGTH),0) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()),'objects',(SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE())+(SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE())+(SELECT COUNT(*) FROM information_schema.EVENTS WHERE EVENT_SCHEMA=DATABASE()));")
        v=json.loads(rows);require(v['database']==DATABASE and v['home']==URL and v['siteurl']==URL)
        require((expected_plugins is None or sha(v['plugins'].encode())==expected_plugins) and v['objects']==0 and 0<=v['bytes']<=MAX_DB)
        return v
    def versions(self):
        result={}
        for name,binary in (('client',self.client),('dump',self.dumper)):
            text=self.run(binary,['--version']).decode('ascii').strip()
            require('MariaDB' in text and len(text)<512)
            result[name]=text
        return result
    def dump(self,out):
        self.run(self.dumper,['--single-transaction','--quick','--skip-lock-tables','--skip-add-locks','--no-tablespaces','--hex-blob','--routines','--events','--triggers','--skip-comments','--skip-dump-date',DATABASE],output=out)


def load_generator():
    path=BASE/'docs/pilot/baseline-select-plan.py.txt';raw=path.read_bytes()
    require(sha(raw)=='509c5acd35e2e165fd1c46a260bc3ec3d117a79b96e92b726cd83dfdd7b13b46')
    ns={'__name__':'backup_generator'};exec(compile(raw,str(path),'exec'),ns)
    return ns['generate']


def bindings(value,mode):
    require(set(value)=={'mode','source_pin','run_token','actor_ref','approval_ref','start','end','custody','config_reviewed','wp_config_sha256','plugins_sha256','protected','db_secret'})
    require(value['mode']==mode and value['source_pin']==IMPLEMENTATION)
    require(re.fullmatch('[a-f0-9]{32}',value['run_token']) and all(isinstance(value[k],str) and re.fullmatch('[A-Za-z0-9_.:-]{1,128}',value[k]) for k in ('actor_ref','approval_ref')))
    start,end=utc(value['start']),utc(value['end']);require(start<=time.time()<end and 0<end-start<=1800)
    require(value['custody']=='local-only' and value['config_reviewed'] is True)
    require(set(value['db_secret'])=={'user','password','host'})
    for key in ('wp_config_sha256','plugins_sha256'):
        require((mode=='inspect' and value[key] is None) or (isinstance(value[key],str) and re.fullmatch('[a-f0-9]{64}',value[key])))
    require(isinstance(value['protected'],list) and 0<len(value['protected'])<=64)
    names=set();paths=set()
    for item in value['protected']:
        require(set(item)=={'path','name','sha256','scope_verified'})
        require(item['scope_verified'] is True and re.fullmatch('[A-Za-z0-9_.-]+',item['name']) and item['name'] not in ('.','..'))
        require(isinstance(item['path'],str) and Path(item['path']).is_absolute() and 'httpdocs' not in Path(item['path']).parts)
        require(item['name'] not in names and item['path'] not in paths and ((mode=='inspect' and item['sha256'] is None) or (isinstance(item['sha256'],str) and re.fullmatch('[a-f0-9]{64}',item['sha256']))))
        names.add(item['name']);paths.add(item['path'])
    return Budget(end)


def free_bytes(parent):
    v=os.statvfs(parent);return v.f_bavail*v.f_frsize


def directory_inventory(root,budget):
    result={}
    for parent,dirs,_ in os.walk(root,followlinks=False,onerror=walk_error):
        budget.check()
        for name in dirs:
            p=Path(parent)/name;i=p.lstat();require(stat.S_ISDIR(i.st_mode) and i.st_dev==root.stat().st_dev)
            relative=p.relative_to(root).as_posix();require(safe_name(relative)==relative)
            require(len(result)<MAX_FILES)
            result[relative]={'mode':stat.S_IMODE(i.st_mode),'uid':i.st_uid,'gid':i.st_gid,'mtime_ns':i.st_mtime_ns}
    return dict(sorted(result.items()))


class ReserveWriter:
    def __init__(self,raw,budget):self.raw=raw;self.budget=budget
    def write(self,data):
        self.budget.check();result=self.raw.write(data);self.budget.check();return result
    def __getattr__(self,name):return getattr(self.raw,name)


def archive_files(dest,root,files,expected,budget,directories=None):
    directories=directories or {}
    with open(dest,'xb') as raw_archive,tarfile.open(fileobj=ReserveWriter(raw_archive,budget),mode='w:gz',format=tarfile.PAX_FORMAT) as out:
        for name,info in sorted(directories.items()):
            budget.check();entry=tarfile.TarInfo(name);entry.type=tarfile.DIRTYPE;entry.mode=info['mode'];entry.uid=info['uid'];entry.gid=info['gid'];entry.mtime=info['mtime_ns']//1000000000;out.addfile(entry)
        for name in sorted(files):
            budget.check();digest,info=hash_regular(root/name,MAX_BYTES,budget)
            require(digest==expected[name]['sha256'])
            entry=tarfile.TarInfo(name);entry.size=info.st_size;entry.mode=stat.S_IMODE(info.st_mode)
            entry.uid=info.st_uid;entry.gid=info.st_gid;entry.mtime=info.st_mtime_ns//1000000000
            fd=os.open(root/name,os.O_RDONLY|os.O_NOFOLLOW)
            try:
                require(stable_stat(os.fstat(fd))==stable_stat(info))
                with os.fdopen(fd,'rb',closefd=False) as inp:out.addfile(entry,inp)
                require(stable_stat(os.fstat(fd))==stable_stat(info))
            finally:os.close(fd)
            digest_after,_=hash_regular(root/name,MAX_BYTES,budget);require(digest_after==digest)
    os.chmod(dest,0o600)
    validate_archive(dest,set(files)|set(directories),budget=budget,expected_files={n:expected[n] for n in files})


def verify_manifest(bundle,expected_digest):
    raw,_=read_regular(bundle/'SHA256SUMS',16384,Budget(time.time()+60,60))
    require(sha(raw)==expected_digest)
    entries={}
    for line in raw.decode('ascii').splitlines():
        require(re.fullmatch(r'[a-f0-9]{64}  [a-zA-Z0-9_.-]+',line))
        digest,name=line.split('  ');require(name in MANIFEST_NAMES and name not in entries);entries[name]=digest
    require(set(entries)==MANIFEST_NAMES)
    for name,digest in entries.items():
        actual,_=hash_regular(bundle/name,MAX_BYTES+MAX_DB,Budget(time.time()+60,60));require(actual==digest)
    return True



def verify_receipt(bundle,expected_receipt_digest):
    budget=Budget(time.time()+120,120)
    raw,_=read_regular(bundle/'receipt.json',16384,budget)
    require(sha(raw)==expected_receipt_digest)
    receipt=json.loads(raw)
    require(set(receipt)=={'version','state','run_token','source','source_pin','started','custody','offserver_verified','restore_verified','drift_counts','schema_changed','manifest_sha256','files','bytes','ended'})
    require(receipt['state']=='complete' and receipt['source_pin']==IMPLEMENTATION)
    require(receipt['source']=={'url':URL,'database':DATABASE,'prefix':PREFIX})
    require(receipt['custody']=='local-only' and receipt['offserver_verified'] is False and receipt['restore_verified'] is False)
    require(receipt['schema_changed'] is False and all(v==0 for v in receipt['drift_counts'].values()))
    verify_manifest(bundle,receipt['manifest_sha256'])
    return True


def capture(value,db,budget,root=ROOT,parent=PARENT):
    """Tests may inject a disposable scope; CLI passes immutable production-excluding constants."""
    require(root.resolve(strict=True)==root and parent.resolve(strict=True)==parent)
    budget.check();bundle=parent/('csf-gate-b-backup-'+value['run_token'])
    require(not bundle.exists() and not bundle.is_symlink())
    bundle.mkdir(mode=0o700);os.chmod(bundle,0o700)
    receipt={'version':1,'state':'incomplete','run_token':value['run_token'],'source':{'url':URL,'database':DATABASE,'prefix':PREFIX},'source_pin':IMPLEMENTATION,'started':timestamp(),'custody':'local-only','offserver_verified':False,'restore_verified':False}
    try:
        before_dirs=directory_inventory(root,budget)
        before=inventory(root,budget);require('wp-config.php' in before and before['wp-config.php']['sha256']==value['wp_config_sha256'])
        validate_config(read_regular(root/'wp-config.php',16*1024**2,budget)[0])
        protected={};protected_bytes=0
        for item in value['protected']:
            path=Path(item['path']);require(path!=root/'wp-config.php' and not path.is_relative_to(root))
            data,info=read_regular(path,16*1024**2-protected_bytes,budget);require(sha(data)==item['sha256'])
            protected[item['name']]={'sha256':sha(data),'size':len(data),'mode':stat.S_IMODE(info.st_mode),'uid':info.st_uid,'gid':info.st_gid,'mtime_ns':info.st_mtime_ns};protected_bytes+=len(data)
        versions=db.versions()
        initial=db.guard(value['plugins_sha256']);generate=load_generator()
        catalog=db.catalog();plan=generate(catalog);raw=db.query(plan);a=parse_rows(raw,catalog)
        source_bytes=sum(x['size'] for x in before.values());reserve=2*1024**3+5*(source_bytes+initial['bytes']+protected_bytes)+64*1024**2
        require(free_bytes(parent)>=reserve);budget.disk_parent=parent
        private_write(bundle/'capture-start-utc.txt',(receipt['started']+'\n').encode())
        write_json(bundle/'before.catalog.json',catalog);private_write(bundle/'before.rows.ndjson',raw)
        # Anonymous unlinked spool: SIGKILL/host process exit closes it without orphaning SQL.
        with tempfile.TemporaryFile(dir=bundle) as inp:
            db.dump(inp);require(os.fstat(inp.fileno()).st_size<MAX_DB*2);inp.seek(0)
            with open(bundle/'database.sql.gz','xb') as dest:
                os.chmod(dest.name,0o600)
                with gzip.GzipFile(filename='',mode='wb',fileobj=ReserveWriter(dest,budget),mtime=0) as zipped:
                    while True:
                        budget.check();chunk=inp.read(1024*1024)
                        if not chunk:break
                        zipped.write(chunk)
        validate_gzip(bundle/'database.sql.gz',budget)
        archive_files(bundle/'wp-content.tar.gz',root,{n for n in before if n.startswith('wp-content/')},before,budget,{n:v for n,v in before_dirs.items() if n=='wp-content' or n.startswith('wp-content/')})
        archive_files(bundle/'wordpress-root.tar.gz',root,{n for n in before if n!='wp-config.php' and not n.startswith('wp-content/')},before,budget,{n:v for n,v in before_dirs.items() if n!='wp-content' and not n.startswith('wp-content/')})
        config,_=read_regular(root/'wp-config.php',MAX_BYTES,budget);require(sha(config)==value['wp_config_sha256']);private_write(bundle/'wp-config.php.recovery',config)
        # No guessed paths, recursive subscription copy or externally executed configuration.
        with open(bundle/'protected-configs.tar.gz','xb') as protected_out,tarfile.open(fileobj=ReserveWriter(protected_out,budget),mode='w:gz',format=tarfile.PAX_FORMAT) as out:
            for item in sorted(value['protected'],key=lambda i:i['name']):
                data,_=read_regular(Path(item['path']),16*1024**2,budget);require(sha(data)==protected[item['name']]['sha256'])
                e=tarfile.TarInfo(item['name']);e.size=len(data);e.mode=0o600;out.addfile(e,io.BytesIO(data))
        os.chmod(bundle/'protected-configs.tar.gz',0o600);validate_archive(bundle/'protected-configs.tar.gz',set(protected),16*1024**2,budget=budget,expected_files=protected)
        after=inventory(root,budget);require(before==after and before_dirs==directory_inventory(root,budget))
        for item in value['protected']:
            data,info=read_regular(Path(item['path']),16*1024**2,budget)
            observed={'sha256':sha(data),'size':len(data),'mode':stat.S_IMODE(info.st_mode),'uid':info.st_uid,'gid':info.st_gid,'mtime_ns':info.st_mtime_ns}
            require(observed==protected[item['name']])
        final=db.guard(None);after_catalog=db.catalog();raw=db.query(generate(after_catalog));b=parse_rows(raw,after_catalog)
        write_json(bundle/'after.catalog.json',after_catalog);private_write(bundle/'after.rows.ndjson',raw)
        policy=json.loads((BASE/'docs/pilot/baseline-contract.json').read_text());review=drift(a,b,policy)
        if sha(final['plugins'].encode())!=value['plugins_sha256']:
            review['review_required']=True;review['counts']['critical']+=1;review['guard_changed']=True
        write_json(bundle/'drift-review.json',review)
        receipt['drift_counts']=review['counts'];receipt['schema_changed']=review['schema_changed']
        for which,inv in (('before',before),('after',after)):
            private_write(bundle/(which+'-files.sha256'),''.join(v['sha256']+'  '+n+'\n' for n,v in inv.items()).encode())
        write_json(bundle/'file-attributes.json',{'before':before,'after':after,'directories':before_dirs,'protected':protected})
        write_json(bundle/'target.json',{'root':str(root),'url':URL,'database':DATABASE,'prefix':PREFIX,'source_pin':IMPLEMENTATION,'native_versions':versions,'before_guard':initial,'after_guard':final,'binding_sha256':sha(canonical({k:v for k,v in value.items() if k!='db_secret'})),'payload_sha256':globals().get('PAYLOAD_DIGEST') or sha(Path(__file__).read_bytes()),'snapshot_note':'Diagnostics and dump are independent MVCC instants; no cross-filesystem atomicity or writer attribution.'})
        private_write(bundle/'capture-end-utc.txt',(timestamp()+'\n').encode());budget.check()
        manifest=''.join(hash_regular(bundle/n,MAX_BYTES+MAX_DB,budget)[0]+'  '+n+'\n' for n in sorted(MANIFEST_NAMES)).encode();private_write(bundle/'SHA256SUMS',manifest)
        verify_manifest(bundle,sha(manifest));budget.check()
        require(free_bytes(parent)>=max(2*1024**3,source_bytes+initial['bytes']))
        receipt.update({'manifest_sha256':sha(manifest),'files':len(before),'bytes':source_bytes,'state':'review-required' if review['review_required'] else 'complete'})
    except (Exception,KeyboardInterrupt) as error:
        # No exception text/SQL/paths/credentials leak. Failed artifacts retained restricted.
        receipt['state']='incomplete';receipt['failure']='stopped';receipt['failure_type']=type(error).__name__
        # Remove only this attempt's uncompressed temporary dump, never prior artifacts.
        temporary=bundle/'dump.sql'
        if temporary.is_file() and not temporary.is_symlink():temporary.unlink()
    finally:
        for path in bundle.iterdir():
            if path.is_file() and not path.is_symlink():os.chmod(path,0o600)
        receipt['ended']=timestamp();write_json(bundle/'receipt.json',receipt)
        directory_fd=os.open(bundle,os.O_RDONLY|os.O_DIRECTORY)
        try:os.fsync(directory_fd)
        finally:os.close(directory_fd)
    return receipt


def inspect_only(value,db,budget,root=ROOT,parent=PARENT):
    """No files, source writes, discovery, WordPress bootstrap or baseline row capture."""
    require(root.resolve(strict=True)==root and parent.resolve(strict=True)==parent)
    before=inventory(root,budget);require(value['wp_config_sha256'] is None or before['wp-config.php']['sha256']==value['wp_config_sha256'])
    validate_config(read_regular(root/'wp-config.php',16*1024**2,budget)[0])
    directory_inventory(root,budget)
    protected_bytes=0;observations={}
    for item in value['protected']:
        path=Path(item['path']);require(not path.is_relative_to(root))
        data,_=read_regular(path,16*1024**2-protected_bytes,budget);protected_bytes+=len(data);require(item['sha256'] is None or sha(data)==item['sha256']);observations[item['name']]=sha(data)
    versions=db.versions()
    v=db.guard(value['plugins_sha256']);catalog=db.catalog();load_generator()(catalog)
    reserve=2*1024**3+5*(sum(x['size'] for x in before.values())+v['bytes']+16*1024**2)+64*1024**2
    require(free_bytes(parent)>=reserve)
    return {'state':'prerequisites-pass','file_count':len(before),'source_bytes':sum(x['size'] for x in before.values()),'tables':12,'engine':'InnoDB','disk_reserve_pass':True,'capture_authorized':False,'native_versions':versions,'observed_wp_config_sha256':before['wp-config.php']['sha256'],'observed_plugins_sha256':sha(v['plugins'].encode()),'observed_protected_sha256':observations}


def main():
    parser=argparse.ArgumentParser();parser.add_argument('mode',choices=['inspect','capture']);args=parser.parse_args()
    old=os.umask(0o077)
    try:
        raw=sys.stdin.buffer.read(65537);require(len(raw)<=65536);value=json.loads(raw);budget=bindings(value,args.mode)
        if args.mode=='inspect':budget=Budget(min(utc(value['end']),time.time()+120),120)
        def alarm(*_): raise Stop('deadline')
        signal.signal(signal.SIGALRM,alarm);signal.signal(signal.SIGTERM,alarm);signal.signal(signal.SIGINT,alarm)
        signal.setitimer(signal.ITIMER_REAL,min(1770,budget.remaining()))
        db=NativeDB(value['db_secret'],budget)
        receipt=inspect_only(value,db,budget) if args.mode=='inspect' else capture(value,db,budget)
        result={'receipt':receipt,'receipt_sha256':sha(canonical(receipt)+b'\n')} if args.mode=='capture' else receipt
        print(json.dumps(result,sort_keys=True));return 0 if receipt['state'] in ('complete','prerequisites-pass') else 2
    except (Exception,KeyboardInterrupt):
        print('{"state":"blocked","details":"private review required"}');return 2
    finally:
        signal.setitimer(signal.ITIMER_REAL,0);os.umask(old)


if __name__=='__main__':sys.exit(main())

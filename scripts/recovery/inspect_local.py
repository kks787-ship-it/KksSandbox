"""Server-local inspection only. No Gateway calls, writes, capture dispatch or PHP execution."""
import contextlib
import datetime as dt
import json
import os
from pathlib import Path
import re
import signal
import sys
import time

DOMAIN='sandbox.cornerstonefarmomak.com'
CONF=Path('/var/www/vhosts/system')/DOMAIN/'conf'
POOL=Path('/opt/plesk/php/8.3/etc/php-fpm.d')/(DOMAIN+'.conf')
AUTH=Path('/var/www/vhosts/system/cornerstonefarmomak.com/pd')
NAMES={'httpd.conf','nginx.conf','vhost.conf','vhost_ssl.conf','vhost_nginx.conf','last_httpd.conf','last_nginx.conf','php-fpm.conf'}


# Codes identify operation categories, never private values, arbitrary errors or exact paths.
CODES={
 'INPUT_UNVERIFIED':'input', 'TIME_WINDOW_UNVERIFIED':'window',
 'TIME_LIMIT_REACHED':'runtime', 'EXECUTION_INTERRUPTED':'runtime', 'SOURCE_SCOPE_BLOCKED':'source',
 'CONFIG_PATH_UNVERIFIED':'config_read', 'CONFIG_LITERALS_UNSUPPORTED':'config_parse',
 'PLESK_CONFIG_PATH_UNVERIFIED':'plesk_discovery', 'PLESK_CONFIG_READ_BLOCKED':'plesk_read',
 'PLESK_SCOPE_BLOCKED':'plesk_discovery', 'PLESK_ROOT_MISMATCH':'plesk_root',
 'PLESK_INCLUDE_SCOPE_BLOCKED':'plesk_include', 'PLESK_POOL_UNVERIFIED':'plesk_pool',
 'HTTP_AUTH_SCOPE_BLOCKED':'http_auth', 'HTTP_AUTH_READ_BLOCKED':'http_auth',
 'DB_CLIENT_UNAVAILABLE':'db_client', 'DB_TARGET_UNVERIFIED':'db_target',
 'DB_METADATA_UNAVAILABLE':'db_metadata', 'DB_SCHEMA_UNVERIFIED':'db_schema',
 'SOURCE_INVENTORY_BLOCKED':'inventory', 'CONFIG_RECHECK_UNVERIFIED':'config_recheck',
 'DISK_RESERVE_UNVERIFIED':'disk', 'BLOCKED_UNCLASSIFIED':'unclassified',
 'CHECKS_PASSED':'complete'
}


def fail(b,code):
    error=b.Stop('blocked');error.inspection_code=code;raise error


@contextlib.contextmanager
def guarded(b,code,budget=None):
    try:yield
    except (b.Stop,OSError,ValueError,KeyError,TypeError) as error:
        if type(getattr(error,'inspection_code',None)) is str and error.inspection_code in CODES:raise
        if budget is not None and (time.time()>=budget.end or time.monotonic()>=budget.deadline):
            fail(b,'TIME_LIMIT_REACHED')
        fail(b,code)


def safe_receipt(code):
    if type(code) is not str or code not in CODES:code='BLOCKED_UNCLASSIFIED'
    return {'state':'prerequisites-pass' if code=='CHECKS_PASSED' else 'blocked',
            'code':code,'stage':CODES[code]}


def discover(b,budget,root,conf=CONF,pool=POOL,auth=AUTH):
    """Bounded target-only directory; referenced auth files, never an auth directory crawl."""
    with guarded(b,'PLESK_CONFIG_PATH_UNVERIFIED',budget):
        b.require(conf.resolve(strict=True)==conf)
    queue=[]
    for name in sorted(NAMES):
        candidate=conf/name
        if candidate.exists() or candidate.is_symlink():
            with guarded(b,'PLESK_SCOPE_BLOCKED',budget):
                target=candidate.resolve(strict=True)
                b.require(target.parent==conf and target.is_file())
            queue.append(target)
    if pool.exists() or pool.is_symlink():
        with guarded(b,'PLESK_POOL_UNVERIFIED',budget):
            b.require(pool.resolve(strict=True)==pool);queue.append(pool)
    htaccess=root/'.htaccess'
    if htaccess.exists():queue.append(htaccess)
    seen={};total=0;web=False;auth_seen=False;pool_seen=False
    while queue:
        with guarded(b,'TIME_LIMIT_REACHED',budget):budget.check()
        path=queue.pop(0)
        if str(path) in seen:continue
        with guarded(b,'PLESK_SCOPE_BLOCKED',budget):b.require(len(seen)<64)
        code='HTTP_AUTH_READ_BLOCKED' if path.is_relative_to(auth) else 'PLESK_CONFIG_READ_BLOCKED'
        with guarded(b,code,budget):
            raw,info=b.read_regular(path,16*1024**2-total,budget);total+=len(raw)
        # Structural text only; hashes/booleans/counts are the only returned observations.
        with guarded(b,code,budget):text=raw.decode('utf-8')
        seen[str(path)]=b.sha(raw)
        if path==pool:pool_seen=True
        if path.parent==auth or path.is_relative_to(auth):
            auth_seen=True;continue
        # Target-specific files may never redirect the inspected site's root to production.
        roots=re.findall(r'^\s*(?:DocumentRoot|root)\s+["\']?([^"\';\s]+)',text,re.M|re.I)
        for value in roots:
            with guarded(b,'PLESK_ROOT_MISMATCH',budget):b.require(value==str(root))
            web=True
        for value in re.findall(r'^\s*(?:AuthUserFile|auth_basic_user_file)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            with guarded(b,'HTTP_AUTH_SCOPE_BLOCKED',budget):
                target=Path(value);resolved=target.resolve(strict=True)
                b.require(target==resolved and resolved.is_relative_to(auth) and resolved!=auth)
            queue.append(resolved)
        # Follow includes only when they are literal paths within this sandbox's conf.
        for value in re.findall(r'^\s*(?:IncludeOptional|Include|include)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            with guarded(b,'PLESK_INCLUDE_SCOPE_BLOCKED',budget):
                candidate=Path(value)
                b.require(candidate.is_absolute() and not any(c in value for c in '*?[$'))
                resolved=candidate.resolve(strict=True)
                b.require(resolved.parent==conf)
            queue.append(resolved)
    with guarded(b,'PLESK_ROOT_MISMATCH',budget):b.require(web)
    with guarded(b,'HTTP_AUTH_SCOPE_BLOCKED',budget):b.require(auth_seen)
    with guarded(b,'PLESK_POOL_UNVERIFIED',budget):b.require(pool_seen)
    return {'files':len(seen),'bytes':total,'map_sha256':b.sha(b.canonical(seen)),
            'http_auth_verified':True,'pool_file_verified':True}


def run(b,params,root=None,parent=None,discovery=None,db_factory=None):
    with guarded(b,'INPUT_UNVERIFIED'):
        b.require(set(params)=={'actor_ref','approval_ref','start','end','run_token'})
        b.require(all(isinstance(params[k],str) and re.fullmatch('[A-Za-z0-9_.:-]{1,128}',params[k]) for k in ('actor_ref','approval_ref')))
        b.require(re.fullmatch('[a-f0-9]{32}',params['run_token']))
    with guarded(b,'TIME_WINDOW_UNVERIFIED'):
        start,end=b.utc(params['start']),b.utc(params['end'])
        b.require(start<=time.time()<end and 0<end-start<=120)
    budget=b.Budget(end,115);root=root or b.ROOT;parent=parent or b.PARENT
    secret={}
    try:
        with guarded(b,'SOURCE_SCOPE_BLOCKED',budget):
            b.require(root.resolve(strict=True)==root and parent.resolve(strict=True)==parent)
        with guarded(b,'CONFIG_PATH_UNVERIFIED',budget):
            raw,_=b.read_regular(root/'wp-config.php',16*1024**2,budget)
        with guarded(b,'CONFIG_LITERALS_UNSUPPORTED',budget):
            secret=b.validate_config(raw,credentials=True)
        with guarded(b,'DB_CLIENT_UNAVAILABLE',budget):db=(db_factory or b.NativeDB)(secret,budget)
        with guarded(b,'PLESK_SCOPE_BLOCKED',budget):observed=(discovery or discover)(b,budget,root)
        with guarded(b,'DB_CLIENT_UNAVAILABLE',budget):versions=db.versions()
        with guarded(b,'DB_TARGET_UNVERIFIED',budget):guard=db.guard(None)
        with guarded(b,'DB_METADATA_UNAVAILABLE',budget):catalog=db.catalog()
        with guarded(b,'DB_SCHEMA_UNVERIFIED',budget):b.load_generator()(catalog)
        with guarded(b,'SOURCE_INVENTORY_BLOCKED',budget):
            files=b.inventory(root,budget);b.directory_inventory(root,budget)
        with guarded(b,'CONFIG_RECHECK_UNVERIFIED',budget):
            b.require(b.sha(b.read_regular(root/'wp-config.php',16*1024**2,budget)[0])==b.sha(raw))
        with guarded(b,'DISK_RESERVE_UNVERIFIED',budget):
            required=2*1024**3+5*(sum(x['size'] for x in files.values())+guard['bytes']+observed['bytes'])+64*1024**2
            b.require(b.free_bytes(parent)>=required);budget.check()
        return {'state':'prerequisites-pass','capture_authorized':False,'source':{'url':b.URL,'database':b.DATABASE,'prefix':b.PREFIX},
                'file_count':len(files),'source_bytes':sum(x['size'] for x in files.values()),'tables':12,'engine':'InnoDB',
                'native_versions':versions,'configuration':observed,'wp_config_sha256':b.sha(raw),'disk_reserve_pass':True,
                'ssh_identity_is_root':os.geteuid()==0}
    finally:secret.clear()


def main(b):
    os.umask(0o077)
    def stop(number,*_):fail(b,'TIME_LIMIT_REACHED' if number==signal.SIGALRM else 'EXECUTION_INTERRUPTED')
    signal.signal(signal.SIGALRM,stop);signal.signal(signal.SIGTERM,stop);signal.signal(signal.SIGINT,stop)
    signal.setitimer(signal.ITIMER_REAL,115)
    try:
        with guarded(b,'INPUT_UNVERIFIED'):
            raw=sys.stdin.buffer.read(16385);b.require(len(raw)<=16384);params=json.loads(raw)
        result=run(b,params)
        if not isinstance(result,dict) or result.get('state')!='prerequisites-pass':fail(b,'BLOCKED_UNCLASSIFIED')
        receipt=safe_receipt('CHECKS_PASSED');status=0
    except (Exception,KeyboardInterrupt) as error:
        code=getattr(error,'inspection_code',None)
        if type(code) is not str or code=='CHECKS_PASSED':code='BLOCKED_UNCLASSIFIED'
        receipt=safe_receipt(code);status=2
    finally:signal.setitimer(signal.ITIMER_REAL,0)
    # Only fixed allowlisted strings are emitted, including on serialization errors elsewhere.
    print('{"state":"'+receipt['state']+'","code":"'+receipt['code']+'","stage":"'+receipt['stage']+'"}')
    return status

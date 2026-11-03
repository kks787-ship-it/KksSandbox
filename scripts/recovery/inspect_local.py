"""Server-local inspection only. No Gateway calls, writes, capture dispatch or PHP execution."""
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


def discover(b,budget,root,conf=CONF,pool=POOL,auth=AUTH):
    """Bounded target-only directory; referenced auth files, never an auth directory crawl."""
    b.require(conf.resolve(strict=True)==conf)
    queue=[]
    for name in sorted(NAMES):
        candidate=conf/name
        if candidate.exists() or candidate.is_symlink():
            target=candidate.resolve(strict=True)
            b.require(target.parent==conf and target.is_file())
            queue.append(target)
    if pool.exists() or pool.is_symlink():
        b.require(pool.resolve(strict=True)==pool);queue.append(pool)
    htaccess=root/'.htaccess'
    if htaccess.exists():queue.append(htaccess)
    seen={};total=0;web=False;auth_seen=False;pool_seen=False
    while queue:
        budget.check();path=queue.pop(0)
        if str(path) in seen:continue
        b.require(len(seen)<64)
        raw,info=b.read_regular(path,16*1024**2-total,budget);total+=len(raw)
        # Structural text only; hashes/booleans/counts are the only returned observations.
        text=raw.decode('utf-8');seen[str(path)]=b.sha(raw)
        if path==pool:pool_seen=True
        if path.parent==auth or path.is_relative_to(auth):
            auth_seen=True;continue
        # Target-specific files may never redirect the inspected site's root to production.
        roots=re.findall(r'^\s*(?:DocumentRoot|root)\s+["\']?([^"\';\s]+)',text,re.M|re.I)
        for value in roots:b.require(value==str(root));web=True
        for value in re.findall(r'^\s*(?:AuthUserFile|auth_basic_user_file)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            target=Path(value);resolved=target.resolve(strict=True)
            b.require(target==resolved and resolved.is_relative_to(auth) and resolved!=auth)
            queue.append(resolved)
        # Follow includes only when they are literal paths within this sandbox's conf.
        for value in re.findall(r'^\s*(?:IncludeOptional|Include|include)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            candidate=Path(value)
            b.require(candidate.is_absolute() and not any(c in value for c in '*?[$'))
            resolved=candidate.resolve(strict=True)
            b.require(resolved.parent==conf)
            queue.append(resolved)
    b.require(web and auth_seen and pool_seen)
    return {'files':len(seen),'bytes':total,'map_sha256':b.sha(b.canonical(seen)),
            'http_auth_verified':True,'pool_file_verified':True}


def run(b,params,root=None,parent=None,discovery=None,db_factory=None):
    b.require(set(params)=={'actor_ref','approval_ref','start','end','run_token'})
    b.require(all(isinstance(params[k],str) and re.fullmatch('[A-Za-z0-9_.:-]{1,128}',params[k]) for k in ('actor_ref','approval_ref')))
    b.require(re.fullmatch('[a-f0-9]{32}',params['run_token']))
    start,end=b.utc(params['start']),b.utc(params['end'])
    b.require(start<=time.time()<end and 0<end-start<=120)
    budget=b.Budget(end,115);root=root or b.ROOT;parent=parent or b.PARENT
    b.require(root.resolve(strict=True)==root and parent.resolve(strict=True)==parent)
    raw,_=b.read_regular(root/'wp-config.php',16*1024**2,budget)
    secret=b.validate_config(raw,credentials=True)
    db=(db_factory or b.NativeDB)(secret,budget)
    # Credentials are never returned, persisted, included in a binding, or printed.
    observed=(discovery or discover)(b,budget,root)
    versions=db.versions();guard=db.guard(None);catalog=db.catalog();b.load_generator()(catalog)
    files=b.inventory(root,budget);b.directory_inventory(root,budget)
    b.require(b.sha(b.read_regular(root/'wp-config.php',16*1024**2,budget)[0])==b.sha(raw))
    required=2*1024**3+5*(sum(x['size'] for x in files.values())+guard['bytes']+observed['bytes'])+64*1024**2
    b.require(b.free_bytes(parent)>=required);budget.check()
    secret.clear()
    return {'state':'prerequisites-pass','capture_authorized':False,'source':{'url':b.URL,'database':b.DATABASE,'prefix':b.PREFIX},
            'file_count':len(files),'source_bytes':sum(x['size'] for x in files.values()),'tables':12,'engine':'InnoDB',
            'native_versions':versions,'configuration':observed,'wp_config_sha256':b.sha(raw),'disk_reserve_pass':True,
            'ssh_identity_is_root':os.geteuid()==0}


def main(b):
    os.umask(0o077)
    def stop(*_):raise b.Stop('deadline')
    signal.signal(signal.SIGALRM,stop);signal.signal(signal.SIGTERM,stop);signal.signal(signal.SIGINT,stop)
    signal.setitimer(signal.ITIMER_REAL,115)
    try:
        raw=sys.stdin.buffer.read(16385);b.require(len(raw)<=16384)
        output=run(b,json.loads(raw));print(json.dumps(output,sort_keys=True));return 0
    except (Exception,KeyboardInterrupt):
        print('{"state":"blocked","details":"private review required"}');return 2
    finally:signal.setitimer(signal.ITIMER_REAL,0)

"""Bounded diagnostic of target config references; never reads an auth credential file."""
import json
import os
from pathlib import Path
import re
import signal
import stat

DOMAIN='sandbox.cornerstonefarmomak.com'
ROOT=Path('/var/www/vhosts/cornerstonefarmomak.com')/DOMAIN
CONF=Path('/var/www/vhosts/system')/DOMAIN/'conf'
AUTH_TARGET=Path('/var/www/vhosts/system')/DOMAIN/'pd'
AUTH_PARENT=Path('/var/www/vhosts/system/cornerstonefarmomak.com/pd')
NAMES={'httpd.conf','nginx.conf','vhost.conf','vhost_ssl.conf','vhost_nginx.conf','last_httpd.conf','last_nginx.conf','php-fpm.conf'}


def classify(value):
    p=Path(value)
    if not p.is_absolute() or any(part=='..' for part in p.parts):return 'OUTSIDE_SCOPE'
    if p.is_relative_to(AUTH_TARGET) and p!=AUTH_TARGET:return 'SANDBOX_PD_REFERENCE'
    if p.is_relative_to(AUTH_PARENT) and p!=AUTH_PARENT:return 'SUBSCRIPTION_PD_REFERENCE'
    if p.is_relative_to(ROOT) and p!=ROOT:return 'SANDBOX_ROOT_REFERENCE'
    return 'OUTSIDE_SCOPE'


def classify_root(value,root=ROOT):
    if value.startswith('$'):return 'DYNAMIC_ROOT'
    path=Path(value)
    if not path.is_absolute() or '..' in path.parts:return 'UNRESOLVED_ROOT'
    if path==root:return 'SANDBOX_DOCUMENT_ROOT'
    if path.is_relative_to(root):return 'SANDBOX_SUBDIRECTORY_ROOT'
    if path.is_relative_to(CONF.parent):return 'SANDBOX_SYSTEM_LOCATION_ROOT'
    if path.is_relative_to(Path('/var/www/vhosts/default')):return 'HOST_STATIC_LOCATION_ROOT'
    return 'OUTSIDE_SANDBOX_ROOT'


def nginx_roots(text):
    tokens=re.findall(r'"(?:[^"\\]|\\.)*"|\'(?:[^\'\\]|\\.)*\'|\#[^\n]*|[{};]|[^\s{};"\'\#]+',text)
    stack=[];pending=[];result=[]
    for token in tokens:
        if token.startswith('#'):continue
        if token=='{':stack.append(pending);pending=[]
        elif token=='}':
            if stack:stack.pop()
            pending=[]
        elif token==';':
            if len(pending)==2 and pending[0]=='root':
                value=pending[1].strip('"\'')
                locations=[h for h in stack if h and h[0]=='location']
                if not locations:kind='NO_LOCATION_CONTEXT'
                else:
                    header=' '.join(locations[-1])
                    if '.well-known/acme-challenge' in header:kind='ACME_CHALLENGE'
                    elif '.well-known/pki-validation' in header:kind='PKI_VALIDATION'
                    elif '/error_docs' in header:kind='ERROR_DOCS'
                    else:kind='OTHER_LOCATION'
                result.append((value,kind))
            pending=[]
        else:pending.append(token)
    return result


def inspect(conf=CONF,root=ROOT):
    if conf.resolve(strict=True)!=conf:raise ValueError('scope')
    queue=[]
    for name in sorted(NAMES):
        p=conf/name
        if p.exists() or p.is_symlink():
            p=p.resolve(strict=True)
            if p.parent!=conf:raise ValueError('scope')
            queue.append(p)
    if (root/'.htaccess').exists():queue.append(root/'.htaccess')
    seen=set();total=0;counts={};roots={};static_contexts={};directive_kinds={};apache_contexts={};parsed_contexts={};includes_blocked=0
    while queue:
        p=queue.pop(0)
        if p in seen:continue
        if len(seen)>=64:raise ValueError('limit')
        seen.add(p);info=p.lstat()
        if p.resolve(strict=True)!=p or not stat.S_ISREG(info.st_mode) or info.st_nlink!=1:raise ValueError('scope')
        if info.st_size>16*1024**2-total:raise ValueError('limit')
        fd=os.open(p,os.O_RDONLY|os.O_NOFOLLOW)
        try:
            with os.fdopen(fd,'rb',closefd=False) as stream:raw=stream.read(16*1024**2-total+1)
        finally:os.close(fd)
        total+=len(raw)
        if total>16*1024**2:raise ValueError('limit')
        text=raw.decode('utf-8')
        for value,kind in nginx_roots(text):
            if classify_root(value,root)=='HOST_STATIC_LOCATION_ROOT':parsed_contexts[kind]=parsed_contexts.get(kind,0)+1
        for kind,value in re.findall(r'^\s*(DocumentRoot|root)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            key=('APACHE' if kind.lower()=='documentroot' else 'NGINX')+'_'+classify_root(value,root)
            directive_kinds[key]=directive_kinds.get(key,0)+1
        for body in re.findall(r'<VirtualHost[^>]*>(.*?)</VirtualHost>',text,re.S|re.I):
            names=re.findall(r'^\s*ServerName\s+([^\s]+)',body,re.M|re.I)
            context='TARGET_SERVER' if DOMAIN in names else 'OTHER_OR_UNVERIFIED_SERVER'
            for value in re.findall(r'^\s*DocumentRoot\s+["\']?([^"\';\s]+)',body,re.M|re.I):
                key=context+'_'+classify_root(value,root);apache_contexts[key]=apache_contexts.get(key,0)+1
        for header,body in re.findall(r'location\s+([^{}]+)\{([^{}]*)\}',text,re.S):
            header=header.strip().split()
            context='ERROR_DOCS' if header in (['^~','/error_docs'],['^~','/error_docs/'],['/error_docs'],['/error_docs/']) else 'OTHER_LOCATION'
            for value in re.findall(r'^\s*root\s+["\']?([^"\';\s]+)',body,re.M):
                if classify_root(value,root)=='HOST_STATIC_LOCATION_ROOT':static_contexts[context]=static_contexts.get(context,0)+1
        for value in re.findall(r'^\s*(?:DocumentRoot|root)\s+[\"\']?([^\"\';\s]+)',text,re.M|re.I):
            category=classify_root(value,root);roots[category]=roots.get(category,0)+1
        for value in re.findall(r'^\s*(?:AuthUserFile|auth_basic_user_file)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            category=classify(value);counts[category]=counts.get(category,0)+1
        for value in re.findall(r'^\s*(?:IncludeOptional|Include|include)\s+["\']?([^"\';\s]+)',text,re.M|re.I):
            candidate=Path(value)
            if not candidate.is_absolute() or any(c in value for c in '*?[$'):
                includes_blocked+=1;continue
            candidate=candidate.resolve(strict=True)
            if candidate.parent!=conf:includes_blocked+=1;continue
            queue.append(candidate)
    return {'state':'probe-complete','configs_read':len(seen),'auth_reference_categories':counts,'root_reference_categories':roots,'static_location_categories':static_contexts,'root_directive_kinds':directive_kinds,'apache_contexts':apache_contexts,'parsed_static_contexts':parsed_contexts,'out_of_scope_includes':includes_blocked,'auth_files_read':0}


def main():
    def stop(*_):raise TimeoutError('deadline')
    signal.signal(signal.SIGALRM,stop);signal.alarm(25)
    try:print(json.dumps(inspect(),sort_keys=True));return 0
    except Exception:print('{"state":"blocked","code":"AUTH_REFERENCE_PROBE_BLOCKED"}');return 2
    finally:signal.alarm(0)

if __name__=='__main__':raise SystemExit(main())

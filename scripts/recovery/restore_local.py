"""Isolated WordPress recovery candidate. Actual execution requires separate owner approval.

Public code contains no private capture identity. A local private builder binds the approved
receipt. Native DB/PHP commands ALWAYS chroot+drop capabilities; coordinator alone mounts.
"""
import contextlib
import datetime as dt
import fcntl
import gzip
import hashlib
import html
import html.parser
import http.client
import http.cookiejar
import io
import json
import os
from pathlib import Path
import pwd
import re
import resource
import secrets
import select
import signal
import socket
import stat
import subprocess
import sys
import tarfile
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request

ORIGIN='http://127.0.0.1:8899'
PHP='/opt/plesk/php/8.3/bin/php'
SOCKET='/work/mysql/clone.sock'
DATADIR='/work/mysql/data'
AUTHORIZATION='one-isolated-functional-restore'
MAX_TOTAL=8*1024**3
CODES={'LOGIN_IDENTITY_BLOCKED','LOGIN_HELPER_BLOCKED','LOGIN_SESSION_BLOCKED','LOGOUT_TEST_BLOCKED','SQL_STRUCTURE_BLOCKED','INPUT_BLOCKED','SOURCE_BINDING_BLOCKED','DEPENDENCY_BLOCKED','ISOLATION_BLOCKED',
       'ARCHIVE_BLOCKED','DATABASE_IMPORT_BLOCKED','DATABASE_INTEGRITY_BLOCKED','SOURCE_SECURITY_BLOCKED',
       'CLONE_CONFIG_BLOCKED','PAGE_TEST_BLOCKED','LOGIN_TEST_BLOCKED','CLEANUP_BLOCKED',
       'TIME_LIMIT_REACHED','RESTORE_UNCLASSIFIED','ISOLATED_RESTORE_VERIFIED','LOGIN_NOT_TESTED','SCHEMA_STATE_REQUIRES_REVIEW'}

class Failure(Exception):
    def __init__(self,code):self.code=code if code in CODES else 'RESTORE_UNCLASSIFIED'


def require(value,code='RESTORE_UNCLASSIFIED'):
    if not value:raise Failure(code)


@contextlib.contextmanager
def stage(code):
    try:yield
    except Failure:raise
    except (Exception,KeyboardInterrupt):raise Failure(code) from None


def binding_check(b,binding):
    require(set(binding)=={'bundle_id','manifest_sha256','receipt_sha256','recovery_token'},'INPUT_BLOCKED')
    require(re.fullmatch('csf-gate-b-backup-[a-f0-9]{32}',binding['bundle_id']) is not None,'INPUT_BLOCKED')
    require(re.fullmatch('[a-f0-9]{32}',binding['recovery_token']) is not None,'INPUT_BLOCKED')
    for key in ('manifest_sha256','receipt_sha256'):require(re.fullmatch('[a-f0-9]{64}',binding[key]) is not None,'INPUT_BLOCKED')


def params_check(b,p):
    require(set(p)=={'actor_ref','approval_ref','start','end','authorization','login_test'},'INPUT_BLOCKED')
    require(p['authorization']==AUTHORIZATION and p['login_test'] in ('clone-admin','skip'),'INPUT_BLOCKED')
    for key in ('actor_ref','approval_ref'):require(isinstance(p[key],str) and re.fullmatch('[A-Za-z0-9_.:-]{1,128}',p[key]),'INPUT_BLOCKED')
    start,end=b.utc(p['start']),b.utc(p['end'])
    require(start<=time.time()<end and 0<end-start<=1800,'INPUT_BLOCKED')
    return b.Budget(end,1770)


def selected_paths(b,binding,parent=None):
    parent=parent or b.PARENT
    require(parent.resolve(strict=True)==parent,'SOURCE_BINDING_BLOCKED')
    source=parent/binding['bundle_id'];dest=parent/('csf-gate-b-recovery-'+binding['recovery_token'])
    require(source.resolve(strict=True)==source and source.is_dir(),'SOURCE_BINDING_BLOCKED')
    require(not dest.exists() and not dest.is_symlink(),'SOURCE_BINDING_BLOCKED')
    require(source!=dest and source.parent==parent and dest.parent==parent,'SOURCE_BINDING_BLOCKED')
    return source,dest


def verify_input(b,source,binding):
    b.verify_receipt(source,binding['receipt_sha256'],binding['bundle_id'])
    require(b.sha((source/'SHA256SUMS').read_bytes())==binding['manifest_sha256'],'SOURCE_BINDING_BLOCKED')
    require({p.name for p in source.iterdir()}==set(b.MANIFEST_NAMES)|{'SHA256SUMS','receipt.json'},'SOURCE_BINDING_BLOCKED')
    target=json.loads((source/'target.json').read_text())
    require(target['root']==str(b.ROOT) and target['database']==b.DATABASE and target['prefix']==b.PREFIX and target['url']==b.URL,'SOURCE_BINDING_BLOCKED')
    return target


def sql_statements(stream):
    """Streaming structural lexer: quoted data is opaque, executable comments are parsed.

    Retain only structural prefix tokens; count INSERT tuple depth without storing raw data.
    Fail closed on truncated literals, oversized identifiers or unsupported delimiter syntax.
    """
    state='normal';word='';tokens=[];first=[];set_literal='';depth=0;rows=0;values=False;quote='';escape=False;comment='';previous=''
    # A bounded one-character lookahead avoids reading a whole SQL dump into memory.
    pending=stream.read(1)
    def token(value):
        nonlocal values
        if tokens and tokens[0].upper()=='SET':require(len(tokens)<64,'SQL_STRUCTURE_BLOCKED')
        if len(tokens)<64:tokens.append(value)
        if value.upper()=='VALUES':values=True
        require(value.upper() not in {'OUTFILE','DUMPFILE','LOAD_FILE','INSTALL','SONAME','DEFINER','GRANT','REVOKE','PROCEDURE','TRIGGER','EVENT'},'SQL_STRUCTURE_BLOCKED')
        require('@@GLOBAL.' not in value.upper() and value.upper()!='GLOBAL','SQL_STRUCTURE_BLOCKED')
    while pending:
        c=pending;pending=stream.read(1)
        if state=='line':
            if c=='\n':state='normal'
            continue
        if state=='comment':
            if previous=='*' and c=='/':state='normal';previous=''
            else:previous=c
            continue
        if state=='quote' and tokens and tokens[0].upper()=='SET':
            if c==quote:
                require(pending!=quote,'SQL_STRUCTURE_BLOCKED');token('literal:'+set_literal);state='normal';continue
            require(c!='\\' and len(set_literal)<256,'SQL_STRUCTURE_BLOCKED');set_literal+=c;continue
        if state=='quote':
            if escape:escape=False;continue
            if c=='\\':escape=True;continue
            if c==quote:
                if pending==quote:pending=stream.read(1);continue
                state='normal'
            continue
        if state=='identifier':
            if c=='`':
                require(re.fullmatch('[A-Za-z0-9_]+',word),'SQL_STRUCTURE_BLOCKED');token(word);word='';state='normal'
            else:word+=c;require(len(word)<=256,'SQL_STRUCTURE_BLOCKED')
            continue
        if c.isalnum() or c in '_@.$':
            word+=c
            if len(word)>1024 and word.lower().startswith('0x'):word='0x'
            require(len(word)<=1024,'SQL_STRUCTURE_BLOCKED');continue
        if word:token(word);word=''
        if c=='/' and pending=='*':
            pending=stream.read(1)
            if pending=='!':
                pending=stream.read(1)
                while pending and pending.isdigit():pending=stream.read(1)
                # Executable comment body is processed as normal SQL. Its closing */ ignored.
                continue
            if pending=='M':
                # MariaDB's sandbox-mode marker is client metadata only; reject other M! bodies.
                marker='M'
                while not marker.endswith('*/'):
                    char=stream.read(1);require(char and len(marker)<1024,'SQL_STRUCTURE_BLOCKED');marker+=char
                require('sandbox mode' in marker,'SQL_STRUCTURE_BLOCKED');pending=stream.read(1);continue
            state='comment';previous='';continue
        if c=='-' and pending=='-':pending=stream.read(1);state='line';continue
        if c=='#':state='line';continue
        if c in "'\"":quote=c;state='quote';set_literal='';continue
        if c=='`':state='identifier';continue
        if c=='(' and values:
            if depth==0:rows+=1
            depth+=1
        if c==')' and values:depth-=1;require(depth>=0,'SQL_STRUCTURE_BLOCKED')
        if c==';':
            require(depth==0,'SQL_STRUCTURE_BLOCKED')
            if tokens:yield tokens,rows
            tokens=[];rows=0;values=False;depth=0
    if word:token(word)
    require(state in ('normal','line') and not tokens and depth==0,'SQL_STRUCTURE_BLOCKED')


def validate_sql(b,stream,expected_tables=None):
    contract=b.schema_contract();allowed={b.PREFIX+n for n in b.NATIVE|contract['SCHEDULING']}
    if expected_tables is not None:
        allowed=contract['table_set'](expected_tables)
    counts={};created=set()
    for tokens,rows in sql_statements(stream):
        words=[t.upper() for t in tokens];kind=words[0]
        if kind=='SET':
            variables={'TIME_ZONE','CHARACTER_SET_CLIENT','CHARACTER_SET_RESULTS','COLLATION_CONNECTION','UNIQUE_CHECKS','FOREIGN_KEY_CHECKS','SQL_MODE','SQL_NOTES','SAVED_CS_CLIENT'}
            for word in words[1:]:
                if word.startswith('LITERAL:'):
                    require(word in ('LITERAL:','LITERAL:+00:00','LITERAL:NO_AUTO_VALUE_ON_ZERO','LITERAL:UTF8MB4','LITERAL:UTF8','LITERAL:BINARY'),'SQL_STRUCTURE_BLOCKED')
                else:
                    name=word.removeprefix('@@').removeprefix('@').removeprefix('SESSION.').removeprefix('OLD_')
                    require(name in variables or word in ('SESSION','LOCAL','NAMES','UTF8MB4','UTF8','BINARY','0','1'),'SQL_STRUCTURE_BLOCKED')
            continue
        if kind=='USE':require(tokens[1:]==[b.DATABASE],'SQL_STRUCTURE_BLOCKED');continue
        if kind=='INSERT':
            require(len(tokens)>3 and words[1]=='INTO' and words[3]=='VALUES','SQL_STRUCTURE_BLOCKED');table=tokens[2]
        elif kind in ('CREATE','DROP','ALTER'):
            if kind=='ALTER':require(len(words)==5 and words[3] in ('ENABLE','DISABLE') and words[4]=='KEYS','SQL_STRUCTURE_BLOCKED')
            require(len(tokens)>2 and words[1]=='TABLE','SQL_STRUCTURE_BLOCKED')
            pos=2
            while pos<len(words) and words[pos] in ('IF','NOT','EXISTS'):pos+=1
            require(pos<len(tokens),'SQL_STRUCTURE_BLOCKED');table=tokens[pos]
            if kind=='CREATE':
                require(table not in created,'SQL_STRUCTURE_BLOCKED');created.add(table);counts.setdefault(table[len(b.PREFIX):],0)
        else:raise Failure('SQL_STRUCTURE_BLOCKED')
        require(table in allowed,'SQL_STRUCTURE_BLOCKED')
        if kind=='INSERT':
            require(table in created,'SQL_STRUCTURE_BLOCKED');counts[table[len(b.PREFIX):]]+=rows
    try:contract['table_set'](created)
    except ValueError:raise Failure('SQL_STRUCTURE_BLOCKED') from None
    require(expected_tables is None or created==set(expected_tables),'SQL_STRUCTURE_BLOCKED')
    return counts


class Runtime:
    def __init__(self,b,work,budget,php=PHP):
        self.b=b;self.work=work;self.jail=work/'jail';self.budget=budget;self.php=str(Path(php).resolve(strict=True));self.children=[]
        self.mysql=pwd.getpwnam('mysql');self.web=pwd.getpwnam('nobody')
        require(self.mysql.pw_uid!=0 and self.web.pw_uid!=0 and self.mysql.pw_uid!=self.web.pw_uid,'DEPENDENCY_BLOCKED')
        self.root_password='';self.app_password='';self._last_inventory=0
    def check(self):
        self.budget.check();require(self.b.free_bytes(self.work)>=2*1024**3,'DEPENDENCY_BLOCKED')
        now=time.monotonic()
        if now-self._last_inventory<1:return
        self._last_inventory=now
        total=0
        for base in (self.work/'logs',self.jail/'site',self.jail/'work',self.work/'protected'):
            if not base.exists():continue
            for root,dirs,files in os.walk(base,followlinks=False):
                for name in files:
                    p=Path(root)/name
                    if p.is_file() and not p.is_symlink():total+=p.stat().st_size
        require(total<=MAX_TOTAL,'DEPENDENCY_BLOCKED')
    def path(self,relative):
        p=self.work/relative;require(p.resolve().is_relative_to(self.work),'ISOLATION_BLOCKED');return p
    def mkdir(self,relative,mode=0o700):
        p=self.path(relative);p.mkdir(parents=True,exist_ok=True,mode=mode);os.chmod(p,mode);return p
    def write(self,relative,data,owner=None):
        p=self.path(relative);require(not p.exists() and not p.is_symlink(),'ISOLATION_BLOCKED')
        self.b.private_write(p,data)
        if owner:os.chown(p,owner.pw_uid,owner.pw_gid)
        return p
    def host(self,args):
        r=subprocess.run(args,stdout=subprocess.PIPE,stderr=subprocess.PIPE,timeout=min(30,self.budget.remaining()),env={'PATH':'/usr/bin:/usr/sbin:/bin','LC_ALL':'C'})
        require(r.returncode==0,'ISOLATION_BLOCKED');return r.stdout
    def prefix(self,web=False):
        user=self.web if web else self.mysql
        return ['/usr/sbin/chroot',str(self.jail),'/usr/bin/setpriv','--reuid='+str(user.pw_uid),'--regid='+str(user.pw_gid),'--clear-groups','--no-new-privs','--bounding-set=-all','--inh-caps=-all','--ambient-caps=-all']
    def execute(self,args,web=False,data=None,daemon=False,pass_fds=()):
        self.check();log=self.work/'logs'/('process-'+secrets.token_hex(6)+'.log')
        output=open(log,'xb');os.chmod(log,0o600)
        with contextlib.ExitStack() as stack:
            incoming=data if hasattr(data,'fileno') else stack.enter_context(os.fdopen(os.memfd_create('clone-stdin',0),'w+b'))
            if isinstance(data,bytes):incoming.write(data);incoming.seek(0)
            response=stack.enter_context(tempfile.TemporaryFile(dir=self.work))
            def limits():resource.setrlimit(resource.RLIMIT_FSIZE,(4*1024**3,4*1024**3))
            proc=subprocess.Popen(self.prefix(web)+args,stdin=incoming,stdout=output if daemon else response,stderr=output,
                                  pass_fds=pass_fds,preexec_fn=limits,env={'PATH':'/usr/bin:/bin','LC_ALL':'C','TZ':'UTC'})
            if daemon:
                self.children.append((proc,output));return proc
            try:
                while proc.poll() is None:
                    self.check();require(log.stat().st_size<=16*1024**2,'DEPENDENCY_BLOCKED')
                    require(os.fstat(response.fileno()).st_size<=64*1024**2,'DATABASE_INTEGRITY_BLOCKED');time.sleep(.05)
                require(proc.returncode==0,'DATABASE_IMPORT_BLOCKED');self.check()
                require(os.fstat(response.fileno()).st_size<=64*1024**2,'DATABASE_INTEGRITY_BLOCKED')
                response.seek(0);return response.read()
            finally:
                if proc.poll() is None:proc.kill()
                proc.wait();output.close()

    def sql(self,statement=None,database=True,input_file=None):
        secret=self.root_password
        fd=os.memfd_create('clone-client',0)
        body='[client]\nuser=root\npassword='+secret+'\nprotocol=SOCKET\nsocket='+SOCKET+'\ndefault-character-set=utf8mb4\n'
        os.write(fd,body.encode());os.lseek(fd,0,0);os.fchown(fd,self.mysql.pw_uid,self.mysql.pw_gid);os.fchmod(fd,0o400)
        try:
            args=['/usr/bin/mariadb','--defaults-file=/proc/self/fd/'+str(fd),'--protocol=SOCKET','--socket='+SOCKET,'--batch','--raw','--skip-column-names']
            if database:args+=['--database='+self.b.DATABASE]
            return self.execute(args,data=input_file if input_file is not None else statement.encode(),pass_fds=(fd,))
        finally:os.close(fd)
    def php_flags(self):
        result=[self.php,'-n']
        r=self.execute([self.php,'-n','-r','echo json_encode([ini_get("extension_dir"),get_loaded_extensions()]);'],web=True)
        directory,loaded=json.loads(r);require(directory.startswith(str(Path(self.php).parents[1])),'DEPENDENCY_BLOCKED')
        for name in ('mysqlnd','mysqli','mbstring','dom'):
            if name not in loaded:
                module=Path(directory)/(name+'.so');require(module.is_file(),'DEPENDENCY_BLOCKED');result+=['-d','extension='+str(module)]
        return result+['-d','sendmail_path=/bin/false','-d','auto_prepend_file=','-d','auto_append_file=',
                       '-d','disable_functions=mail,exec,shell_exec,system,passthru,proc_open,popen,pcntl_exec,pcntl_fork,dl']
    def stop(self):
        ok=True
        for proc,output in reversed(self.children):
            try:
                if proc.poll() is None:proc.terminate()
                try:proc.wait(timeout=8)
                except subprocess.TimeoutExpired:proc.kill();proc.wait(timeout=5)
                ok=ok and proc.poll() is not None
            except Exception:ok=False
            finally:output.close()
        self.children=[];self.root_password='';self.app_password=''
        for relative in ('jail/work/login-input.json','jail/site/wp-config.php'):
            p=self.path(relative)
            if p.is_file() and not p.is_symlink():p.unlink()
        return ok


def setup_jail(rt,source,parent_ns):
    with stage('ISOLATION_BLOCKED'):
        require(os.getpid()==1,'ISOLATION_BLOCKED')
        require(set(parent_ns)=={'mnt','net','pid'},'ISOLATION_BLOCKED')
        require(all(os.readlink('/proc/self/ns/'+n)!=parent_ns[n] for n in parent_ns),'ISOLATION_BLOCKED')
        rt.host(['/usr/bin/mount','--make-rprivate','/'])
        rt.host(['/usr/sbin/ip','link','set','lo','up'])
        require([name for _,name in socket.if_nameindex()]==['lo'],'ISOLATION_BLOCKED')
        require(len(Path('/proc/net/route').read_text().splitlines())<=1,'ISOLATION_BLOCKED')
        rt.mkdir('jail',0o755);rt.mkdir('logs')
        # Read-only executable/library trees, no /etc,/root,/home,/var,/run or generic /tmp.
        software=['/usr/bin','/usr/sbin','/usr/lib','/usr/share/mysql']
        if Path('/usr/lib64').exists():software.append('/usr/lib64')
        phpbase=Path(rt.php).parents[1]
        for suffix in ('bin','lib','lib64'):
            candidate=phpbase/suffix
            if candidate.exists() and not str(candidate).startswith('/usr/'):software.append(str(candidate))
        if str(phpbase)=='/usr/local':software+=['/usr/local/bin','/usr/local/lib']
        for value in sorted(set(software)):
            src=Path(value);require(src.is_dir(),'DEPENDENCY_BLOCKED')
            for ancestor in reversed(src.parents):rt.mkdir('jail'+str(ancestor),0o755)
            dest=rt.mkdir('jail'+value,0o755)
            rt.host(['/usr/bin/mount','--bind',str(src),str(dest)])
            rt.host(['/usr/bin/mount','-o','remount,bind,ro,nosuid,nodev',str(dest)])
            require(os.statvfs(dest).f_flag & os.ST_RDONLY,'ISOLATION_BLOCKED')
        # Honor the host's merged-/usr layout without exposing another root.
        for name in ('bin','sbin','lib','lib64'):
            src=Path('/')/name
            if src.is_symlink():
                target=os.readlink(src);resolved=src.resolve(strict=True)
                require(resolved.is_relative_to('/usr'),'DEPENDENCY_BLOCKED')
                rt.path('jail/'+name).symlink_to(str(resolved))
            elif src.is_dir():
                dest=rt.mkdir('jail/'+name,0o755)
                rt.host(['/usr/bin/mount','--bind',str(src),str(dest)])
                rt.host(['/usr/bin/mount','-o','remount,bind,ro,nosuid,nodev',str(dest)])
        for directory in ('etc','dev','proc','site','work','work/mysql','work/mysql/data','work/mysql/tmp','tmp'):
            rt.mkdir('jail/'+directory,0o755 if directory in ('etc','dev','proc','work','work/mysql') else 0o700)
        for name in ('null','zero','random','urandom'):
            src=Path('/dev')/name;require(stat.S_ISCHR(src.stat().st_mode),'DEPENDENCY_BLOCKED')
            target=rt.write('jail/dev/'+name,b'')
            rt.host(['/usr/bin/mount','--bind',str(src),str(target)])
            rt.host(['/usr/bin/mount','-o','remount,bind,ro,nosuid,noexec',str(target)])
        rt.path('jail/dev/fd').symlink_to('/proc/self/fd')
        rt.host(['/usr/bin/mount','-t','proc','-o','nosuid,nodev,noexec,hidepid=2','proc',str(rt.jail/'proc')])
        for p in ('work/mysql','work/mysql/data','work/mysql/tmp','tmp'):os.chown(rt.jail/p,rt.mysql.pw_uid,rt.mysql.pw_gid)
        rt.mkdir('jail/work/blocked-io',0o555);os.chmod(rt.jail/'tmp',0o1777)
        passwd='root:x:0:0:root:/root:/bin/false\n'
        for user in (rt.mysql,rt.web):passwd+=user.pw_name+':x:'+str(user.pw_uid)+':'+str(user.pw_gid)+':restore:/work:/bin/false\n'
        rt.write('jail/etc/passwd',passwd.encode());os.chmod(rt.jail/'etc/passwd',0o644)
        groups='root:x:0:\n'+''.join(user.pw_name+':x:'+str(user.pw_gid)+':\n' for user in (rt.mysql,rt.web))
        rt.write('jail/etc/group',groups.encode());os.chmod(rt.jail/'etc/group',0o644)
        rt.write('jail/etc/nsswitch.conf',b'passwd: files\ngroup: files\nhosts: files\n');os.chmod(rt.jail/'etc/nsswitch.conf',0o644)
        rt.write('jail/etc/hosts',b'127.0.0.1 localhost\n');os.chmod(rt.jail/'etc/hosts',0o644)
        target=rt.mkdir('jail/input')
        rt.host(['/usr/bin/mount','--bind',str(source),str(target)])
        rt.host(['/usr/bin/mount','-o','remount,bind,ro,nosuid,nodev,noexec',str(target)])
        require(os.statvfs(target).f_flag & os.ST_RDONLY,'ISOLATION_BLOCKED')
        # Check effective UID, capability drop, NNP, and lack of host filesystem access
        # before invoking a database or executing any restored WordPress code.
        probe="import os,pathlib; s=pathlib.Path('/proc/self/status').read_text(); assert int(s.split('CapEff:')[1].split()[0],16)==0; assert s.split('NoNewPrivs:')[1].split()[0]=='1'; assert os.geteuid()!=0; assert not pathlib.Path('/var/www/vhosts').exists(); assert not pathlib.Path('/run/mysqld').exists(); assert not pathlib.Path('/root/.my.cnf').exists(); assert os.statvfs('/usr/bin').f_flag & os.ST_RDONLY; print('ISOLATED')"
        for web in (False,True):require(rt.execute(['/usr/bin/python3','-I','-B','-c',probe],web=web).strip()==b'ISOLATED','ISOLATION_BLOCKED')


def extract_archive(rt,archive,destination,allowed,expected):
    b=rt.b;rt.check();b.validate_archive(archive,set(allowed),budget=rt.budget,expected_files=expected)
    destination.mkdir(parents=True,exist_ok=True,mode=0o700)
    with tarfile.open(archive,'r:gz') as source:
        for entry in source:
            rt.check();name=b.safe_name(entry.name);target=destination/name
            require(target.resolve().is_relative_to(destination),'ARCHIVE_BLOCKED')
            if entry.isdir():target.mkdir(parents=True,exist_ok=True,mode=0o700);continue
            require(entry.isfile() and not target.exists() and not target.is_symlink(),'ARCHIVE_BLOCKED')
            target.parent.mkdir(parents=True,exist_ok=True,mode=0o700)
            fd=os.open(target,os.O_CREAT|os.O_EXCL|os.O_WRONLY|os.O_NOFOLLOW,0o600)
            with os.fdopen(fd,'wb') as out,source.extractfile(entry) as inp:
                while True:
                    rt.check();chunk=inp.read(1024*1024)
                    if not chunk:break
                    out.write(chunk)
            require(b.hash_regular(target,b.MAX_BYTES,rt.budget)[0]==expected[name]['sha256'],'ARCHIVE_BLOCKED')


def unpack(rt):
    b=rt.b;source=rt.jail/'input'
    with stage('ARCHIVE_BLOCKED'):
        attrs=json.loads((source/'file-attributes.json').read_text());files=attrs['before'];dirs=attrs['directories']
        for name in [*files,*dirs]:require(b.safe_name(name)==name,'ARCHIVE_BLOCKED')
        rootfiles={n:v for n,v in files.items() if n!='wp-config.php' and not n.startswith('wp-content/')}
        contentfiles={n:v for n,v in files.items() if n.startswith('wp-content/')}
        rootdirs={n for n in dirs if n!='wp-content' and not n.startswith('wp-content/')}
        contentdirs=set(dirs)-rootdirs
        extract_archive(rt,source/'wordpress-root.tar.gz',rt.jail/'site',set(rootfiles)|rootdirs,rootfiles)
        extract_archive(rt,source/'wp-content.tar.gz',rt.jail/'site',set(contentfiles)|contentdirs,contentfiles)
        raw,_=b.read_regular(source/'wp-config.php.recovery',16*1024**2,rt.budget)
        rt.write('jail/site/wp-config.php',raw)
        for name,info in files.items():require(b.hash_regular(rt.jail/'site'/name,b.MAX_BYTES,rt.budget)[0]==info['sha256'],'ARCHIVE_BLOCKED')
        extract_archive(rt,source/'protected-configs.tar.gz',rt.mkdir('protected'),set(attrs['protected']),attrs['protected'])
        require(b.sha(raw)==files['wp-config.php']['sha256'],'ARCHIVE_BLOCKED')
        return raw


def import_database(rt):
    b=rt.b;source=rt.jail/'input'
    with stage('DATABASE_IMPORT_BLOCKED'):
        # Validate executable structure in an anonymous spool, then import that exact stream.
        with tempfile.TemporaryFile(dir=rt.work) as spool:
            with gzip.open(source/'database.sql.gz','rb') as inp:
                size=0
                while True:
                    rt.check();chunk=inp.read(1024*1024)
                    if not chunk:break
                    size+=len(chunk);require(size<2*1024**3,'DATABASE_IMPORT_BLOCKED');spool.write(chunk)
            spool.seek(0)
            text=io.TextIOWrapper(spool,encoding='utf-8',errors='strict')
            try:expected=validate_sql(b,text,{c['table'] for c in json.loads((source/'before.catalog.json').read_text())['columns']})
            finally:text.detach()
            spool.seek(0)
            rt.execute(['/usr/bin/mariadb-install-db','--no-defaults','--basedir=/usr','--datadir='+DATADIR,'--auth-root-authentication-method=normal','--skip-test-db'])
            rt.execute(['/usr/sbin/mariadbd','--no-defaults','--basedir=/usr','--datadir='+DATADIR,
                        '--socket='+SOCKET,'--pid-file=/work/mysql/server.pid','--skip-networking','--skip-log-bin',
                        '--event-scheduler=OFF','--local-infile=0','--tmpdir=/work/mysql/tmp','--secure-file-priv=/work/blocked-io',
                        '--innodb-buffer-pool-size=64M','--innodb-log-file-size=32M','--max-connections=8',
                        '--max-allowed-packet=64M','--log-error=/work/mysql/server.log'],daemon=True)
            for attempt in range(30):
                try:rt.sql('SELECT 1;',database=False);break
                except Failure:
                    if attempt==29:raise
                    time.sleep(1)
            rt.root_password=secrets.token_hex(32)
            password=rt.root_password;rt.root_password=''
            rt.sql("ALTER USER 'root'@'localhost' IDENTIFIED BY '"+password+"';CREATE DATABASE "+b.DATABASE+" CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;",database=False)
            rt.root_password=password
            rt.sql(input_file=spool)
        result=rt.sql('CHECK TABLE '+','.join('`'+b.PREFIX+n+'`' for n in sorted(expected))+';')
        require(result.count(b'\tstatus\tOK')==len(expected),'DATABASE_INTEGRITY_BLOCKED')
        adapter=b.NativeDB({'user':'unused','password':'unused','host':'localhost'},rt.budget)
        adapter.query=rt.sql
        catalog=adapter.catalog()
        original_catalog=json.loads((source/'before.catalog.json').read_text())
        catalog={k:v for k,v in catalog.items() if k in original_catalog}
        plan=b.load_generator()(catalog);rows=rt.sql(plan)
        baseline=b.parse_rows(rows,catalog)
        rt.write('artifact.catalog.json',b.canonical(catalog)+b'\n');rt.write('artifact.rows.ndjson',rows)
        for name,count in expected.items():
            value=rt.sql('SELECT COUNT(*) FROM `'+b.PREFIX+name+'`;').strip()
            require(value==str(count).encode(),'DATABASE_INTEGRITY_BLOCKED')
        previous=b.parse_rows((source/'before.rows.ndjson').read_bytes(),json.loads((source/'before.catalog.json').read_text()))
        require(previous['schema_digest']==baseline['schema_digest'],'DATABASE_INTEGRITY_BLOCKED')
        policy=json.loads(b.POLICY_TEXT) if hasattr(b,'POLICY_TEXT') else json.loads((b.BASE/'docs/pilot/baseline-contract.json').read_text())
        comparison=b.drift(previous,baseline,policy)
        rt.write('artifact-source-comparison.json',b.canonical(comparison)+b'\n')
        require(not comparison['review_required'],'DATABASE_INTEGRITY_BLOCKED')
        if b.schema_contract()['phase'](original_catalog) in ('scheduling_v3','scheduling_v4'):
            pairs=[('allocations','appointment_id','appointments'),('allocations','resource_id','resources'),('participants','appointment_id','appointments'),('participants','rider_id','riders'),('slots','appointment_id','appointments')]
            for child,key,parent in pairs:
                sql='SELECT COUNT(*) FROM `'+b.PREFIX+'csf_sched_'+child+'` c LEFT JOIN `'+b.PREFIX+'csf_sched_'+parent+'` p ON c.`'+key+'`=p.id WHERE p.id IS NULL;'
                require(rt.sql(sql).strip()==b'0','DATABASE_INTEGRITY_BLOCKED')
            rt.write('scheduling-relationships.json',b.canonical({'checked':len(pairs),'orphans':0})+b'\n')
        return adapter,baseline


GUARD_PHP=b'''<?php
// Clone-only containment; never installed on the farm.
add_filter('pre_wp_mail', function(){ return false; }, -999999);
add_filter('pre_http_request', function(){ return new WP_Error('restore_offline','Restore is offline'); }, -999999, 3);
'''
ROUTER_PHP=b'''<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (!is_string($path) || str_contains($path,"..")) { http_response_code(400); exit; }
$file=realpath('/site'.$path);
if ($file && str_starts_with($file,'/site/') && is_file($file) && basename($file)!=='wp-config.php') return false;
if ($file && is_dir($file) && is_file($file.'/index.php')) { chdir($file); require $file.'/index.php'; exit; }
require '/site/index.php';
'''
LOGIN_PHP=b'''<?php
require '/site/wp-load.php';
$s=json_decode(file_get_contents('/work/login-input.json'),true,512,JSON_THROW_ON_ERROR);
if (($s['action']??'password')==='schema') {
 require_once ABSPATH.'wp-content/plugins/csf-scheduling/csf-scheduling.php';
 echo json_encode(['ready'=>CSF_Scheduling_Schema::inspect()['ready']]);exit;
}
if (($s['action']??'password')==='totp') {
 $u=get_userdata((int)$s['id']);
 if(!class_exists('Two_Factor_Core')||!Two_Factor_Core::is_user_using_two_factor($u)){echo json_encode(['required'=>false]);exit;}
 $provider=Two_Factor_Core::get_primary_provider_for_user($u);
 if(!($provider instanceof Two_Factor_Totp))exit(3);
 $last=(int)get_user_meta($u->ID,'_two_factor_totp_last_successful_login',true);
 $wait=max(0,$last+30-time());if($wait>31)exit(4);if($wait)sleep($wait);
 echo json_encode(['required'=>true,'code'=>Two_Factor_Totp::calc_totp($provider->get_user_totp_key($u->ID))]);exit;
}
global $wpdb;
$n=$wpdb->update($wpdb->users, ['user_pass'=>wp_hash_password($s['password'])], ['ID'=>(int)$s['id']]);
if ($n===false) exit(2);
echo "OK";
'''


def prepare_wordpress(rt,raw_config):
    b=rt.b
    with stage('SOURCE_SECURITY_BLOCKED'):
        plugins=rt.sql("SELECT option_value FROM TXGEh_options WHERE option_name='active_plugins';").strip()
        plugin_names(plugins)
        site=rt.jail/'site';mu=site/'wp-content/mu-plugins'
        require(not mu.exists() or not list(mu.rglob('*.php')),'SOURCE_SECURITY_BLOCKED')
        for name in ('db.php','object-cache.php','advanced-cache.php','sunrise.php'):
            require(not (site/'wp-content'/name).exists(),'SOURCE_SECURITY_BLOCKED')
        require(security_config_safe(raw_config),'SOURCE_SECURITY_BLOCKED')
    with stage('CLONE_CONFIG_BLOCKED'):
        rt.app_password=secrets.token_hex(32)
        rt.sql("CREATE USER 'csf_restore_app'@'localhost' IDENTIFIED BY '"+rt.app_password+"';GRANT ALL PRIVILEGES ON "+b.DATABASE+".* TO 'csf_restore_app'@'localhost';",database=False)
        config="<?php\ndefine('DB_NAME','"+b.DATABASE+"');define('DB_USER','csf_restore_app');define('DB_PASSWORD','"+rt.app_password+"');define('DB_HOST','localhost:"+SOCKET+"');define('DB_CHARSET','utf8mb4');define('DB_COLLATE','');\n"
        for name in ('AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'):config+="define('"+name+"','"+secrets.token_hex(32)+"');\n"
        for key,value in security_flags(raw_config).items():config+="define('"+key+"',"+('true' if value else 'false')+");\n"
        config+="$table_prefix='"+b.PREFIX+"';define('WP_ENVIRONMENT_TYPE','local');define('DISABLE_WP_CRON',true);define('DISALLOW_FILE_MODS',true);define('DISALLOW_FILE_EDIT',true);define('WP_HTTP_BLOCK_EXTERNAL',true);define('AUTOMATIC_UPDATER_DISABLED',true);define('WP_DEBUG_DISPLAY',false);define('WP_HOME','"+ORIGIN+"');define('WP_SITEURL','"+ORIGIN+"');if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/');require_once ABSPATH.'wp-settings.php';\n"
        (site/'wp-config.php').unlink();rt.write('jail/site/wp-config.php',config.encode())
        rt.sql("UPDATE TXGEh_options SET option_value='"+ORIGIN+"' WHERE option_name IN ('home','siteurl');")
        mu.mkdir(exist_ok=True,mode=0o700);rt.write('jail/site/wp-content/mu-plugins/csf-restore-guard.php',GUARD_PHP)
        for root,dirs,files in os.walk(site,followlinks=False):
            os.chown(root,rt.web.pw_uid,rt.web.pw_gid);os.chmod(root,0o750)
            for name in files:
                p=Path(root)/name;require(not p.is_symlink(),'ISOLATION_BLOCKED');os.chown(p,rt.web.pw_uid,rt.web.pw_gid);os.chmod(p,0o640)
        rt.write('jail/work/router.php',ROUTER_PHP,rt.web);rt.write('jail/work/login.php',LOGIN_PHP,rt.web)
        return rt.php_flags()


class LoopbackRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,req,fp,code,msg,headers,newurl):
        target=urllib.parse.urlsplit(newurl);require(target.scheme=='http' and target.netloc=='127.0.0.1:8899' and target.username is None,'PAGE_TEST_BLOCKED')
        return super().redirect_request(req,fp,code,msg,headers,newurl)


def http_client():
    jar=http.cookiejar.CookieJar()
    return urllib.request.build_opener(urllib.request.ProxyHandler({}),LoopbackRedirect(),urllib.request.HTTPCookieProcessor(jar)),jar


def request(opener,path,data=None):
    url=urllib.parse.urljoin(ORIGIN+'/',path);parsed=urllib.parse.urlsplit(url)
    require(parsed.scheme=='http' and parsed.netloc=='127.0.0.1:8899' and parsed.username is None,'PAGE_TEST_BLOCKED')
    with opener.open(url,data=data,timeout=15) as result:
        body=result.read(4*1024*1024+1);require(len(body)<=4*1024*1024,'PAGE_TEST_BLOCKED')
        return result.status,result.geturl(),body


class FactorForm(html.parser.HTMLParser):
    def __init__(self):super().__init__();self.forms=[];self.current=None
    def handle_starttag(self,tag,attrs):
        attrs=dict(attrs)
        if tag=='form':self.current={'action':attrs.get('action',''),'fields':{}}
        if tag=='input' and self.current is not None and attrs.get('name'):
            self.current['fields'][attrs['name']]=attrs.get('value','')
    def handle_endtag(self,tag):
        if tag=='form' and self.current is not None:self.forms.append(self.current);self.current=None


def functional_tests(rt,php_flags,login_choice):
    with stage('PAGE_TEST_BLOCKED'):
        rt.execute(php_flags+['-S','127.0.0.1:8899','-t','/site','/work/router.php'],web=True,daemon=True)
        opener,jar=http_client()
        for attempt in range(30):
            try:status,url,body=request(opener,'/');break
            except (OSError,urllib.error.URLError):
                if attempt==29:raise
                time.sleep(.25)
        for path in ('/','/lessons/','/contact/'):
            status,url,body=request(opener,path)
            require(status==200 and b'<html' in body.lower() and b'<title>' in body.lower(),'PAGE_TEST_BLOCKED')
            require(not any(error in body.lower() for error in (b'there has been a critical error',b'error establishing a database connection',b'fatal error')),'PAGE_TEST_BLOCKED')
    if login_choice=='skip':return False
    saved=None
    with stage('LOGIN_TEST_BLOCKED'):
        # Roles, login and state stay private. Never infer a real human identity.
        raw=rt.sql("SELECT JSON_OBJECT('id',u.ID,'login',u.user_login,'hash',u.user_pass,'activation',u.user_activation_key) FROM TXGEh_users u JOIN TXGEh_usermeta m ON m.user_id=u.ID WHERE m.meta_key='TXGEh_capabilities' AND m.meta_value LIKE '%\"administrator\";b:1%';")
        accounts=[json.loads(line) for line in raw.splitlines()];require(len(accounts)==1,'LOGIN_IDENTITY_BLOCKED');saved=accounts[0]
        uid=int(saved['id']);require(uid>0,'LOGIN_TEST_BLOCKED')
        sessions=rt.sql("SELECT JSON_OBJECT('id',umeta_id,'value',meta_value) FROM TXGEh_usermeta WHERE user_id="+str(uid)+" AND meta_key='session_tokens';")
        saved['sessions']=[json.loads(line) for line in sessions.splitlines()]
        saved['metadata']=[json.loads(line) for line in rt.sql("SELECT JSON_OBJECT('id',umeta_id,'key',meta_key,'value',meta_value) FROM TXGEh_usermeta WHERE user_id="+str(uid)+' ORDER BY umeta_id;').splitlines()]
        password=secrets.token_hex(24)
        rt.write('jail/work/login-input.json',rt.b.canonical({'id':uid,'password':password}),rt.web)
        try:
            helper=rt.execute(php_flags+['/work/login.php'],web=True)
            if helper.strip()!=b'OK':
                rt.write('login-helper-private.log',helper)
                raise Failure('LOGIN_HELPER_BLOCKED')
            opener,jar=http_client();request(opener,'/wp-login.php')
            fields=urllib.parse.urlencode({'log':saved['login'],'pwd':password,'wp-submit':'Log In','redirect_to':ORIGIN+'/wp-admin/','testcookie':'1'}).encode()
            status,url,body=request(opener,'/wp-login.php',fields)
            # Derive only an ephemeral TOTP inside the isolated clone. No seeds/codes
            # enter public output; source factors/recovery codes are never regenerated.
            p=rt.jail/'work/login-input.json';p.unlink()
            rt.write('jail/work/login-input.json',rt.b.canonical({'id':uid,'action':'totp'}),rt.web)
            factor=json.loads(rt.execute(php_flags+['/work/login.php'],web=True))
            if factor.get('required') is True:
                parser=FactorForm();parser.feed(body.decode('utf-8'))
                forms=[form for form in parser.forms if 'authcode' in form['fields']]
                require(len(forms)==1 and re.fullmatch('[0-9]{6}',factor.get('code','')),'LOGIN_SESSION_BLOCKED')
                form=forms[0];require(form['fields'].get('wp-auth-id')==str(uid),'LOGIN_SESSION_BLOCKED')
                form['fields']['authcode']=factor['code']
                status,url,body=request(opener,html.unescape(form['action']),urllib.parse.urlencode(form['fields']).encode())
                factor.clear()
            status,url,body=request(opener,'/wp-admin/')
            require(status==200 and b'id="wpbody-content"' in body and any(c.name.startswith('wordpress_logged_in_') for c in jar),'LOGIN_SESSION_BLOCKED')
            matches=re.findall(rb'href=[\"\']([^\"\']*wp-login\.php\?action=logout[^\"\']*)',body)
            require(matches,'LOGOUT_TEST_BLOCKED');logout=html.unescape(matches[0].decode())
            request(opener,logout);status,url,body=request(opener,'/wp-admin/')
            require('/wp-login.php' in url and not any(c.name.startswith('wordpress_logged_in_') for c in jar),'LOGOUT_TEST_BLOCKED')
        finally:
            def value(v):return 'NULL' if v is None else "CONVERT(0x"+v.encode('utf-8').hex()+" USING utf8mb4)" if v else "''"
            rt.sql('UPDATE TXGEh_users SET user_pass='+value(saved['hash'])+',user_activation_key='+value(saved['activation'])+' WHERE ID='+str(uid)+';')
            rt.sql("DELETE FROM TXGEh_usermeta WHERE user_id="+str(uid)+';')
            for row in saved['metadata']:
                rt.sql("INSERT INTO TXGEh_usermeta(umeta_id,user_id,meta_key,meta_value) VALUES("+str(int(row['id']))+','+str(uid)+","+value(row['key'])+','+value(row['value'])+');')
            restored=rt.sql("SELECT JSON_OBJECT('hash',user_pass,'activation',user_activation_key) FROM TXGEh_users WHERE ID="+str(uid)+';')
            require(json.loads(restored)=={'hash':saved['hash'],'activation':saved['activation']},'LOGIN_TEST_BLOCKED')
            restored_sessions=rt.sql("SELECT JSON_OBJECT('id',umeta_id,'value',meta_value) FROM TXGEh_usermeta WHERE user_id="+str(uid)+" AND meta_key='session_tokens';")
            require(sorted([json.loads(line) for line in restored_sessions.splitlines()],key=lambda x:x['id'])==sorted(saved['sessions'],key=lambda x:x['id']),'LOGIN_TEST_BLOCKED')
            restored_meta=[json.loads(line) for line in rt.sql("SELECT JSON_OBJECT('id',umeta_id,'key',meta_key,'value',meta_value) FROM TXGEh_usermeta WHERE user_id="+str(uid)+' ORDER BY umeta_id;').splitlines()]
            require(restored_meta==saved['metadata'],'LOGIN_TEST_BLOCKED')
            p=rt.jail/'work/login-input.json'
            if p.is_file():p.unlink()
    return True


def worker(b,binding,params,parent_ns):
    budget=params_check(b,params);source,work=selected_paths_for_worker(b,binding)
    rt=Runtime(b,work,budget);receipt={'state':'failed','code':'RESTORE_UNCLASSIFIED','integrity_verified':False,'isolation_verified':False,'pages_verified':False,'login_verified':False,'cleanup_verified':False,'restore_verified':False}
    try:
        setup_jail(rt,source,parent_ns);receipt['isolation_verified']=True
        with stage('SOURCE_BINDING_BLOCKED'):verify_input(b,rt.jail/'input',binding)
        raw_config=unpack(rt);import_database(rt);receipt['integrity_verified']=True
        flags=prepare_wordpress(rt,raw_config)
        phase=b.schema_contract()['phase'](json.loads((source/'before.catalog.json').read_text()))
        app_schema_ready=None
        if phase in ('scheduling_v3','scheduling_v4'):
            rt.write('jail/work/login-input.json',b.canonical({'action':'schema'}),rt.web)
            app_schema_ready=json.loads(rt.execute(flags+['/work/login.php'],web=True)).get('ready') is True
            (rt.jail/'work/login-input.json').unlink()
        login=functional_tests(rt,flags,params['login_test']);receipt['pages_verified']=True;receipt['login_verified']=login
        with stage('SOURCE_BINDING_BLOCKED'):verify_input(b,rt.jail/'input',binding)
        receipt['code']='ISOLATED_RESTORE_VERIFIED' if login else 'LOGIN_NOT_TESTED'
        receipt['state']='verified' if login else 'partial';receipt['restore_verified']=login
        catalog=json.loads((source/'before.catalog.json').read_text())
        phase=b.schema_contract()['phase'](catalog)
        b.write_json(work/'schema-state.json',{'phase':phase,'tables_preserved':len({c['table'] for c in catalog['columns']}),'application_schema_ready':app_schema_ready})
        if phase not in ('pre_scheduling','scheduling_v3','scheduling_v4') or (phase in ('scheduling_v3','scheduling_v4') and app_schema_ready is not True):
            receipt.update(state='partial',code='SCHEMA_STATE_REQUIRES_REVIEW',restore_verified=False)
    except Failure as error:receipt['code']=error.code
    except (Exception,KeyboardInterrupt):receipt['code']='RESTORE_UNCLASSIFIED'
    finally:
        try:receipt['cleanup_verified']=rt.stop()
        except Exception:receipt['cleanup_verified']=False
        if not receipt['cleanup_verified']:
            receipt.update(state='failed',code='CLEANUP_BLOCKED',restore_verified=False)
        b.write_json(work/'restore-result.json',receipt)
    return receipt


def selected_paths_for_worker(b,binding):
    source=b.PARENT/binding['bundle_id'];work=b.PARENT/('csf-gate-b-recovery-'+binding['recovery_token'])
    require(source.resolve(strict=True)==source and work.resolve(strict=True)==work and work.is_dir(),'SOURCE_BINDING_BLOCKED')
    require(stat.S_IMODE(work.stat().st_mode)==0o700,'SOURCE_BINDING_BLOCKED')
    return source,work


def plugin_names(raw):
    """Decode only a bounded flat indexed list, never PHP unserialize arbitrary data."""
    if isinstance(raw,bytes):raw=raw.decode('utf-8')
    match=re.fullmatch(r'a:([0-4]):\{(.*)\}',raw,re.S)
    require(match is not None,'SOURCE_SECURITY_BLOCKED')
    names=[];offset=0
    while offset<len(match[2]):
        row=re.match(r'i:([0-3]);s:([0-9]+):"([a-z0-9/-]+\.php)";',match[2][offset:])
        require(row is not None and int(row[1])==len(names) and int(row[2])==len(row[3]),'SOURCE_SECURITY_BLOCKED')
        names.append(row[3]);offset+=row.end()
    allowed={'csf-core/csf-core.php','csf-scheduling/csf-scheduling.php','csf-mfa/csf-mfa.php','two-factor/two-factor.php'}
    require(len(names)==int(match[1]) and len(set(names))==len(names) and set(names)<=allowed,'SOURCE_SECURITY_BLOCKED')
    require('csf-mfa/csf-mfa.php' not in names or 'two-factor/two-factor.php' in names,'SOURCE_SECURITY_BLOCKED')
    return names


def security_flags(data):
    """Accept only top-level literal approved booleans; source PHP is never evaluated."""
    text=data.decode('utf-8');out=[];flags={};i=0;depth=0
    while i<len(text):
        if text.startswith('/*',i):
            end=text.find('*/',i+2);require(end>=0,'SOURCE_SECURITY_BLOCKED');i=end+2;continue
        if text.startswith('//',i) or text[i]=='#':
            end=text.find('\n',i);i=len(text) if end<0 else end;continue
        previous=''.join(out).rsplit(';',1)[-1].replace('<?php','').strip() if text.startswith('define',i) else 'not_definition'
        match=re.match(r"define\s*\(\s*(['\"])(CSF_MFA_ENABLED|CSF_MFA_ENFORCE_ADMIN)\1\s*,\s*(true|false)\s*\)\s*;",text[i:]) if depth==0 and not previous else None
        if match:
            name=match[2];require(name not in flags,'SOURCE_SECURITY_BLOCKED')
            flags[name]=match[3]=='true';out.append('APPROVED_LITERAL;');i+=match.end();continue
        if text[i] in "\"'":
            quote=text[i];start=i;i+=1
            while i<len(text):
                if text[i]=='\\':i+=2;continue
                if text[i]==quote:i+=1;break
                i+=1
            else:raise Failure('SOURCE_SECURITY_BLOCKED')
            out.append(text[start:i]);continue
        if text[i] in '{(':depth+=1
        elif text[i] in '})':depth-=1
        out.append(text[i]);i+=1
    clean=''.join(out)
    require(not re.search(r'(?<![A-Za-z0-9_])(?:CSF_MFA_[A-Za-z0-9_]*|TWO_FACTOR[A-Za-z0-9_]*|FORCE_SSL_ADMIN|MULTISITE)(?![A-Za-z0-9_])',clean),'SOURCE_SECURITY_BLOCKED')
    return flags


def security_config_safe(data):
    try:security_flags(data);return True
    except Failure:return False


def source_profile(b,binding):
    source,_=selected_paths(b,binding);target=verify_input(b,source,binding)
    attrs=json.loads((source/'file-attributes.json').read_text());names=set(attrs['before'])
    raw=(source/'wp-config.php.recovery').read_bytes()
    plugins=target['before_guard']['plugins']
    active_names=re.findall(r's:\d+:"([A-Za-z0-9_./-]+)";',plugins)
    try:plugin_names(plugins);supported=True
    except Failure:supported=False
    return {'state':'source-profile','plugins_supported':supported,
            'active_plugin_count':len(active_names),'mfa_plugin_detected':any(any(key in n.lower() for key in ('two-factor','wordfence','csf-mfa')) for n in active_names),
            'mustuse_php_present':any(n.startswith('wp-content/mu-plugins/') and n.endswith('.php') for n in names),
            'dropin_present':bool(names.intersection({'wp-content/db.php','wp-content/object-cache.php','wp-content/advanced-cache.php','wp-content/sunrise.php'})),
            'config_marker_present':any(marker in raw for marker in (b'CSF_MFA_',b'TWO_FACTOR',b'FORCE_SSL_ADMIN',b'MULTISITE')), 'executable_config_marker_present':not security_config_safe(raw), 'marker_classes':{key:bool(re.search(r'(?<![A-Za-z0-9_])'+re.escape(key)+r'(?![A-Za-z0-9_])',raw.decode())) for key in ('CSF_MFA_ENFORCE_ADMIN','TWO_FACTOR','FORCE_SSL_ADMIN','MULTISITE','WP_ALLOW_MULTISITE')}}


def preflight(b,binding):
    binding_check(b,binding)
    with stage('SOURCE_BINDING_BLOCKED'):
        source,dest=selected_paths(b,binding);verify_input(b,source,binding)
    with stage('DEPENDENCY_BLOCKED'):
        for tool in ('/usr/bin/unshare','/usr/bin/mount','/usr/sbin/ip','/usr/sbin/chroot','/usr/bin/setpriv','/usr/bin/mariadb','/usr/sbin/mariadbd','/usr/bin/mariadb-install-db','/usr/bin/python3','/usr/bin/timeout',PHP):
            require(Path(tool).is_file() and os.access(tool,os.X_OK),'DEPENDENCY_BLOCKED')
        require(os.geteuid()==0 and hasattr(os,'pidfd_open') and hasattr(signal,'pidfd_send_signal'),'DEPENDENCY_BLOCKED')
        caps=int(Path('/proc/self/status').read_text().split('CapEff:')[1].split()[0],16)
        require(caps & (1<<21) and caps & (1<<12),'DEPENDENCY_BLOCKED')
        require(pwd.getpwnam('mysql').pw_uid!=0 and pwd.getpwnam('nobody').pw_uid!=0,'DEPENDENCY_BLOCKED')
        attrs=json.loads((source/'file-attributes.json').read_text());receipt=json.loads((source/'receipt.json').read_text())
        target=json.loads((source/'target.json').read_text())
        plugin_names(target['before_guard']['plugins'])
        # The contained DB runtime is MariaDB. Do not silently promise cross-engine
        # physical-schema equivalence for an unqualified MySQL-source restore.
        require('MariaDB' in target['before_guard'].get('server_version',''),'DEPENDENCY_BLOCKED')
        names=set(attrs['before'])
        require(not any(name.startswith('wp-content/mu-plugins/') and name.endswith('.php') for name in names),'SOURCE_SECURITY_BLOCKED')
        require(not names.intersection({'wp-content/db.php','wp-content/object-cache.php','wp-content/advanced-cache.php','wp-content/sunrise.php'}),'SOURCE_SECURITY_BLOCKED')
        config=(source/'wp-config.php.recovery').read_bytes()
        require(security_config_safe(config),'SOURCE_SECURITY_BLOCKED')
        # Conservative headroom includes DB files/logs and restored content/reporting.
        expected=2*1024**3+5*sum(row['size'] for row in attrs['before'].values())+2*1024**3
        require(b.free_bytes(b.PARENT)>=expected,'DEPENDENCY_BLOCKED')
        b.validate_gzip(source/'database.sql.gz',b.Budget(time.time()+120,120))
        with gzip.open(source/'database.sql.gz','rt',encoding='utf-8') as sql:validate_sql(b,sql,{c['table'] for c in json.loads((source/'before.catalog.json').read_text())['columns']})
        fresh=time.time()-b.utc(receipt['started'])<=86400
    return {'state':'preflight-pass','source_binding_verified':True,'tools_present':True,'sql_structure_verified':True,
            'space_guard_passed':True,'backup_fresh_at_probe':fresh,'restore_executed':False}


def supervise(b,binding,params,payload_text):
    budget=params_check(b,params);preflight(b,binding);source,work=selected_paths(b,binding)
    # This is the first write: only after the explicit restore authorization marker/window.
    work.mkdir(mode=0o700);os.chmod(work,0o700)
    b.write_json(work/'private-input-binding.json',binding)
    b.write_json(work/'private-execution.json',params)
    b.write_json(work/'restore-incomplete.json',{'state':'incomplete','code':'RESTORE_UNCLASSIFIED','restore_verified':False,'cleanup_verified':False})
    parent_ns={name:os.readlink('/proc/self/ns/'+name) for name in ('mnt','net','pid')}
    env={'PATH':'/usr/bin:/usr/sbin:/bin','LC_ALL':'C','TZ':'UTC',
         '_DR_PAYLOAD':payload_text,'_DR_BINDING':json.dumps(binding),'_DR_PARENT_NS':json.dumps(parent_ns)}
    hostproc=os.open('/proc',os.O_RDONLY|os.O_DIRECTORY|os.O_CLOEXEC)
    env['_DR_HOSTPROC_FD']=str(hostproc)
    code="import os,json,sys; fd=int(os.environ.pop('_DR_HOSTPROC_FD')); pid=int(os.readlink('self',dir_fd=fd)); os.close(fd); print(json.dumps({'worker_pid':pid}),flush=True); p=os.environ.pop('_DR_PAYLOAD'); v=json.loads(os.environ.pop('_DR_BINDING')); n=json.loads(os.environ.pop('_DR_PARENT_NS')); exec(compile(p,'<restore-worker>','exec'),{'__name__':'__main__','__file__':'<restore-worker>','RESTORE_BINDING':v,'RESTORE_PARENT_NS':n,'RESTORE_PAYLOAD_TEXT':p})"
    error=open(work/'launcher-private.log','xb');os.chmod(error.name,0o600)
    proc=subprocess.Popen(['/usr/bin/unshare','--mount','--net','--pid','--fork','--kill-child=TERM','--mount-proc','/usr/bin/python3','-I','-B','-c',code],
                          stdin=subprocess.PIPE,stdout=subprocess.PIPE,stderr=error,env=env,pass_fds=(hostproc,))
    os.close(hostproc)
    pidfd=None
    try:
        # Bootstrap self-identifies through a parent-proc descriptor, closes it BEFORE
        # entering the payload, then blocks on stdin. No kernel children-list support needed.
        require(select.select([proc.stdout],[],[],min(5,budget.remaining()))[0],'ISOLATION_BLOCKED')
        hello=json.loads(proc.stdout.readline(256))
        require(set(hello)=={'worker_pid'} and isinstance(hello['worker_pid'],int) and hello['worker_pid']>1,'ISOLATION_BLOCKED')
        pidfd=os.pidfd_open(hello['worker_pid'])
        status=Path('/proc/'+str(hello['worker_pid'])+'/status').read_text()
        require(int(status.split('PPid:')[1].split()[0])==proc.pid,'ISOLATION_BLOCKED')
        require(all(os.readlink('/proc/'+str(hello['worker_pid'])+'/ns/'+name)!=parent_ns[name] for name in parent_ns),'ISOLATION_BLOCKED')
        output,_=proc.communicate(input=b.canonical(params),timeout=budget.remaining())
        require(proc.returncode in (0,2) and len(output)<=4096,'CLEANUP_BLOCKED')
        result=json.loads(output)
        require(set(result)=={'state','code','integrity_verified','isolation_verified','pages_verified','login_verified','cleanup_verified','restore_verified'},'RESTORE_UNCLASSIFIED')
        require(result['code'] in CODES and result['cleanup_verified'] is True,'CLEANUP_BLOCKED')
        with stage('SOURCE_BINDING_BLOCKED'):verify_input(b,source,binding)
        return result
    finally:
        if proc.poll() is None:proc.terminate()
        if pidfd is not None:
            try:
                if not select.select([pidfd],[],[],15)[0]:
                    signal.pidfd_send_signal(pidfd,signal.SIGKILL)
                    require(select.select([pidfd],[],[],5)[0],'CLEANUP_BLOCKED')
            finally:os.close(pidfd)
        try:proc.wait(timeout=5)
        except subprocess.TimeoutExpired:proc.kill();proc.wait(timeout=5)
        for relative in ('jail/work/login-input.json','jail/site/wp-config.php'):
            path=work/relative
            if path.is_file() and not path.is_symlink():path.unlink()
        error.close()


def main(b,binding,payload_text,parent_ns=None,probe_only=False):
    os.umask(0o077)
    def stop(*_):raise Failure('TIME_LIMIT_REACHED')
    signal.signal(signal.SIGALRM,stop);signal.signal(signal.SIGTERM,stop);signal.signal(signal.SIGINT,stop)
    signal.setitimer(signal.ITIMER_REAL,115 if probe_only else 1770)
    try:
        binding_check(b,binding)
        if probe_only=='profile':result=source_profile(b,binding)
        elif probe_only:result=preflight(b,binding)
        else:
            data=sys.stdin.buffer.read(16385);require(len(data)<=16384,'INPUT_BLOCKED');params=json.loads(data)
            result=worker(b,binding,params,parent_ns) if parent_ns is not None else supervise(b,binding,params,payload_text)
        status=0 if result['state'] in ('verified','preflight-pass','source-profile') else 2
    except Failure as error:result={'state':'blocked','code':error.code};status=2
    except (Exception,KeyboardInterrupt):result={'state':'blocked','code':'RESTORE_UNCLASSIFIED'};status=2
    finally:signal.setitimer(signal.ITIMER_REAL,0)
    print(json.dumps(result,sort_keys=True));return status

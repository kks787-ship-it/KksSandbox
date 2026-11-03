"""Read-only software/ephemeral-namespace capability probe. No restore/storage/service writes."""
import json
import os
import pwd
import re
import subprocess

TOOLS={'unshare':'/usr/bin/unshare','mount':'/usr/bin/mount','ip':'/usr/sbin/ip','chroot':'/usr/sbin/chroot',
       'setpriv':'/usr/bin/setpriv','mariadb':'/usr/bin/mariadb','mariadbd':'/usr/sbin/mariadbd',
       'install_db':'/usr/bin/mariadb-install-db','check':'/usr/bin/mariadb-check','php':'/opt/plesk/php/8.3/bin/php',
       'python':'/usr/bin/python3','timeout':'/usr/bin/timeout'}

def main():
    flags={name:os.path.isfile(path) and os.access(path,os.X_OK) for name,path in TOOLS.items()}
    accounts={}
    for name in ('mysql','nobody'):
        try:accounts[name]=pwd.getpwnam(name).pw_uid!=0
        except KeyError:accounts[name]=False
    builtin=[];shared={};php_version=None
    if flags['php']:
        p=subprocess.run([TOOLS['php'],'-n','-r','echo json_encode([PHP_VERSION,ini_get("extension_dir"),get_loaded_extensions()]);'],capture_output=True,timeout=10)
        if p.returncode==0:
            version,directory,modules=json.loads(p.stdout);php_version=version if re.fullmatch(r'[0-9.]+',version) else None
            builtin=sorted(name for name in ('mysqli','mysqlnd','mbstring','openssl','json','hash','filter','session','zlib','ctype','iconv','dom') if name in modules)
            shared={name:os.path.isfile(os.path.join(directory,name+'.so')) for name in ('mysqlnd','mysqli','mbstring','openssl','curl','dom','iconv','xml')}
    namespace=False
    if flags['unshare'] and flags['python']:
        before={name:os.readlink('/proc/self/ns/'+name) for name in ('mnt','net','pid')}
        code="import os,json,socket,sys; old=json.loads(sys.argv[1]); assert all(os.readlink('/proc/self/ns/'+n)!=old[n] for n in old); assert [n for _,n in socket.if_nameindex()]==['lo']; print('OK')"
        r=subprocess.run([TOOLS['unshare'],'--mount','--net','--pid','--fork','--kill-child=TERM','--mount-proc',TOOLS['python'],'-I','-B','-c',code,json.dumps(before)],capture_output=True,timeout=10)
        namespace=r.returncode==0 and r.stdout.strip()==b'OK'
    print(json.dumps({'state':'probe-complete','tools':flags,'existing_unprivileged_accounts':accounts,
                      'ephemeral_namespace_isolation':namespace,'php_version':php_version,'php_builtin':builtin,'php_shared_available':shared},sort_keys=True))

if __name__=='__main__':main()

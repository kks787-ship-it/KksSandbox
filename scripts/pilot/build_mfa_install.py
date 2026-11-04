"""Build owner-approved A payload; private recovery binding stays outside Git."""
import base64
import hashlib
import json
from pathlib import Path
import shlex
import subprocess
import sys
import zlib

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT/'scripts/recovery'))
import build
PIN = '2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1'


def package(binding):
    inventory = json.loads((ROOT/'docs/test-results/scheduling-security-install-inventory.json').read_text())
    assert inventory['candidate_commit'] == PIN
    rows = []
    for row in inventory['files']:
        if '/csf-scheduling/' in row['path']: continue
        if '/csf-mfa/' in row['path']:
            data = subprocess.check_output(['git','-C',str(ROOT),'show',PIN+':'+row['path']])
            assert data == (ROOT/row['path']).read_bytes()
        else:
            data = (ROOT/'.wp-local/mfa-official'/row['path'].removeprefix('wp-content/plugins/')).read_bytes()
        assert hashlib.sha256(data).hexdigest() == row['sha256']
        rows.append({'path':row['path'],'sha256':row['sha256'],'text':None if '/two-factor/' in row['path'] else data.decode()})
    library = build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())", '')
    parts = [library, '\nimport types,signal\n_B=types.SimpleNamespace(**globals())']
    for key,path in (('_I','scripts/recovery/inspect_local.py'),('_N','scripts/pilot/native_mail_runtime.py'),('_S','scripts/pilot/stage_scheduling.py'),('_M','scripts/pilot/install_mfa.py')):
        parts.append(key+'={"__name__":"installation_module"}\nexec(compile('+repr((ROOT/path).read_text())+',"<installation-module>","exec"),'+key+')')
    template = (ROOT/'scripts/pilot/mfa_activation.php.txt').read_text()
    parts.append("signal.signal(signal.SIGALRM,lambda *a:(_ for _ in ()).throw(TimeoutError()))\nsignal.signal(signal.SIGTERM,lambda *a:(_ for _ in ()).throw(TimeoutError()))\nsignal.alarm(240)\ntry:\n rows=_M['vendor_entries']("+repr(rows)+")\n result=_M['run'](_B,types.SimpleNamespace(**_I),types.SimpleNamespace(**_N),types.SimpleNamespace(**_S),rows,"+repr(template)+','+repr(binding)+")\n code=0\nexcept Exception:\n result={'state':'MFA_INSTALLATION_STOPPED','reconciliation_required':True}\n code=2\nprint(json.dumps(result,sort_keys=True))\nsys.exit(code)")
    payload = '\n'.join(parts).encode(); digest = hashlib.sha256(payload).hexdigest()
    encoded = base64.b64encode(zlib.compress(payload,9)).decode()
    assert len(encoded) <= 113536
    code = "import sys,zlib,base64,hashlib;a='"+encoded[:48000]+"'.encode()+sys.stdin.buffer.read(65537);assert len(a)<=113536;d=zlib.decompressobj();p=d.decompress(base64.b64decode(a,validate=True),2097152);assert d.eof and not d.unused_data and hashlib.sha256(p).hexdigest()=='"+digest+"';exec(compile(p,'<approved-mfa-install>','exec'),{'__name__':'__main__','__file__':'<approved-mfa-install>'})"
    command = 'set -euo pipefail\nset +x\numask 077\nexec /usr/bin/timeout --kill-after=10s 250s /usr/bin/python3 -I -B -c '+shlex.quote(code)+'\n'
    assert len(command.encode()) <= 65536
    return command, encoded[48000:]

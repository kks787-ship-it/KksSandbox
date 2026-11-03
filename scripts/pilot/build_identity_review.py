"""Build the bounded read-only identity verifier; no private values or execution."""
import argparse
import base64
import hashlib
from pathlib import Path
import shlex
import sys
import zlib
ROOT=Path(__file__).resolve().parents[2]
sys.path.insert(0,str(ROOT/'scripts/recovery'))
import build

def command():
    library=build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())",'')
    source=(ROOT/'scripts/pilot/verify_calendar_identities.py').read_text()
    payload=(library+"\nimport types\n_B=types.SimpleNamespace(**globals())\n_I={'__name__':'read_only_identity'}\nexec(compile("+repr(source)+",'<identity-review>','exec'),_I)\nsys.exit(_I['main'](_B))\n").encode()
    digest=hashlib.sha256(payload).hexdigest();encoded=base64.b64encode(zlib.compress(payload,9)).decode()
    code="import base64,zlib,hashlib; p=zlib.decompress(base64.b64decode('"+encoded+"')); assert hashlib.sha256(p).hexdigest()=='"+digest+"';exec(compile(p,'<pinned-review>','exec'),{'__name__':'review','__file__':'<pinned-review>'})"
    result=('set -euo pipefail\nset +x\numask 077\nexec /usr/bin/timeout --kill-after=5s 55s /usr/bin/python3 -I -B -c '+shlex.quote(code)+'\n').encode();assert len(result)<65536;return result

if __name__=='__main__':
    p=argparse.ArgumentParser();p.add_argument('--output',required=True);a=p.parse_args();path=Path(a.output)
    path.write_bytes(command());path.chmod(0o600)
    print('PASS bounded read-only command prepared; no private inputs used')

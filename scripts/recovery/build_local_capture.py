"""Build one standalone local-only R-Backup candidate; never executes it."""
import base64
import hashlib
from pathlib import Path
import shlex
import zlib
import build

ROOT=Path(__file__).resolve().parents[2]

def artifacts():
    library=build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())",'')
    inspector=(ROOT/'scripts/recovery/inspect_local.py').read_text()
    capture=(ROOT/'scripts/recovery/capture_local.py').read_text()
    payload=(library+"\nimport types\n_CAPTURE_LIBRARY=types.SimpleNamespace(**globals())\n_I={'__name__':'capture_discovery'}\nexec(compile("+repr(inspector)+",'<capture-discovery>','exec'),_I)\n_C={'__name__':'local_capture'}\nexec(compile("+repr(capture)+",'<local-capture>','exec'),_C)\nif __name__=='__main__':sys.exit(_C['main'](_CAPTURE_LIBRARY,types.SimpleNamespace(**_I)))\n").encode()
    digest=hashlib.sha256(payload).hexdigest();encoded=base64.b64encode(zlib.compress(payload,9)).decode()
    bootstrap="import base64,zlib,hashlib; p=zlib.decompress(base64.b64decode('"+encoded+"')); assert hashlib.sha256(p).hexdigest()=='"+digest+"'; exec(compile(p,'<reviewed-local-capture>','exec'),{'__name__':'__main__','__file__':'<reviewed-local-capture>','PAYLOAD_DIGEST':'"+digest+"'})"
    command='set -euo pipefail\nset +x\numask 077\nexport PATH=/usr/bin:/bin\nexec /usr/bin/timeout --signal=TERM --kill-after=30s 1770s /usr/bin/python3 -I -B -c '+shlex.quote(bootstrap)+'\n'
    return {'scripts/recovery/capture-local.payload.py':payload,'scripts/recovery/capture-local.command.sh':command.encode()}

if __name__=='__main__':
    for name,data in artifacts().items():(ROOT/name).write_bytes(data)
    names=sorted([*artifacts(),'scripts/recovery/build_local_capture.py','scripts/recovery/build.py','scripts/recovery/backup.py','scripts/recovery/inspect_local.py','scripts/recovery/capture_local.py','docs/pilot/baseline-contract.json','docs/pilot/baseline-select-plan.py.txt'])
    (ROOT/'scripts/recovery/LOCAL-CAPTURE.sha256').write_text(''.join(hashlib.sha256((ROOT/n).read_bytes()).hexdigest()+'  '+n+'\n' for n in names))

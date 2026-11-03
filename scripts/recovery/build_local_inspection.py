"""Build credential-free command transport for the server-local inspection. No server access."""
import base64
import hashlib
from pathlib import Path
import shlex
import zlib
import build

ROOT=Path(__file__).resolve().parents[2]

def artifacts():
    library=build.artifacts()['scripts/recovery/payload.py'].decode()
    library=library.replace("if __name__=='__main__':sys.exit(main())",'')
    inspector=(ROOT/'scripts/recovery/inspect_local.py').read_text()
    payload=(library+"\nimport types\n_INSPECT_LIBRARY=types.SimpleNamespace(**globals())\n_INSPECT_NAMESPACE={'__name__':'server_local_inspection'}\nexec(compile("+repr(inspector)+",'<local-inspector>','exec'),_INSPECT_NAMESPACE)\nif __name__=='__main__':sys.exit(_INSPECT_NAMESPACE['main'](_INSPECT_LIBRARY))\n").encode()
    digest=hashlib.sha256(payload).hexdigest();encoded=base64.b64encode(zlib.compress(payload,9)).decode()
    bootstrap="import base64,zlib,hashlib; p=zlib.decompress(base64.b64decode('"+encoded+"')); assert hashlib.sha256(p).hexdigest()=='"+digest+"'; exec(compile(p,'<reviewed-local-inspection>','exec'),{'__name__':'__main__','__file__':'<reviewed-local-inspection>','PAYLOAD_DIGEST':'"+digest+"'})"
    command='set -euo pipefail\nset +x\numask 077\nexport PATH=/usr/bin:/bin\nexec /usr/bin/timeout --signal=TERM --kill-after=5s 115s /usr/bin/python3 -I -B -c '+shlex.quote(bootstrap)+'\n'
    return {'scripts/recovery/inspect-local.payload.py':payload,'scripts/recovery/inspect-local.command.sh':command.encode()}

if __name__=='__main__':
    for name,data in artifacts().items():(ROOT/name).write_bytes(data)
    names=sorted([*artifacts(),'scripts/recovery/build_local_inspection.py','scripts/recovery/build.py','scripts/recovery/inspect_local.py','scripts/recovery/backup.py','docs/pilot/baseline-contract.json','docs/pilot/baseline-select-plan.py.txt'])
    (ROOT/'scripts/recovery/LOCAL-INSPECTION.sha256').write_text(''.join(hashlib.sha256((ROOT/n).read_bytes()).hexdigest()+'  '+n+'\n' for n in names))

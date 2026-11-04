"""Deterministic bounded mail-indicator command; no private inputs or execution."""
import base64
import hashlib
from pathlib import Path
import shlex
import sys
import zlib

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'scripts/recovery'))
import build


def command():
    library = build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())", '')
    source = (ROOT / 'scripts/pilot/inspect_mail_configuration.py').read_text()
    payload = (library + "\nimport types\n_B=types.SimpleNamespace(**globals())\n_M={'__name__':'mail_indicators'}\nexec(compile(" + repr(source) + ",'<mail-indicators>','exec'),_M)\nsys.exit(_M['main'](_B))\n").encode()
    digest = hashlib.sha256(payload).hexdigest()
    encoded = base64.b64encode(zlib.compress(payload, 9)).decode()
    code = "import base64,zlib,hashlib;p=zlib.decompress(base64.b64decode('" + encoded + "'));assert hashlib.sha256(p).hexdigest()=='" + digest + "';exec(compile(p,'<pinned-mail-review>','exec'),{'__name__':'review','__file__':'<pinned-mail-review>'})"
    result = ('set -euo pipefail\nset +x\numask 077\nexec /usr/bin/timeout --kill-after=5s 55s /usr/bin/python3 -I -B -c ' + shlex.quote(code) + '\n').encode()
    assert len(result) < 65536
    return result

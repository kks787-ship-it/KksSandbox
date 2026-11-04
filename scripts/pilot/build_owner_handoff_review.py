"""Bounded completion review of one secure temporary handoff; no WordPress/mail operations."""
from pathlib import Path
import base64
import hashlib
import re
import shlex
import zlib

ROOT = Path(__file__).resolve().parents[2]


def command(directory, review_function='review'):
    if review_function not in ('review', 'mail_readiness'):
        raise ValueError('Unknown review function')
    if re.fullmatch(r'/tmp/dr-controller-review-[a-f0-9]{32}', directory) is None:
        raise ValueError('Exact temporary review directory required')
    source = (ROOT / 'scripts/pilot/review_owner_handoff.py').read_text()
    body = '''
import os,stat,json
from pathlib import Path
directory=Path(DIRECTORY)
path=directory/'handoff'
result={'state':'blocked','code':'HANDOFF_REVIEW_UNAVAILABLE','execution_authorized':False}
identity=None
parent=None
try:
    parent=directory.lstat()
    assert stat.S_ISDIR(parent.st_mode) and directory.resolve()==directory and parent.st_uid==os.geteuid() and stat.S_IMODE(parent.st_mode)==0o700
    fd=os.open(path,os.O_RDONLY|os.O_NOFOLLOW)
    try:
        identity=os.fstat(fd)
        assert stat.S_ISREG(identity.st_mode) and identity.st_uid==os.geteuid() and identity.st_nlink==1 and stat.S_IMODE(identity.st_mode)==0o600
        assert identity.st_size<=65536
        raw=os.read(fd,65537)
    finally:os.close(fd)
    result=namespace[REVIEW_FUNCTION](raw)
except Exception:
    pass
finally:
    removed=False
    try:
        current=directory.lstat()
        assert parent is not None and (current.st_dev,current.st_ino)==(parent.st_dev,parent.st_ino)
        if identity is not None:
            current=path.lstat()
            assert stat.S_ISREG(current.st_mode) and (current.st_dev,current.st_ino)==(identity.st_dev,identity.st_ino)
            path.unlink()
        directory.rmdir()
        removed=True
    except Exception:pass
    result['temporary_copy_removed']=removed
    if not removed:result={'state':'blocked','code':'TEMPORARY_CLEANUP_REQUIRES_REVIEW','execution_authorized':False,'temporary_copy_removed':False}
print(json.dumps(result,sort_keys=True))
'''
    payload = ('namespace={"__name__":"reference_review"}\nexec(compile(' + repr(source) + ',"<reference-review>","exec"),namespace)\nDIRECTORY=' + repr(directory) + '\nREVIEW_FUNCTION=' + repr(review_function) + '\n' + body).encode()
    digest = hashlib.sha256(payload).hexdigest()
    encoded = base64.b64encode(zlib.compress(payload, 9)).decode()
    code = "import base64,zlib,hashlib;p=zlib.decompress(base64.b64decode('" + encoded + "'));assert hashlib.sha256(p).hexdigest()=='" + digest + "';exec(compile(p,'<bounded-owner-review>','exec'))"
    return ('set -euo pipefail\nset +x\numask 077\nexec /usr/bin/timeout --kill-after=5s 25s /usr/bin/python3 -I -B -c ' + shlex.quote(code) + '\n').encode()

"""Deterministic public source bundle plus owner-private inactive-staging commands."""
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import shlex
import stat
import subprocess
import sys
import zlib
ROOT=Path(__file__).resolve().parents[2]
sys.path.insert(0,str(ROOT/'scripts/recovery'))
import build
PIN='2fd02a2a1d3518289f7c4aa19bc9ddfb1dec91b1'

def artifacts():
    rows=[]
    for line in (ROOT/'docs/test-results/scheduling-security-files.sha256').read_text().splitlines():
        digest,path=line.split()
        if not path.startswith('wp-content/plugins/csf-scheduling/'):continue
        data=subprocess.check_output(['git','-C',str(ROOT),'show',PIN+':'+path])
        assert hashlib.sha256(data).hexdigest()==digest and (ROOT/path).read_bytes()==data
        rows.append({'path':path,'sha256':digest,'text':data.decode()})
    assert len(rows)==20
    library=build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())",'')
    script=(ROOT/'scripts/pilot/stage_scheduling.py').read_text()
    payload=(library+"\nimport types\n_B=types.SimpleNamespace(**globals())\n_S={'__name__':'inactive_staging'}\nexec(compile("+repr(script)+",'<inactive-staging>','exec'),_S)\nif __name__=='__main__':sys.exit(_S['main'](_B,globals().get('BINDING',{}),"+repr(rows)+",globals().get('MODE','preflight')))\n").encode()
    return payload

def command(payload,binding,mode):
    assert mode in ('preflight','stage-inactive')
    digest=hashlib.sha256(payload).hexdigest()
    encoded=base64.b64encode(zlib.compress(payload,9)).decode();assert len(encoded)<=113536
    code="import sys,zlib,base64,hashlib; a='"+encoded[:48000]+"'.encode()+sys.stdin.buffer.read(65537); assert len(a)<=113536; d=zlib.decompressobj(); p=d.decompress(base64.b64decode(a,validate=True),2097152); assert d.eof and not d.unused_data and hashlib.sha256(p).hexdigest()=='"+digest+"'; exec(compile(p,'<pinned-staging>','exec'),{'__name__':'__main__','__file__':'<pinned-staging>','BINDING':"+repr(binding)+",'MODE':"+repr(mode)+"})"
    result=('set -euo pipefail\nset +x\numask 077\nexec /usr/bin/timeout --signal=TERM --kill-after=5s 115s /usr/bin/python3 -I -B -c '+shlex.quote(code)+'\n').encode()
    assert len(result)<=65536
    return result

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('--private-binding');parser.add_argument('--output');args=parser.parse_args()
    payload=artifacts()
    if args.private_binding:
        path=Path(args.private_binding);info=path.lstat();assert stat.S_ISREG(info.st_mode) and stat.S_IMODE(info.st_mode)==0o600 and info.st_uid==os.getuid()
        binding=json.loads(path.read_text());assert args.output
        prefix=Path(args.output)
        for suffix,data in (('.preflight.sh',command(payload,binding,'preflight')),('.stage.sh',command(payload,binding,'stage-inactive'))):
            fd=os.open(prefix.with_suffix(suffix),os.O_CREAT|os.O_WRONLY|os.O_TRUNC|os.O_NOFOLLOW,0o600)
            with os.fdopen(fd,'wb') as out:out.write(data)
        path=prefix.with_suffix('.public-input.txt');fd=os.open(path,os.O_CREAT|os.O_WRONLY|os.O_TRUNC|os.O_NOFOLLOW,0o600)
        with os.fdopen(fd,'wb') as out:out.write(base64.b64encode(zlib.compress(payload,9))[48000:])
        print('PASS private staging envelopes prepared; values withheld')
    else:
        names=['scripts/pilot/stage_scheduling.py','scripts/pilot/build_inactive_staging.py','scripts/recovery/build.py','scripts/recovery/backup.py','docs/pilot/baseline-contract.json','docs/pilot/baseline-select-plan.py.txt','docs/test-results/scheduling-security-files.sha256']
        (ROOT/'scripts/pilot/STAGING.sha256').write_text(''.join(hashlib.sha256((ROOT/p).read_bytes()).hexdigest()+'  '+p+'\n' for p in sorted(names)))
        print('PASS pinned public staging build')

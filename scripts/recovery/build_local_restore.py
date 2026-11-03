"""Build public restore code and an owner-private command envelope. Never executes it.

Private receipt/manifest/path bindings MUST stay outside Git. Runtime command is sensitive
in Gateway; only code fingerprints/status may be published. No credentials are input here.
"""
import argparse
import base64
import hashlib
import json
import os
from pathlib import Path
import re
import secrets
import stat
import shlex
import zlib
import build

ROOT=Path(__file__).resolve().parents[2]

def public_payload():
    library=build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())",'')
    restore=(ROOT/'scripts/recovery/restore_local.py').read_text()
    return (library+"\nimport types\n_RESTORE_LIBRARY=types.SimpleNamespace(**globals())\n_R={'__name__':'isolated_restore'}\nexec(compile("+repr(restore)+",'<isolated-restore>','exec'),_R)\nif __name__=='__main__':sys.exit(_R['main'](_RESTORE_LIBRARY,globals().get('RESTORE_BINDING',{}),globals().get('RESTORE_PAYLOAD_TEXT',''),globals().get('RESTORE_PARENT_NS'),globals().get('RESTORE_PROBE_ONLY',False)))\n").encode()


def command(payload,binding,probe=False):
    digest=hashlib.sha256(payload).hexdigest();encoded=base64.b64encode(zlib.compress(payload,9)).decode()
    # Public code is SHA-pinned independently of owner-private artifact bindings.
    bootstrap="import base64,zlib,hashlib; p=zlib.decompress(base64.b64decode('"+encoded+"')); assert hashlib.sha256(p).hexdigest()=='"+digest+"'; exec(compile(p,'<reviewed-restore>','exec'),{'__name__':'__main__','__file__':'<reviewed-restore>','PAYLOAD_DIGEST':'"+digest+"','RESTORE_BINDING':"+repr(binding)+",'RESTORE_PAYLOAD_TEXT':p.decode(),'RESTORE_PROBE_ONLY':"+repr(probe)+"})"
    seconds,grace=(115,5) if probe else (1770,30)
    return ('set -euo pipefail\nset +x\numask 077\nexport PATH=/usr/bin:/usr/sbin:/bin\nexec /usr/bin/timeout --signal=TERM --kill-after='+str(grace)+'s '+str(seconds)+'s /usr/bin/python3 -I -B -c '+shlex.quote(bootstrap)+'\n').encode()


def private_build(record_path,output):
    for path in (record_path,):
        info=path.lstat()
        if not stat.S_ISREG(info.st_mode) or stat.S_IMODE(info.st_mode)!=0o600 or info.st_uid!=os.getuid():raise ValueError('Private input must be owned regular0600')
    record=json.loads(record_path.read_text());receipt=record['receipt']
    if record['status']!='succeeded' or receipt['code']!='LOCAL_CAPTURE_VERIFIED':raise ValueError('Unverified input record')
    binding={'bundle_id':receipt['bundle_id'],'manifest_sha256':receipt['manifest_sha256'],'receipt_sha256':receipt['receipt_sha256'],'recovery_token':secrets.token_hex(16)}
    if not re.fullmatch('csf-gate-b-backup-[a-f0-9]{32}',binding['bundle_id']):raise ValueError('Bad private source identity')
    # Preserve an existing candidate binding/nonce; never silently choose a new destination.
    binding_path=output.with_suffix('.binding.json')
    if binding_path.is_symlink():raise ValueError('Private binding alias forbidden')
    if binding_path.exists():
        info=binding_path.stat()
        if stat.S_IMODE(info.st_mode)!=0o600 or info.st_uid!=os.getuid():raise ValueError('Private binding custody invalid')
        old=json.loads(binding_path.read_text())
        if any(old[k]!=binding[k] for k in ('bundle_id','manifest_sha256','receipt_sha256')):raise ValueError('Different source record')
        binding=old
    else:
        fd=os.open(binding_path,os.O_CREAT|os.O_EXCL|os.O_WRONLY,0o600)
        with os.fdopen(fd,'w') as out:json.dump(binding,out,sort_keys=True)
    payload=public_payload()
    for suffix,data in (('.command.sh',command(payload,binding)),('.probe.sh',command(payload,binding,True))):
        path=output.with_suffix(suffix);fd=os.open(path,os.O_CREAT|os.O_TRUNC|os.O_WRONLY|os.O_NOFOLLOW,0o600)
        with os.fdopen(fd,'wb') as out:out.write(data)
        os.chmod(path,0o600)
    release={'source_code_sha256':hashlib.sha256(payload).hexdigest(),'private_command_sha256':hashlib.sha256(command(payload,binding)).hexdigest()}
    path=output.with_suffix('.release.json');fd=os.open(path,os.O_CREAT|os.O_TRUNC|os.O_WRONLY|os.O_NOFOLLOW,0o600)
    with os.fdopen(fd,'w') as out:json.dump(release,out,sort_keys=True);out.flush();os.fsync(out.fileno())
    os.chmod(path,0o600)
    return {'source_code_sha256':hashlib.sha256(payload).hexdigest(),'private_command_ready':True}


if __name__=='__main__':
    p=argparse.ArgumentParser();p.add_argument('--private-record');p.add_argument('--private-output');args=p.parse_args()
    if args.private_record:
        if not args.private_output:raise SystemExit('Private output required')
        private_build(Path(args.private_record),Path(args.private_output))
        print('PASS private restore command/binding prepared; values and private digest withheld')
    else:
        (ROOT/'scripts/recovery/restore-local.payload.py').write_bytes(public_payload())
        (ROOT/'scripts/recovery/restore-local.command.sh').write_bytes(command(public_payload(),{}))
        paths=['scripts/recovery/restore_local.py','scripts/recovery/build_local_restore.py','scripts/recovery/restore-local.payload.py','scripts/recovery/restore-local.command.sh','scripts/recovery/build.py','scripts/recovery/backup.py','docs/pilot/baseline-contract.json','docs/pilot/baseline-select-plan.py.txt']
        (ROOT/'scripts/recovery/LOCAL-RESTORE.sha256').write_text(''.join(hashlib.sha256((ROOT/name).read_bytes()).hexdigest()+'  '+name+'\n' for name in sorted(paths)))

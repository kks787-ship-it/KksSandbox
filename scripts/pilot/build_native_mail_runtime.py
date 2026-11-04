"""Build a bounded native-runtime preview. No Gateway execution or private values printed."""
import base64
import hashlib
from pathlib import Path
import shlex
import sys
import zlib

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'scripts/recovery'))
import build


def command(backup, dispatch=None):
    if dispatch is None and (not isinstance(backup,dict) or set(backup) != {'bundle_id', 'receipt_sha256', 'manifest_sha256'}):
        raise ValueError('Private recovery binding required')
    if dispatch is not None and backup is not None:raise ValueError('Dispatch recovery is a verified runtime binding')
    library = build.artifacts()['scripts/recovery/payload.py'].decode().replace("if __name__=='__main__':sys.exit(main())", '')
    inspector = (ROOT / 'scripts/recovery/inspect_local.py').read_text()
    runtime = (ROOT / 'scripts/pilot/native_mail_runtime.py').read_text()
    template = (ROOT / 'scripts/pilot/native_mail_runtime.php.txt').read_text()
    ledger=(ROOT/'scripts/pilot/security_mail_ledger.py').read_text()
    metadata=(ROOT/'scripts/pilot/inspect_mail_configuration.py').read_text()
    if dispatch is not None and (set(dispatch) != {'snapshot','test_ref'}):raise ValueError('Reviewed dispatch binding required')
    invoke = "_N['main'](_B,types.SimpleNamespace(**_I),"+repr(template)+','+repr(backup)+')' if dispatch is None else "_N['dispatch_main'](_B,types.SimpleNamespace(**_I),"+repr(template)+','+repr(backup)+','+repr(dispatch['snapshot'])+','+repr(dispatch['test_ref'])+",_L['SubmissionLedger'])"
    payload = (library + "\nimport types\n_B=types.SimpleNamespace(**globals())\n_Q={'__name__':'native_queue_metadata'}\nexec(compile("+repr(metadata)+",'<native-queue-metadata>','exec'),_Q)\n_B.QUEUE_LIMITS=_Q['queue_limits']\n_I={'__name__':'native_pool_metadata'}\nexec(compile(" + repr(inspector) + ",'<native-pool-metadata>','exec'),_I)\n_N={'__name__':'native_mail_runtime'}\nexec(compile(" + repr(runtime) + ",'<native-mail-runtime>','exec'),_N)\n_L={'__name__':'submission_gate'}\nexec(compile("+repr(ledger)+",'<submission-gate>','exec'),_L)\nsys.exit("+invoke+')\n').encode()
    digest = hashlib.sha256(payload).hexdigest()
    encoded = base64.b64encode(zlib.compress(payload, 9)).decode()
    code = "import base64,zlib,hashlib;p=zlib.decompress(base64.b64decode('" + encoded + "'));assert hashlib.sha256(p).hexdigest()=='" + digest + "';exec(compile(p,'<native-preview>','exec'),{'__name__':'__main__','__file__':'<native-preview>'})"
    limit,grace=(55,10) if dispatch is None else (110,10)
    result=('set -euo pipefail\nset +x\numask 077\nexec /usr/bin/timeout --kill-after='+str(grace)+'s '+str(limit)+'s /usr/bin/python3 -I -B -c ' + shlex.quote(code) + '\n').encode()
    if len(result)>65536:raise ValueError('Command limit')
    return result

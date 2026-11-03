set -euo pipefail
set +x
umask 077
export PATH=/usr/bin:/bin
exec /usr/bin/timeout --signal=TERM --kill-after=30s 1770s /usr/bin/python3 -I -B -c 'import os,hashlib; p=os.environ.pop('"'"'CSF_REVIEWED_PAYLOAD'"'"'); h=hashlib.sha256(p.encode()).hexdigest(); assert h=='"'"'9707ae4099812ab1b44d9c8579257c7a964e10326bb946d07ff1faa6499c565c'"'"','"'"'payload digest mismatch'"'"'; exec(compile(p,'"'"'<reviewed-memory-payload>'"'"','"'"'exec'"'"'),{'"'"'__name__'"'"':'"'"'__main__'"'"','"'"'__file__'"'"':'"'"'<reviewed-memory-payload>'"'"','"'"'PAYLOAD_DIGEST'"'"':h})' capture

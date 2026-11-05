set -euo pipefail
set +x
umask 077
export PATH=/usr/bin:/bin
exec /usr/bin/timeout --signal=TERM --kill-after=30s 1770s /usr/bin/python3 -I -B -c 'import os,hashlib; p=os.environ.pop('"'"'CSF_REVIEWED_PAYLOAD'"'"'); h=hashlib.sha256(p.encode()).hexdigest(); assert h=='"'"'63d7871408b94b0c704ada2f4d0336c573a2085c9e50cbd71579517301cfceda'"'"','"'"'payload digest mismatch'"'"'; exec(compile(p,'"'"'<reviewed-memory-payload>'"'"','"'"'exec'"'"'),{'"'"'__name__'"'"':'"'"'__main__'"'"','"'"'__file__'"'"':'"'"'<reviewed-memory-payload>'"'"','"'"'PAYLOAD_DIGEST'"'"':h})' capture

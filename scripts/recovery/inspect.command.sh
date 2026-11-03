set -euo pipefail
set +x
umask 077
export PATH=/usr/bin:/bin
exec /usr/bin/timeout --signal=TERM --kill-after=5s 115s /usr/bin/python3 -I -B -c 'import os,hashlib; p=os.environ.pop('"'"'CSF_REVIEWED_PAYLOAD'"'"'); h=hashlib.sha256(p.encode()).hexdigest(); assert h=='"'"'3c6ed8e1d26bd6a73f9e092e3a05d2af0ef0a634d8c91640dd466e633059e21d'"'"','"'"'payload digest mismatch'"'"'; exec(compile(p,'"'"'<reviewed-memory-payload>'"'"','"'"'exec'"'"'),{'"'"'__name__'"'"':'"'"'__main__'"'"','"'"'__file__'"'"':'"'"'<reviewed-memory-payload>'"'"','"'"'PAYLOAD_DIGEST'"'"':h})' inspect

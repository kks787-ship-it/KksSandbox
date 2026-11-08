set -euo pipefail
set +x
umask 077
export PATH=/usr/bin:/bin
exec /usr/bin/timeout --signal=TERM --kill-after=5s 115s /usr/bin/python3 -I -B -c 'import os,hashlib; p=os.environ.pop('"'"'CSF_REVIEWED_PAYLOAD'"'"'); h=hashlib.sha256(p.encode()).hexdigest(); assert h=='"'"'b217ebc81a079f54cdf34879b9530908b8818678729a921c7c66cc1105e74f8c'"'"','"'"'payload digest mismatch'"'"'; exec(compile(p,'"'"'<reviewed-memory-payload>'"'"','"'"'exec'"'"'),{'"'"'__name__'"'"':'"'"'__main__'"'"','"'"'__file__'"'"':'"'"'<reviewed-memory-payload>'"'"','"'"'PAYLOAD_DIGEST'"'"':h})' inspect

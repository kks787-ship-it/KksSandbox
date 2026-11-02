set -euo pipefail
set +x
umask 077
export PATH=/usr/bin:/bin
exec /usr/bin/timeout --signal=TERM --kill-after=5s 115s /usr/bin/python3 -I -B -c 'import os,hashlib; p=os.environ.pop('"'"'CSF_REVIEWED_PAYLOAD'"'"'); h=hashlib.sha256(p.encode()).hexdigest(); assert h=='"'"'8bd20008c495a3dacdacae29324d98c0264307a54dc28a82f4f3229b2088f2ed'"'"','"'"'payload digest mismatch'"'"'; exec(compile(p,'"'"'<reviewed-memory-payload>'"'"','"'"'exec'"'"'),{'"'"'__name__'"'"':'"'"'__main__'"'"','"'"'__file__'"'"':'"'"'<reviewed-memory-payload>'"'"','"'"'PAYLOAD_DIGEST'"'"':h})' inspect

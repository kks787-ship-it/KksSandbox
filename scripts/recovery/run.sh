#!/usr/bin/env bash
# Only invoke after the exact mode/payload digest/private stdin has separate owner approval.
# No fallback target, sudo, staging, credential file, background job, transfer or restore.
set -euo pipefail
set +x
umask 077
export PATH=/usr/bin:/bin
unset PYTHONPATH PYTHONHOME BASH_ENV ENV || true
[[ $# -eq 1 && ( $1 == inspect || $1 == capture ) ]] || exit 2
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.."
# The working tree is immutable and privately staged before this call under a separate approval.
sha256sum --check --strict scripts/recovery/PAYLOAD.sha256 >/dev/null
if [[ $1 == inspect ]]; then
  exec timeout --signal=TERM --kill-after=5s 115s python3 -I -B scripts/recovery/payload.py inspect
fi
exec timeout --signal=TERM --kill-after=30s 1770s python3 -I -B scripts/recovery/payload.py capture

#!/usr/bin/env bash
# Local disposable client process only: no DB daemon, network, farm paths or real secrets.
set -euo pipefail
exec docker run --rm -i --pull=never --network none --read-only --cap-drop ALL \
  --security-opt no-new-privileges --user 65534:65534 \
  --tmpfs /tmp:rw,noexec,nosuid,size=8m --entrypoint /bin/bash sha256:d29a34d05b1de1afa84c88bdad35cbba6dc1d1135ad2a95a8ceb56a710819e82 -s <<'INNER'
set -euo pipefail
umask 077
mkdir /tmp/hostile
cat > /tmp/hostile/.my.cnf <<'CNF'
[client]
port=13306
init-command=HOSTILE_SENTINEL
CNF
cat > /tmp/approved.cnf <<'CNF'
[client]
host=localhost
port=3306
user=synthetic
password=synthetic
CNF
export HOME=/tmp/hostile
for binary in mariadb mariadb-dump; do
  exec 3< /tmp/approved.cnf
  result=$("$binary" --defaults-file=/proc/self/fd/3 --print-defaults)
  [[ "$result" == *--port=3306* && "$result" != *--port=13306* && "$result" != *HOSTILE_SENTINEL* ]]
  exec 3<&-
  printf 'PASS %s explicit defaults and inherited descriptor\n' "$binary"
done
mariadb --no-defaults --version
INNER

#!/usr/bin/env bash
# Local-only synthetic recovery. Never run on the farm or pass protected data to it.
set -euo pipefail
root=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
[[ $(docker image inspect mariadb:10.6 --format '{{.Id}}') == sha256:d29a34d05b1de1afa84c88bdad35cbba6dc1d1135ad2a95a8ceb56a710819e82 ]]
[[ $(docker image inspect wordpress:6.8.3-php8.1-apache --format '{{.Id}}') == sha256:21ed73c19da1a0208130861a74838f3ee181ae7d77c9bdb5ff0cbec559f9a94c ]]
[[ $(sha256sum /usr/bin/python3.13 | cut -d ' ' -f1) == 889c603f0d17cb54060951bcf4c4f9b8c9ebd9e52b392c70209bbb9755d797d9 ]]
context=$(mktemp -d /tmp/csf-restore-fixture.XXXXXXXX)
source_container="csf-restore-software-${context##*.}"
image="csf-restore-fixture:${context##*.}"
fixture_container="csf-restore-running-${context##*.}"
base_container="csf-restore-base-${context##*.}"
cleanup() {
  docker rm -f "$source_container" "$fixture_container" "$base_container" >/dev/null 2>&1 || true
  docker image rm "$image" >/dev/null 2>&1 || true
  rm -rf -- "$context"
}
trap cleanup EXIT
mkdir -p "$context/rootfs/usr/bin" "$context/rootfs/usr/sbin" "$context/rootfs/usr/lib/x86_64-linux-gnu" "$context/rootfs/usr/share" "$context/rootfs/fixture/scripts/recovery" "$context/rootfs/fixture/docs/pilot" "$context/rootfs/fixture/tests/recovery"
cp /usr/bin/python3.13 "$context/rootfs/usr/bin/python3"
cp -a /usr/lib/python3.13 "$context/rootfs/usr/lib/"
cp "$root"/scripts/recovery/*.py "$context/rootfs/fixture/scripts/recovery/"
cp "$root"/docs/pilot/baseline-contract.json "$root"/docs/pilot/baseline-select-plan.py.txt "$context/rootfs/fixture/docs/pilot/"
cp "$root"/tests/recovery/restore_fixture.py "$context/rootfs/fixture/tests/recovery/"
cp "$root"/tests/recovery/restore-fixture.Dockerfile "$context/Dockerfile"
mkdir -p "$context/rootfs/etc" "$context/rootfs/opt/plesk/php"
ln -s /usr/local "$context/rootfs/opt/plesk/php/8.3"
docker create --name "$base_container" --network none wordpress:6.8.3-php8.1-apache >/dev/null
docker cp "$base_container:/etc/passwd" "$context/rootfs/etc/passwd"
docker cp "$base_container:/etc/group" "$context/rootfs/etc/group"
! grep -qE '^(mysql:|[^:]*:[^:]*:997:)' "$context/rootfs/etc/passwd"
printf 'mysql:x:997:997:synthetic database:/nonexistent:/bin/false\n' >> "$context/rootfs/etc/passwd"
printf 'mysql:x:997:\n' >> "$context/rootfs/etc/group"
docker rm "$base_container" >/dev/null
docker create --name "$source_container" --network none mariadb:10.6 >/dev/null
for name in mariadb mariadb-dump mariadb-install-db mariadb-check my_print_defaults resolveip aria_chk; do
  docker cp -L "$source_container:/usr/bin/$name" "$context/rootfs/usr/bin/$name"
done
for name in mariadbd ip; do
  docker cp -L "$source_container:/usr/sbin/$name" "$context/rootfs/usr/sbin/$name"
done
for name in liburing.so.2 libwrap.so.0 libbpf.so.0 libmnl.so.0 libedit.so.2 libncurses.so.6 libelf.so.1; do
  docker cp -L "$source_container:/lib/x86_64-linux-gnu/$name" "$context/rootfs/usr/lib/x86_64-linux-gnu/$name"
done
docker cp "$source_container:/usr/lib/mysql" "$context/rootfs/usr/lib/mysql"
docker cp "$source_container:/usr/share/mysql" "$context/rootfs/usr/share/mysql"
docker rm "$source_container" >/dev/null
# Public software/context directories must remain traversable after COPY; private
# capture/restore permissions are created separately by the unchanged runtime.
find "$context/rootfs" -type d -exec chmod 0755 {} +
docker build --network none --pull=false -t "$image" "$context"
timeout --signal=TERM --kill-after=30s 360s docker run --rm --name "$fixture_container" --network none --cap-add SYS_ADMIN --cap-add NET_ADMIN --security-opt seccomp=unconfined --security-opt apparmor=unconfined --memory 768m --pids-limit 256 -e CSF_SYNTHETIC_RESTORE=container-only "$image"

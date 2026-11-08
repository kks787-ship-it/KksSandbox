#!/usr/bin/env bash
# Download only official, hash-bound software into disposable scratch. No installation.
set -euo pipefail
[[ $# == 1 && "$1" == /* && ! -e "$1" ]] || { echo 'Provide a new absolute scratch directory' >&2; exit 1; }
mkdir -p "$1/packages" "$1/root"
for item in \
 'python3.13-minimal:c1fda9e7b1bf15b2fa75a238d8a8710e142fe14c722fc650c5fc614f078c9073' \
 'libpython3.13-minimal:a4aa6a9a8c77f87bfe545e1d14ec42c001d189ace90e3aa75deec882b39dee49' \
 'libpython3.13-stdlib:db161322a3481d2c0c3b9a3b9a03c3ab0e2b1718f54b88755fb4a3f939165b84'; do
 name=${item%%:*}; digest=${item#*:}; file="${name}_3.13.5-2+deb13u5_amd64.deb"
 curl --fail --show-error --location --retry 2 "https://deb.debian.org/debian/pool/main/p/python3.13/$file" -o "$1/packages/$file"
 printf '%s  %s\n' "$digest" "$1/packages/$file" | sha256sum --check -
 dpkg-deb -x "$1/packages/$file" "$1/root"
done
printf '%s  %s\n' '889c603f0d17cb54060951bcf4c4f9b8c9ebd9e52b392c70209bbb9755d797d9' "$1/root/usr/bin/python3.13" | sha256sum --check -
printf 'PINNED_PYTHON_READY=%s/root/usr\n' "$1"

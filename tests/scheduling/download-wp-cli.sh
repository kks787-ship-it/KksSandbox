#!/usr/bin/env bash
# Official WP-CLI 2.12.0 bytes only; no install, credentials or farm access.
set -euo pipefail
mkdir -p .wp-local
release=https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar
asset=https://api.github.com/repos/wp-cli/wp-cli/releases/assets/487001819
if ! curl --fail --show-error --location --connect-timeout 15 --max-time 90 --retry 2 "$release" -o .wp-local/wp-cli.phar; then
  curl --fail --show-error --location --connect-timeout 15 --max-time 90 --retry 2 -H 'Accept: application/octet-stream' "$asset" -o .wp-local/wp-cli.phar
fi
echo 'ce34ddd838f7351d6759068d09793f26755463b4a4610a5a5c0a97b68220d85c  .wp-local/wp-cli.phar' | sha256sum --check -

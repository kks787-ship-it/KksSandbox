#!/usr/bin/env bash
# Download only the previously qualified official Two-Factor release bytes.
set -euo pipefail
mkdir -p .wp-local
release=https://github.com/WordPress/two-factor/releases/download/0.17.0/two-factor.zip
asset=https://api.github.com/repos/WordPress/two-factor/releases/assets/594537395
if ! curl --fail --show-error --location --connect-timeout 15 --max-time 90 --retry 2 "$release" -o .wp-local/two-factor.zip; then
  curl --fail --show-error --location --connect-timeout 15 --max-time 90 --retry 2 -H 'Accept: application/octet-stream' "$asset" -o .wp-local/two-factor.zip
fi
python3 tests/scheduling/verify-mfa-release.py .wp-local/two-factor.zip

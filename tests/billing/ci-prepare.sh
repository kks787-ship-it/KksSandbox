#!/usr/bin/env bash
# Official pinned software only, disposable GitHub runner. No secrets or farm data.
set -euo pipefail
mkdir -p .wp-local/site
curl --fail --show-error --location --connect-timeout 15 --max-time 180 --retry 3 https://wordpress.org/wordpress-7.1.4.tar.gz -o .wp-local/wordpress-mfa.tar.gz
echo 'b175879c4fb2d35300f9abe1153da9b543a35a2b  .wp-local/wordpress-mfa.tar.gz' | sha1sum --check -
tar -xzf .wp-local/wordpress-mfa.tar.gz --strip-components=1 -C .wp-local/site
bash tests/scheduling/download-wp-cli.sh
bash tests/scheduling/download-mfa.sh
docker pull mariadb:10.6
docker pull wordpress:6.8.3-php8.1-apache
docker build --build-arg CSF_TEST_PHP_VERSION=8.3 -t csf-scheduling-php83 tests/scheduling
bash tests/recovery/prepare-pinned-python.sh "$RUNNER_TEMP/csf-billing-recovery-python"
echo "CSF_RECOVERY_PYTHON_ROOT=$RUNNER_TEMP/csf-billing-recovery-python/root/usr" >> "$GITHUB_ENV"

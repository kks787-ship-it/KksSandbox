#!/usr/bin/env bash
# Exercise real deployment/backup scripts without contacting or changing a site.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/bin" "$TMP/site with spaces/wp-content"
export CSF_GUARD_CALLS="$TMP/calls"
cat > "$TMP/bin/wp" <<'MOCK'
#!/usr/bin/env bash
printf '%s\n' "$*" >> "$CSF_GUARD_CALLS"
if [[ "${2:-}" == eval ]]; then
  [[ "$CSF_TEST_ENV" != failure ]] || exit 1
  printf '%s' "$CSF_TEST_ENV"
elif [[ "${2:-}" == db ]]; then
  printf 'local test database\n' > "$4"
fi
MOCK
cat > "$TMP/bin/rsync" <<'MOCK'
#!/usr/bin/env bash
printf 'rsync %s\n' "$*" >> "$CSF_GUARD_CALLS"
MOCK
chmod +x "$TMP/bin/"*
export PATH="$TMP/bin:$PATH"
checks=0
for script in deploy-plugins backup-dev; do
  for CSF_TEST_ENV in production unknown failure ''; do
    export CSF_TEST_ENV
    : > "$CSF_GUARD_CALLS"
    if bash "$ROOT/scripts/$script.sh" "$TMP/site with spaces" "$TMP/backup" > "$TMP/output" 2>&1; then
      echo "FAIL: $script allowed '$CSF_TEST_ENV'"; exit 1
    fi
    if rg -q '^rsync | db ' "$CSF_GUARD_CALLS" || [[ -e "$TMP/backup" ]]; then
      echo "FAIL: $script performed work before environment verification"; exit 1
    fi
    checks=$((checks+1))
  done
done
for CSF_TEST_ENV in local development staging; do
  export CSF_TEST_ENV
  : > "$CSF_GUARD_CALLS"
  bash "$ROOT/scripts/deploy-plugins.sh" "$TMP/site with spaces" > "$TMP/output"
  [[ "$(rg -c '^rsync .*--dry-run' "$CSF_GUARD_CALLS")" == 2 ]]
  bash "$ROOT/scripts/deploy-plugins.sh" "$TMP/site with spaces" --apply > "$TMP/output"
  bash "$ROOT/scripts/backup-dev.sh" "$TMP/site with spaces" "$TMP/backup-$CSF_TEST_ENV" > "$TMP/output"
  (cd "$TMP/backup-$CSF_TEST_ENV"/csf-dev-* && sha256sum -c SHA256SUMS > /dev/null)
  checks=$((checks+3))
done
echo "PASS: $checks deployment/backup scenarios (blocked targets, dry run, apply, backup checksums, paths with spaces)"

#!/usr/bin/env bash
# Exercise real rsync with 0600/0700 source files, without touching a real WordPress site.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
CSF_RSYNC_BIN="${CSF_RSYNC_BIN:-$(command -v rsync || true)}"
[[ -x "$CSF_RSYNC_BIN" ]] || { echo 'Set CSF_RSYNC_BIN to an installed rsync executable.' >&2; exit 1; }
export CSF_RSYNC_BIN
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/bin" "$TMP/checkout/scripts" "$TMP/target/wp-content/plugins"
cp "$ROOT/scripts/"{deploy-plugins,backup-dev,verify-dev-backup}.sh "$TMP/checkout/scripts/"
cat > "$TMP/bin/wp" <<'MOCK'
#!/usr/bin/env bash
case "${2:-}" in
 eval) printf development ;;
 option) printf https://dev.example.invalid ;;
 db) printf 'synthetic SQL backup\n' > "$4" ;;
 core) printf '7.1.4\n' ;;
 plugin) printf 'name,status\n' ;;
 *) exit 1 ;;
esac
MOCK
cat > "$TMP/bin/rsync" <<'MOCK'
#!/usr/bin/env bash
exec "$CSF_RSYNC_BIN" "$@"
MOCK
chmod +x "$TMP/bin/"*
export PATH="$TMP/bin:$PATH"
umask 077
for plugin in csf-core csf-emergency; do
  mkdir -p "$TMP/checkout/wp-content/plugins/$plugin/nested"
  printf '<?php // synthetic plugin\n' > "$TMP/checkout/wp-content/plugins/$plugin/$plugin.php"
  printf 'new asset\n' > "$TMP/checkout/wp-content/plugins/$plugin/nested/asset.css"
done
printf 'protected configuration\n' > "$TMP/target/wp-config.php"
chmod 600 "$TMP/target/wp-config.php"
mkdir -p "$TMP/target/wp-content/plugins/csf-core"
printf 'old content\n' > "$TMP/target/wp-content/plugins/csf-core/csf-core.php"
printf 'obsolete fixture\n' > "$TMP/target/wp-content/plugins/csf-core/obsolete"
bash "$TMP/checkout/scripts/backup-dev.sh" "$TMP/target" "$TMP/backups" > "$TMP/backup-output"
backup=("$TMP/backups"/csf-dev-*)
printf 'Synthetic local fixture restore evidence; no real site.\n' > "$TMP/restore-evidence"
bash "$TMP/checkout/scripts/deploy-plugins.sh" "$TMP/target" > "$TMP/dry-run"
[[ "$(stat -c %a "$TMP/target/wp-content/plugins/csf-core/csf-core.php")" == 600 ]]
[[ "$(cat "$TMP/target/wp-content/plugins/csf-core/csf-core.php")" == 'old content' ]]
[[ ! -e "$TMP/target/wp-content/plugins/csf-emergency" ]]
for iteration in first update; do
  if [[ "$iteration" == update ]]; then
    printf 'updated asset content\n' > "$TMP/checkout/wp-content/plugins/csf-core/nested/asset.css"
  fi
  bash "$TMP/checkout/scripts/deploy-plugins.sh" "$TMP/target" --apply \
    --expected-url https://dev.example.invalid --backup "${backup[0]}" \
    --restore-evidence "$TMP/restore-evidence" > "$TMP/apply-output"
  for plugin in csf-core csf-emergency; do
    cmp "$TMP/checkout/wp-content/plugins/$plugin/$plugin.php" "$TMP/target/wp-content/plugins/$plugin/$plugin.php"
    cmp "$TMP/checkout/wp-content/plugins/$plugin/nested/asset.css" "$TMP/target/wp-content/plugins/$plugin/nested/asset.css"
    [[ -z "$(find "$TMP/target/wp-content/plugins/$plugin" -type d ! -perm 0755 -print -quit)" ]]
    [[ -z "$(find "$TMP/target/wp-content/plugins/$plugin" -type f ! -perm 0644 -print -quit)" ]]
  done
  [[ ! -e "$TMP/target/wp-content/plugins/csf-core/obsolete" ]]
  [[ "$(stat -c %a "$TMP/target/wp-config.php")" == 600 ]]
  [[ "$(cat "$TMP/target/wp-config.php")" == 'protected configuration' ]]
done
# Narrowing a public-site update must leave the inactive emergency plugin untouched.
printf 'emergency target must stay unchanged\n' > "$TMP/target/wp-content/plugins/csf-emergency/csf-emergency.php"
chmod 600 "$TMP/target/wp-content/plugins/csf-emergency/csf-emergency.php"
bash "$TMP/checkout/scripts/deploy-plugins.sh" "$TMP/target" --only-core > "$TMP/core-dry-run"
! rg -q '== csf-emergency' "$TMP/core-dry-run"
bash "$TMP/checkout/scripts/deploy-plugins.sh" "$TMP/target" --only-core --apply \
  --expected-url https://dev.example.invalid --backup "${backup[0]}" \
  --restore-evidence "$TMP/restore-evidence" > "$TMP/core-apply"
[[ "$(cat "$TMP/target/wp-content/plugins/csf-emergency/csf-emergency.php")" == 'emergency target must stay unchanged' ]]
[[ "$(stat -c %a "$TMP/target/wp-content/plugins/csf-emergency/csf-emergency.php")" == 600 ]]
echo 'PASS: real-rsync dry run preserves target; initial/repeat updates normalize selected plugin permissions under umask 077; wp-config stays 0600; --only-core leaves emergency unchanged.'

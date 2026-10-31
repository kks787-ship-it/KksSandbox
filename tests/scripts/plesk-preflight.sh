#!/usr/bin/env bash
# Tests target identity gates; PHP runtime checks are mocked, not server validation.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/bin" "$TMP/dev root"
cat > "$TMP/bin/wp" <<'MOCK'
#!/usr/bin/env bash
while [[ "${1:-}" == --* ]]; do shift; done
case "$1 $2" in
 'eval echo wp_get_environment_type();') printf '%s' "$CSF_TEST_TYPE" ;;
 'option get')
  case "$3" in
   home|siteurl) printf '%s' "$CSF_TEST_URL" ;;
   blog_public) printf '%s' "$CSF_TEST_PUBLIC" ;;
   template) printf '%s' "$CSF_TEST_THEME" ;;
   permalink_structure) printf '/%%postname%%/' ;;
  esac ;;
 *) exit 0 ;;
esac
MOCK
chmod +x "$TMP/bin/wp"
export PATH="$TMP/bin:$PATH"
checks=0
for scenario in valid production unknown wrong-url indexed standin; do
 export CSF_TEST_TYPE=development CSF_TEST_URL=https://dev.example.invalid CSF_TEST_PUBLIC=0 CSF_TEST_THEME=kadence
 case "$scenario" in
  production) CSF_TEST_TYPE=production ;;
  unknown) CSF_TEST_TYPE=unknown ;;
  wrong-url) CSF_TEST_URL=https://production.example.invalid ;;
  indexed) CSF_TEST_PUBLIC=1 ;;
  standin) CSF_TEST_THEME=csf-kadence-standin ;;
 esac
 if bash "$ROOT/scripts/plesk-preflight.sh" "$TMP/dev root" https://dev.example.invalid > "$TMP/output" 2>&1; then
  [[ "$scenario" == valid ]] || { echo "FAIL: preflight allowed $scenario"; exit 1; }
 else
  [[ "$scenario" != valid ]] || { cat "$TMP/output"; exit 1; }
 fi
 checks=$((checks+1))
done
echo "PASS: $checks Plesk preflight identity scenarios (runtime checks mocked)"

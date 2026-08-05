#!/usr/bin/env bash
set -euo pipefail

readonly EXPECTED_FLUTTER_SHA="77d00027b7771e219ac1a74fbea4b7e1029e905d36afe3fcb97d1b2f557f6ec8"
readonly EXPECTED_LARAVEL_SHA="412765575258c3c17f9da652d225306b5cbab3cd69f4953b65e02a4980693037"
readonly EXPECTED_MOBILEKIT_SHA="a2f9bda06a35a2d82217692bdff736a8b901cef795422fc98f5816f667c6a736"

usage() {
  cat <<'EOF'
Usage:
  import-donors.sh \
    --flutter /path/to/qrpay-user-app.zip \
    --laravel /path/to/qrpay-web.zip \
    --mobilekit /path/to/mobilekit.zip \
    [--skip-hash-check]

Run from the repository root on a clean working tree.
The script replaces only these donor targets:
  mobile apps/titan-hub
  mobile apps/mobilekit-reference
  integrations/qrpay-web
EOF
}

FLUTTER_ZIP=""
LARAVEL_ZIP=""
MOBILEKIT_ZIP=""
SKIP_HASH_CHECK=false

while [[ $# -gt 0 ]]; do
  case "$1" in
    --flutter) FLUTTER_ZIP="${2:-}"; shift 2 ;;
    --laravel) LARAVEL_ZIP="${2:-}"; shift 2 ;;
    --mobilekit) MOBILEKIT_ZIP="${2:-}"; shift 2 ;;
    --skip-hash-check) SKIP_HASH_CHECK=true; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; usage; exit 2 ;;
  esac
done

for value in "$FLUTTER_ZIP" "$LARAVEL_ZIP" "$MOBILEKIT_ZIP"; do
  [[ -n "$value" && -f "$value" ]] || { echo "Missing archive: $value" >&2; exit 1; }
done

for command in git unzip find sha256sum awk sed grep; do
  command -v "$command" >/dev/null 2>&1 || {
    echo "Required command is unavailable: $command" >&2
    exit 1
  }
done

[[ -d .git ]] || { echo "Run from the repository root." >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo "Working tree must be clean." >&2; exit 1; }

verify_hash() {
  local archive="$1"
  local expected="$2"
  local label="$3"
  local actual

  actual="$(sha256sum "$archive" | awk '{print $1}')"
  if [[ "$actual" != "$expected" ]]; then
    echo "$label hash mismatch." >&2
    echo "Expected: $expected" >&2
    echo "Actual:   $actual" >&2
    exit 1
  fi
  printf 'Verified %-24s %s\n' "$label" "$actual"
}

if [[ "$SKIP_HASH_CHECK" == false ]]; then
  verify_hash "$FLUTTER_ZIP" "$EXPECTED_FLUTTER_SHA" "QRPay Flutter"
  verify_hash "$LARAVEL_ZIP" "$EXPECTED_LARAVEL_SHA" "QRPay Laravel"
  verify_hash "$MOBILEKIT_ZIP" "$EXPECTED_MOBILEKIT_SHA" "MobileKit"
else
  echo "WARNING: archive hash verification was explicitly skipped." >&2
fi

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

extract_one() {
  local archive="$1"
  local destination="$2"
  local staging="$TMP_DIR/$(basename "$destination" | tr ' ' '-')"

  mkdir -p "$staging"
  unzip -q "$archive" -d "$staging"

  find "$staging" -type d -name '__MACOSX' -prune -exec rm -rf {} +
  find "$staging" -type f -name '.DS_Store' -delete
  find "$staging" -type d \( \
    -name build -o \
    -name .dart_tool -o \
    -name node_modules -o \
    -name vendor \
  \) -prune -exec rm -rf {} +
  find "$staging" -type d \( \
    -name logs -o \
    -name cache -o \
    -name sessions \
  \) -path '*/storage/*' -prune -exec rm -rf {} +

  mkdir -p "$destination"

  # Flatten exactly one archive wrapper directory, while retaining the donor's
  # internal project structure and dotfiles.
  shopt -s dotglob nullglob
  local entries=("$staging"/*)
  if [[ ${#entries[@]} -eq 1 && -d "${entries[0]}" ]]; then
    cp -a "${entries[0]}/." "$destination/"
  else
    cp -a "$staging/." "$destination/"
  fi
  shopt -u dotglob nullglob
}

readonly FLUTTER_DEST='mobile apps/titan-hub'
readonly MOBILEKIT_DEST='mobile apps/mobilekit-reference'
readonly LARAVEL_DEST='integrations/qrpay-web'

rm -rf "$FLUTTER_DEST" "$MOBILEKIT_DEST" "$LARAVEL_DEST"
extract_one "$FLUTTER_ZIP" "$FLUTTER_DEST"
extract_one "$MOBILEKIT_ZIP" "$MOBILEKIT_DEST"
extract_one "$LARAVEL_ZIP" "$LARAVEL_DEST"

remove_known_sensitive_files() {
  rm -f \
    "$FLUTTER_DEST/android/key.properties" \
    "$FLUTTER_DEST/android/app/key.jks" \
    "$FLUTTER_DEST/android/app/google-services.json" \
    "$FLUTTER_DEST/ios/Runner/GoogleService-Info.plist" \
    "$LARAVEL_DEST/.env" \
    "$LARAVEL_DEST/storage/oauth-private.key" \
    "$LARAVEL_DEST/storage/oauth-public.key"
}

remove_known_sensitive_files

SENSITIVE_PATHS="$TMP_DIR/sensitive-paths.txt"
find "$FLUTTER_DEST" "$LARAVEL_DEST" "$MOBILEKIT_DEST" \
  -type f \( \
    -iname '*.jks' -o \
    -iname '*.keystore' -o \
    -iname 'key.properties' -o \
    -iname '.env' -o \
    -iname '.env.*' -o \
    -iname 'oauth-private.key' -o \
    -iname '*private*.pem' -o \
    -iname '*private*.key' \
  \) -print > "$SENSITIVE_PATHS"

# Placeholder examples are allowed only after manual inspection. A plain `.env`
# or private/signing key is always blocked.
if grep -Ev '/\.env\.example$|/\.env\.sample$' "$SENSITIVE_PATHS" | grep -q .; then
  echo 'Sensitive files remain after import:' >&2
  grep -Ev '/\.env\.example$|/\.env\.sample$' "$SENSITIVE_PATHS" >&2
  exit 1
fi

AUDIT_FILE="$TMP_DIR/import-audit.txt"
{
  echo "Titan Hub donor import audit"
  echo "Generated: $(date -u +'%Y-%m-%dT%H:%M:%SZ')"
  echo
  echo "File counts"
  printf 'Flutter:   %s\n' "$(find "$FLUTTER_DEST" -type f | wc -l | tr -d ' ')"
  printf 'Laravel:   %s\n' "$(find "$LARAVEL_DEST" -type f | wc -l | tr -d ' ')"
  printf 'MobileKit: %s\n' "$(find "$MOBILEKIT_DEST" -type f | wc -l | tr -d ' ')"
  echo
  echo "Largest files"
  find "$FLUTTER_DEST" "$LARAVEL_DEST" "$MOBILEKIT_DEST" -type f -printf '%s %p\n' \
    | sort -nr \
    | head -20
  echo
  echo "Potential credential or sensitive logging references requiring review"
  grep -RInE \
    --exclude-dir=.git \
    --exclude='*.lock' \
    --exclude='*.min.js' \
    '(api[_-]?key|client[_-]?secret|private[_-]?key|BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|Authorization: Bearer|print\(.*response|log\(.*request)' \
    "$FLUTTER_DEST" "$LARAVEL_DEST" 2>/dev/null \
    | head -300 || true
} > "$AUDIT_FILE"

cat "$AUDIT_FILE"

echo
echo 'Donor sources imported and baseline-sensitive files removed.'
echo 'Next steps:'
echo '  1. Review the audit output above.'
echo '  2. Run the repository secret scanner and dependency audits.'
echo '  3. Inspect hard-coded domains, API keys and sensitive logging.'
echo '  4. Review git status and git diff before committing.'
echo '  5. Commit donor source separately from Titan feature modifications.'

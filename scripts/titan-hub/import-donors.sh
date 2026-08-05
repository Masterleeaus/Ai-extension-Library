#!/usr/bin/env bash
set -euo pipefail

usage() {
  cat <<'EOF'
Usage:
  import-donors.sh \
    --flutter /path/to/qrpay-user-app.zip \
    --laravel /path/to/qrpay-web.zip \
    --mobilekit /path/to/mobilekit.zip

Run from the repository root on a clean working tree.
EOF
}

FLUTTER_ZIP=""
LARAVEL_ZIP=""
MOBILEKIT_ZIP=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --flutter) FLUTTER_ZIP="$2"; shift 2 ;;
    --laravel) LARAVEL_ZIP="$2"; shift 2 ;;
    --mobilekit) MOBILEKIT_ZIP="$2"; shift 2 ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Unknown argument: $1" >&2; usage; exit 2 ;;
  esac
done

for value in "$FLUTTER_ZIP" "$LARAVEL_ZIP" "$MOBILEKIT_ZIP"; do
  [[ -f "$value" ]] || { echo "Missing archive: $value" >&2; exit 1; }
done

[[ -d .git ]] || { echo "Run from the repository root." >&2; exit 1; }
[[ -z "$(git status --porcelain)" ]] || { echo "Working tree must be clean." >&2; exit 1; }

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

extract_one() {
  local archive="$1"
  local destination="$2"
  local staging="$TMP_DIR/$(basename "$destination" | tr ' ' '-')"

  mkdir -p "$staging" "$destination"
  unzip -q "$archive" -d "$staging"

  find "$staging" -type d -name '__MACOSX' -prune -exec rm -rf {} +
  find "$staging" -type f -name '.DS_Store' -delete
  find "$staging" -type d \( -name build -o -name .dart_tool -o -name node_modules -o -name vendor \) -prune -exec rm -rf {} +
  find "$staging" -type d \( -name logs -o -name cache -o -name sessions \) -path '*/storage/*' -prune -exec rm -rf {} +

  # Flatten exactly one archive wrapper directory when present.
  shopt -s dotglob nullglob
  local entries=("$staging"/*)
  if [[ ${#entries[@]} -eq 1 && -d "${entries[0]}" ]]; then
    cp -a "${entries[0]}/." "$destination/"
  else
    cp -a "$staging/." "$destination/"
  fi
  shopt -u dotglob nullglob
}

rm -rf 'mobile apps/titan-hub' 'mobile apps/mobilekit-reference' 'integrations/qrpay-web'
extract_one "$FLUTTER_ZIP" 'mobile apps/titan-hub'
extract_one "$MOBILEKIT_ZIP" 'mobile apps/mobilekit-reference'
extract_one "$LARAVEL_ZIP" 'integrations/qrpay-web'

rm -f \
  'mobile apps/titan-hub/android/key.properties' \
  'mobile apps/titan-hub/android/app/key.jks' \
  'mobile apps/titan-hub/android/app/google-services.json' \
  'mobile apps/titan-hub/ios/Runner/GoogleService-Info.plist' \
  'integrations/qrpay-web/.env' \
  'integrations/qrpay-web/storage/oauth-private.key' \
  'integrations/qrpay-web/storage/oauth-public.key'

find 'mobile apps/titan-hub' 'integrations/qrpay-web' 'mobile apps/mobilekit-reference' \
  -type f \( -iname '*.jks' -o -iname '*.keystore' -o -iname 'key.properties' -o -iname '.env' -o -iname 'oauth-private.key' \) \
  -print -quit | grep -q . && {
    echo 'Sensitive files remain after import; aborting.' >&2
    exit 1
  }

echo 'Donor sources imported and baseline-sensitive files removed.'
echo 'Next: run secret scanning, inspect hard-coded domains/keys, and review git diff before commit.'

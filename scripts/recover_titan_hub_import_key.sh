#!/usr/bin/env bash
set -euo pipefail

: "${GITHUB_REPOSITORY:?GITHUB_REPOSITORY is required}"
: "${GH_TOKEN:?GH_TOKEN is required}"

api_headers=(
  -H "Accept: application/vnd.github+json"
  -H "Authorization: Bearer ${GH_TOKEN}"
  -H "X-GitHub-Api-Version: 2022-11-28"
)

curl --fail --silent --show-error \
  "${api_headers[@]}" \
  "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/artifacts?name=titan-hub-import-private-key&per_page=100" \
  > /tmp/titan-hub-artifacts.json

python - <<'PY'
import json
from pathlib import Path

artifacts = json.loads(Path('/tmp/titan-hub-artifacts.json').read_text()).get('artifacts', [])
artifacts = [item for item in artifacts if not item.get('expired')]
if not artifacts:
    raise SystemExit('No unexpired private-key artifact is available')
artifact = max(artifacts, key=lambda item: item['created_at'])
Path('/tmp/titan-hub-import-artifact-id').write_text(str(artifact['id']), encoding='utf-8')
PY

artifact_id="$(cat /tmp/titan-hub-import-artifact-id)"
curl --fail --silent --show-error \
  -D /tmp/titan-hub-artifact-headers.txt \
  -o /dev/null \
  "${api_headers[@]}" \
  "https://api.github.com/repos/${GITHUB_REPOSITORY}/actions/artifacts/${artifact_id}/zip"

python - <<'PY'
from pathlib import Path

locations = []
for line in Path('/tmp/titan-hub-artifact-headers.txt').read_text(encoding='utf-8').splitlines():
    if line.lower().startswith('location:'):
        locations.append(line.split(':', 1)[1].strip())
if not locations:
    raise SystemExit('GitHub artifact response did not include a redirect location')
Path('/tmp/titan-hub-artifact-location').write_text(locations[-1], encoding='utf-8')
PY

# The signed Azure URL is fetched separately and receives no GitHub token.
curl --fail --silent --show-error --location \
  "$(cat /tmp/titan-hub-artifact-location)" \
  -o /tmp/titan-hub-import-private.zip

test "$(unzip -Z1 /tmp/titan-hub-import-private.zip)" = "titan-hub-import-private.pem"
unzip -q /tmp/titan-hub-import-private.zip -d /tmp

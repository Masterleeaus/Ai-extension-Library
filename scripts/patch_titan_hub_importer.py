#!/usr/bin/env python3
from pathlib import Path

path = Path('.github/workflows/import-titan-hub-donors.yml')
text = path.read_text(encoding='utf-8')

old_download = """          download = urllib.request.Request(artifact['archive_download_url'], headers=headers)
          archive = Path('/tmp/titan-hub-import-private.zip')
          with urllib.request.urlopen(download, timeout=120) as response, archive.open('wb') as output:
              output.write(response.read())
"""
new_download = """          class StripAuthorizationOnRedirect(urllib.request.HTTPRedirectHandler):
              def redirect_request(self, req, fp, code, msg, response_headers, newurl):
                  redirected = super().redirect_request(req, fp, code, msg, response_headers, newurl)
                  if redirected is not None:
                      redirected.remove_header('Authorization')
                  return redirected

          opener = urllib.request.build_opener(StripAuthorizationOnRedirect)
          download = urllib.request.Request(artifact['archive_download_url'], headers=headers)
          archive = Path('/tmp/titan-hub-import-private.zip')
          with opener.open(download, timeout=120) as response, archive.open('wb') as output:
              output.write(response.read())
"""
old_cleanup = """          rm .github/workflows/import-titan-hub-donors.yml
          rm -rf transport/titan-hub-import
"""
new_cleanup = """          rm .github/workflows/import-titan-hub-donors.yml
          rm -f .github/workflows/inspect-titan-hub-donor-import.yml
          rm -rf transport/titan-hub-import
"""
old_add = """            .github/workflows/import-titan-hub-donors.yml \\
            transport/titan-hub-import
"""
new_add = """            .github/workflows/import-titan-hub-donors.yml \\
            .github/workflows/inspect-titan-hub-donor-import.yml \\
            transport/titan-hub-import
"""

for old, new, label in (
    (old_download, new_download, 'artifact redirect'),
    (old_cleanup, new_cleanup, 'temporary workflow cleanup'),
    (old_add, new_add, 'temporary workflow staging'),
):
    if old not in text:
        raise SystemExit(f'Expected {label} block was not found')
    text = text.replace(old, new, 1)

path.write_text(text, encoding='utf-8')
print('Patched Titan Hub importer workflow')

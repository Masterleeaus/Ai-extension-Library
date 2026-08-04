# Agent 4 Pass 4 — Final Regression and Cleanup

## Scope

Final Agent 4 regression pass covering attachment integrity, knowledge cursor safety, IndexedDB lifecycle behaviour, service-worker privacy and network reachability compatibility.

## Root causes corrected

1. Strict image/PDF MIME validation accepted files whose signatures could not be recognised.
2. Attachment deletion could leave resumable-upload chunk metadata behind.
3. Knowledge refresh retained a stale cursor when the server returned no next cursor.
4. Cyclic server cursors could repeatedly download the same knowledge pages until the page limit.
5. Server knowledge updates could overwrite a locally selected favourite when the field was omitted.
6. IndexedDB transaction completion listeners were registered after the operation callback, creating a completion race.
7. Open database handles did not close on version changes, potentially blocking migrations in another tab.
8. The service worker dynamically cached navigated HTML despite its authenticated-response exclusion guarantee.
9. Reachability checks failed against valid endpoints that reject HEAD but support GET.
10. SVG was enabled without a complete sanitisation/rendering policy.

## Changes

- Require recognised signatures for strict image and PDF types.
- Remove SVG from default supported attachments.
- Delete attachment chunk records whenever an attachment is removed.
- Clear completed knowledge cursors and detect cursor cycles.
- Preserve existing local favourites when server payloads omit favourite state.
- Register IndexedDB transaction completion before issuing requests.
- Close IndexedDB and emit `workcore:database-versionchange` on upgrades.
- Stop runtime caching of navigated HTML; retain the precached offline shell fallback.
- Fall back from HEAD to GET when reachability returns HTTP 405.
- Advance the Agent 4 contract to 1.4.0 and service-worker shell to v9-agent4-final.

## Verification

- Agent 4 contract tests: 14 passed, 0 failed.
- JavaScript syntax validation: all included JavaScript files passed `node --check`.
- Archive integrity: verified with `unzip -t`.

# Chatbot Team Chat — Full Cumulative Delta

This archive combines:

- Team Chat extraction passes 1–4
- Direct staff conversations
- Group chats and participant roles
- Business channels and linked rooms
- Local-first IndexedDB integration
- Realtime, read-receipt and typing foundations
- Staff-inbox UI wiring repair
- Direct/group/channel creation workflows
- Channel discovery and joining
- Message payload, realtime event and CSRF corrections
- Regression tests and manifests

Apply this delta over the Agent 1 final chatbot extension or the matching chatbot base used for the extraction series.

## Required deletion

Remove the path listed in `DELETE-FILES.txt` if it exists. It is an accidental temporary file and is not included in this archive.

No `legacy`, `source`, or `integration` directories are introduced.

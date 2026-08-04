# Offline ERP Façade Extraction Notes

## Reusable patterns

- LocalBrain subprocess adapter using JSON stdin/stdout.
- Explicit intent-to-tool allowlist.
- Confidence gate before execution.
- Deterministic response templates.
- Scoped SQLite conversation history.
- Fail-closed behavior when LocalBrain is unavailable.
- Minimal privacy-preserving operational logs.

## Do not reintroduce

- External language-model provider selection.
- API-key configuration.
- Silent online fallback.
- Python-side duplicate intent classification.
- Global conversation-memory lists.
- Unscoped history retrieval.

## Generalizing beyond schools

Replace the eight school tools and intent mapping, while preserving:

```text
LocalBrain perception
 -> confidence and policy gate
 -> allowlisted domain tool
 -> verified local data
 -> deterministic response
 -> scoped memory and audit
```

For business writes, route through the interaction engine's governed command/approval boundary rather than calling database functions directly.

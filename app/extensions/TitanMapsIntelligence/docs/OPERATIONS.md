# Operations and Troubleshooting

## Metrics to collect

- search count and completion rate
- queue depth and age
- provider latency and error rate
- rate-limit events
- results discovered, processed, skipped, and duplicated
- candidate approval and promotion rates
- ambiguous match count
- estimated provider cost by company and operation
- export count, size, expiry, and failed signed-link access

## Stable errors

| Code | Meaning | Operator action |
|---|---|---|
| `MAPS_HOST_CONTRACT_MISSING` | Host adapter absent | Bind the named authoritative contract |
| `MAPS_PROVIDER_NOT_CONFIGURED` | No enabled company connection | Add provider connection and Vault reference |
| `MAPS_PROVIDER_AUTH_FAILED` | Credential rejected | Rotate/validate Vault secret and key restrictions |
| `MAPS_PROVIDER_RATE_LIMITED` | Provider throttled request | Observe retry, quotas, and company caps |
| `MAPS_PROVIDER_UNAVAILABLE` | Transient provider/network issue | Check health and bounded retries |
| `MAPS_AMBIGUOUS_MATCH` | Candidate may duplicate WorkCore record | Resolve manually before promotion |
| `MAPS_PERMISSION_DENIED` | User/agent lacks permission | Review delegated role, not database rows |
| `MAPS_EXPORT_FORMAT_UNSUPPORTED` | Export format is outside CSV/JSON/XLSX | Correct the request; do not silently substitute |
| `MAPS_SEARCH_NOT_FOUND` | Search is absent from authorised company scope | Verify active company and identifier |

## Queue recovery

Search and run state is durable. Restart workers normally. Completed and cancelled jobs are idempotent no-ops. Failed runs retain a safe code and safe description; provider response bodies are not stored.

## Data refresh

Treat `last_observed_at` as observation time, not proof of current availability. Configure provider-specific refresh intervals. Emergency searches should perform a fresh provider request and still describe availability as provider-reported.

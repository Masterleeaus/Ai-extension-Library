# Security and Privacy

## Security controls

- disabled by default
- fail-closed host contract resolution
- authenticated protected routes
- server-derived company context
- controller and service permission checks
- company predicates on records and jobs
- per-company Vault references
- HTTPS-only provider transport with host allowlist
- bounded timeout, retry, radius, and result counts
- explicit Google field masks; wildcard masks rejected
- safe stable errors without provider bodies
- suppression and do-not-reintroduce records
- no embedded browser, DOM scraper, vendor updater, or vendor telemetry
- no direct WorkCore writes

## Privacy classes

The extension may process public business information. Public availability does not remove privacy, contractual, marketing, or retention obligations. Configure a defined purpose before collecting or promoting data.

## Contact governance

Discovery does not authorise outreach. Do not automatically message, call, or market to candidates. Separate operational provider sourcing from marketing campaigns. Apply Australian privacy, spam, telemarketing, suppression, and record-retention requirements as applicable.

## Raw payloads

The default implementation does not persist provider raw payloads. If a later provider allows and requires raw retention, store an encrypted private reference with explicit expiry, terms metadata, and `raw-data.read` permission. Never return it through Quattro tools.

## Threats considered

- cross-company access
- forged company identifiers
- agent privilege escalation
- secret leakage
- SSRF/provider-host substitution
- unbounded scraping or cost
- silent duplicate merge
- stale data presented as live
- unsafe provider error disclosure
- prohibited donor-code inclusion

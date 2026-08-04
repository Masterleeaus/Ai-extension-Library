# Architecture

## Position in Titan Zero

```text
Meetup Chat
  └─ Titan Zero orchestration
      └─ Titan Uno / Duo / Trio delegation
          └─ Titan Maps Intelligence Quattro tools
              ├─ Authorised host company context
              ├─ Provider adapter registry
              ├─ Durable discovery queues
              ├─ External observation store
              ├─ Candidate governance
              ├─ Private governed export
              └─ WorkCore gateway
```

Meetup owns interaction. Titan Zero owns intent and delegation. This extension owns external observations and discovery workflow state. WorkCore owns operational business records.

## Trust boundaries

The extension consumes, but never replaces:

- authenticated identity
- active-company context
- memberships and permissions
- Titan Vault secret resolution
- audit storage
- capability registration
- private signed-export storage
- WorkCore entity lookup and promotion

When enabled, the service provider resolves every required contract through `RequiredHostContract`. Missing contracts cause `MAPS_HOST_CONTRACT_MISSING` and routes are not safely usable until the host is complete.

## Main components

| Component | Responsibility |
|---|---|
| `PlacesProvider` | Provider-neutral search/details contract |
| `GooglePlacesProvider` | Google Places API (New) request and normalisation |
| `PlacesProviderRegistry` | Per-company provider factory resolution |
| `DiscoverySearchService` | Authorised search creation, reading, cancellation |
| Queue jobs | Durable, idempotent page processing and checkpointing |
| `ExternalPlaceRepository` | Exact provider identity upsert and field observations |
| Candidate services | Classification, ranking, matching, review, suppression |
| `CandidatePromotionService` | Permission-checked WorkCore promotion and lineage |
| Territory manager | Deterministic geographic summaries, not demand claims |
| `CandidateExportService` | Safe-field streaming exports through host-private storage |
| Meetup components | Search composer, progress, candidate review, and territory summary surfaces |
| Quattro tools | Typed, permission-bound Titan capability handlers |

## Data flow

```text
Search intent
  → company and permission check
  → discovery_search + queued job
  → company provider connection
  → Vault credential resolution
  → licensed provider request
  → normalised ExternalPlaceData
  → external place observation + provenance
  → suppression check
  → deterministic classification and ranking
  → staged candidate
  → optional governed export through private signed storage
  → WorkCore lookup and match review
  → human/policy approval
  → WorkCore promotion gateway
  → WorkCore event + extension lineage + audit
```

## Multi-tenancy

Every extension-owned record carries `company_id`. Optional `branch_id` and `workspace_id` are context metadata. API controllers derive company authority from `AuthorisedCompanyContext`; request-supplied `company_id` is prohibited. Queue payloads include the already-authorised company ID and every query repeats the company predicate.

## Offline behaviour

External provider calls require connectivity. The host may queue search commands while offline. Stored permitted observations and candidate review remain available locally. The extension never labels cached data as live availability, and it does not silently merge offline conflicts.

# Domain Model

## Tables

| Table | Purpose |
|---|---|
| `maps_provider_connections` | Per-company provider metadata and Vault credential reference |
| `discovery_searches` | User/agent search intent and lifecycle |
| `discovery_runs` | Provider cursor, checkpoint, counts, retries, safe failures |
| `external_places` | Normalised latest observation per company/provider/place ID |
| `external_place_contacts` | Public contact observations and verification state |
| `field_observations` | Field-level source, time, confidence, restrictions, observation type |
| `discovery_candidates` | Governed classification and review state |
| `candidate_matches` | WorkCore candidate match score and evidence |
| `candidate_promotions` | Source-to-WorkCore command/event lineage |
| `territory_analyses` | Deterministic summary output and source coverage |
| `maps_usage_records` | Provider operation, units, results, and estimated cost |
| `maps_suppressions` | Company suppression and do-not-reintroduce entries |

## Data separation

Three data classes remain distinct:

1. **Provider observation:** what an external provider supplied at an observed time.
2. **Classification or inference:** deterministic or AI-assisted categorisation, with evidence and confidence.
3. **WorkCore authority:** accepted operational data created through WorkCore services.

An inferred classification never overwrites a provider observation. Promotion copies only explicitly accepted fields and preserves source lineage.

## Identity and duplicate handling

The strongest identity is `company_id + provider + provider_place_id`. A canonical key supports cross-provider and fallback comparison. Ambiguous matches are stored for review; no service silently merges WorkCore records.

## Lifecycle states

Searches: `draft`, `queued`, `running`, `paused`, `completed`, `partially_completed`, `failed`, `cancelled`.

Candidates: `new`, `classified`, `review_required`, `approved`, `rejected`, `promoted`, `expired`.

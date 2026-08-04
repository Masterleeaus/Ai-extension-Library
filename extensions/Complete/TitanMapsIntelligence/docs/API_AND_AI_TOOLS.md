# API and AI Tool Contract

## Protected API

Base path: `/api/titan/maps-intelligence`

| Method | Path | Permission |
|---|---|---|
| POST | `/searches` | `search.create` |
| GET | `/searches/{search}` | `search.read` |
| POST | `/searches/{search}/cancel` | `search.cancel` |
| POST | `/searches/{search}/export` | `search.export` |
| GET | `/candidates` | `candidate.read` |
| GET | `/candidates/{candidate}` | `candidate.read` |
| POST | `/candidates/{candidate}/classify` | `candidate.classify` |
| POST | `/candidates/{candidate}/match` | `candidate.read` |
| POST | `/candidates/{candidate}/approve` | `candidate.approve` |
| POST | `/candidates/{candidate}/reject` | `candidate.reject` |
| POST | `/candidates/{candidate}/promote` | `candidate.promote` |
| POST | `/territories/analyse` | `territory.analyse` |
| GET | `/territories/{analysis}` | `territory.analyse` |
| GET | `/usage` | `usage.read` |

All routes require `auth:sanctum`, `titan.company-context`, and `titan.permission`. Request `company_id` is prohibited.

## Titan Quattro capabilities

The manifest and `MapsCapabilityService` register 12 handlers:

- search businesses
- read search
- cancel search
- export staged candidates as CSV, JSON, or XLSX
- list candidates
- match candidate
- classify candidate
- approve candidate
- reject candidate
- promote candidate
- analyse territory
- read provider usage

Every definition includes a permission, JSON input schema, JSON output schema, and handler class. Outputs exclude raw provider payloads.

## Response envelope

Tools return:

```json
{
  "ok": true,
  "data": {},
  "error": null
}
```

Errors should use stable codes such as `MAPS_PROVIDER_NOT_CONFIGURED`, `MAPS_PERMISSION_DENIED`, `MAPS_AMBIGUOUS_MATCH`, and `MAPS_PROMOTION_VALIDATION_FAILED`.

## Export contract

Exports contain approved external-record fields only. They exclude raw provider payloads and are written through the host-provided `PrivateExportStore`. The store must return a short-lived signed URL, a non-secret reference, and an expiry timestamp. Every export is permission checked and audited.

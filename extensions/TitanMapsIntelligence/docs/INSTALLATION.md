# Installation Guide

## Requirements

- PHP 8.2 or later
- Laravel 11 or 12 host application
- Titan Zero Extension SDK v2 loader
- configured queue backend and worker
- SQLite or PostgreSQL
- authenticated company context and permissions
- Titan Vault-compatible secret resolver
- audit and capability registries
- WorkCore lookup and promotion adapters

## 1. Install the extension

Place the package at the host extension location expected by the SDK, normally:

```text
app/Extensions/TitanMapsIntelligence
```

Run the host's extension discovery/install command. Do not manually enable routes before the host bindings below exist.

## 2. Bind required host contracts

The host service provider must bind:

```php
AuthorisedCompanyContext::class
PermissionAuthorizer::class
SecretResolver::class
AuditRecorder::class
CapabilityRegistrar::class
WorkCoreCandidateGateway::class
WorkCoreCandidateLookup::class
PrivateExportStore::class
```

No null production implementations are supplied. `PrivateExportStore` must write to private storage and issue short-lived, authorised links; it must not create public files.

## 3. Register middleware aliases

The API group expects:

```text
auth:sanctum
titan.company-context
titan.permission
```

`titan.company-context` must validate authenticated membership and establish active company context. It must not trust a request body, route parameter, query value, or agent claim.

## 4. Run migrations

```bash
php artisan migrate
```

All extension tables are additive and use portable Laravel schema primitives.

## 5. Configure a provider connection

Create one `maps_provider_connections` row per company/provider. For Google Places:

- `provider`: `google-places`
- `credential_reference`: a Titan Vault reference, not the key itself
- `enabled`: `true`
- `configuration`: optional timeouts, field masks, or allowed company settings

The runtime resolves the referenced secret only when executing a provider call.

## 6. Start queue workers

```bash
php artisan queue:work --queue=default --tries=3 --timeout=120
```

Adapt queue names to the host policy. Use supervised workers in production.

## 7. Enable the extension

Set the host-managed extension setting corresponding to:

```php
extensions.titan_maps_intelligence.enabled = true
```

The next boot validates required contracts, registers the Google provider factory, registers all 12 Quattro capabilities, policies, views, and routes.

## 8. Smoke test

1. Authenticate as a company member with `titan-maps-intelligence.search.create`.
2. Create a search without sending `company_id`.
3. Confirm a queued search and run record are company scoped.
4. Confirm the key is absent from logs and tables.
5. Approve and promote a test candidate through a WorkCore sandbox adapter.
6. Export the staged candidates and confirm the returned link expires and cannot cross company scope.
7. Confirm WorkCore command/event IDs appear in `candidate_promotions`.

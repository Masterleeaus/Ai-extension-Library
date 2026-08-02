# Provider Adapter Guide

## Contract

A provider implements `PlacesProvider`:

```php
public function id(): string;
public function capabilities(): ProviderCapabilities;
public function attribution(): string;
public function search(PlaceSearchRequest $request): PlaceSearchPage;
public function nearby(NearbySearchRequest $request): PlaceSearchPage;
public function details(string $providerPlaceId): ExternalPlaceData;
```

## Registration

Register a per-company factory with `PlacesProviderRegistry::registerFactory()`. The factory receives the authorised company ID, loads that company's enabled provider connection, resolves the Vault reference, and creates the adapter.

## Provider rules

Every adapter must:

- use HTTPS and a strict host allowlist
- use bounded timeout and retry settings
- translate errors to stable safe codes
- declare capabilities and unsupported filters
- preserve required attribution
- return normalised DTOs, never provider response objects
- record usage units where available
- store data only where provider terms permit
- never expose credential values or raw error bodies

## Google adapter

The included Google adapter uses Google Places API (New):

- Text Search: `POST /v1/places:searchText`
- Nearby Search: `POST /v1/places:searchNearby`
- Place Details: `GET /v1/places/{place_id}`
- `X-Goog-Api-Key` and explicit `X-Goog-FieldMask` headers

Wildcard field masks are rejected. Page size and radius are bounded by DTO validation and provider limits.

## Unsupported filter behaviour

Do not silently ignore unsupported filters. Return a stable `MAPS_UNSUPPORTED_FILTER` error or expose the capability difference to Titan Zero before execution.

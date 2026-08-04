# Meetup UI Integration

Titan Maps Intelligence supplies four Blade components and a small framework-neutral JavaScript bridge. Meetup remains the owner of page layout, conversation state, accessibility review, visual design, polling/realtime subscriptions, and user notifications.

## Blade components

```blade
<x-titan-maps-intelligence::search-composer />
<x-titan-maps-intelligence::search-progress-card :search="$search" />
<x-titan-maps-intelligence::candidate-card :candidate="$candidate" />
<x-titan-maps-intelligence::territory-summary :analysis="$analysis" />
```

The service provider registers these under the `titan-maps-intelligence` view namespace.

## JavaScript bridge

Publish or bundle `resources/js/titan-maps-intelligence.js` through the host asset pipeline. It:

- submits the search composer to the protected API;
- sends same-origin credentials and CSRF protection;
- emits `titan-maps:search-created` and `titan-maps:search-cancelled` events;
- emits `titan-maps:candidate-action` for Meetup/Titan Zero to open the appropriate approval conversation;
- never accepts or sends a company ID or provider credential.

The candidate component deliberately does not autonomously promote a business. Meetup or Titan Zero must gather required promotion fields and call the governed candidate endpoint or Quattro tool.

## Realtime progress

Subscribe Meetup's existing realtime layer to extension domain events and refresh the progress card for:

- `maps.search_started`
- `maps.search_progressed`
- `maps.search_completed`
- `maps.search_failed`
- `maps.search_cancelled`

Do not claim a provider is currently available from a cached card. Show observation timestamps and provider attribution.

## Export interaction

Call:

```http
POST /api/titan/maps-intelligence/searches/{search}/export
Content-Type: application/json

{"format":"xlsx","candidate_status":"approved"}
```

The response contains a short-lived signed URL from the host `PrivateExportStore`. Meetup should display the expiry and never persist the signed URL as a permanent document link.

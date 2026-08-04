# Titan Zero Interaction Engine — Offline LocalBrain v2

A Laravel-compatible module providing universal wizards, local intelligence, governance, cognitive events, offline queues and an 80-engine modular foundation.

## Offline-only policy

- LocalBrain v2 is the only intelligence runtime.
- External AI clients and configuration have been removed.
- `src/AI/LocalBrainGuidanceService.php` provides deterministic local guidance where the legacy AI service contract is still required.
- `bin/localbrain.php` exposes LocalBrain through local JSON stdin/stdout for sibling processes.
- `tests/offline_only_run.php` protects this boundary.

## Included capabilities

- 38 workflow templates: 29 ready, 9 draft.
- 29 executable wizard definitions.
- Intent/entity classification, persona signals, memory reranking, reasoning, temporal intelligence and predictive completion.
- Capability policies, authority levels, approval signing, audit and cognitive events.
- Offline command outbox, vector clocks, conflict handling and TypeScript IndexedDB companion.
- 80 engine contracts and 80 implementations across executive, cognitive, memory, learning, planning, interaction, AI-infrastructure and business-intelligence domains.
- Partial WorkCore command adapters.

## Template discovery and catalogue

The Laravel integration exposes template discovery through:

```text
GET  /templates
GET  /templates/{templateId}
```

The catalogue contains **29 ready templates** and 9 drafts. It includes assurance workflows such as **Incident Response** and the **Commerce and multi-vertical template pack**. Ready templates reference executable wizard definitions; draft templates remain unavailable until their wizard and host capability are implemented.

## Configuration

```dotenv
INTERACTION_OFFLINE_ENABLED=true
INTERACTION_LOCAL_INTELLIGENCE=true
INTERACTION_LOCAL_MIN_CONFIDENCE=0.65
INTERACTION_WIZARD_SESSION_TTL=86400
INTERACTION_OUTBOX_SECRET=${APP_KEY}
```

There are no AI provider, key or model settings.

## LocalBrain CLI

```bash
printf '%s' '{"message":"Show my attendance","context":{"tenant_id":"school-a","user_id":"u1"}}' \
  | php bin/localbrain.php
```

## Verification

```bash
php tests/run.php
php tests/localbrain_v2_run.php
php tests/offline_only_run.php
npm test
```

## Production boundary

The package remains an integration module, not a complete Laravel host. WorkCore writes require host-provided services, authenticated tenant context, complete policy coverage and connected-host tests.

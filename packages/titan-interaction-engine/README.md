# Titan Interaction Engine

Authoritative Laravel/WorkCore interaction runtime distributed as the Composer package `titanzero/interaction-engine`.

## Responsibilities

- interaction and wizard compilation
- server-side validation, policy, authority and approval controls
- tenant-aware command dispatch into WorkCore
- canonical run, answer, event and cognitive-event persistence
- online provider integrations
- conflict resolution and authoritative sync validation
- Laravel routes, migrations, views and package discovery

The browser/offline implementation is intentionally excluded. Install `@titanzero/interaction-engine-offline` in the Chatbot PWA or through `InteractionEngineChatbotBridge`.

## Local monorepo installation

```json
{
  "repositories": [
    {"type": "path", "url": "packages/titan-interaction-contracts"},
    {"type": "path", "url": "packages/titan-interaction-engine"}
  ],
  "require": {
    "titanzero/interaction-engine": "^1.0"
  }
}
```

## Verification

```bash
php tests/run.php
php bin/verify.php
```

## Template discovery

Authenticated hosts expose the interaction template catalogue through:

```text
GET  /templates
GET  /templates/{template}
```

The package currently includes **29 ready templates** plus draft templates that remain non-executable until their entry wizard and host capabilities are verified.

### Assurance workflows

Ready assurance workflows include **Incident Response**, inspection corrective action, client intake consent, clinical service incident escalation, practical completion defects and job-variation approval.

### Commerce and multi-vertical template pack

The Commerce and multi-vertical template pack covers customer order capture, inventory adjustment, order fulfilment, payment reconciliation, returns, refunds, marketplace-related operational workflows and field-service vertical handovers. See `docs/COMMERCE_MULTI_VERTICAL_TEMPLATE_PACK.md` for compatibility status and activation rules.

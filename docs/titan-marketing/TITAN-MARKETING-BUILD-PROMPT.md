# Titan Marketing — Extension Upgrade Prompt

Upgrade the existing MagicAI `MarketingBot` extension into the user-facing **Titan Marketing** product.

## Compatibility rules

- Keep the install folder and internal identity unchanged: `MarketingBot`.
- Keep existing PHP namespaces, service provider names, route names, migration names, table names, config keys and extension slug unless a backwards-compatible alias is added.
- Change customer-facing UI text only to **Titan Marketing**: manifest display name, menu labels, headings, breadcrumbs, onboarding, settings, notices and plan-feature labels.
- Existing installations must upgrade without data loss or route breakage.

## Product responsibility

Titan Marketing is the authoritative campaign and customer-journey orchestrator. It owns campaigns, audiences, segmentation, offers, templates, omnichannel journeys, approvals, budgets, attribution and reporting.

It must not duplicate canonical CRM contacts, provider credentials, telephony call state, social publishing authority or the durable workflow engine. Use adapters to WorkCore/Titan CRM, the shared credential vault, Titan Voice/PhoneCallAgent, Titan Social and AIAgent/shared workflow runtime.

## Required channels

Implement a provider-neutral `MarketingChannel` contract and adapters for installed providers:

- email through Gmail and future Outlook/SMTP connectors
- SMS/MMS through a provider-neutral adapter, initially Twilio when available
- WhatsApp through `AIAgentWhatsappChannel`
- Telegram
- Slack through `AIAgentSlackChannel`
- Messenger and Instagram Direct when connectors are installed
- chatbot and in-app messaging
- push notifications when available
- outbound voice campaigns through `PhoneCallAgent` and compatible voice-call extensions
- optional Titan Social publishing through a cross-product adapter

Missing providers must be shown as unavailable integrations and must not prevent the extension from booting.

## Voice campaign requirements

Add approved outbound call lists, consent and do-not-call checks, timezone-aware call windows, AI disclosure policy, versioned scripts, voicemail handling, human transfer rules, recording/transcription policy, retry limits, provider budgets, outcome tracking and conversion receipts. Bulk outbound calls must require approval and must never be initiated solely because an AI model selected recipients.

## Campaign and journey capabilities

Support one-off broadcasts, scheduled campaigns, event-triggered journeys, delays, conditions, branches, multi-channel fallback, frequency caps, quiet hours, suppression lists, consent-purpose matching, idempotent execution, pause/resume/cancel, preview mode, dry-run audience counts and immutable execution receipts. Large sends must run through queues or the shared durable workflow runtime, never synchronously in a web request.

## Titan Marketing UI

Create these visible sections:

1. Overview
2. Campaigns
3. Journeys
4. Audiences
5. Content
6. Inbox
7. Channels
8. Analytics
9. Settings

Use **Titan Marketing** consistently in visible copy while preserving `MarketingBot` internally.

## AI assistants

Provide bounded assistants for Campaigns, Lead Reactivation, Referrals, Content, Reviews, Offers, SEO and Conversion Analysis. Assistants may research, draft, segment and recommend. Publishing, spending, bulk outbound messages and voice calls remain governed actions requiring approval and receipts.

## Security and compliance

- Encrypt provider credentials and tokens.
- Verify inbound webhooks fail-closed.
- Enforce tenant scoping and authorization on every resource.
- Record consent purpose, source, timestamp and revocation.
- Apply suppression lists and quiet hours before each delivery.
- Use idempotency keys, replay protection and bounded retries.
- Validate remote files and media through the shared safe-fetch service.
- Redact secrets and personal data from logs.
- Add retention controls for messages, recordings, transcripts and analytics.

## Migration strategy

1. Rebrand visible copy without changing internal identifiers.
2. Introduce shared contracts and adapters around existing services.
3. Add unified campaigns, audiences and journeys.
4. Connect email, WhatsApp, messaging and voice providers.
5. Add attribution and analytics.
6. Deprecate duplicated internal implementations only after compatibility tests pass.

## Testing acceptance criteria

- Clean install and upgrade from the current `MarketingBot` version.
- Existing routes, migrations and data continue to work.
- UI displays Titan Marketing while filesystem and namespaces remain `MarketingBot`.
- Every channel has contract, authorization, consent, idempotency and failure-path tests.
- Voice campaigns test approvals, disclosure, call windows, suppression and retry limits.
- Missing optional extensions do not break boot.
- Cross-tenant access is rejected.
- No external send or call occurs without an execution receipt.

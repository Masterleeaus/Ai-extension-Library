# AI Extensions Implementation Readiness Audit

**Audit date:** 2026-08-04  
**Working branch:** `audit/ai-extensions-upgrade-2026-08-04`  
**Baseline:** `main` at `644a4c513748fc685430781b59b7156885e14cd6`  
**Current materialised scope:** 78 extensions, 4,213 extension files, plus `foundation/TitanAI-Hybrid/`  
**Status:** Pass 3 is a useful shared-foundation increment; broader production migration remains blocked by Phase 0 security and host-conformance gaps.

## Purpose

This audit reconciles:

- the complete 78-extension catalogue;
- the Pass 3 replacement of `AIAgent`, `AIChatPro`, and `Chatbot`;
- the shared `foundation/TitanAI-Hybrid/` overlay;
- the older TitanAI deployment documents in `docs/`;
- the bounded-domain programme in `upgrade plan/`;
- direct source tracing through high-risk webhook, ingestion, action, bridge, and media paths.

It is not another rewrite proposal. It establishes the current authority, records what Pass 3 actually proves, identifies what remains unproved, and defines the implementation gates for the next branch sequence.

## Scan method and limits

This pass combined repository-wide inventory analysis with direct inspection of representative source paths across core and add-on extensions. GitHub's connected repository APIs were used because the audit runtime could not resolve `github.com` for a local clone.

The repository's materialisation workflow now restores the base MiniUp dataset, applies the verified Pass 3 overlay, validates manifests, lints PHP, reconstructs a temporary host layout, and runs the packaged Pass 3 verifier. That is meaningful package-level evidence.

It does **not** prove the complete WorkCore host's Composer resolution, Laravel service-provider boot order, route precedence, database migrations, queues, scheduler, authorization, tenant isolation, external callbacks, or production secrets. Those remain host-integration requirements.

## Current authority decision

The current architectural authority is:

1. `docs/CORE-SUITES-PASS3-REPLACEMENT.md` for the materialised core-suite baseline;
2. `upgrade plan/README.md` for the programme rules;
3. `upgrade plan/00-DEEP-SCAN-FINDINGS.md`;
4. `upgrade plan/01-TARGET-ARCHITECTURE-AND-ROADMAP.md`;
5. `upgrade plan/02-KNOWLEDGE-ENGINE-FOUNDATION.md`;
6. this implementation-readiness audit.

The older package instructions under `docs/` are historical inputs and must not be treated as production deployment authority.

## What Pass 3 improves

The current overlay adds or hardens:

- shared component discovery across skills, actions, connectors, and tools;
- failure-isolated cross-extension events;
- action correlation and idempotency metadata;
- transactional AIAgent memory mirroring;
- bounded Chatbot shared-memory consumption;
- dynamic late registration;
- duplicate-listener prevention;
- safer diagnostics that omit raw memory values and sensitive identifiers;
- expired-memory cleanup;
- the Chatbot five-application registry and legacy-slug migration;
- reproducible overlay materialisation, checksums, PHP linting, and packaged regression verification.

These changes should be retained. They are foundation improvements, not evidence that the full Phase 0 programme is complete.

## Documentation drift requiring correction

`upgrade plan/README.md` still reports the pre-Pass-3 totals and versions:

- 4,204 files rather than 4,213;
- Chatbot 1,548 files rather than 1,554;
- AIAgent 169 files rather than 171;
- AIChatPro 47 files rather than 48.

The counts do not invalidate the architecture, but they make the programme look stale immediately after a core replacement. The upgrade folder needs a baseline-refresh note and a clear distinction between:

- **implemented by Pass 3**;
- **partially implemented**;
- **planned but absent**;
- **host verification required**.

The older documents below remain materially unsafe as deployment instructions:

- `docs/README.md`;
- `docs/00_READ_ME_FIRST.md`;
- `docs/TITANAI-UPGRADE-PLAN.md`.

They describe the older package as low-risk or production-ready, claim full coverage or no vulnerabilities, and imply that a single base provider plus shared memory is enough to unify the ecosystem. Those claims are not supported by the current extension tree.

## Confirmed implementation findings

### P0-01 — webhook verification remains fragmented and fail-open

**Evidence paths**

- `extensions/SocialMediaAutomation/System/SocialMediaAutomationServiceProvider.php`
- `extensions/SocialMediaAutomation/System/Http/Controllers/WebhookController.php`
- `extensions/AIAgentWhatsappChannel/System/Http/Controllers/Webhook/WhatsappWebhookController.php`

**Confirmed behaviour**

- X/Twitter delivery is processed without a request-signature check.
- TikTok delivery is processed without a request-signature check.
- LinkedIn delivery is processed without a request-signature check.
- Facebook and Instagram checks are fail-open when the app secret is absent.
- webhook exceptions are logged and still acknowledged with HTTP 200 without a durable delivery receipt or dead-letter state;
- Facebook debugging records the decoded payload and failed signature header;
- the AIAgent WhatsApp POST handler verifies neither `X-Hub-Signature-256` nor replay/timestamp state;
- the WhatsApp handler resolves a channel from a raw numeric route parameter without an explicit shared tenant-context repository boundary.

No shared `WebhookVerifier` implementation was located in the current repository index.

**Required response**

Create one fail-closed verifier contract with provider adapters, tenant resolution, signature and timestamp checks, replay storage, idempotency, bounded logging, normalized inbound envelopes, durable delivery receipts, rate limits, and conformance tests. Autonomous actions must not execute before verification succeeds.

### P0-02 — duplicated URL crawlers expose SSRF and unbounded-work risks

**Evidence paths**

- `extensions/Chatbot/System/Parsers/LinkParser.php`
- `extensions/PhoneCallAgent/System/Parsers/LinkParser.php`
- `extensions/ChatbotVoice/System/Parsers/LinkParser.php`
- equivalent copies in `MarketingBot` and `ElevenLabsVoiceChat`

**Confirmed behaviour**

- user-selected URLs are fetched with `file_get_contents()`;
- execution time is disabled rather than bounded;
- crawling is recursive and synchronous;
- there is no shared scheme policy, resolved-IP validation, private/reserved/metadata range rejection, DNS-rebinding protection, redirect revalidation, byte limit, MIME/magic validation, connection budget, total crawl budget, or durable cancellation;
- the implementations are duplicated, so fixes can drift;
- legacy records are written directly rather than through the proposed canonical Knowledge Engine.

The Pass 3 Chatbot replacement retains this exact parser unchanged.

**Required response**

Build the shared SSRF-safe fetcher and quarantine boundary before expanding knowledge ingestion. Migrate one legacy parser through a compatibility adapter, then remove the copies progressively.

### P0-03 — provider callback routes collide

**Evidence paths**

- `extensions/FluxPro/System/FluxProServiceProvider.php`
- `extensions/SeeDreamV4/System/SeeDreamV4ServiceProvider.php`
- `extensions/NanoBanana/System/NanoBananaServiceProvider.php`
- `extensions/FluxPro/System/Http/Controllers/FalAIWebhookController.php`

Three extensions register the same public route path and route name:

```text
ANY generator/webhook/fal-ai
generator.webhook.fal-ai
```

The effective handler can depend on provider-registration order. The inspected Flux callback has no provider-signature verification and resolves an in-queue record by request ID/status without an explicit tenant-safe callback repository. Its image condition also enters the image branch when the image list is empty.

**Required response**

Introduce a provider callback router with unique provider/capability keys, verified signatures, tenant-safe attempt resolution, immutable provider-attempt records, idempotent transitions, and architecture tests for route collisions.

### P0-04 — inbound media bypasses quarantine and durable attachment storage

The AIAgent WhatsApp handler downloads remote media, buffers it, and base64-encodes the full body into the message object. It lacks an explicit byte cap, stream-to-quarantine policy, magic-byte validation, malware scanning, durable attachment identity, or queue-payload limit.

**Required response**

Use a dedicated media-ingestion boundary. Events and jobs should carry attachment identifiers and bounded metadata, not full base64 bodies.

### P1-01 — Pass 3 lifecycle events do not yet govern AIAgent action execution

**Evidence path**

- `extensions/AIAgent/System/Actions/AiCallAction.php`

The shared foundation now adds useful correlation and idempotency to action lifecycle events, but the action contract still accepts generic arrays and performs execution locally.

**Confirmed concerns**

- configurable `store_output_as` may overwrite reserved context keys;
- `history_turns` has no contract-level maximum;
- history selection can mix every outbound message in a channel with one sender-filtered inbound stream rather than a strict conversation identity;
- whole knowledge-source content is appended directly to the system prompt without retrieval evidence or citations;
- model-native web search can be enabled without a shared permission/risk/approval object;
- credit checking is not the same as an action permission, budget, timeout, retry, receipt, or rollback policy.

Chatbot contains its own governed executor and action-receipt implementation, but that governance is not yet the universal execution boundary across AIAgent, AIChatPro connectors, PhoneCallAgent, MarketingBot, SocialMediaAgent, and bridges.

**Required response**

Adopt shared typed `ExecutionContext`, `ToolDefinition`, `ToolResult`, and `ActionReceipt` contracts at every execution entry point. Preserve Chatbot's proven governance components as source material, but move cross-suite policy to a host-level shared boundary rather than copying it.

### P1-02 — duplicate class authority remains embedded in two packages

**Evidence paths**

- `extensions/AIAgentToolChatbot/System/Actions/...`
- `extensions/Chatbot/System/TitanAI/modules/agent-chatbot/System/Actions/...`

Inspected bridge classes exist byte-for-byte in both locations and declare the same `App\Extensions\AIAgentToolChatbot` namespace. This creates two physical authorities for the same classes and can become autoload/package drift.

**Required response**

Choose one canonical package. Keep the second location only as a generated artefact or thin compatibility loader with checksum/conformance enforcement.

### P1-03 — packaged verification is not equivalent to extension test coverage

The Pass 3 workflow materially improves verification, but native test distribution remains concentrated:

- Chatbot: 51 bundled tests;
- AIAgent: no bundled test tree;
- AIChatPro: no bundled test tree;
- most other selected extensions: no detected test tree.

The shared verification suite exercises the TitanAI integration overlay, but it cannot substitute for per-extension authorization, migration, webhook, queue, provider, and failure-path tests.

**Required response**

Add a root host-conformance harness that:

- boots every manifest and provider in dependency order;
- detects duplicate routes, route names, migrations, namespaces, classes, aliases, and container bindings;
- verifies public-route authentication and authorization;
- runs webhook signature/replay conformance;
- runs tenant-isolation and secret-redaction tests;
- validates queues, retries, cancellation, stale recovery, schedules, install/disable/uninstall, migration rollback, and feature-flag rollback.

### P2-01 — VideoEditor command construction is safer than its remote-fetch boundary

`FfmpegExportService` constructs argument lists and escapes each argument before `proc_open`, which is materially safer than raw shell concatenation. Remaining risks are:

- arbitrary remote URLs and followed redirects;
- no private-address rejection or download-size/content limit;
- broad writable-directory fallbacks;
- worker CPU/memory/disk isolation;
- error output that may disclose paths or remote URLs;
- hostile-media fixtures and cancellation tests.

**Required response**

Use the shared safe-fetch boundary, non-world-writable job directories, isolated workers, allow-listed binaries/paths, bounded resources, sanitized errors, and malicious-media test fixtures.

## Phase 0 completion assessment

| Capability | Current state | Gate |
|---|---|---|
| Shared registry | Implemented foundation | Host boot/order tests |
| Cross-extension events | Implemented foundation | Failure/retry and schema-version tests |
| Correlation/idempotency metadata | Partially implemented | Shared durable idempotency store and consumers |
| Unified contextual memory | Implemented foundation | Tenant/retention/host persistence tests |
| Tenant context | Fragmented across runtimes | One shared host contract and negative tests |
| Authorization | Fragmented | Shared policies at every action/retrieval boundary |
| Webhook verification | Provider-specific/incomplete | Fail-closed shared verifier and replay store |
| Credential vault | Not established as ecosystem authority | Vault references, rotation, redaction tests |
| SSRF-safe fetcher | Not found for general ingestion | Shared fetch/quarantine service |
| Governed tool execution | Strong inside Chatbot, fragmented elsewhere | Universal shared adapter boundary |
| Audit receipts | Strong inside Chatbot, fragmented elsewhere | Cross-suite receipt schema and storage |
| Architecture collision tests | Package verifier only | Real host boot/route/migration/binding harness |

## Required implementation gates

No broad migration should begin until:

- [ ] Pass 3 baseline metrics are propagated through `upgrade plan/`.
- [ ] Historical deployment documents carry a prominent deprecated banner.
- [ ] The complete WorkCore host passes provider/manifest boot tests.
- [ ] Route, class, namespace, migration, and binding collisions are tested.
- [ ] One tenant-context and authorization contract is used by queues, webhooks, tools, and retrieval.
- [ ] Shared webhook verification and replay prevention are operational.
- [ ] Shared idempotency and action-receipt infrastructure is durable.
- [ ] Shared SSRF-safe fetch and file/media quarantine services are operational.
- [ ] Credentials are vault references and absent from logs, events, jobs, and result payloads.
- [ ] One legacy knowledge route runs through a reversible canonical adapter.
- [ ] cross-tenant negative tests pass.

## Recommended branch sequence

1. **Baseline and documentation reconciliation** — update metrics, mark historical plans deprecated, and publish one authority/status index.
2. **Real host architecture harness** — boot providers and detect route/class/migration/binding collisions.
3. **Webhook security foundation** — verifier, replay store, normalized envelope, idempotent receipt, provider conformance.
4. **SSRF-safe ingestion foundation** — safe fetcher, quarantine, durable jobs, and one crawler adapter.
5. **Governed execution adapters** — typed context, reserved keys, policy, budgets, timeout, receipts, and cross-suite adapters.
6. **Knowledge Engine vertical slice** — execute `02-KNOWLEDGE-ENGINE-FOUNDATION.md` against one legacy controller.
7. **Progressive migration** — one extension at a time behind feature flags, shadow reads, comparison, and reversible cutover.

## Immediate conclusion

The Pass 3 overlay should remain. It improves shared discovery, events, memory interoperability, diagnostics, reproducibility, and regression checking while retaining the correct product authority boundaries.

It is not the final production architecture. The next work is not another broad merge or global base-provider refactor; it is Phase 0 host conformance, verified inbound boundaries, safe ingestion, and universal governed execution.

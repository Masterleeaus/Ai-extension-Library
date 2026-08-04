---
title: "MagicAI 11 AI Providers and Model Routing"
scan_number: 13
date: "2026-08-03"
magicai_version: "11.00"
status: "Completed"
scope:
  - "Magicai-Server-Files(3).zip"
  - "WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/13-magicai-ai-providers-and-model-routing.md"
---

# Scan 13 — MagicAI 11 AI Providers and Model Routing

## Executive conclusion

MagicAI 11 contains a very broad AI runtime:

| Measure | Confirmed |
|---|---:|
| Engine/provider enum cases | 33 |
| Engine driver classes | 33 |
| Entity/model enum cases | 248 |
| Entity driver classes | 248 |
| WorkCore native-AI files | 89 |
| WorkCore domain AI tool registries | 30 |
| WorkCore declared tools | 55 |
| Non-empty WorkCore tool registries | 14 |

MagicAI is strongest at provider execution, model breadth, streaming, media generation, model entitlements and platform-credit charging.

WorkCore is strongest at company-scoped policy, purpose routing, local/private models, failover, circuit breaking, budgets, permissions, approvals, audit and tool governance.

The correct integration is:

> **MagicAI owns provider execution adapters and the broad model catalogue.**  
> **WorkCore owns company policy, purpose routing, privacy, residency, budgets, permissions, approvals and operational attribution.**

WorkCore should call MagicAI through a dedicated, stateless execution adapter instead of duplicating every cloud-provider client.

---

# 1. MagicAI engine and model inventory

MagicAI separates AI into:

```text
Engine
    Provider family and provider-level state.

Entity
    Concrete model or model capability.
```

Container bindings:

```text
ai.engine
ai.entity
```

Confirmed engines include:

```text
OpenAI
Anthropic
Gemini
DeepSeek
XAI
OpenRouter
Together
Stable Diffusion
Fal AI
PiAPI
ElevenLabs
Google
Azure
Speechify
Serper
Perplexity
Clipdrop
Novita
Freepik
Synthesia
HeyGen
Pebblely
Gamma AI
Minimax
Creatify
Topview
Vizard
Klap
Captions
Unsplash
Pexels
Pixabay
PlagiarismCheck
```

The 248 entity definitions cover:

- Text and reasoning
- Search and research
- Realtime chat
- Embeddings
- Speech-to-text and text-to-speech
- Vision and image editing
- Image generation
- Video generation, editing, dubbing and upscaling
- Music
- Avatars
- Product photography
- Presentations
- Stock media
- Plagiarism checking

---

# 2. Critical catalogue defects

## Delete-on-read behaviour

Engine and entity cache population deletes database rows whose keys are absent from the current PHP enums.

Consequences:

- Database-only custom models are deleted.
- Extension-added models cannot survive unless core enums are patched.
- Local/private providers can disappear during ordinary reads.
- Historical plan references can be damaged.

### Required fix

Replace deletion-on-read with an explicit catalogue synchronisation command:

```text
discover definitions
→ upsert vendor metadata
→ mark missing definitions deprecated
→ preserve historical rows
→ require explicit purge
```

Add extension contributor contracts for engines and entities.

## Missing uniqueness

`engines.key` and `entities.key` are indexed but not uniquely constrained.

Duplicate rows can make runtime selection nondeterministic.

Add:

```text
unique engines.key
unique entities.key
```

after duplicate reconciliation.

## Seeder drift

Engine and entity seeders use `firstOrCreate`, so source changes do not update existing title, engine mapping, status or metadata.

Use version-aware upserts while preserving administrator enable/disable choices.

---

# 3. Enabled-state bypass

MagicAI can calculate whether an engine or entity is enabled, but direct execution through entity drivers does not centrally reject disabled models.

A model can therefore be hidden from UI selection yet remain directly callable.

Every execution must enforce:

```text
engine enabled
entity enabled
plan entitlement
company policy
actor permission
credentials available
budget available
```

This check belongs inside the execution gateway, not only menus and selectors.

---

# 4. Mutable singleton driver risk

`EntityManager` is a singleton Laravel Manager and caches driver instances.

Drivers retain mutable state such as:

```text
user
plan
team
guest flag
calculated credit
last user ID
```

Context setters do not reliably clear previous state:

- `forUser()` does not clear team, plan or guest.
- `forGuest()` does not clear user state.
- Credit calculations can remain on the cached driver.

This creates cross-call and cross-user leakage risk, especially under:

- Laravel Octane
- Queue workers
- Realtime services
- Long-running agents
- Multiple calls to the same model in one request

### Required fix

Drivers must be stateless, transient or cloned per execution. Actor, team, plan, credentials and credit data should live in an immutable execution context.

---

# 5. BYO key defects

MagicAI supports user BYO keys for selected providers, but resolution is inconsistent.

Confirmed issues:

- Credential helpers use global `auth()->user()` rather than the explicit user attached to the entity driver.
- Background jobs and system agents may resolve no key or the wrong request user.
- API-key helpers mutate global Laravel configuration such as `openai.api_key`, `gemini.api_key` and `xai.api_key`.
- Long-lived processes can retain another user's key.
- Global or plan-level personal-key mode forces personal-key selection even when the field is blank.
- There is no deliberate platform-key fallback policy for a blank user key.
- Key pools are comma-separated strings selected randomly without trimming, validation, health scoring or empty-entry removal.
- The user settings UI saves OpenAI, Anthropic and Gemini keys, but not the available XAI field.
- DeepSeek BYO logic reads `users.deepseek_api_secret`, but no corresponding user migration was found.

### Required design

Credential resolution must receive explicit context:

```text
company
platform user
actor
provider
purpose
allowed credential sources
```

Resolution order should be policy controlled:

```text
company Titan Vault credential
→ approved local endpoint
→ actor BYO key when company permits
→ platform key when plan/company permits
→ deny
```

Never read credentials implicitly from global authentication state.

---

# 6. Global configuration leakage

MagicAI writes request-specific provider keys into application-global configuration.

That is unsafe under Octane or other persistent workers.

Provider clients should be constructed with per-call credentials. Until all code paths are converted, long-lived workers require explicit request-termination cleanup and isolation tests.

---

# 7. Routing inconsistency

Default-model selection is implemented in more than one place.

One helper supports:

```text
OpenAI
Anthropic
Gemini
DeepSeek
XAI
```

`EntityManager::defaultWordModel()` supports only:

```text
OpenAI
Anthropic
Gemini
```

and falls back to GPT-5 Mini for other engines.

Different features can therefore choose different models from the same settings.

### Required fix

Create one authoritative model-policy resolver and remove controller-specific switches.

---

# 8. No central failover or health policy

MagicAI has broad provider support but no common cross-provider system for:

- Provider health
- Latency
- Quota state
- Circuit breaking
- Regional policy
- Credential cooldown
- Purpose-based fallback

WorkCore's `NativeModelGateway` already provides a better ordered-profile and circuit-breaker design.

That design should remain authoritative, with MagicAI adapters supplying normalized provider execution and error classification.

---

# 9. Pricing and credit defects

MagicAI hard-codes approximate model pricing in source enums while also maintaining credit indexes and database overrides.

Risks:

- Provider price changes require source updates.
- Cached-input pricing is inconsistent.
- Image, video and audio units require model-specific handling.
- Provider invoice totals can diverge from application estimates.

Use versioned pricing:

```text
provider
model
region
input price
cached-input price
output price
media unit
currency
effective dates
source
```

## Non-atomic usage charging

Current credit flow can record total spend and user usage before feature-limit validation and successful shared-credit deduction.

A denied request can therefore increase reported usage.

Usage, reservation, provider execution and settlement must become one coordinated operation:

```text
estimate
→ validate limits
→ reserve budget/credits
→ execute
→ calculate actual usage
→ settle
→ write immutable usage transaction
→ release unused reservation
```

## Legacy credit race

Separated credit balances are mutable JSON and are updated without row locking. Concurrent calls can overwrite deductions.

The shared-credit ledger is the better base after the defects documented in Scan 08 are corrected.

## Schema drift

Confirmed drift includes:

- `user_usage_credits.credit` is integer while runtime supplies fractional values.
- Its down migration drops the wrong table.
- `Token` still declares `cost_per_token` after the column was removed.
- Usage is user-scoped rather than WorkCore-company-scoped.
- User deletion cascades usage history.

---

# 10. WorkCore native AI architecture

WorkCore already contains a strong governance kernel:

- Company provider profiles
- Purpose-specific model profiles
- Provider priority
- Failover
- Circuit breakers
- Rate limits
- Token budgets
- Credential resolver contract
- Agent profiles and versions
- Policy versions
- Conversations and messages
- Memory
- Approval requests
- Model and tool runs
- Usage ledger
- Tool catalogue
- Action/read adapters
- Sensitive-data redaction
- Company access gate

This should become Titan Zero's AI policy layer.

---

# 11. WorkCore AI module is unreachable

The AI module is defined in `WorkModuleRegistry`, but no aggregate WorkCore provider loads it.

Setting:

```text
WORKCORE_NATIVE_AI_ENABLED=true
```

does not register `WorkCoreAIServiceProvider`.

### Required fix

Load the AI module explicitly when enabled and add a boot diagnostic proving:

```text
provider loaded
config loaded
bindings available
routes active
credential resolver valid
```

---

# 12. WorkCore AI configuration is broken

The real config file is:

```text
app/Domains/WorkCore/Config/workcore_ai.php
```

The AI provider points to a different path and case:

```text
../../../config/workcore_ai.php
```

The root provider merges it under:

```text
workcore.workcore_ai
```

while AI code reads:

```text
workcore_ai.*
```

This means the provider cannot reliably access its intended settings.

Choose one namespace—preferably `workcore.ai`—and use it consistently.

---

# 13. Authentication and credentials

WorkCore AI routes use generic `auth`, while MagicAI's API authority is Passport through `auth:api`.

The extension should use configurable middleware and default to:

```text
api
auth:api
workcore.tenant
workcore.api
```

The default `NullCredentialResolver` correctly fails closed, but no production resolver is bound.

Add:

```text
MagicAIWorkCoreCredentialResolver
```

or Titan Vault integration before enabling native AI.

---

# 14. WorkCore provider limitations

The current WorkCore provider factory supports:

```text
OpenAI-compatible
local
Ollama
null
```

It does not support MagicAI's broader Anthropic, Gemini, XAI, DeepSeek, media, speech or video adapters.

The current provider contract is also:

- Text only
- Non-streaming
- `/v1/chat/completions` only
- Not multimodal
- No embeddings
- No image, speech, video or realtime contract

### Recommended adapter

Add a `MagicAIEntityProvider` that maps a WorkCore model profile to a MagicAI entity and executes through a stateless MagicAI gateway.

WorkCore remains responsible for policy, attribution and failover.

---

# 15. WorkCore model-profile defects

## One profile per provider family

The schema allows only one provider profile per company/provider key.

A company cannot configure:

- Two OpenAI-compatible endpoints
- Separate production and local endpoints
- Two regions
- Separate credentials for sensitive and ordinary workloads

Use a provider-profile key distinct from provider family.

## Wildcard priority

A wildcard-purpose default profile can outrank an exact-purpose non-default profile.

Required ordering:

```text
exact purpose
→ wildcard purpose
→ explicit priority
→ health/cost policy
```

## Missing cost propagation

Model profiles store input and output prices, but the resolved profile does not carry them to execution.

Provider usage therefore records null cost.

Pass a versioned pricing object into the resolved profile.

## Budget overrun

The budget guard checks already-consumed daily tokens but does not reserve the requested maximum.

A request can begin below the limit and exceed it substantially.

Use pre-execution reservation and post-execution settlement.

---

# 16. Privacy gap

WorkCore's sensitive-data redactor currently protects audit snapshots, not outbound prompts.

The provider still receives original content.

Before provider execution, apply company AI policy:

```text
classify data
→ block prohibited fields
→ redact/tokenize where required
→ choose allowed provider and region
→ record policy version
```

Examples requiring policy:

- Customer names and addresses
- NDIS or health information
- Payroll
- Incident reports
- Property access codes
- Bank details
- Worker credentials
- Security-system information

Provider error messages must also be redacted before persistence.

---

# 17. Tool coverage

WorkCore defines 30 domain AI tool registries and 55 tools, but only 14 registries contain tools.

Empty registries include major domains such as:

```text
Operations
Scheduling
Finance
Premises
Recurring Services
Reviews
Repairs
Fleet
Payroll
People
Territories
Supply
```

The architecture is ahead of implementation.

Only actually registered, permission-protected tools should be advertised to agents.

---

# 18. Authority matrix

| Concern | Authority |
|---|---|
| Provider client implementation | MagicAI |
| Broad model catalogue | MagicAI |
| Platform provider credentials | MagicAI vault |
| User BYO credentials | MagicAI/Titan vault |
| Company credential policy | WorkCore |
| Company provider profiles | WorkCore |
| Purpose-specific routing | WorkCore |
| Local/private provider preference | WorkCore |
| Plan model entitlement | MagicAI projected to WorkCore |
| Company AI capability | WorkCore |
| Actor AI permission | WorkCore |
| Tool permission and approval | WorkCore |
| Failover and circuit breaking | WorkCore |
| Privacy and residency | WorkCore |
| Company cost budget | WorkCore |
| SaaS credit settlement | MagicAI |
| Operational usage attribution | WorkCore |
| Provider execution receipt | MagicAI adapter normalized by WorkCore |

---

# 19. Company AI policy

A WorkCore company should configure:

```text
allowed providers and models
blocked providers
approved purposes
local-only purposes
residency regions
maximum data sensitivity by provider
BYO-key requirement
platform-key allowance
retention policy
training opt-out requirement
fallback chain
token and cost budgets
human-approval requirements
```

A user's preferred model must never override company policy.

---

# 20. Device and local models

Titan Zero's device-first design requires first-class support for:

```text
device model
office server model
private VPS model
Ollama/OpenAI-compatible endpoint
approved cloud provider
MagicAI platform provider
```

Example routing:

```text
restricted property/security data
    → local/private provider only

ordinary marketing content
    → lowest-cost approved provider

visual inspection
    → approved vision provider

high-risk operational decision
    → approved reasoning model plus human confirmation
```

MagicAI's catalogue must be made extension-safe so custom/local profiles are not deleted.

---

# 21. AI usage receipt

Every WorkCore AI run should record:

```text
company
actor
agent
purpose
provider profile
MagicAI entity
credential source
input/output/cached tokens
media units
estimated and actual cost
MagicAI credits charged
latency
fallback attempts
policy version
correlation ID
```

MagicAI should return a normalized receipt rather than forcing WorkCore to parse provider-specific responses.

---

# 22. Recommended execution flow

```text
WorkCore tool or agent requests model
→ NativeAiAccessGate
→ company AI policy
→ MagicAI plan/model entitlement
→ token/cost reservation
→ purpose-specific profile resolution
→ privacy and residency policy
→ explicit credential resolution
→ MagicAI stateless entity adapter
→ provider execution
→ normalized usage receipt
→ WorkCore run and usage ledger
→ MagicAI credit settlement
→ budget settlement
→ audit and diagnostics
```

---

# 23. Required MagicAI fixes

## Critical

1. Remove delete-on-read catalogue behaviour.
2. Add unique engine and entity keys.
3. Make entity drivers stateless or transient.
4. Reset actor, plan, team, guest and credit context per call.
5. Remove implicit global-auth credential lookup.
6. Stop mutating global config with user keys.
7. Encrypt platform and user credentials.
8. Fix blank-personal-key policy.
9. Fix or remove broken DeepSeek user-key lookup.
10. Add XAI key persistence or remove unsupported UI.
11. Enforce enabled status at execution.
12. Make usage and credit settlement atomic.
13. Fix usage schema/model drift.

## High

14. Add provider health and normalized retryability.
15. Centralize default-model selection.
16. Centralize provider execution.
17. Replace comma-separated random key pools.
18. Add company attribution to usage bridge.
19. Version provider pricing.
20. Preserve/anonymize usage history.
21. Add extension contributors for engines/entities.
22. Add Octane isolation tests.

---

# 24. Required WorkCore fixes

## Critical

1. Load native AI when enabled.
2. Correct config path and namespace.
3. Use Passport-compatible route middleware.
4. Bind a production credential resolver.
5. Add MagicAI Entity execution adapter.
6. Apply privacy policy before transmission.
7. Calculate and record cost.
8. Reserve budget before requests.
9. Redact persisted provider errors.
10. Keep native AI disabled until runtime tests pass.

## High

11. Permit multiple profiles per provider family.
12. Rank exact purpose above wildcard.
13. Add multimodal request DTOs.
14. Add streaming.
15. Add embedding, image, speech and vision contracts.
16. Add provider capability declarations.
17. Fill priority operational tool registries.
18. Add company AI-policy UI.
19. Add plan-entitlement adapter.
20. Add MagicAI credit-settlement bridge.
21. Add provider health diagnostics.
22. Record all failover attempts.
23. Add local/device provider discovery.

---

# 25. Required tests

## Catalogue and execution

- Unknown extension model is not deleted during read.
- Duplicate keys are rejected.
- Disabled engine/entity cannot execute.
- Seeder updates vendor metadata safely.
- Deprecated records remain for history.

## Isolation

- User B never receives User A's driver state or key.
- Team/plan/guest state resets between calls.
- Queue jobs use explicit identity.
- Octane requests do not share config or credentials.

## Credentials

- Blank user key follows explicit policy.
- Platform fallback occurs only when allowed.
- Revoked keys enter cooldown.
- Empty key-pool entries are rejected.
- No credential appears in logs, APIs or audit.

## Routing and policy

- Exact-purpose profile outranks wildcard.
- Company policy blocks unapproved provider.
- Local-only workloads never fall back to cloud.
- Plan entitlement and company permission are both enforced.
- Disabled models cannot be selected directly.

## Credits and budget

- Denied requests create no usage.
- Failed calls release reservation.
- Successful calls settle once.
- Fractional usage is preserved.
- Cost uses correct price version.
- Company attribution is present.

## WorkCore reachability

- Enabling AI loads its provider.
- Correct config is merged.
- Credential resolver is valid.
- Passport request reaches AI routes.
- Disabled module exposes no routes.

## Privacy and failover

- Sensitive data is blocked or redacted before provider transmission.
- Provider errors are redacted.
- Residency policy is enforced.
- Retryable errors select the next approved profile.
- Circuit state is company scoped.
- All attempts appear in the final run record.

---

# 26. Decisions established by Scan 13

1. MagicAI remains the provider-execution host.
2. MagicAI remains the broad model catalogue.
3. WorkCore owns company policy and purpose routing.
4. WorkCore owns privacy, residency, permissions and approvals.
5. WorkCore retains failover and circuit breaking.
6. WorkCore will not duplicate all MagicAI provider clients.
7. A stateless MagicAI Entity adapter will bridge execution.
8. Credentials become explicit execution context.
9. Unknown custom models are never deleted during reads.
10. Model enabled state is enforced centrally.
11. Credit settlement and usage become atomic.
12. WorkCore company attribution is mandatory.
13. Local/private models are first-class.
14. WorkCore native AI remains disabled until configuration, loading, authentication and credentials are repaired.

---

# 27. Next scan

```text
14-magicai-ai-chat-runtime-and-context-hooks.md
```

It will map:

- AI Chat routes
- Categories and assistants
- Conversation and message storage
- Streaming and realtime chat
- Context assembly
- Conversation ownership
- Team sharing
- Files and images
- CRM Assistant hooks
- Chat export
- Tool calls
- Prompt-injection boundaries
- WorkCore Operations Assistant
- Company-scoped context
- Offline/device chat
- Retention and privacy

---

# Evidence files

## MagicAI

```text
app/Domains/Engine/
app/Domains/Entity/
app/Helpers/Classes/Traits/HasApiKeys.php
app/Helpers/Classes/ApiHelper.php
app/Helpers/Classes/Helper.php
app/Services/Ai/AiCompletionService.php
app/Services/Stream/StreamService.php
app/Services/SharedCredit/SharedCreditService.php
app/Models/Setting.php
app/Models/SettingTwo.php
app/Models/User.php
app/Models/Token.php
app/Models/UserUsageCredit.php
app/Models/SharedCreditCost.php
app/Models/SharedCreditTransaction.php
app/Models/Finance/AiChatModelPlan.php
app/Http/Controllers/Dashboard/UserController.php
database/seeders/EngineSeeder.php
database/seeders/EntitySeeder.php
database/seeders/TokenSeeder.php
```

## WorkCore

```text
app/Domains/WorkCore/Config/workcore_ai.php
app/Domains/WorkCore/WorkCoreServiceProvider.php
app/Domains/WorkCore/Providers/
app/Domains/WorkCore/System/Registry/WorkModuleRegistry.php
app/Domains/WorkCore/System/AI/
app/Domains/WorkCore/System/AI/Providers/WorkCoreAIServiceProvider.php
app/Domains/WorkCore/System/AI/Providers/NativeModelGateway.php
app/Domains/WorkCore/System/AI/Providers/ModelProviderFactory.php
app/Domains/WorkCore/System/AI/Providers/OpenAICompatibleProvider.php
app/Domains/WorkCore/System/AI/Persistence/DatabaseModelProfileResolver.php
app/Domains/WorkCore/System/AI/Persistence/DatabaseAiRunRecorder.php
app/Domains/WorkCore/System/AI/Policies/AiBudgetGuard.php
app/Domains/WorkCore/System/AI/Policies/AiRateLimiter.php
app/Domains/WorkCore/System/AI/Policies/ProviderCircuitBreaker.php
app/Domains/WorkCore/System/AI/Security/NativeAiAccessGate.php
app/Domains/WorkCore/System/AI/Security/SensitiveDataRedactor.php
app/Domains/WorkCore/System/AI/routes/api.php
```

---

# Evidence limitations

This was a static source scan. It did not call live providers, verify current pricing, run Octane, execute concurrent deductions, boot WorkCore AI inside MagicAI, test local models or reconcile provider invoices.

Provider declarations prove intended support, not that every external model remains available or correctly priced.

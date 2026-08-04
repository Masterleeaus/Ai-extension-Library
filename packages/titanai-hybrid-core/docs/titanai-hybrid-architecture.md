# TitanAI Hybrid Architecture

## Purpose

TitanAI keeps Chatbot, AI Agent, and AIChatPro independently deployable while giving them a small shared discovery, event, and memory foundation.

## Runtime topology

```text
Chatbot bundled skills ──┐
                         ├── UnifiedRegistry ── immutable metadata/discovery snapshots
AI Agent actions ────────┤
                         │
AIChatPro connectors ────┘

All three extensions ─────── Laravel events ───── loose cross-extension signals
All three extensions ─────── UnifiedMemoryRepository ───── unified_memories table
```

## Foundation

`TitanAI\Hybrid\TitanAIServiceProvider` is registered by each extension provider. Laravel de-duplicates provider registration, so any extension can still deploy independently.

It binds:

- `UnifiedRegistry` / `titanai.registry`
- `UnifiedMemoryRepository` / `titanai.memory`
- the root `config/titanai.php`
- the unified memory migration path

Each extension also has a master `enabled` flag. Turning it off disables only that extension's shared TitanAI registration and listeners; its native provider boot path remains intact.

## Component adaptation

The shared contracts do not replace native extension contracts.

- Chatbot reads the real `bundled-skills.json`, verifies every SHA-256 through `FieldServiceSkillRegistry`, and wraps each definition with `UnifiedSkillAdapter`. Unified skill entries expose governed metadata and must be executed through `FieldServiceSkillContextProvider`; they do not return internal skill instructions directly.
- AI Agent first registers its existing four actions in `AIAgentActionRegistry`, then wraps resolved container instances with `UnifiedActionAdapter`. `AIAgentActionRegistry::onRegistered()` replays existing actions and mirrors actions registered later by independent extensions.
- AIChatPro preserves its provider-extension model. `ConnectorRegistry::onRegistered()` mirrors current and later connector definitions through `UnifiedConnectorAdapter`.

Native action and connector registries treat same-key/same-class registration as idempotent and reject conflicting replacements. This prevents native execution state and the unified view from silently drifting apart.

## Registry integrity

`UnifiedRegistry` rejects duplicate component keys by default. Its `allSkills()`, `allActions()`, `allConnectors()`, and `allTools()` methods return cloned collections, so consumers cannot mutate registry state through a returned collection.

Discovery events carry serialisable metadata snapshots, not live service objects:

- `SkillsDiscovered`
- `ActionsDiscovered`
- `ConnectorsDiscovered`

Operational events remain available for loose coupling:

- `ActionInvoked`
- `ActionCompleted`
- `ActionFailed`
- `UserMemoryUpdated`
- `WorkflowMemoryUpdated`
- `ConnectorStateChanged`
- `ExtensionBooted`
- `ExtensionShuttingDown`

## Shared memory

`UnifiedMemoryRepository` persists JSON-wrapped values in `unified_memories`. A logical memory key is unique across:

`scope + entity_type + entity_id + key`

Writes use an atomic upsert, preserve the original `created_at`, update `updated_at`, and record the latest `source`. Entity identifiers, key lengths, source length, and TTL bounds are validated before a database write. Optional TTL is enforced when memory is read and by `purgeExpired()`.

When `titanai.memory.emit_events` is enabled, successful USER and WORKFLOW writes emit `UserMemoryUpdated` and `WorkflowMemoryUpdated` respectively.

## Independence guarantees

Each extension retains its original routes, models, migrations, views, native registries, and execution interfaces. The shared layer is additive. Disabling an extension's TitanAI `enabled` flag, auto-registration, or event listeners leaves native behavior unchanged.

## Pass 3 orchestration and failure isolation

Pass 3 adds three shared runtime services without replacing native extension engines:

- `TitanAIEventBus` publishes cross-extension events as failure-isolated side channels. Listener exceptions are recorded and logged but do not change native operation results unless strict mode is explicitly enabled. Explicit idempotency keys suppress duplicate delivery, and the in-process key cache is bounded.
- `CrossExtensionOrchestrator` provides structured action and connector execution through `UnifiedRegistry`. It returns a correlation ID and a stable success/failure envelope while keeping native exception details in diagnostics rather than exposing them to callers.
- `TitanAIDiagnostics` maintains bounded, non-sensitive counters for event delivery, shared-memory bridging, orchestration outcomes, and current registry counts. Chatbot's authenticated runtime diagnostics endpoint exposes this as `hybrid_foundation`.

Provider event listeners and native registry bridge listeners are registered under stable listener keys. Repeated provider boot or test-container reload therefore does not multiply subscriptions.

## Native memory adoption

AI Agent's existing `ext_ai_agent_memories` table remains authoritative during the transition. `MemoryRepository` mirrors creates, updates, deletes, and user-wide deletion into shared memory using keys shaped as:

`aiagent.memory.{native_memory_id}`

The native write and shared mirror run in the same database transaction. The bridge is best-effort by default so an unavailable shared table cannot break the existing extension; strict mode can be enabled after Pass 4 host validation.

Chatbot's `MemoryContextProvider` combines:

1. memory explicitly supplied in the request payload;
2. shared USER memory for the current user;
3. shared WORKFLOW memory for the current workflow.

Shared reads are bounded by `titanai.memory.context_entry_limit`. Read failures are isolated and the existing conversation messages and request-supplied memory remain available.

## Expiry operations

`TitanAIServiceProvider` registers `titanai:memory:purge`. When `titanai.memory.auto_cleanup` is enabled, the command is scheduled hourly with overlap protection. Host scheduling is verified in Pass 4.

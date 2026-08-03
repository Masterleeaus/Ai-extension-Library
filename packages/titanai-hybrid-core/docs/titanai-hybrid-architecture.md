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

`TitanAI\Hybrid\TitanAIServiceProvider` is installed through Composer and registered by Laravel package discovery. Each extension also registers it defensively, which keeps deployments that disable package discovery supported.

It binds:

- `UnifiedRegistry` / `titanai.registry`
- `UnifiedMemoryRepository` / `titanai.memory`
- `TitanAIEventBus` / `titanai.events`
- `TitanAIDiagnostics` / `titanai.diagnostics`
- `CrossExtensionOrchestrator` / `titanai.orchestrator`
- package `config/titanai.php`
- package migration paths

Each extension retains a master `enabled` flag. Turning it off disables only that extension's shared TitanAI registration and listeners; its native provider boot path remains intact.

## Component adaptation

The shared contracts do not replace native extension contracts.

- Chatbot reads the real bundled skill manifest, verifies hashes, and wraps definitions through a governed adapter.
- AI Agent preserves its action registry and mirrors current and future actions into the shared registry.
- AIChatPro preserves its provider-extension connector model and mirrors registered connectors.

Native action and connector registries reject conflicting replacements so native execution and shared discovery cannot silently drift.

## Registry integrity

`UnifiedRegistry` rejects duplicate component keys by default. Collection accessors return cloned snapshots, and discovery events carry serialisable metadata rather than live service objects.

## Shared memory

`UnifiedMemoryRepository` persists contextual memory in `unified_memories` using a logical identity of `scope + entity_type + entity_id + key`. Writes use an atomic upsert, preserve creation time, validate schema limits, and support TTL cleanup.

## Pass 3 orchestration and failure isolation

- `TitanAIEventBus` treats optional cross-extension events as failure-isolated side channels.
- `CrossExtensionOrchestrator` executes registered actions and connector sends through stable result envelopes.
- `TitanAIDiagnostics` maintains bounded, non-sensitive counters.
- `titanai:memory:purge` removes expired memory and may be scheduled hourly.

## Independence guarantees

Each extension retains its original routes, models, migrations, views, native registries, and execution interfaces. WorkCore remains authoritative for operational business records.

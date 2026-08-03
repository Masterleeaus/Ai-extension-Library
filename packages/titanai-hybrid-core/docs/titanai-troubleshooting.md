# TitanAI Troubleshooting

## Run the complete portable gate

From this bundle, run:

```bash
php bin/verify-titanai-pass2.php
```

It executes the architecture gate, standalone foundation and hardening smoke tests, Chatbot PHP/Python/Node contracts, and JSON validation. The host application's Composer, Artisan, database, and PHPUnit tests must still be run after installation.

## Registry is empty

1. Confirm at least one extension provider is loaded.
2. Confirm `config/titanai.php` exists in the host application.
3. Check the extension's `enabled` and `auto_register_to_unified_registry` settings.
4. Clear cached configuration:

```bash
php artisan optimize:clear
composer dump-autoload
```

5. Inspect logs for `unified ... registration failed` messages.

## Chatbot has fewer than seven bundled skills

Run:

```bash
php bin/verify-titanai-pass2.php
```

A missing file or SHA-256 mismatch causes `FieldServiceSkillRegistry` to reject the affected bundle rather than silently loading modified instructions.

## A caller receives an exception from unified skill execution

This is intentional. `UnifiedSkillAdapter::handle()` does not expose bundled internal instructions. Resolve the skill through the governed Chatbot context/runtime path (`FieldServiceSkillContextProvider`) instead.

## AI Agent actions do not appear

Confirm the native registry first:

```php
app(AIAgentActionRegistry::class)->keys();
```

The expected base actions are `ai_call`, `send_message`, `generate_report`, and `path`. Actions registered later are replayed into `UnifiedRegistry`. Resolution failures are logged and do not stop other actions from registering.

## AIChatPro shows zero connectors

The base AIChatPro archive contains the connector host and registry but no provider connector implementations. Install or enable an independent connector extension and confirm it calls `ConnectorRegistry::register()`.

## Memory does not persist

1. Run `php artisan migrate`.
2. Confirm the `unified_memories` table is writable.
3. Confirm the host database connection is not read-only.
4. Check whether the memory TTL has expired.
5. Check logs for rejected empty entity IDs, overlength identifiers/keys/sources, or an invalid TTL.

TTL must be a non-negative integer no greater than `4294967295`. Entity type is limited to 96 characters, entity ID and key to 191 characters, and source to 64 characters, matching the migration schema.

## Memory events do not fire

Confirm:

```env
TITANAI_MEMORY_EMIT_EVENTS=true
```

Only USER and WORKFLOW writes emit their corresponding typed update events. Successful persistence occurs before dispatch.

## Duplicate component key

The default unified registry rejects replacement registrations. Rename the component key or deliberately enable:

```env
TITANAI_REGISTRY_ALLOW_OVERRIDES=true
```

Only enable unified overrides when provider precedence is explicitly controlled. Native AI Agent action and AIChatPro connector registries deliberately reject conflicting same-key registrations regardless of this setting.

## Extension manifest cannot be parsed

Run the pass-two verifier. It parses every JSON file and catches malformed content or hidden byte-order marks before packaging.

## Events appear missing

Events are notifications, not durable queues or registry state. Read current components from `UnifiedRegistry`; use events to react to changes. Confirm the relevant extension `enabled`, `listen_to_events`, and `emit_discovery_events` settings remain enabled.

## A listener error changes an action result

Pass 3 routes shared lifecycle events through `TitanAIEventBus`. Confirm the adapter was created by `AIAgentServiceProvider` and that `TITANAI_EVENTS_STRICT` is not enabled.

```env
TITANAI_EVENTS_ENABLED=true
TITANAI_EVENTS_STRICT=false
```

Inspect the authenticated Chatbot runtime diagnostics endpoint and look under `hybrid_foundation.events`. A non-zero `failed` count means at least one optional listener threw. The native operation remains isolated in non-strict mode.

## Events are repeated after provider reload

Providers and native component registries use stable listener keys. Confirm custom provider code also supplies a unique `listenerKey` to `onRegistered()` or uses `TitanAIEventBus::listenOnce()`.

Explicit event idempotency keys are retained even after a failed synchronous dispatch because earlier listeners may already have produced side effects. This prevents a retry from duplicating partial work.

## AI Agent memory is not visible to Chatbot

1. Confirm the unified memory migration has run.
2. Confirm shared memory and the bridge are enabled:

```env
TITANAI_SHARED_MEMORY=true
TITANAI_AIAGENT_MEMORY_BRIDGE_ENABLED=true
```

3. Create or update an AI Agent memory and check for a shared key beginning with `aiagent.memory.` under entity type `user` and the same user ID.
4. Check `hybrid_foundation.memory.failed` in runtime diagnostics.

The bridge deliberately does not backfill historical rows automatically in the archive pass. A host-aware backfill should be run only after Pass 4 verifies tenant and user identity mapping.

## Shared memory is making prompts too large

Lower the context entry limit:

```env
TITANAI_MEMORY_CONTEXT_ENTRY_LIMIT=25
```

The limit applies independently to USER and WORKFLOW memory loaded by Chatbot. Native conversation-message limits remain controlled by `titan-ai.memory_message_limit`.

## Expired memory is not being removed

Run the command manually:

```bash
php artisan titanai:memory:purge
```

Then confirm the Laravel scheduler is running and `TITANAI_MEMORY_AUTO_CLEANUP=true`. Scheduling and database behaviour require the complete host and are verified in Pass 4.

## Cross-extension action or connector returns a structured failure

Resolve the orchestrator and inspect the stable `error_code` rather than parsing messages:

- `feature_disabled`
- `component_not_found`
- `connector_not_configured`
- `action_execution_failed`
- `connector_send_failed`

Native exception messages are not returned for execution or delivery failures. They are reduced to exception classes in bounded diagnostics and remain available in application logs.

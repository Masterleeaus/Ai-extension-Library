# TitanAI Rollback Runbook

## Code-only rollback

Restore the previous extension bundle, then run:

```bash
composer dump-autoload
php artisan optimize:clear
```

Because the integration is additive, native Chatbot, AI Agent, and AIChatPro registries remain the rollback path.

## Disable shared integration without replacing files

The fastest isolation switch is the extension master flag:

```env
TITANAI_CHATBOT_ENABLED=false
TITANAI_AIAGENT_ENABLED=false
TITANAI_AICHATPRO_ENABLED=false
```

Disable only the affected extension first. Its native provider behavior remains active while shared registry registration, discovery events, and TitanAI listeners are bypassed.

More granular switches remain available for each extension:

- `auto_register_to_unified_registry`
- `emit_discovery_events`
- `listen_to_events`

Shared memory events can be disabled independently:

```env
TITANAI_MEMORY_EMIT_EVENTS=false
```

## Database rollback

Before removing the table, export it if shared memory may be needed:

```bash
php artisan db:show
# Use the host database backup procedure.
php artisan migrate:rollback --step=1
```

Verify the migration batch before using `--step=1`; another migration may have run after TitanAI.

## Rollback triggers

Rollback or disable the shared layer when any of these are confirmed:

- an extension cannot boot;
- duplicate registration causes production errors;
- unified memory queries materially regress latency;
- event listeners create repeated side effects;
- connector provider registration fails at scale.

## Post-rollback checks

- All three native extension dashboards load.
- Existing workflow actions execute.
- Chatbot bundled skills still resolve natively.
- AIChatPro connector provider extensions still appear in their native registry.
- No code path requires `UnifiedRegistry` when the corresponding integration flag is disabled.

## Pass 3 isolation switches

Disable all shared event publication while keeping native extension events and execution intact:

```env
TITANAI_EVENTS_ENABLED=false
```

Disable only cross-extension execution surfaces:

```env
TITANAI_CROSS_EXTENSION_ACTIONS=false
TITANAI_CROSS_EXTENSION_CONNECTORS=false
```

Disable shared memory consumption and AI Agent mirroring:

```env
TITANAI_SHARED_MEMORY=false
TITANAI_AIAGENT_MEMORY_BRIDGE_ENABLED=false
```

Do not enable `TITANAI_AIAGENT_MEMORY_BRIDGE_STRICT=true` until the complete host migration and transaction behaviour pass in Pass 4. In strict mode, a shared-memory failure intentionally rolls back the native AI Agent memory transaction.

After disabling the bridge, the existing `ext_ai_agent_memories` table remains authoritative. Shared mirror rows can be retained for later recovery or removed by source/key prefix after a verified backup.

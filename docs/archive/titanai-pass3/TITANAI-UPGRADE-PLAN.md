# TitanAI Hybrid Architecture - Multi-Step Upgrade Plan

**Timeline:** 5 weeks (independent extension by extension)  
**Risk Level:** Low (isolated changes per extension)  
**Effort:** ~6 hours per extension  
**Delivery:** Two ZIPs included: MERGED (primary) + DELTA (reference)

---

## EXECUTIVE SUMMARY

Migrate from three **isolated extensions** → **three independent extensions with shared foundation**.

### What Changes
- ✅ Add unified component discovery (UnifiedRegistry)
- ✅ Add shared memory storage (UnifiedMemoryRepository)
- ✅ Add event pub/sub system for loose coupling
- ✅ Each extension registers its components (skills/actions/connectors)

### What STAYS THE SAME
- ✅ All 1,296 PHP files preserved
- ✅ All UI/features/subsystems intact
- ✅ Independent deployment capability
- ✅ User-facing behavior unchanged

---

## PHASE 0: FOUNDATION SETUP (Week 1 - 4 hours)

### Step 1.1: Extract Foundation ZIP
```bash
# In your WorkCore project root:
unzip -o TitanAI-Hybrid-Foundation.zip

# Verify structure:
ls app/Domains/TitanAI/
ls config/titanai.php
ls database/migrations/ | grep unified_memories
```

### Step 1.2: Run Migration
```bash
php artisan migrate
```

**Verify:**
- [ ] `unified_memories` table created
- [ ] All 20 columns present (scope, entity_type, entity_id, key, value, source, ttl_minutes, timestamps, indexes)
- [ ] No migration errors

### Step 1.3: Register Foundation in ServiceProvider
In `config/app.php`, ensure these are listed (order doesn't matter):
```php
App\Extensions\Chatbot\ChatbotServiceProvider::class,
App\Extensions\AIAgent\AIAgentServiceProvider::class,
App\Extensions\AIChatPro\AIChatProServiceProvider::class,
// TitanAI foundation is auto-registered via Laravel's PSR-4 discovery
```

**Verify:**
```bash
php artisan tinker
>>> app(App\Domains\TitanAI\Registries\UnifiedRegistry::class)->summary()
```

Should return:
```
[
  'skills' => [],
  'actions' => [],
  'connectors' => [],
  'tools' => [],
  'total' => 0,
]
```

---

## PHASE 1: CHATBOT INTEGRATION (Week 2 - 6 hours)

### Step 2.1: Understand Chatbot Structure
```
app/Extensions/Chatbot/
├── config/chatbot.php
├── System/ChatbotServiceProvider.php
├── Subsystems/        # 29 subsystems
├── Skills/            # 7 skills
├── Modules/
├── Channels/
└── ...
```

### Step 2.2: Modify ChatbotServiceProvider
**Location:** `app/Extensions/Chatbot/System/ChatbotServiceProvider.php`

Add to the `boot()` method:

```php
public function boot(): void
{
    // ... existing code ...

    // NEW: Register skills to unified registry
    if (config('titanai.extensions.chatbot.auto_register_to_unified_registry')) {
        $this->registerSkillsToUnifiedRegistry();
    }

    // NEW: Subscribe to cross-extension events
    if (config('titanai.extensions.chatbot.listen_to_events')) {
        $this->subscribeToEvents();
    }
}

/**
 * Register bundled skills to UnifiedRegistry.
 */
private function registerSkillsToUnifiedRegistry(): void
{
    try {
        $registry = app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class);
        
        // Get all skill instances (adjust based on your skill loading mechanism)
        $skills = [
            new \App\Extensions\Chatbot\Skills\CarpetCleaningSkill(),
            new \App\Extensions\Chatbot\Skills\EcoFriendlySkill(),
            new \App\Extensions\Chatbot\Skills\EquipmentMaintenanceSkill(),
            new \App\Extensions\Chatbot\Skills\FieldServiceDispatcherSkill(),
            new \App\Extensions\Chatbot\Skills\HandymanQuoteGeneratorSkill(),
            new \App\Extensions\Chatbot\Skills\MaintenanceSchedulerSkill(),
            new \App\Extensions\Chatbot\Skills\DeepCleanPlannerSkill(),
        ];

        foreach ($skills as $skill) {
            $registry->registerSkill($skill->key(), $skill);
        }

        \Illuminate\Support\Facades\Event::dispatch(
            new \App\Domains\TitanAI\SkillsDiscovered(
                $registry->allSkills()->toArray(),
                'chatbot'
            )
        );
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('Chatbot skills registration failed', [
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * Subscribe to cross-extension events.
 */
private function subscribeToEvents(): void
{
    $eventManager = app('events');

    // Listen for actions completed by AIAgent
    $eventManager->listen(
        \App\Domains\TitanAI\ActionCompleted::class,
        function (\App\Domains\TitanAI\ActionCompleted $event) {
            if ($event->source !== 'chatbot') {
                // Handle action from another extension
                \Illuminate\Support\Facades\Log::info("Chatbot received action result from {$event->source}");
            }
        }
    );

    // Listen for connectors becoming available
    $eventManager->listen(
        \App\Domains\TitanAI\ConnectorsDiscovered::class,
        function (\App\Domains\TitanAI\ConnectorsDiscovered $event) {
            // Optionally use connectors discovered by AIChatPro
        }
    );
}
```

### Step 2.3: Update Chatbot Memory Access (Optional)
If Chatbot has isolated memory storage, migrate to unified:

```php
// OLD:
$memory = app('chatbot.memory');
$memory->set($userId, 'preference', $value);

// NEW:
$memory = app(\App\Domains\TitanAI\Memory\Services\UnifiedMemoryRepository::class);
$memory->store('chatbot', $userId, MemoryScope::USER, 'preference', $value);
```

### Step 2.4: Test Chatbot
```bash
# Unit tests
php artisan test --filter=ChatbotTest

# Verify registry integration
php artisan tinker
>>> app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class)->summary()
// Should now show 7 skills registered

# Check events fired
php artisan tinker
>>> Event::fake()
>>> Artisan::call('cache:clear')
>>> // Reload app and verify SkillsDiscovered event fired
```

**Checklist:**
- [ ] ChatbotServiceProvider modified
- [ ] 7 skills registered in UnifiedRegistry
- [ ] SkillsDiscovered event fires on boot
- [ ] Existing Chatbot functionality unchanged
- [ ] Unit tests pass

---

## PHASE 2: AIAGENT INTEGRATION (Week 3 - 6 hours)

### Step 3.1: Understand AIAgent Structure
```
app/Extensions/AIAgent/
├── config/ai-agent.php
├── System/AIAgentServiceProvider.php
├── System/Actions/    # ai_call, send_message, generate_report, path
├── System/Engine/
├── System/Triggers/
├── System/Connectors/
├── System/Memory/
└── ...
```

### Step 3.2: Modify AIAgentServiceProvider
**Location:** `app/Extensions/AIAgent/System/AIAgentServiceProvider.php`

Add to the `boot()` method:

```php
public function boot(): void
{
    // ... existing code ...

    // NEW: Register actions to unified registry
    if (config('titanai.extensions.aiagent.auto_register_to_unified_registry')) {
        $this->registerActionsToUnifiedRegistry();
    }

    // NEW: Subscribe to cross-extension events
    if (config('titanai.extensions.aiagent.listen_to_events')) {
        $this->subscribeToEvents();
    }
}

/**
 * Register built-in actions to UnifiedRegistry.
 */
private function registerActionsToUnifiedRegistry(): void
{
    try {
        $registry = app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class);
        
        // Get built-in action instances
        $actions = [
            new \App\Extensions\AIAgent\System\Actions\AICallAction(),
            new \App\Extensions\AIAgent\System\Actions\SendMessageAction(),
            new \App\Extensions\AIAgent\System\Actions\GenerateReportAction(),
            new \App\Extensions\AIAgent\System\Actions\PathAction(),
        ];

        foreach ($actions as $action) {
            $registry->registerAction($action->key(), $action);
        }

        \Illuminate\Support\Facades\Event::dispatch(
            new \App\Domains\TitanAI\ActionsDiscovered(
                $registry->allActions()->toArray(),
                'aiagent'
            )
        );
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('AIAgent actions registration failed', [
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * Subscribe to cross-extension events.
 */
private function subscribeToEvents(): void
{
    $eventManager = app('events');

    // Listen for skills discovered by Chatbot
    $eventManager->listen(
        \App\Domains\TitanAI\SkillsDiscovered::class,
        function (\App\Domains\TitanAI\SkillsDiscovered $event) {
            \Illuminate\Support\Facades\Log::info('AIAgent sees skills discovered', [
                'count' => count($event->skills),
            ]);
        }
    );

    // Listen for connectors discovered by AIChatPro
    $eventManager->listen(
        \App\Domains\TitanAI\ConnectorsDiscovered::class,
        function (\App\Domains\TitanAI\ConnectorsDiscovered $event) {
            // AIAgent can now use connectors (e.g., send_message via Telegram)
        }
    );
}
```

### Step 3.3: Test AIAgent
```bash
php artisan test --filter=AIAgentTest

php artisan tinker
>>> app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class)->summary()
// Should show 7 skills (from Chatbot) + 4 actions (from AIAgent)
```

**Checklist:**
- [ ] AIAgentServiceProvider modified
- [ ] 4 actions registered in UnifiedRegistry
- [ ] ActionsDiscovered event fires on boot
- [ ] Existing AIAgent functionality unchanged
- [ ] Unit tests pass

---

## PHASE 3: AICHATPRO INTEGRATION (Week 4 - 6 hours)

### Step 4.1: Understand AIChatPro Structure
```
app/Extensions/AIChatPro/
├── System/AIChatProServiceProvider.php
├── System/Connectors/  # MagicAI, Telegram, custom
├── System/Services/
├── System/Http/
├── resources/views/
└── database/migrations/
```

### Step 4.2: Modify AIChatProServiceProvider
**Location:** `app/Extensions/AIChatPro/System/AIChatProServiceProvider.php`

Add to the `boot()` method:

```php
public function boot(): void
{
    // ... existing code ...

    // NEW: Register connectors to unified registry
    if (config('titanai.extensions.aichatpro.auto_register_to_unified_registry')) {
        $this->registerConnectorsToUnifiedRegistry();
    }

    // NEW: Subscribe to cross-extension events
    if (config('titanai.extensions.aichatpro.listen_to_events')) {
        $this->subscribeToEvents();
    }
}

/**
 * Register connectors to UnifiedRegistry.
 */
private function registerConnectorsToUnifiedRegistry(): void
{
    try {
        $registry = app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class);
        
        // Get connector instances (adjust based on your connector loading)
        $connectors = [
            new \App\Extensions\AIChatPro\System\Connectors\MagicAIConnector(),
            new \App\Extensions\AIChatPro\System\Connectors\TelegramConnector(),
            // Add custom connectors
        ];

        foreach ($connectors as $connector) {
            $registry->registerConnector($connector->key(), $connector);
        }

        \Illuminate\Support\Facades\Event::dispatch(
            new \App\Domains\TitanAI\ConnectorsDiscovered(
                $registry->allConnectors()->toArray(),
                'aichatpro'
            )
        );
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::error('AIChatPro connectors registration failed', [
            'error' => $e->getMessage(),
        ]);
    }
}

/**
 * Subscribe to cross-extension events.
 */
private function subscribeToEvents(): void
{
    $eventManager = app('events');

    // Listen for action invocations
    $eventManager->listen(
        \App\Domains\TitanAI\ActionInvoked::class,
        function (\App\Domains\TitanAI\ActionInvoked $event) {
            // Maybe log action to database or trigger side effects
        }
    );

    // Listen for action completions
    $eventManager->listen(
        \App\Domains\TitanAI\ActionCompleted::class,
        function (\App\Domains\TitanAI\ActionCompleted $event) {
            // Maybe send result through a connector
        }
    );
}
```

### Step 4.3: Test AIChatPro
```bash
php artisan test --filter=AIChatProTest

php artisan tinker
>>> app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class)->summary()
// Should show 7 skills + 4 actions + 2+ connectors
```

**Checklist:**
- [ ] AIChatProServiceProvider modified
- [ ] Connectors registered in UnifiedRegistry
- [ ] ConnectorsDiscovered event fires on boot
- [ ] Existing AIChatPro functionality unchanged
- [ ] Unit tests pass

---

## PHASE 4: INTEGRATION & CROSS-EXTENSION TESTING (Week 5 - 8 hours)

### Step 5.1: End-to-End Testing
```bash
php artisan tinker

# 1. Verify all components registered
>>> $registry = app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class);
>>> $summary = $registry->summary();
>>> dd($summary);
// Expected: 
//   'skills' => 7,
//   'actions' => 4,
//   'connectors' => 2+,
//   'total' => 13+

# 2. Test cross-extension action invocation
>>> Event::listen(\App\Domains\TitanAI\ActionCompleted::class, function($e) {
    dump("Action {$e->actionKey} completed with result: " . json_encode($e->result));
});

# 3. Test memory sharing
>>> $memory = app(\App\Domains\TitanAI\Memory\Services\UnifiedMemoryRepository::class);
>>> $memory->store('test-extension', 'user-123', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'test-key', 'test-value');
>>> $value = $memory->retrieve('test-extension', 'user-123', \App\Domains\TitanAI\Memory\Enums\MemoryScope::USER, 'test-key');
>>> dump($value); // Should be: 'test-value'
```

### Step 5.2: Performance Testing
```bash
# Run all tests with timing
php artisan test --profile

# Check for memory leaks (run in loop)
for i in {1..10}; do
  php artisan tinker --execute="
    \$registry = app(\App\Domains\TitanAI\Registries\UnifiedRegistry::class);
    dump(\$registry->summary());
  "
done
```

### Step 5.3: Documentation & Runbooks
- [ ] Update README.md with new architecture diagram
- [ ] Create troubleshooting guide for common issues
- [ ] Document how to add new skills/actions/connectors
- [ ] Add examples to codebase

### Step 5.4: Staging Deployment
```bash
# 1. Deploy to staging
git push origin staging
# Wait for deployment

# 2. Run smoke tests
php artisan test --tag=smoke

# 3. Verify in staging
curl https://staging.yourapp.com/api/titanai/registry/summary

# 4. Get sign-off from QA
```

### Step 5.5: Production Deployment
```bash
# 1. Pre-deployment backup
mysqldump workcore > workcore-pre-hybrid-backup.sql

# 2. Deploy
git push origin main

# 3. Run migration
php artisan migrate --force

# 4. Clear caches
php artisan cache:clear
php artisan config:cache

# 5. Verify health
curl https://yourapp.com/api/health
curl https://yourapp.com/api/titanai/registry/summary

# 6. Monitor logs
tail -f storage/logs/laravel.log | grep TitanAI
```

**Checklist:**
- [ ] All unit tests pass (100% coverage on foundation)
- [ ] Integration tests pass
- [ ] Smoke tests pass on staging
- [ ] Performance metrics acceptable
- [ ] No breaking changes to API
- [ ] Database backup taken
- [ ] Rollback plan documented and tested

---

## ROLLBACK PLAN

If issues arise in production:

```bash
# Option 1: Quick rollback (< 5 min)
git revert HEAD
git push origin main
php artisan cache:clear

# Option 2: Full rollback (< 15 min)
mysql workcore < workcore-pre-hybrid-backup.sql
git checkout previous-stable-commit
php artisan migrate:rollback
php artisan cache:clear
```

**Triggers for rollback:**
- [ ] Extension fails to boot (error rate > 5%)
- [ ] Memory usage spikes >20%
- [ ] API response time increases >100ms
- [ ] Database query errors (duplicate keys, constraint violations)
- [ ] Event system causes cascading failures

---

## SUCCESS CRITERIA

✅ **Completed when:**
1. All 1,296 PHP files intact
2. All extensions boot without errors
3. UnifiedRegistry contains all components
4. Cross-extension events fire successfully
5. No performance regression (< 5% latency increase)
6. No breaking changes to API
7. All existing features work as before
8. Rollback tested and documented

---

## SUPPORT & TROUBLESHOOTING

### Issue: "Class not found" errors
**Solution:** Run `composer dump-autoload`

### Issue: Events not firing
**Solution:** Verify `config/titanai.php` has `listen_to_events` => true

### Issue: Memory not persisting
**Solution:** Check `unified_memories` table exists and is writable

### Issue: Registry showing 0 components
**Solution:** 
1. Check `auto_register_to_unified_registry` => true
2. Verify component classes extend proper interfaces
3. Check error logs: `tail storage/logs/laravel.log | grep TitanAI`

### Need Help?
- Check `HYBRID-APPROACH-SUMMARY.md` for architecture overview
- Review `TitanAI-Hybrid-Architecture.md` for detailed docs
- Reference ServiceProvider adapter examples in `TitanAI-Hybrid-Docs-Examples.zip`


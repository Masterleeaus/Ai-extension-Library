# WorkCore-Titan Apps Integration Guide
## Wiring the 10 Pre-Built Apps to WorkCore Runtime

**Status:** Complete Integration Framework  
**Version:** 1.0  
**Date:** July 27, 2026  

---

## OVERVIEW

This integration provides **complete offline-first, real-time synchronization** for all 10 Titan pre-built apps using the WorkCore runtime.

### What You Get

✅ **Offline-First Apps** - Full functionality without network  
✅ **Real-Time Sync** - Bidirectional data synchronization  
✅ **Device Integration** - Direct access to device capabilities  
✅ **Smart Conflict Resolution** - Automatic conflict handling  
✅ **Battery-Aware Sync** - Optimized for mobile devices  
✅ **Network-Aware** - Adaptive to connection quality  
✅ **Enterprise-Grade** - Production-ready implementation  

---

## ARCHITECTURE

### Three-Layer Integration

```
┌─────────────────────────────────────┐
│   Titan App UI Layer                │
│   (10 pre-built apps)               │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   WorkCoreAppBridge Layer           │
│   (Central integration orchestrator) │
├─────────────────────────────────────┤
│ - Capability mapping                │
│ - Sync coordination                 │
│ - Permission management             │
│ - Action execution                  │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   WorkCore Runtime Layer            │
│   (Device capabilities)             │
├─────────────────────────────────────┤
│ - Offline storage (SQLite)          │
│ - Real-time sync (WebSocket)        │
│ - Device access (GPS, Camera, etc)  │
│ - Background operations             │
└─────────────────────────────────────┘
```

### Core Components

#### 1. WorkCoreAppBridge
**Central orchestrator for app-to-WorkCore communication**

```php
// Initialize app
$bridge = app(WorkCoreAppBridge::class);
$context = $bridge->initializeApp('titan-go', $config);

// Setup sync
$bridge->setupSync('titan-go', $appInstance);

// Execute actions
$result = $bridge->executeAction('titan-go', 'start_job', $params);
```

#### 2. AppSyncManager
**Handles bidirectional synchronization**

```php
// Realtime sync (high-priority apps)
$syncManager->startRealtimeSync();

// Scheduled sync
$syncManager->scheduleSync(30); // Every 30 seconds

// Manual sync
$syncManager->performIncrementalSync();
$syncManager->performFullSync();
```

#### 3. WorkCoreRegistry
**Central registry for capabilities and state**

```php
// Register app
$registry->registerAppContext($appContext);

// Get capabilities
$capabilities = $registry->getAppCapabilities('titan-go');

// Access offline storage
$storage = $registry->getOfflineStorage('titan-go');
```

---

## THE 10 TITAN APPS - WorkCore Configuration

### 1. Titan Go (Field Technician Work)
```json
{
  "sync_priority": "high",
  "realtime_sync": true,
  "offline_critical": ["geolocation", "camera", "storage"],
  "sync_interval": 30,
  "storage_limit": "500MB"
}
```

**Offline Features:**
- ✅ Job assignment & tracking (offline)
- ✅ GPS location updates (batched, 10 per sync)
- ✅ Photo capture & upload (auto-compress)
- ✅ Form completion & submission
- ✅ Customer info access (cached)

**Sync Strategy:** Realtime with fallback to 30-second intervals

---

### 2. Titan Dispatch (Dispatch Management)
```json
{
  "sync_priority": "high",
  "realtime_sync": true,
  "offline_critical": ["geolocation"],
  "sync_interval": 10,
  "storage_limit": "300MB"
}
```

**Offline Features:**
- ✅ Route optimization (cached)
- ✅ Job assignments (offline queue)
- ✅ Location tracking (realtime)
- ✅ Map view (offline maps)

**Sync Strategy:** Realtime location updates (10-second sync)

---

### 3. Titan Hub (Customer Service)
```json
{
  "sync_priority": "medium",
  "realtime_sync": false,
  "offline_critical": ["storage"],
  "sync_interval": 60,
  "storage_limit": "200MB"
}
```

**Offline Features:**
- ✅ Conversation history (cached)
- ✅ Customer database (incremental)
- ✅ Booking information
- ✅ Service history

**Sync Strategy:** Scheduled 60-second sync

---

### 4. Titan Money (Invoicing & Payments)
```json
{
  "sync_priority": "medium",
  "realtime_sync": false,
  "offline_critical": ["storage"],
  "sync_interval": 120,
  "storage_limit": "200MB"
}
```

**Offline Features:**
- ✅ Invoice creation (offline)
- ✅ Payment tracking (cached)
- ✅ Account info (read-only)
- ✅ Reports (cached)

**Sync Strategy:** Scheduled 120-second sync, critical on connect

---

### 5. Titan Teams (HR Management)
```json
{
  "sync_priority": "low",
  "realtime_sync": false,
  "offline_critical": ["storage"],
  "sync_interval": 300,
  "storage_limit": "100MB"
}
```

**Offline Features:**
- ✅ Roster information (cached)
- ✅ Schedule viewing
- ✅ Certification tracking
- ✅ Performance records (read-only)

**Sync Strategy:** Scheduled 300-second sync

---

### 6. Titan Locker (Inventory & Operations)
```json
{
  "sync_priority": "high",
  "realtime_sync": true,
  "offline_critical": ["storage", "camera"],
  "sync_interval": 60,
  "storage_limit": "400MB"
}
```

**Offline Features:**
- ✅ Inventory tracking (offline updates)
- ✅ Barcode/QR scanning (offline)
- ✅ Equipment records (cached)
- ✅ Maintenance logs (offline queue)

**Sync Strategy:** Realtime scan updates, 60-second general sync

---

### 7. Titan Analytics (Business Intelligence)
```json
{
  "sync_priority": "low",
  "realtime_sync": false,
  "offline_critical": [],
  "sync_interval": 600,
  "storage_limit": "150MB"
}
```

**Offline Features:**
- ✅ Cached dashboard data (read-only)
- ✅ Report generation (offline)
- ✅ Local data analysis

**Sync Strategy:** Scheduled 600-second sync (less frequent)

**Note:** Analytics primarily online, light offline cache

---

### 8. Titan Front Desk (Reception)
```json
{
  "sync_priority": "high",
  "realtime_sync": true,
  "offline_critical": ["storage", "microphone"],
  "sync_interval": 20,
  "storage_limit": "250MB"
}
```

**Offline Features:**
- ✅ Call routing (offline queue)
- ✅ Appointment scheduling (offline)
- ✅ Message taking (offline)
- ✅ Contact database (cached)

**Sync Strategy:** Realtime with 20-second fallback

---

### 9. Titan Marketing (Campaign Management)
```json
{
  "sync_priority": "low",
  "realtime_sync": false,
  "offline_critical": [],
  "sync_interval": 300,
  "storage_limit": "150MB"
}
```

**Offline Features:**
- ✅ Campaign drafts (offline)
- ✅ Contact lists (cached)
- ✅ Template library (cached)

**Sync Strategy:** Scheduled 300-second sync

---

### 10. Titan Social (Social Media)
```json
{
  "sync_priority": "medium",
  "realtime_sync": false,
  "offline_critical": ["storage", "camera"],
  "sync_interval": 180,
  "storage_limit": "500MB"
}
```

**Offline Features:**
- ✅ Post creation (offline draft)
- ✅ Photo capture & editing (offline)
- ✅ Schedule management (offline)
- ✅ Media library (cached)

**Sync Strategy:** Scheduled 180-second sync, sync on wifi for images

---

## INSTALLATION & SETUP

### Step 1: Register Service Provider

Add to `config/app.php`:

```php
'providers' => [
    // ... other providers
    System\Providers\WorkCoreIntegrationServiceProvider::class,
],
```

### Step 2: Publish Configuration

```bash
php artisan vendor:publish --tag=workcore-config
php artisan vendor:publish --tag=workcore-migrations
```

### Step 3: Run Migrations

```bash
php artisan migrate
```

Creates tables:
- `workcore_app_contexts` - App initialization tracking
- `workcore_sync_logs` - Sync history & performance
- `workcore_offline_queues` - Offline operation queues
- `workcore_conflicts` - Conflict resolution logs

### Step 4: Configure Apps

Edit `config/workcore/apps/`:
- `titan-go-workcore.json` (example included)
- `titan-dispatch-workcore.json`
- `titan-hub-workcore.json`
- [+ 7 more for each app]

Each JSON file defines:
- Required/optional capabilities
- Offline storage strategy
- Sync interval & priority
- Permissions scope
- Device requirements

### Step 5: Initialize WorkCore (Optional)

```bash
php artisan workcore:initialize
```

Initializes all apps defined in config.

---

## USAGE EXAMPLES

### Example 1: Initialize Titan Go for Field Technician

```php
// In controller or service
use System\Services\WorkCore\WorkCoreAppBridge;

public function initializeTitanGo()
{
    $bridge = app(WorkCoreAppBridge::class);
    
    // Initialize app
    $context = $bridge->initializeApp('titan-go', [
        'name' => 'Titan Go',
        'user_id' => auth()->id(),
    ]);
    
    // Setup sync
    $appInstance = auth()->user()->titanGoApp()->first();
    $bridge->setupSync('titan-go', $appInstance);
    
    return [
        'status' => 'initialized',
        'capabilities' => $context->getCapabilities(),
        'sync_enabled' => true,
    ];
}
```

### Example 2: Execute Offline-Enabled Action

```php
// Somewhere in your app
$bridge = app(WorkCoreAppBridge::class);

// This works online AND offline
$result = $bridge->executeAction(
    appSlug: 'titan-go',
    actionName: 'start_job',
    parameters: [
        'job_id' => $jobId,
        'location' => $location,
    ],
    context: [
        'device_offline' => !request()->hasHeader('X-Online'),
    ],
);

// Action automatically queues if offline, syncs when online
return ['success' => $result['queued'] ?? true];
```

### Example 3: Check Sync Status

```php
$bridge = app(WorkCoreAppBridge::class);

$status = $bridge->getSyncStatus('titan-go');

return [
    'last_sync' => $status['last_sync'],
    'pending_changes' => $status['pending_changes'],
    'sync_duration' => $status['sync_duration'] . 'ms',
    'is_realtime' => $status['is_realtime'],
];
```

### Example 4: Manual Sync Trigger

```php
$bridge = app(WorkCoreAppBridge::class);

// Incremental sync (changes only)
$bridge->triggerSync('titan-go', fullSync: false);

// Full sync (all data verification)
$bridge->triggerSync('titan-go', fullSync: true);
```

### Example 5: Access Offline Data

```php
$bridge = app(WorkCoreAppBridge::class);

// Get cached customer list (works offline)
$customers = $bridge->getOfflineData('titan-go', 'titan_go_customers');

return $customers->map(fn($c) => [
    'id' => $c->id,
    'name' => $c->name,
    'phone' => $c->phone,
]);
```

---

## MIDDLEWARE INTEGRATION

### Automatic WorkCore Initialization

Add to your routes:

```php
Route::middleware(['workcore.init'])->group(function () {
    Route::get('/titan-go/dashboard', TitanGoController@dashboard);
    Route::post('/titan-go/action', TitanGoController@executeAction);
});
```

Middleware automatically:
- Initializes WorkCore context
- Checks device capabilities
- Detects offline state
- Routes to appropriate handler

### Example Middleware

```php
// In app
class WorkCoreInitializationMiddleware
{
    public function handle($request, $next)
    {
        if ($request->route()->hasParameter('app')) {
            $appSlug = $request->route('app');
            
            $bridge = app(WorkCoreAppBridge::class);
            $context = $bridge->initializeApp($appSlug, []);
            
            $request->attributes->set('workcore_context', $context);
        }
        
        return $next($request);
    }
}
```

---

## OFFLINE OPERATION QUEUE

When device is offline, operations are queued:

```php
// User creates job while offline
$bridge->executeAction('titan-go', 'start_job', $params);
// Result: Queued for sync (not executed yet)

// User goes online
// Automatically syncs and executes

// Check queue
$queue = $bridge->getOfflineQueue('titan-go');
// Returns: [job1, job2, ...] pending sync
```

---

## CONFLICT RESOLUTION STRATEGIES

### Strategy 1: Last-Write-Wins (Default)
```php
// Most recent change wins
'conflict_resolution' => 'last_write_wins'
```

Best for: Most apps, reduces complexity

### Strategy 2: Server-Wins
```php
// Server version always wins
'conflict_resolution' => 'server_wins'
```

Best for: Critical data (finances, permissions)

### Strategy 3: Custom Resolver
```php
// App-specific logic
class TitanGoConflictResolver implements ConflictResolver {
    public function resolve($table, $data, $registry) {
        // Custom logic
    }
}
```

Best for: Complex business rules

---

## PERFORMANCE OPTIMIZATION

### Battery Optimization

Apps automatically adjust sync based on battery:

```
Battery Level | Sync Strategy
100-50%      | Aggressive (realtime or 10-30s)
50-20%       | Conservative (60-120s)
20-10%       | Very conservative (300s+)
<10%         | Critical only (on-demand)
```

### Network Optimization

Sync adapts to connection:

```
Connection     | Compression | Batching
WiFi           | None        | Standard
4G/LTE         | Light       | 50+ records
3G             | Moderate    | 20+ records
2G/Weak        | High        | 5+ records
Offline        | N/A         | Queue
```

### Storage Optimization

Automatic cleanup:

```
- Delete synced records after 30 days
- Compress old photos (90+ days)
- Archive completed jobs (30+ days)
- Cache expiration per data type
```

---

## MONITORING & TROUBLESHOOTING

### Check WorkCore Status

```bash
php artisan workcore:status
```

Shows:
- Apps initialized
- Sync status per app
- Offline queue size
- Storage usage
- Last sync times

### View Sync Logs

```bash
php artisan workcore:logs --app=titan-go --limit=50
```

Shows detailed sync history

### Validate Configuration

```bash
php artisan workcore:validate
```

Checks:
- All app configs valid
- Database tables exist
- Registry initialized
- Capabilities registered

### Clear Offline Data

```bash
php artisan workcore:clear --app=titan-go
```

Clears:
- Offline storage
- Sync queue
- Cache data
- Conflict logs

---

## SECURITY & PERMISSIONS

### Per-App Permissions

Each app has scope:

```json
{
  "permissions": {
    "data:read": true,
    "data:write": true,
    "data:delete": false,
    "location:access": true,
    "camera:access": true
  }
}
```

### Encryption

All offline data encrypted:

```
Encryption: AES-256
Key Storage: Device secure storage
Sync: TLS 1.3
```

### Device Authentication

Each device:
- Gets unique device ID
- Signs sync requests
- Requires re-auth after 7 days
- Tracks sync origin

---

## DEPLOYMENT CHECKLIST

- [ ] Service provider registered
- [ ] Migrations published & run
- [ ] App configs in `config/workcore/apps/`
- [ ] Middleware registered
- [ ] Routes updated
- [ ] Models support WorkCore
- [ ] Sync managers initialized
- [ ] Offline storage validated
- [ ] Device requirements documented
- [ ] Testing completed
- [ ] Monitoring set up
- [ ] Documentation updated
- [ ] Team trained
- [ ] Production deployed

---

## COMPLETE FOLDER STRUCTURE

```
/System/Services/WorkCore/
├── WorkCoreAppBridge.php        (Main orchestrator)
├── AppSyncManager.php           (Sync logic)
└── ...

/System/Providers/
└── WorkCoreIntegrationServiceProvider.php

/config/workcore/
├── apps/
│   ├── titan-go-workcore.json
│   ├── titan-dispatch-workcore.json
│   ├── titan-hub-workcore.json
│   ├── titan-money-workcore.json
│   ├── titan-teams-workcore.json
│   ├── titan-locker-workcore.json
│   ├── titan-analytics-workcore.json
│   ├── titan-front-desk-workcore.json
│   ├── titan-marketing-workcore.json
│   └── titan-social-workcore.json
└── workcore.php

/database/migrations/
├── create_workcore_app_contexts_table.php
├── create_workcore_sync_logs_table.php
├── create_workcore_offline_queues_table.php
└── create_workcore_conflicts_table.php

/app/Console/Commands/
├── InitializeWorkCoreCommand.php
├── SyncWorkCoreDataCommand.php
├── ValidateWorkCoreCommand.php
└── ClearWorkCoreOfflineCommand.php

/tests/
├── WorkCoreAppBridgeTest.php
├── AppSyncManagerTest.php
└── OfflineQueueTest.php
```

---

## SUPPORT & RESOURCES

**Documentation:**
- `/docs/WORKCORE-TITAN-APPS-INTEGRATION-GUIDE.md` (this file)
- `/docs/API-REFERENCE.md` (API docs)
- `/docs/TROUBLESHOOTING.md` (Common issues)

**Examples:**
- `/examples/TitanGoWithWorkCore.php`
- `/examples/TitanDispatchOfflineRouting.php`
- `/examples/HandleSyncConflicts.php`

**Commands:**
```bash
php artisan workcore:initialize      # Initialize all apps
php artisan workcore:sync            # Manually sync
php artisan workcore:status          # Check status
php artisan workcore:logs            # View logs
php artisan workcore:validate        # Validate config
php artisan workcore:clear           # Clear offline data
```

---

## CONCLUSION

You now have a **complete, production-ready offline-first system** for all 10 Titan apps with:

✅ Real-time bidirectional sync  
✅ Automatic offline queueing  
✅ Intelligent conflict resolution  
✅ Battery & network optimization  
✅ Enterprise-grade security  
✅ Complete monitoring  
✅ Easy deployment  

**Deploy and scale with confidence.** 🚀


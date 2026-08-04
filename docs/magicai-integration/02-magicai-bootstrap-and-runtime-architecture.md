---
title: "MagicAI 11 Bootstrap and Runtime Architecture"
scan_number: 2
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope: "Magicai-Server-Files(3).zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/02-magicai-bootstrap-and-runtime-architecture.md"
---

# Scan 02 — MagicAI 11 Bootstrap and Runtime Architecture

## Executive conclusion

MagicAI 11 uses the conventional Laravel 10 application lifecycle, with its core application services registered through `config/app.php` and optional paid extensions registered dynamically by `MarketplaceServiceProvider`.

The architecture is capable of hosting the complete WorkCore system as a native extension, but several runtime behaviours must be corrected before WorkCore is integrated.

The most important findings are:

1. **The correct WorkCore insertion point is the Marketplace provider registry.**
2. MagicAI registers its AI Entity and Engine managers before the Marketplace provider, giving WorkCore a usable AI host at extension boot.
3. WorkCore can register routes, migrations, views, translations, commands, middleware and AI tools from its own service provider without placing implementation code in MagicAI core.
4. MagicAI performs database-dependent work during application boot.
5. MagicAI currently rewrites every queued job to the `default` queue and starts a one-job queue worker during every application boot.
6. The queue behaviour conflicts directly with WorkCore domain queues, priorities, offline processing and event delivery.
7. All `dashboard/*` requests are exempted from CSRF verification.
8. Extension installation routes and installation side effects require privilege and deployment hardening.
9. Extension installation can execute migrations and asset publishing twice.
10. The native `App\Providers\ExtensionServiceProvider` and `App\Providers\ChatbotServiceProvider` are not registered in the current provider manifest and are therefore dormant in this package.
11. The extension runtime has both a newer isolated provider-based installer and an older host-file-copying installer.
12. WorkCore should use only the newer `app/Extensions` provider architecture.

The recommended boundary is:

> Add one explicit WorkCore registration to MagicAI's extension provider map, then keep all WorkCore code, routes, migrations, views, configuration and services inside the WorkCore extension.

---

# 1. Scan scope

This scan traced:

- HTTP entry point
- Console entry point
- Application container creation
- Laravel provider loading
- Provider registration order
- Provider boot order
- Core container bindings
- AI Engine and Entity registration
- Marketplace provider registration
- Optional extension loading
- HTTP middleware
- Web and API route loading
- Panel route loading
- Event registration
- Broadcasting
- Exception handling
- Scheduler and console command loading
- Extension installation side effects
- Runtime configuration mutation
- Upgrade-safe customisation hooks
- WorkCore insertion alternatives

This report focuses on runtime structure. The next scan will examine the Marketplace and extension package format in greater depth.

---

# 2. Application entry points

## 2.1 HTTP entry point

MagicAI enters through:

```text
public/index.php
```

The request lifecycle is:

```text
HTTP request
→ public/index.php
→ optional maintenance response
→ Composer autoloader
→ bootstrap/app.php
→ resolve HTTP Kernel
→ Kernel handles request
→ response is sent
→ Kernel termination
```

Before Laravel is bootstrapped, `public/index.php` checks whether the request URI is exactly:

```text
/update-manual
```

When it matches, it deletes:

```text
bootstrap/cache/packages.php
bootstrap/cache/services.php
```

This happens before route authentication or controller execution.

### Runtime implication

An unauthenticated request can trigger provider-cache deletion by requesting `/update-manual`.

This does not directly execute the update, but it creates avoidable rebuild work and should be removed or protected.

---

## 2.2 Console entry point

MagicAI enters CLI execution through:

```text
artisan
```

The console lifecycle is:

```text
CLI command
→ Composer autoloader
→ bootstrap/app.php
→ resolve Console Kernel
→ register and boot providers
→ execute command
→ terminate console kernel
```

The same application providers used by web requests are booted for Artisan commands.

This is important because MagicAI's `AppServiceProvider::boot()` performs queue and database work. Those side effects are therefore not restricted to web requests.

---

# 3. Application container bootstrap

`bootstrap/app.php` creates a conventional Laravel application:

```php
$app = new Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);
```

It binds:

```php
Illuminate\Contracts\Http\Kernel::class
    → App\Http\Kernel::class

Illuminate\Contracts\Console\Kernel::class
    → App\Console\Kernel::class

Illuminate\Contracts\Debug\ExceptionHandler::class
    → App\Exceptions\Handler::class
```

No WorkCore-specific application subclass or custom container is required.

WorkCore can use normal Laravel service-provider registration.

---

# 4. Laravel bootstrap sequence

Under the standard Laravel 10 lifecycle, the application performs these broad stages:

```text
Load environment variables
→ load configuration
→ configure exception handling
→ register facades
→ register service providers
→ boot service providers
→ run HTTP or console kernel
```

The critical distinction is:

- `register()` is for container bindings and definitions.
- `boot()` is for runtime integration after all providers have been registered.

WorkCore must preserve that distinction.

## WorkCore rule

`WorkCoreServiceProvider::register()` must not:

- Query the database
- Run migrations
- Write files
- Register routes with request-dependent state
- Start queues
- Execute business actions
- Resolve authenticated users

It should only:

- Merge configuration
- Bind interfaces
- Register singletons
- Register adapters
- Register internal module providers
- Register deferred registries

`boot()` can load:

- Routes
- Migrations
- Views
- Translations
- Commands
- Middleware aliases
- Events and listeners
- Menu definitions
- AI tools
- Scheduled operations

---

# 5. Provider loading order

The cached provider manifest contains 73 provider entries.

The application-level providers appear in this order:

```text
App\Providers\AppServiceProvider
App\Providers\AuthServiceProvider
App\Providers\BroadcastServiceProvider
App\Providers\EventServiceProvider
App\Providers\RouteServiceProvider
App\Providers\ViewServiceProvider
App\Providers\MacrosServiceProvider
App\Providers\AwsServiceProvider
App\Domains\Entity\EntityServiceProvider
App\Domains\Engine\EngineServiceProvider
App\Domains\Marketplace\MarketplaceServiceProvider
Elseyyid\LaravelJsonLocationsManager\Providers\LaravelJsonLocationsManagerServiceProvider
Barryvdh\DomPDF\ServiceProvider
Igaster\LaravelTheme\themeServiceProvider
```

The cached manifest also contains package-discovered providers for Passport, Sanctum, Socialite, Cashier, Livewire, Octane, Sentry, Spatie Permission, Spatie Health, Xero, Telegram, OpenAI Laravel, Installer, Localisation, DataTables, PDF rendering, Themes and other support packages.

## 5.1 Duplicate provider declarations

The provider manifest contains duplicate declarations for:

- `Spatie\Permission\PermissionServiceProvider`
- `Barryvdh\DomPDF\ServiceProvider`
- `Igaster\LaravelTheme\themeServiceProvider`

Laravel generally avoids registering the same provider class twice, but these duplicates indicate configuration drift between package auto-discovery and manual provider registration.

They should be removed during host cleanup to make provider ordering easier to reason about.

---

# 6. Core application provider

`App\Providers\AppServiceProvider` is MagicAI's main runtime bootstrap provider.

## 6.1 Register phase

It registers one confirmed singleton:

```php
UGCSourceRegistry::class
```

## 6.2 Boot phase

The boot sequence is:

```text
Force HTTPS in production
→ set language path
→ test database connection
→ set default string length
→ cache table inventory
→ cache AI chat plan models
→ load settings from database
→ mutate Pusher configuration
→ mutate reCAPTCHA configuration
→ mutate SMTP configuration
→ normalise and run queue work
→ set application locale
→ register health checks
→ register Blade directives
→ register model observers
```

## 6.3 Database-dependent boot

When a database connection exists, MagicAI performs database-dependent work before normal request handling.

It creates `magicai_tables` as a container singleton containing the database table inventory. It also creates `ai_chat_model_plan` as a singleton based on database records.

### WorkCore implication

WorkCore boots after `AppServiceProvider`, so it may safely resolve `magicai_tables` during its own `boot()` phase.

However, WorkCore must not resolve it during `register()`, and must not assume all WorkCore migrations have run.

Any WorkCore boot code that depends on a table must first verify that the table exists.

---

# 7. Critical queue-runtime defect

MagicAI executes this logic during `AppServiceProvider::boot()`:

```php
DB::table('jobs')
    ->where('queue', '<>', 'default')
    ->update(['queue' => 'default']);

Artisan::call('queue:work --once');
```

This means that whenever the application boots and the `jobs` table exists, MagicAI:

1. Changes every non-default queued job to the `default` queue.
2. Runs one queue job immediately.

Because application providers boot during both HTTP and console execution, this can occur during page requests, API requests, Artisan commands, migration commands, cache commands, scheduler execution and extension installation.

The code catches all exceptions silently.

## 7.1 Why this blocks WorkCore

WorkCore requires separated operational queues such as:

```text
workcore-critical
workcore-actions
workcore-outbox
workcore-sync
workcore-notifications
workcore-media
workcore-ai
workcore-reports
```

MagicAI's current boot behaviour would collapse all of them into `default`.

That would destroy priority ordering, queue isolation, independent worker scaling, retry policies, slow-job separation, offline-sync processing, event outbox guarantees, incident escalation priority and AI workload isolation.

It also makes request latency unpredictable and hides worker failures because exceptions are swallowed.

## Required correction

Before WorkCore queues are enabled:

1. Remove queue processing from `AppServiceProvider::boot()`.
2. Remove the blanket queue-name rewrite.
3. Run queue workers using Supervisor, systemd, Horizon-compatible workers or container orchestration.
4. Preserve the original queue name on every job.
5. Configure queue-specific retries, timeouts and worker counts.
6. Add failed-job monitoring.
7. Add WorkCore correlation and action IDs to queued work.

This is a **mandatory pre-integration fix**.

---

# 8. Dynamic configuration during boot

MagicAI loads settings from the database and mutates Laravel configuration at runtime.

Confirmed dynamically set configuration includes Pusher credentials, reCAPTCHA credentials, SMTP settings and mail sender identity.

## WorkCore implication

WorkCore integrations that use mail, broadcast or storage configuration should resolve configuration at execution time rather than capturing it too early.

Long-running queue workers may need restarting when these settings change.

WorkCore should emit configuration-change events or provide a controlled worker restart process.

---

# 9. AI Engine provider

`EngineServiceProvider` registers `App\Domains\Engine\Engine` as a singleton and aliases it as `ai.engine`.

During boot, it loops through `EngineEnum` and registers each engine driver.

## WorkCore use

WorkCore should resolve the engine manager rather than instantiate provider clients directly. This allows WorkCore to inherit provider configuration, model availability, provider status, provider-specific adapters and MagicAI upgrades to engine drivers.

---

# 10. AI Entity provider

`EntityServiceProvider` registers `App\Domains\Entity\EntityManager` as a singleton and aliases it as `ai.entity`.

During boot, it registers a collection mixin and every declared entity driver.

The Entity system represents text, reasoning, vision, speech, embeddings, image generation, video generation, music, search and provider-specific cost handling.

## WorkCore use

WorkCore should use the Entity manager for model selection, capability discovery, cost estimation, credit charging, BYO key routing and feature availability.

WorkCore should not introduce a duplicate AI model registry unless local or private models cannot be represented by MagicAI's Entity system.

---

# 11. Marketplace provider lifecycle

`MarketplaceServiceProvider` is the main extension host.

It declares `public static array $extensionProviders` with 114 optional provider mappings and binds `ExtensionRepositoryInterface` to `ExtensionRepository`.

## 11.1 Register phase

Its `register()` method executes `extensionProviderRegister()`.

For every configured extension provider:

```php
if (class_exists($provider)) {
    $this->app->register($provider);
}
```

Missing extension classes are ignored.

## 11.2 Boot phase

Its `boot()` method registers Marketplace installation and uninstallation routes.

## 11.3 WorkCore insertion point

The current vendor-compatible registration is:

```php
'workcore' => WorkCoreServiceProvider::class,
'ai-agent-tool-workcore' => AIAgentToolWorkCoreServiceProvider::class,
```

The provider classes should live under:

```text
app/Extensions/WorkCore/System/
app/Extensions/AIAgentToolWorkCore/System/
```

## 11.4 Provider-order advantage

MagicAI registers the Entity and Engine managers before it registers Marketplace extensions.

Under the normal Laravel provider lifecycle:

```text
Entity provider register
→ Engine provider register
→ Marketplace provider register
→ WorkCore provider register
→ boot all providers
```

This gives WorkCore access to registered AI manager bindings during its register phase, and fully booted AI drivers by the time WorkCore's own boot phase runs.

---

# 12. Dormant native extension provider

The package contains `app/Providers/ExtensionServiceProvider.php`, which would register `App\Providers\ChatbotServiceProvider`.

However, `ExtensionServiceProvider` is absent from `config/app.php`, `bootstrap/cache/services.php` and other provider-registration calls. It is therefore not active in this build.

## 12.1 Dormant chatbot provider

The package also contains `app/Providers/ChatbotServiceProvider.php`. It defines a `chatbot_api` middleware group and public chatbot routes, but because its parent provider is not registered, these routes are not activated by the supplied core package.

The optional paid Chatbot extension has a separate provider namespace: `App\Extensions\Chatbot\System\ChatbotServiceProvider`.

## Corrected capability classification

The core package contains dormant chatbot transport code, but the active chatbot runtime is not proven without registering the native provider or installing the separate Chatbot extension.

This is an example of why source presence alone must not be counted as active capability.

---

# 13. HTTP kernel

MagicAI's HTTP kernel declares a global middleware stack, a `web` middleware group, an `api` middleware group, modern middleware aliases and additional legacy route aliases.

## 13.1 Global middleware

Every request passes through:

```text
TrustProxies
RefererMiddleware
HandleCors
PreventRequestsDuringMaintenance
ValidatePostSize
TrimStrings
ConvertEmptyStringsToNull
```

`TrustHosts` is disabled.

MagicAI trusts all proxies. WorkCore should not derive security-sensitive company or callback identity from forwarded headers without validation.

The referer middleware can write a referral record before authentication, creating database activity on first visits.

## 13.2 Web middleware group

Web requests receive:

```text
EncryptCookies
AddQueuedCookiesToResponse
StartSession
ShareErrorsFromSession
ApplicationCheckLicense
ApplicationStatus
VerifyCsrfToken
SubstituteBindings
LocaleMiddleware
ThemeMiddleware
```

## 13.3 API middleware group

API requests receive:

```text
ThrottleRequests:api
SubstituteBindings
```

The API limiter allows 60 requests per minute and keys the limit by authenticated user ID or request IP.

The API group itself is not authenticated. Protected routes explicitly use `auth:api`.

## WorkCore API rule

Every WorkCore API route must explicitly use `auth:api`, `ResolveWorkCoreCompany` and `EnsureWorkCoreCapability`. Replayable writes should also require an idempotency key.

---

# 14. Critical CSRF finding

MagicAI's `VerifyCsrfToken` middleware excludes:

```text
dashboard/*
```

from CSRF verification.

The exclusion applies to all dashboard requests, including authenticated write actions.

## 14.1 WorkCore impact

If WorkCore uses dashboard routes, its browser-based write actions would inherit the dashboard-wide CSRF exemption.

That is unsafe for creating jobs, assigning workers, changing schedules, approving timesheets, recording incidents, issuing purchase orders, preparing invoices and changing company settings.

## Required correction

Before WorkCore write routes are enabled:

1. Remove the blanket `dashboard/*` exemption where practical.
2. Replace it with narrowly scoped exemptions for endpoints that genuinely cannot use CSRF tokens.
3. Verify every existing dashboard form and JavaScript request includes a valid CSRF token.
4. Add explicit WorkCore request-forgery tests.
5. Require API authentication and idempotency for device and external integrations.

If the host exemption cannot immediately be removed, WorkCore must add a second explicit CSRF-validation middleware to its browser write routes.

This is a **mandatory security prerequisite**.

---

# 15. Route loading architecture

`RouteServiceProvider` loads only two root files directly:

```text
routes/api.php
routes/web.php
```

API routes are loaded under the `api` prefix and middleware group.

Web routes are loaded with the `web` middleware group plus `ViewSharedMiddleware` and `NewExtensionInstalled`.

`routes/web.php` includes:

```text
routes/auth.php
routes/panel.php
routes/webhooks.php
```

It optionally includes `routes/custom_routes_web.php`.

`routes/panel.php` optionally includes `routes/custom_routes_panel.php` and every file under `routes/extroutes/*.php`.

`routes/auth.php` optionally includes `routes/custom_routes_auth.php`.

## Runtime implication

There are three custom single-file hooks and one multi-file extension route directory:

```text
custom_routes_web.php
custom_routes_panel.php
custom_routes_auth.php
routes/extroutes/*.php
```

The `extroutes` directory is the safer legacy hook because multiple extension files can coexist. However, provider-owned route loading is cleaner for WorkCore.

## Recommended WorkCore route structure

```text
app/Extensions/WorkCore/Routes/web.php
app/Extensions/WorkCore/Routes/api.php
app/Extensions/WorkCore/Routes/admin.php
app/Extensions/WorkCore/Routes/field.php
```

Do not copy WorkCore routes into the global panel route file.

---

# 16. Panel runtime

The main panel route group uses `auth` and `updateUserActivity`, with the `dashboard` prefix and `dashboard.` route-name namespace.

Panel routes inherit web sessions, licence checks, application status, theme, locale, authentication and user activity tracking.

## WorkCore route recommendation

Use route families such as:

```text
dashboard.user.operations.*
dashboard.user.workforce.*
dashboard.user.resources.*
dashboard.user.commercial.*
dashboard.admin.workcore.*
```

This aligns WorkCore with MagicAI's menu and entitlement conventions.

---

# 17. View-sharing middleware

`ViewSharedMiddleware` runs on every web route. For authenticated users it calculates and caches total words, documents, text documents and image documents.

As WorkCore increases page volume, this global middleware may impose unnecessary work when cache entries expire. WorkCore should not add further global database aggregates to this middleware.

---

# 18. New-extension middleware

`NewExtensionInstalled` runs on every web request. When the `new_extension_installed` cache flag is set, it executes:

```text
optimize:clear
cache:clear
migrate --force
vendor:publish --tag=extension --force
```

The new `ExtensionInstallService` already runs the menu seeder, cache clearing, migrations and extension publishing before setting the flag.

## 18.1 Duplicate installation work

The next web request can therefore execute much of the same installation work again.

For WorkCore this increases install time, delays the first user request, creates concurrency risk and makes failure reporting unclear.

## Required correction

WorkCore installation should use one controlled deployment transaction:

```text
Preflight
→ maintenance mode
→ backup
→ extract extension
→ register provider
→ run migrations once
→ seed menus and permissions
→ publish assets once
→ clear caches
→ validate health
→ exit maintenance mode
```

The first ordinary web request should not be responsible for completing installation.

---

# 19. Administrator permission runtime

`AdminPermissionMiddleware` loads administrator role permissions, generates the menu, converts permitted menu keys into allowed route patterns and only allows matching named routes.

## WorkCore implication

For administrator routes, WorkCore menu keys are part of the authorisation model.

A WorkCore admin route can return `401` when its menu entry is missing, its active-route pattern is wrong, its menu key is not attached to the role or the menu cache is stale.

WorkCore must register administrator menus, permission keys, active route patterns, role assignments and menu cache regeneration. This relationship requires integration tests.

---

# 20. Plan entitlement runtime

`CheckTemplateTypeAndPlan` supports administrator bypass, team-member inheritance, exact route mappings, wildcard route-prefix mappings, free-feature checks, premium-feature checks, global feature switches and plan item checks.

CRM already maps:

```text
dashboard.user.crm.* → ext_crm_dropdown
dashboard.user.sales.* → ext_sales_dropdown
```

Recommended WorkCore mappings are:

```text
dashboard.user.operations.* → ext_workcore_operations
dashboard.user.workforce.* → ext_workcore_workforce
dashboard.user.resources.* → ext_workcore_resources
dashboard.user.commercial.* → ext_workcore_commercial
```

Plan access is not operational permission. WorkCore must apply its own company-scoped capability middleware after MagicAI plan checks.

---

# 21. Authentication runtime

MagicAI's authentication middleware applies Google 2FA after user authentication. WorkCore routes that use `auth` automatically inherit this behaviour.

The authenticated MagicAI user should become the WorkCore actor, but not the WorkCore tenant.

The complete request context should be:

```text
MagicAI authenticated user
+ active WorkCore company
+ WorkCore company membership
+ operational role
+ branch or territory
+ granted capabilities
```

---

# 22. Locale and theme runtime

MagicAI loads locale from database settings and can override it per request. Theme middleware selects the front or dashboard theme based on request path.

WorkCore views should use MagicAI's active theme and an extension view namespace. Avoid hard-coding paths into the default theme.

Use:

```php
$this->loadViewsFrom($path, 'workcore');
```

and render `workcore::...` views.

---

# 23. Events and listeners

MagicAI's core Event Service Provider uses explicit event-listener mappings and disables automatic event discovery.

WorkCore listeners will not be discovered automatically. WorkCore must explicitly register them through its own event provider or module providers.

Recommended event families include company, membership, customer, service site, work order, schedule, dispatch, workforce, compliance, incident, inventory, commercial, offline sync, integration and audit.

Do not modify MagicAI's central Event Service Provider for every WorkCore event.

---

# 24. Broadcasting

MagicAI registers broadcasting routes and loads `routes/channels.php`.

Potential WorkCore private channels include:

```text
company.{companyId}
work-order.{workOrderId}
worker.{workerId}
dispatch.{companyId}
incident.{incidentId}
```

Every channel authorisation callback must verify company membership and operational capability.

---

# 25. Exception handling

MagicAI sends unhandled exceptions to Sentry, special-cases two MagicAI API exception types and uses Laravel's default renderer for everything else.

WorkCore should define stable domain exception categories for validation, permission, tenancy, conflict, idempotency, approval, offline conflict and external integration failures.

Every WorkCore error response should include an error code, safe message, correlation ID and retry guidance where applicable.

Sensitive operational data must not be added to Sentry without filtering.

---

# 26. Response macros

MagicAI registers simple `response()->success()` and `response()->error()` macros.

WorkCore can reuse them for basic responses, but governed actions need a richer envelope containing correlation ID, action ID, company ID and version metadata.

Approval-required responses should return a structured proposal and confirmation token.

---

# 27. View service provider

MagicAI's view provider shares application state, settings and frontend data and registers `PlanComposer` for navigation views.

It depends on `AppServiceProvider` having registered `magicai_tables`.

WorkCore should not replace or reorder `AppServiceProvider`. WorkCore menu and plan integration should happen after MagicAI's table and settings foundations are available.

---

# 28. Blade directives

MagicAI registers custom Blade directives for number formatting, short number formatting, credit display and push-once behaviour.

WorkCore can use these directives but should not couple domain logic to view directives. WorkCore-specific directives should be registered from the WorkCore provider and clearly namespaced.

---

# 29. Model observers

MagicAI registers observers for settings, frontend configuration, generators, advertisements and users.

WorkCore should register observers only for integration-bound records. Core operational consequences should use explicit domain services and events rather than hidden Eloquent observer side effects.

---

# 30. Health checks

MagicAI registers checks for debug mode, environment, database and memory limit.

WorkCore should add migration state, outbox backlog, failed actions, queue worker health, offline sync backlog, storage, AI provider availability, accounting integration and notification delivery health.

---

# 31. Console scheduler

The Console Kernel schedules payment checks, subscription checks, cleanup commands, a crontab heartbeat and a no-op test command.

It also checks for `app/Console/CustomScheduler.php`, which is absent in the supplied package.

WorkCore should not depend on a single shared CustomScheduler file. Register WorkCore scheduling through its provider or a dedicated console provider.

Recommended schedules include outbox dispatch, recurring work-order generation, certification expiry checks, roster validation, inventory reorder checks, invoice-ready projection, offline reconciliation, attachment recovery, retention enforcement and Rewind verification.

---

# 32. Extension installation architecture

MagicAI contains two extension-installation generations.

## 32.1 New provider-based installer

The new installer checks dependencies and versions, downloads and extracts the ZIP under `app/Extensions`, runs menu seeding, clears caches, runs migrations, publishes extension assets and records installation state.

This is the architecture WorkCore should use.

## 32.2 Legacy copying installer

The older installer can execute raw SQL, copy routes into `routes/extroutes`, copy controllers into host paths, copy arbitrary stubs into base paths and delete files during uninstall.

This architecture causes core drift and difficult upgrades.

## WorkCore rule

WorkCore must not use the legacy installer. It should not copy controllers, models or routes into MagicAI core, execute raw install SQL or delete shared host files during uninstall.

All implementation should remain inside `app/Extensions/WorkCore`.

---

# 33. Extension installation authorisation risk

Marketplace installation and uninstallation routes use `web` and `auth` but do not explicitly require administrator privilege. Legacy installation routes also do not declare `auth` or `admin` at the route definition, and their controller methods primarily check demo mode.

Before WorkCore is distributed through this system:

1. Require authentication.
2. Require super-administrator privilege.
3. Require CSRF for browser actions.
4. Use POST or DELETE rather than GET.
5. Record audit details.
6. Require recent-password or step-up confirmation.
7. Block concurrent installs.
8. Verify package integrity.
9. Verify the expected provider and manifest before extraction.

---

# 34. Filesystem architecture

The extension filesystem disk points to `app/Extensions`.

This is suitable for App-namespace autoloading because Composer maps `App\` to `app/`.

An extracted `app/Extensions/WorkCore/System/WorkCoreServiceProvider.php` can therefore autoload without adding a new PSR-4 root.

---

# 35. Package and provider cache state

The package includes generated files under `bootstrap/cache`, and the Blade icon cache contains an absolute path from a developer workstation.

Before installing WorkCore or deploying MagicAI, delete generated bootstrap caches, run Composer installation and package discovery, clear optimisation state and rebuild caches on the target host.

Do not ship development-machine provider or asset caches as authoritative runtime state.

---

# 36. WorkCore service-provider design

Recommended root provider:

```text
App\Extensions\WorkCore\System\WorkCoreServiceProvider
```

## 36.1 Register responsibilities

The register phase should merge WorkCore configuration, bind contracts, register company context, permissions, action dispatch, outbox, audit and MagicAI adapters, and register internal WorkCore domain providers.

Recommended bindings include:

```text
WorkCoreTenantResolver
WorkCoreActorResolver
WorkCorePermissionResolver
WorkCoreStorageAdapter
WorkCoreNotificationAdapter
WorkCoreAIEngineAdapter
WorkCoreCreditAdapter
WorkCoreMenuRegistry
WorkCoreActionRegistry
WorkCoreReadModelRegistry
WorkCoreIntegrationRegistry
```

## 36.2 Boot responsibilities

The boot phase should load migrations, routes, views, translations, commands, schedules, middleware aliases, events, menus, plan features and AI tools.

## 36.3 No-boot-side-effect rule

WorkCore boot must not start workers, dispatch actions, modify queue names, run migrations on every request, perform network calls, depend on the current user, repeatedly seed data or drop business tables.

---

# 37. Internal WorkCore providers

The umbrella provider should register bounded providers such as:

```text
WorkCoreCoreServiceProvider
BusinessNetworkServiceProvider
WorkOperationsServiceProvider
PropertyOperationsServiceProvider
WorkforceAssuranceServiceProvider
CommercialServiceProvider
WorkCoreIntelligenceServiceProvider
WorkCoreOfflineServiceProvider
WorkCoreIntegrationServiceProvider
WorkCoreVerticalPackServiceProvider
```

MagicAI should see one installable extension while WorkCore retains internal modularity.

---

# 38. Required WorkCore middleware

Recommended middleware chain:

```text
auth
→ MagicAI plan entitlement
→ resolve active WorkCore company
→ verify company membership
→ verify operational capability
→ enforce idempotency for replayable writes
→ enforce approval policy where required
→ execute controller or action
```

Recommended classes:

```text
ResolveActiveWorkCoreCompany
EnsureWorkCoreMembership
EnsureWorkCoreCapability
RequireWorkCoreIdempotencyKey
RequireWorkCoreApproval
AttachWorkCoreCorrelationId
```

Field and device APIs should also verify registered device, revocation state, sync protocol version, offline batch signature and attachment integrity.

---

# 39. WorkCore route registration

Suggested route groups:

```text
/dashboard/user/operations
/dashboard/user/workforce
/dashboard/user/resources
/dashboard/user/commercial
/dashboard/admin/workcore
/api/v1/workcore
/api/v1/workcore/webhooks/{provider}
/portal/workcore
```

Every route family must use explicit middleware and named-route conventions.

---

# 40. WorkCore migration strategy

WorkCore migrations should remain normal Laravel migration classes under:

```text
app/Extensions/WorkCore/Database/Migrations
```

The provider should load them with `loadMigrationsFrom()`.

Installation should run migrations once in a controlled deployment step.

Disabling or uninstalling WorkCore must preserve business data by default. A destructive purge must be separate, explicit, super-admin only, confirmed, audited and backup-aware.

---

# 41. WorkCore event strategy

Because core event discovery is disabled, WorkCore should explicitly map events through its own provider structure.

Integration events should use the outbox pattern rather than direct fragile cross-system calls.

---

# 42. WorkCore AI integration timing

Because the AI Entity and Engine providers are registered before Marketplace extensions, WorkCore can bind to `ai.engine` and `ai.entity`, register tools during boot and resolve model capabilities at action time.

Tool flow:

```text
MagicAI AI Agent
→ WorkCore tool schema
→ resolve company and actor
→ permission check
→ validation
→ approval policy
→ business action dispatcher
→ domain event
→ outbox and audit
→ structured tool result
```

---

# 43. Upgrade-safe insertion alternatives

## Option A — Marketplace registry entry

Add:

```php
'workcore' => WorkCoreServiceProvider::class,
```

Advantages: matches MagicAI's architecture, supports Marketplace state, keeps WorkCore optional and requires only a minimal host change.

Disadvantage: vendor updates can overwrite the registration.

**Assessment: recommended initial approach.** Maintain the one-line registration as a tracked compatibility patch with an automated verification test.

## Option B — Add WorkCore directly to `config/app.php`

This is simple but always loads WorkCore, bypasses Marketplace state and creates a larger upgrade conflict.

**Assessment: not recommended.**

## Option C — Composer package auto-discovery

This avoids a manual provider entry but requires Composer-based deployment and bypasses Marketplace installation state.

**Assessment: useful for development or enterprise distribution, but not the primary MagicAI extension path.**

## Option D — Generic external provider manifest

Patch MagicAI once so Marketplace reads additional provider mappings from a configuration file or extension manifests.

This removes future registry edits and supports WorkCore vertical packs cleanly.

**Assessment: recommended long-term architecture for the Titan Zero fork.** Initial implementation can use Option A, then migrate to Option D.

---

# 44. Required host corrections before WorkCore activation

## Critical

1. Remove queue execution from application boot.
2. Preserve named queues.
3. Harden CSRF for dashboard writes.
4. Restrict extension installation and removal to super administrators.
5. Stop migration and publishing work from repeating on the next web request.
6. Sanitize provider and package caches.
7. Complete Laravel 10 compatibility work for WorkCore.

## High

8. Add integration tests for provider loading.
9. Add route and middleware tests.
10. Add tenant-resolution tests.
11. Add permission and entitlement tests.
12. Add installation rollback.
13. Add extension package integrity verification.
14. Add reliable exception and correlation handling.
15. Remove or protect unauthenticated provider-cache deletion.

## Medium

16. Remove duplicate provider declarations.
17. Remove the no-op test command from the per-minute scheduler.
18. Reduce global view-query overhead.
19. Replace legacy extension file copying for new extensions.
20. Generate fresh provider caches on deployment.

---

# 45. Recommended implementation sequence

```text
1. Build MagicAI runtime compatibility test harness
2. Fix queue boot behaviour
3. Harden dashboard CSRF
4. Harden extension install permissions
5. Add WorkCore provider registry entry
6. Port WorkCore contracts and shared kernel to Laravel 10
7. Register WorkCore company and actor context
8. Load WorkCore migrations
9. Add WorkCore route groups and middleware
10. Add menu and entitlement registration
11. Add governed action dispatcher
12. Add events, outbox and audit
13. Add AI Engine and Entity adapters
14. Add AI Agent tool provider
15. Add Operations Assistant
16. Add internal WorkCore domain modules
17. Add installation and upgrade tests
18. Add rollback and data-preservation tests
```

---

# 46. Decisions established by Scan 02

1. WorkCore will integrate through a native service provider.
2. The initial provider registration will use the Marketplace registry.
3. WorkCore implementation will remain under `app/Extensions/WorkCore`.
4. WorkCore will not use the legacy extension file-copy installer.
5. WorkCore will not register through `config/app.php` unless Marketplace loading proves impossible.
6. WorkCore will retain its own company context and operational permissions.
7. WorkCore will reuse MagicAI's AI Engine and Entity managers.
8. WorkCore routes will be provider-owned.
9. WorkCore events will be explicitly registered.
10. WorkCore queues will remain separated by purpose.
11. MagicAI's boot-time queue behaviour must be removed before operational queues launch.
12. WorkCore browser writes require explicit CSRF protection.
13. Extension install and uninstall must become super-admin operations.
14. Extension migrations will run once through a controlled deployment process.
15. WorkCore uninstall will preserve business data by default.
16. The dormant native Chatbot provider will not be counted as an active capability.

---

# 47. Next scan

The next report should be:

```text
03-magicai-extension-marketplace-runtime.md
```

It should map extension package folder structure, ZIP extraction layout, provider naming conventions, Marketplace database records, manifests, parent-child dependencies, minimum-version checks, installation routes, permissions, migration loading, asset publishing, menu seeding, upgrades, uninstallation, legacy/current installer paths, file collision risks, data preservation, WorkCore manifests, vertical-pack dependencies and package integrity.

---

# Appendix A — Application provider order

```text
59  App\Providers\AppServiceProvider
60  App\Providers\AuthServiceProvider
61  App\Providers\BroadcastServiceProvider
62  App\Providers\EventServiceProvider
63  App\Providers\RouteServiceProvider
64  App\Providers\ViewServiceProvider
65  App\Providers\MacrosServiceProvider
66  App\Providers\AwsServiceProvider
67  App\Domains\Entity\EntityServiceProvider
68  App\Domains\Engine\EngineServiceProvider
69  App\Domains\Marketplace\MarketplaceServiceProvider
70  Elseyyid\LaravelJsonLocationsManager\Providers\LaravelJsonLocationsManagerServiceProvider
71  Barryvdh\DomPDF\ServiceProvider
72  Igaster\LaravelTheme\themeServiceProvider
```

---

# Appendix B — Global HTTP middleware

```text
TrustProxies
RefererMiddleware
HandleCors
PreventRequestsDuringMaintenance
ValidatePostSize
TrimStrings
ConvertEmptyStringsToNull
```

---

# Appendix C — Web middleware group

```text
EncryptCookies
AddQueuedCookiesToResponse
StartSession
ShareErrorsFromSession
ApplicationCheckLicense
ApplicationStatus
VerifyCsrfToken
SubstituteBindings
LocaleMiddleware
ThemeMiddleware
```

---

# Appendix D — API middleware group

```text
ThrottleRequests:api
SubstituteBindings
```

---

# Appendix E — Middleware aliases

```text
auth
auth.basic
auth.session
cache.headers
can
guest
password.confirm
signed
throttle
verified
admin
is_not_demo
newExtensionInstalled
localize
localizationRedirect
localeSessionRedirect
localeCookieRedirect
localeViewPath
checkInstallation
custom
updateUserActivity
sentry.context
surveyMiddleware
```

---

# Appendix F — Customisation hooks found

```text
routes/custom_routes_web.php
routes/custom_routes_auth.php
routes/custom_routes_panel.php
routes/extroutes/*.php
app/Console/CustomScheduler.php
MarketplaceServiceProvider::$extensionProviders
vendor:publish tag extension
MenuSeeder
MagicAI menu service
MagicAI plan feature mapping
```

The custom route and scheduler files were not present in the supplied package.

---

# Appendix G — Primary evidence files

```text
public/index.php
artisan
bootstrap/app.php
bootstrap/cache/services.php
config/app.php
config/filesystems.php
composer.json
app/Http/Kernel.php
app/Console/Kernel.php
app/Exceptions/Handler.php
app/Providers/AppServiceProvider.php
app/Providers/AuthServiceProvider.php
app/Providers/BroadcastServiceProvider.php
app/Providers/EventServiceProvider.php
app/Providers/RouteServiceProvider.php
app/Providers/ViewServiceProvider.php
app/Providers/MacrosServiceProvider.php
app/Providers/AwsServiceProvider.php
app/Providers/ExtensionServiceProvider.php
app/Providers/ChatbotServiceProvider.php
app/Domains/Entity/EntityServiceProvider.php
app/Domains/Engine/EngineServiceProvider.php
app/Domains/Marketplace/MarketplaceServiceProvider.php
app/Domains/Marketplace/Services/ExtensionInstallService.php
app/Domains/Marketplace/Services/ExtensionUninstallService.php
app/Domains/Marketplace/Http/Middleware/NewExtensionInstalled.php
app/Services/Extension/ExtensionService.php
app/Services/Extension/Traits/InstallExtension.php
app/Services/Extension/Traits/UninstallExtension.php
routes/web.php
routes/api.php
routes/auth.php
routes/panel.php
routes/webhooks.php
routes/console.php
```

---

# Appendix H — Evidence limitations

This report establishes static boot and runtime architecture from the supplied source.

It does not yet prove behaviour with Composer dependencies installed, exact runtime provider order under every deployment cache state, successful paid-extension installation, extension package integrity, migration rollback, concurrent installation safety, Laravel Octane compatibility, production queue-worker behaviour, frontend compatibility after CSRF hardening or CRM provider behaviour because the CRM source is absent.

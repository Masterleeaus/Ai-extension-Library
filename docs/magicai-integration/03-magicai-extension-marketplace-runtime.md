---
title: "MagicAI 11 Extension and Marketplace Runtime"
scan_number: 3
date: "2026-08-02"
magicai_version: "11.00"
status: "Completed"
scope: "Magicai-Server-Files(3).zip and WorkCore-FULL-MERGED-CLEANED-PASS3-DEEP.zip"
repository_target: "Masterleeaus/Documents"
repository_path: "workcore/magicai-integration/03-magicai-extension-marketplace-runtime.md"
---

# Scan 03 — MagicAI 11 Extension and Marketplace Runtime

## Executive conclusion

MagicAI 11 contains a capable **extension host**, but it does not yet contain a safe, complete extension package manager suitable for installing the full WorkCore operational system without modification.

The marketplace runtime provides:

- A hard-coded extension-to-service-provider registry
- Conditional provider loading through `class_exists()`
- A marketplace database table
- Remote extension catalogue and licensing calls
- ZIP download and extraction
- Minimum MagicAI version checks
- One-level parent-extension dependency checks
- Migration execution
- Asset publishing
- Menu reseeding
- Static extension uninstall hooks
- A legacy extension installer for older packages

This is enough to establish WorkCore as a native MagicAI extension.

It is **not** enough to safely operate WorkCore unchanged because the current runtime has major weaknesses:

1. Installation state is split between database flags, provider classes, filesystem presence and loaded-provider state.
2. The database `installed` flag does not control runtime activation.
3. Extension ZIPs are extracted directly into the live application without signature, checksum or path validation.
4. Installation is non-atomic and has no rollback transaction.
5. Provider-owned migrations and assets may not be registered until the next request.
6. A global 60-second cache flag attempts to complete installation on the next web request.
7. If no web request arrives before the flag expires, provider-owned installation work may never complete.
8. Installation and publishing work can run twice.
9. The installer can report success even when the ZIP was not successfully opened or extracted.
10. Extension install and uninstall routes are not consistently protected as super-administrator actions.
11. Uninstall deletes the extension code directory but provides no safe data-retention contract.
12. Dependencies are checked during install but not protected during uninstall.
13. Upgrades extract over the existing directory and can leave stale files.
14. The global `vendor:publish --tag=extension --force` operation can republish or overwrite assets from unrelated extensions.
15. The legacy installer can execute raw SQL and copy or delete arbitrary host files.
16. There is no first-class manifest schema, dependency graph, package integrity check, install journal, migration ownership, health validation or rollback.

The recommended architecture is:

> Use MagicAI's provider registry and marketplace presentation layer, but introduce a hardened Titan extension installer for WorkCore and future Titan packs.

WorkCore should be packaged as one umbrella extension with internally modular domains. Vertical packs and AI tool packs can be separate child extensions that declare WorkCore as their parent.

---

# 1. Scan scope

This scan inspected:

- Marketplace service provider
- Extension provider registry
- Runtime activation logic
- Marketplace repository
- Marketplace database model and migrations
- Remote vendor API calls
- Modern install service
- Modern uninstall service
- Installation routes and controllers
- Marketplace user interface and JavaScript
- Extension filesystem configuration
- New-extension middleware
- Menu reseeding
- Migration and asset publishing behaviour
- Parent-extension checks
- Minimum-version checks
- Upgrade behaviour
- Legacy extension installation and removal
- Raw SQL and file-copy behaviour
- WorkCore's current service provider and package structure
- Existing extension metadata examples from earlier supplied archives

The scan distinguishes confirmed code behaviour from recommended WorkCore architecture.

---

# 2. Two extension systems coexist

MagicAI 11 contains two distinct extension generations.

## 2.1 Modern provider-based extension system

The modern system installs extension source under:

```text
app/Extensions/
```

It expects provider classes referenced by:

```php
MarketplaceServiceProvider::$extensionProviders
```

Typical modern package structure is:

```text
app/Extensions/ExtensionName/
├── extension.json
├── System/
│   └── ExtensionNameServiceProvider.php
├── config/
├── database/
│   └── migrations/
├── resources/
│   ├── views/
│   ├── lang/
│   ├── js/
│   └── css/
├── routes/
└── public/
```

This is the correct architectural generation for WorkCore.

## 2.2 Legacy host-copying extension system

The legacy installer uses an `index.json` manifest under:

```text
resources/extensions/{slug}/index.json
```

It can:

- Extract into a shared temporary folder
- Execute raw install SQL
- Execute raw uninstall SQL
- Copy route files into `routes/extroutes`
- Copy controllers into host application folders
- Copy arbitrary stubs to base application paths
- Delete listed host files during uninstall
- Apply old-version cleanup instructions

This creates direct host drift and weak ownership boundaries.

WorkCore must **not** use the legacy installer.

---

# 3. Modern provider registry

MagicAI's extension host is:

```text
App\Domains\Marketplace\MarketplaceServiceProvider
```

It maintains:

```php
public static array $extensionProviders
```

The registry contains 114 extension slugs mapped to provider classes.

Provider registration is:

```php
foreach (self::$extensionProviders as $provider) {
    if (class_exists($provider)) {
        $this->app->register($provider);
    }
}
```

## 3.1 Meaning of the registry

The registry provides:

- Known extension slugs
- Expected provider class names
- Conditional provider loading
- A stable hook for optional modules
- Safe host boot when a provider class is absent

It does not provide:

- Dynamic provider discovery from manifests
- Dependency resolution
- Provider version resolution
- Package signature validation
- Extension state reconciliation
- Provider isolation
- Sandbox execution

## 3.2 WorkCore registration

The initial WorkCore mappings should be:

```php
'workcore' => WorkCoreServiceProvider::class,
'ai-agent-tool-workcore' => AIAgentToolWorkCoreServiceProvider::class,
```

Potential child registrations include:

```php
'workcore-vertical-cleaning'
    => WorkCoreCleaningServiceProvider::class,

'workcore-vertical-property'
    => WorkCorePropertyServiceProvider::class,

'workcore-vertical-home-services'
    => WorkCoreHomeServicesServiceProvider::class,
```

Every child provider currently requires a hard-coded registry entry.

## 3.3 Long-term improvement

The Titan Zero fork should replace repeated hard-coded imports with a validated local provider manifest, for example:

```text
config/titan-extensions.php
```

or signed extension manifests discovered from approved extension directories.

The loader must only accept allowlisted namespaces and validated provider declarations.

---

# 4. Runtime activation semantics

MagicAI uses several separate indicators of extension state.

## 4.1 Database state

The `extensions` table stores:

```text
id
version
slug
installed
is_theme
created_at
updated_at
```

Earlier descriptive columns were added and later removed.

There is no confirmed unique database constraint on:

```text
slug + is_theme
```

The repository tries to identify records by that pair in application logic.

## 4.2 Filesystem state

Modern extension files live under:

```text
app/Extensions/{extension_folder}
```

If the provider class exists in that directory and is autoloadable, it may be loaded.

## 4.3 Registry state

The slug and expected provider class must be present in:

```php
MarketplaceServiceProvider::$extensionProviders
```

## 4.4 Loaded-provider state

`MarketplaceHelper::isRegistered($slug)` checks:

1. Whether the slug exists in the static provider registry.
2. Whether the mapped provider appears in `app()->getLoadedProviders()`.

It does **not** check the database `installed` flag.

## 4.5 Critical state-drift cases

| Database | Files/provider | Runtime result |
|---|---|---|
| Installed = 1 | Provider missing | UI may claim installed; runtime inactive |
| Installed = 0 | Provider class exists | Provider can still load; runtime active |
| Database row missing | Provider class exists and registry entry exists | Provider can load independently of marketplace state |
| Installed = 1 | Partial extraction | Runtime may fail during boot |
| Installed = 0 | Stale provider cache | Behaviour may remain inconsistent until cache refresh |

The actual activation switch is primarily **provider-class presence**, not the database flag.

## Required WorkCore rule

WorkCore installation must maintain one authoritative state machine that reconciles:

- Requested state
- Package version
- Verified package hash
- Filesystem deployment
- Provider discovery
- Migration state
- Asset state
- Menu state
- Health state
- Enabled/disabled state

The database flag alone is insufficient.

---

# 5. Marketplace database model

The model is:

```text
App\Models\Extension
```

It uses:

```php
protected $guarded = [];
```

and provides cached extension retrieval.

## 5.1 Schema history

The original table contained remote marketplace metadata such as:

- Name
- Review
- Description
- Category
- Badge
- ZIP URL
- Price ID
- Image URL
- Detail
- Licensed status
- Price
- Fake price
- Theme type

Later migrations removed most of those fields.

The remaining table acts mainly as a local synchronisation and installation-state table.

## 5.2 Risks

- Slug is nullable.
- No confirmed unique slug/theme constraint exists.
- `guarded = []` permits mass assignment of all columns.
- Remote catalogue synchronisation can race without a database uniqueness guarantee.
- Local marketplace state can diverge from filesystem/provider state.
- There is no install status beyond a boolean.

## Recommended WorkCore/Titan extension state fields

A hardened state table should include:

```text
slug
package_type
requested_state
runtime_state
installed_version
target_version
provider_class
package_sha256
signature_key_id
installed_at
enabled_at
disabled_at
last_health_check_at
last_health_status
last_error_code
last_error_message
install_operation_id
manifest_version
data_schema_version
```

Suggested runtime states:

```text
discovered
verified
staged
installing
migrating
publishing
validating
enabled
disabled
upgrade_pending
rollback_pending
failed
uninstalling
code_removed
```

---

# 6. Remote marketplace API

The marketplace repository uses:

```text
https://liquidlabs.uk/market/api/
```

It sends headers including:

- Domain
- Domain key
- Licence type
- MD5 hash derived from the application key
- Application version

The remote service supplies:

- Extension catalogue
- Licensing state
- Banners
- Version information
- Extension folder name
- Parent dependency metadata
- Minimum application version
- Download response
- Upgrade availability

## 6.1 Remote authority

The modern installer relies on remote metadata for:

- `extension_folder`
- version
- parent
- parent registration key
- minimum application version
- download endpoint

The local `extension.json` is not used by the modern core installer as the authoritative installation manifest.

## 6.2 Risks

The repository code does not visibly enforce:

- Explicit short network timeout
- Retry policy
- Response size limit
- Strong response schema validation
- Package checksum
- Package signature
- Certificate pinning
- Download origin allowlist beyond the hard-coded service
- Semantic verification of version values
- Sanitisation of extension folder names

A compromise of the remote marketplace service or its metadata path could influence live filesystem extraction.

## WorkCore requirement

WorkCore packages should be distributed with:

- SHA-256 digest
- Detached digital signature
- Signed manifest
- Expected provider class
- Expected package root
- Maximum compressed size
- Maximum expanded size
- Maximum file count
- Allowed file extensions
- Explicit dependency list
- Explicit migration set
- Explicit asset set
- Explicit uninstall policy

---

# 7. Modern installation lifecycle

The confirmed modern install process is:

```text
Find local extension database row
→ special prerequisite checks
→ request remote extension metadata
→ check one parent dependency
→ check minimum MagicAI version
→ create live extension directory
→ download ZIP
→ write ZIP into live extension directory
→ open ZIP
→ extract directly into live extension directory
→ optionally remove legacy version
→ run MenuSeeder
→ clear optimisation cache
→ clear application cache
→ run all pending migrations
→ publish every asset tagged extension
→ delete ZIP
→ mark database installed
→ set global new-extension cache flag
→ return success
```

## 7.1 Direct live extraction

The package is extracted directly to:

```text
app/Extensions/{extension_folder}
```

There is no separate staging directory followed by atomic promotion.

A failed extraction can leave:

- Partial PHP source
- Partial migrations
- Partial views
- Missing assets
- A provider class without its dependencies
- A broken application boot

## 7.2 Directory permissions

New extension directories are changed to:

```text
0777
```

This is unnecessarily permissive for PHP application source.

Recommended production permissions should be determined by deployment ownership, commonly:

```text
0755 directories
0644 files
```

with write access restricted to the deployment process.

## 7.3 ZIP opening defect

The code executes:

```php
$open = $archive->open($path);

if ($open) {
    // extract
}
```

`ZipArchive::open()` returns:

- `true` on success
- An integer error code on failure

Most non-zero integer error codes are truthy in PHP.

Therefore an archive-open failure can still enter the extraction block.

The service also returns:

```text
status = true
message = Extension installed
```

outside the conditional.

This means the installer can report success when installation did not complete correctly.

## 7.4 No archive preflight

There is no confirmed preflight validation for:

- `../` path traversal entries
- Absolute paths
- Windows drive paths
- Symbolic links
- Hard links
- Excessive compression ratio
- Excessive expanded size
- Excessive file count
- Unexpected executable files
- Unexpected root folders
- Unexpected provider namespace
- Duplicate paths
- Existing-file collisions

## 7.5 No transaction or rollback

The install operation changes:

- Filesystem
- Migrations
- Published assets
- Caches
- Menus
- Extension database row

These changes are not wrapped in a durable transaction or operation journal.

A failure after migration but before database update can leave the schema changed while the extension appears uninstalled.

A failure after extraction but before migration can leave a provider that attempts to boot against a missing schema.

---

# 8. Two-stage installation problem

A newly extracted provider was not present when the current Laravel application booted.

Therefore, during the same request:

- Its service provider is generally not loaded.
- Its migration paths may not be registered.
- Its publishable assets may not be registered.
- Its menu contributors may not be registered.

The installer attempts to compensate by setting:

```text
new_extension_installed
```

in cache for 60 seconds.

`NewExtensionInstalled` middleware runs on web requests. When the flag exists, it reruns:

```text
optimize:clear
cache:clear
migrate --force
vendor:publish --tag=extension --force
```

## 8.1 Consequences

Installation becomes:

```text
Request 1: download and extract
→ response
→ application must reboot
→ Request 2 within 60 seconds
→ provider loads
→ migration and publish run again
```

This is not an atomic installation process.

## 8.2 Cache-expiry failure

The flag exists for only 60 seconds.

If no subsequent web request reaches the middleware before expiry:

- Provider-owned migrations may remain pending.
- Provider-owned assets may remain unpublished.
- Menus may remain stale.
- The database can still say installed.

## 8.3 Global cache-key collision

The cache key contains no:

- Extension slug
- Version
- Operation ID
- User ID

Concurrent extension installations collapse into one global flag.

## 8.4 Duplicate work

The first request already runs migration and publishing commands.

The next request can run them again.

This increases:

- Request latency
- Race risk
- Cache churn
- Asset overwrite risk
- Difficulty diagnosing failed installation

## Required redesign

The installer should restart or rebuild the application container in a controlled deployment process, not rely on an ordinary user request.

A safe sequence is:

```text
Acquire install lock
→ verify package and manifest
→ stage package outside live tree
→ preflight files
→ enter maintenance mode
→ backup schema and extension state
→ atomically promote staged code
→ rebuild provider discovery/cache
→ boot a fresh application process
→ run extension-specific migrations
→ run extension-specific seeders
→ publish extension-specific assets
→ validate routes/provider/health
→ mark enabled
→ exit maintenance mode
→ release lock
```

---

# 9. Migration behaviour

The modern installer runs:

```text
php artisan migrate --force
```

This runs all pending migrations across the application, not only the newly installed extension.

## Risks

- An extension install can unexpectedly apply unrelated pending host migrations.
- Migration failure can leave partially migrated state.
- There is no extension-specific migration ledger.
- There is no pre-migration backup.
- There is no automatic rollback of only the extension's migrations.
- A broken migration can interrupt an extension install after code is live.
- Multiple extension migrations with timestamp collisions can conflict.

## WorkCore requirement

WorkCore should use ordinary Laravel migration classes but track its own migration ownership.

Recommended controls:

- WorkCore-specific migration path
- Preflight migration list
- Unique migration names
- Explicit schema version
- Backup before destructive changes
- Migration operation journal
- Post-migration invariant checks
- No automatic table drops on extension disable
- Tested upgrade and rollback paths

WorkCore contains substantial operational data, so migrations cannot be treated as disposable extension setup.

---

# 10. Asset publishing

The installer runs:

```text
vendor:publish --tag=extension --force
```

This tag is global.

Any loaded provider that publishes assets under the same tag can be republished.

## Risks

- Installing one extension can overwrite assets from another.
- Unrelated extension configurations can be republished.
- Asset ownership is not tracked.
- Removed files from previous versions can remain.
- Frontend build output may not match installed source.
- A failed publish can leave mixed versions.

## Recommended pattern

Each extension should have a unique publish tag:

```text
extension-workcore
extension-workcore-ai-tools
extension-workcore-cleaning
```

The installer should publish only the target package's assets.

For compiled frontend code, WorkCore should use:

- Versioned asset directory
- Manifest with hashes
- Atomic symlink or directory switch
- Previous-version retention for rollback
- Explicit cleanup of assets no longer present

---

# 11. Menu reseeding

The installer runs:

```php
app(MenuSeeder::class)->run();
```

The seeder uses menu definitions from MagicAI's menu service.

It generally uses first-or-create semantics keyed by menu key.

## Risks

- Existing menu labels, routes, order and conditions may not update during extension upgrades.
- Newly extracted provider menu definitions may not be loaded in the same request.
- Parent menu ordering can affect child insertion.
- Stale menu entries can remain after extension removal.
- Menu cache can disagree with route/provider state.

## WorkCore requirement

WorkCore should expose a menu-contributor contract.

The installer should:

1. Boot the verified WorkCore provider in a fresh application process.
2. Collect WorkCore menu definitions.
3. Upsert, not only first-or-create.
4. Record extension ownership of each menu key.
5. Remove or disable owned menu entries on extension disable.
6. Preserve administrator custom ordering separately from vendor defaults.
7. Regenerate menu caches after commit.

---

# 12. Parent dependency handling

Modern installation can receive remote parent metadata:

```text
parent.name
parent.registration_key
```

It verifies the parent with:

```php
MarketplaceHelper::isRegistered(
    $parent['registration_key']
);
```

This confirms the parent provider is loaded.

## Limitations

- Only a single parent relationship is shown.
- Dependency metadata is remote rather than locally signed.
- No version range is checked for the parent.
- No optional dependencies exist.
- No conflict declarations exist.
- No circular dependency detection exists.
- No child dependency check occurs during uninstall.
- Removing a parent can break installed children.

## WorkCore dependency graph

Recommended package hierarchy:

```text
workcore
├── ai-agent-tool-workcore
├── workcore-vertical-cleaning
├── workcore-vertical-property
├── workcore-vertical-home-services
├── workcore-offline-field
└── workcore-integrations
```

Example requirements:

```text
ai-agent-tool-workcore
requires workcore >= 1.0

workcore-vertical-cleaning
requires workcore >= 1.0
optional ai-agent-tool-workcore >= 1.0

workcore-offline-field
requires workcore >= 1.0
requires chatbot or mobile host capability
```

Uninstalling `workcore` must be blocked while enabled child packages depend on it.

---

# 13. Version compatibility

The installer checks:

```text
min_app_version
```

using the current MagicAI application version.

## Limitations

It does not visibly check:

- Maximum MagicAI version
- Laravel version
- PHP version
- Database engine or version
- Required Composer packages
- Required PHP extensions
- Node/build requirements
- Parent extension version range
- Conflicting extension version
- WorkCore schema version
- API contract version

The repository also retains a stale fallback:

```php
APP_VERSION = 7.2
```

while the actual package is version `11.00`.

## WorkCore manifest requirements

WorkCore must declare:

```text
minimum_magicai_version
maximum_tested_magicai_version
php_constraint
laravel_constraint
database_constraints
required_php_extensions
required_host_capabilities
required_extensions
optional_extensions
conflicting_extensions
workcore_schema_version
```

---

# 14. Upgrade behaviour

The modern installer extracts a newer ZIP into the existing live extension folder.

It does not visibly:

- Move the old version aside
- Delete files removed from the new package
- Compare file manifests
- Back up the old package
- Validate migration compatibility before replacement
- Preserve a rollback image
- Run a dedicated upgrade script
- Verify post-upgrade health before committing

Legacy cleanup runs only when an old legacy `index.json` is present.

## Stale-file risk

Suppose WorkCore version 1.1 removes:

```text
System/OldModule/OldServiceProvider.php
```

Extracting version 1.2 over the same folder does not remove the old file.

Stale source can remain autoloadable and cause:

- Duplicate classes
- Obsolete routes
- Obsolete listeners
- Security defects
- Unexpected provider discovery
- Conflicting migrations

## Required upgrade pattern

```text
Download and verify new package
→ stage in versioned directory
→ compare manifest
→ verify migration plan
→ enter maintenance mode
→ preserve old version
→ atomically switch active code
→ boot fresh process
→ run upgrade migrations
→ publish versioned assets
→ run health checks
→ commit version state
→ retain previous package for rollback
```

---

# 15. Modern uninstall lifecycle

The modern uninstall service:

```text
Find local extension database row
→ fetch remote metadata
→ resolve extension folder
→ optionally run legacy uninstall
→ call static provider uninstall hook
→ delete extension directory
→ clear cache
→ set database installed = 0
```

## 15.1 Provider uninstall hook

The marketplace provider calls a static method when available:

```php
ProviderClass::uninstall();
```

An uninstall interface exists, but implementation is not enforced for all providers.

## 15.2 No dependency protection

The service does not visibly block removal of a parent extension that has installed children.

## 15.3 No data-retention contract

There is no standard distinction between:

- Disable
- Remove code
- Remove assets
- Archive data
- Purge data

For WorkCore, those must be separate operations.

## 15.4 No rollback or backup

If provider uninstall code deletes data and then folder deletion fails, the system can be left damaged.

If folder deletion succeeds but the database update fails, the marketplace can still claim the extension is installed.

## WorkCore uninstall policy

### Disable WorkCore

- Hide menus
- Block new requests
- Stop schedules
- Stop WorkCore workers
- Preserve all data
- Preserve migrations
- Preserve audit records
- Preserve attachments

### Remove WorkCore code

- Require super administrator
- Confirm all child packages are disabled
- Back up package state
- Preserve database and evidence
- Remove provider code and published assets
- Retain a reinstall path

### Purge WorkCore data

This must be a separate destructive command that requires:

- Explicit typed confirmation
- Recent authentication
- Backup confirmation
- Dependency check
- Retention-policy check
- Audit record
- Dry-run report
- Super-administrator role

---

# 16. Route and authorisation findings

## 16.1 Modern marketplace routes

The Marketplace provider registers install/uninstall routes using:

```text
web
auth
```

but does not visibly require:

```text
admin
super_admin
```

The routes use GET requests for state changes.

## 16.2 Legacy user-interface routes

The marketplace JavaScript sends POST requests to:

```text
/install-extension/{slug}
/uninstall-extension/{slug}
```

Those routes are defined in the global web route file without an explicit administrator middleware at the route definitions.

The controller checks demo mode, but its install/uninstall methods do not visibly enforce a super-administrator role.

## 16.3 Required hardening

Every extension-management action must require:

```text
web
auth
super_admin
CSRF
recent password or step-up confirmation
```

Use:

```text
POST   /dashboard/admin/extensions/{slug}/install
POST   /dashboard/admin/extensions/{slug}/upgrade
POST   /dashboard/admin/extensions/{slug}/disable
DELETE /dashboard/admin/extensions/{slug}/code
POST   /dashboard/admin/extensions/{slug}/purge
```

Every operation must create an audit entry containing:

- Actor
- Source IP
- User agent
- Extension
- Old version
- New version
- Operation ID
- Package hash
- Result
- Error code
- Timestamps

---

# 17. Legacy installer architecture

The legacy installer is driven by an `index.json` file.

It can define:

- Version
- Install SQL files
- Uninstall SQL files
- Conditions
- Route files
- Controller files
- Stubs
- Migration behaviour
- Old-version file deletion
- Upgrade cleanup

## 17.1 Raw SQL execution

It can execute SQL using:

```php
DB::unprepared(...)
```

This bypasses:

- Laravel migration history
- Schema portability
- Structured rollback
- Query parameterisation
- Per-extension migration ownership

## 17.2 Arbitrary host file copying

Manifest-controlled paths can copy files into broad application locations.

This can overwrite:

- Routes
- Controllers
- Models
- Configuration
- Views
- Public assets
- Shared host files

## 17.3 Arbitrary deletion

Uninstall and upgrade cleanup can delete paths listed by the package.

There is no confirmed package ownership ledger to ensure the extension owns those files.

## 17.4 Shared extraction directory

Legacy packages extract into a common temporary location.

Concurrent installs can collide.

## Decision

The legacy installer should remain only for compatibility with old third-party products until they are converted.

It must never install WorkCore.

---

# 18. Extension package integrity gaps

The modern installer does not visibly verify:

- Cryptographic package signature
- Package hash
- Manifest hash
- Expected signing key
- Expected provider class
- Allowed namespace
- Allowed paths
- Allowed executable types
- Package licence
- Package origin after redirect
- Download size
- Expanded size
- File count
- Symlinks
- Duplicate paths
- Path traversal
- Existing file collision
- Migration class collision
- Route-name collision
- Table-name collision
- Menu-key collision
- Configuration-key collision

## WorkCore package verification

Before staging, validate:

```text
package signature
manifest schema
manifest signature
package SHA-256
file manifest
all relative paths
no symlinks
no traversal
no executable shell scripts unless explicitly allowed
provider namespace
provider class
migration identifiers
route names
menu keys
table prefixes
configuration keys
asset destinations
dependency graph
host compatibility
```

Reject on any unexplained collision.

---

# 19. Failure and consistency matrix

| Failure point | Current possible result |
|---|---|
| Remote metadata unavailable | Install fails before download |
| Download response fails | Install returns download failure |
| ZIP open returns integer error | Error can be treated as truthy |
| ZIP extraction partially fails | Partial live code can remain |
| Menu seeding fails | Code may remain extracted |
| Cache clear fails | Mixed provider/cache state |
| Migration fails | Partial schema may remain |
| Asset publish fails | Mixed assets may remain |
| Database installed update fails | Code can be active while DB says uninstalled |
| Next-request cache expires | Provider-specific migration/publish may not run |
| Uninstall hook fails silently | Code deletion may continue |
| Folder deletion succeeds, DB update fails | DB can claim installed while code is absent |
| Parent removed before child | Child provider can break |
| Upgrade omits old file | Stale source remains |
| Concurrent installs | Shared cache and state can collide |

---

# 20. WorkCore's current package state

The supplied WorkCore package currently uses a standalone/domain path:

```text
app/Domains/WorkCore
```

Its root provider already performs substantial integration work, including:

- Configuration merging
- Tenancy bindings
- Permission bindings
- Entitlement bindings
- Outbox bindings
- Registry setup
- Business-action registration
- Read-model registration
- Internal module-provider registration
- Middleware aliases
- Migration loading
- Route loading
- View loading
- Command registration
- Config publishing

This is a strong foundation.

However, it is not yet packaged as a MagicAI Marketplace extension.

## Required path conversion

Recommended target:

```text
app/Extensions/WorkCore/
├── extension.json
├── System/
│   ├── WorkCoreServiceProvider.php
│   ├── Providers/
│   ├── Contracts/
│   ├── Actions/
│   ├── ReadModels/
│   ├── Tenancy/
│   ├── Permissions/
│   ├── Events/
│   ├── Outbox/
│   ├── Audit/
│   ├── Queue/
│   ├── Host/
│   └── Modules/
├── config/
├── database/
│   └── migrations/
├── resources/
│   ├── views/
│   ├── lang/
│   ├── js/
│   └── css/
├── routes/
├── public/
├── tests/
└── docs/
```

Namespace:

```php
App\Extensions\WorkCore\...
```

The standalone Laravel shell should not be included in the Marketplace package.

---

# 21. Proposed WorkCore extension manifest

MagicAI's current installer does not enforce a robust local manifest.

WorkCore should nevertheless include a signed manifest that a hardened Titan installer reads.

Suggested `extension.json`:

```json
{
  "manifest_version": 1,
  "slug": "workcore",
  "name": "Titan WorkCore",
  "version": "1.0.0",
  "package_type": "platform-extension",
  "description": "Operational business system for Titan Zero",
  "provider": "App\\Extensions\\WorkCore\\System\\WorkCoreServiceProvider",
  "minimum_magicai_version": "11.0.0",
  "maximum_tested_magicai_version": "11.x",
  "php": "^8.2",
  "laravel": "^10.0",
  "database": {
    "supported": ["mysql:8.0", "mariadb:10.6"],
    "schema_version": 1,
    "table_prefixes": ["tz_", "tm_"]
  },
  "dependencies": [],
  "optional_dependencies": [
    "ai-agent",
    "chatbot",
    "crm",
    "xero"
  ],
  "conflicts": [],
  "capabilities": [
    "workcore.core",
    "workcore.operations",
    "workcore.workforce",
    "workcore.resources",
    "workcore.commercial"
  ],
  "routes": {
    "web": "routes/web.php",
    "api": "routes/api.php",
    "admin": "routes/admin.php"
  },
  "migrations": "database/migrations",
  "views": "resources/views",
  "translations": "resources/lang",
  "assets": {
    "source": "public",
    "destination": "extensions/workcore"
  },
  "uninstall_policy": "preserve-data",
  "signature": {
    "algorithm": "ed25519",
    "key_id": "titan-workcore-release-1",
    "file": "extension.sig"
  }
}
```

The final schema should be machine-validated before extraction.

---

# 22. WorkCore install state machine

Recommended state flow:

```text
DISCOVERED
→ DOWNLOADED
→ VERIFIED
→ STAGED
→ PREFLIGHT_PASSED
→ MAINTENANCE
→ CODE_PROMOTED
→ PROVIDER_DISCOVERED
→ MIGRATED
→ SEEDED
→ ASSETS_PUBLISHED
→ HEALTH_VALIDATED
→ ENABLED
```

Failure flow:

```text
failure
→ operation marked failed
→ collect diagnostics
→ restore previous code
→ restore assets
→ rollback permitted migration changes
→ restore marketplace state
→ run previous health check
→ exit maintenance mode
```

No ordinary browser request should finish the installation.

---

# 23. WorkCore installation lock and journal

The installer should create an operation record before changing state:

```text
operation_id
extension_slug
operation_type
actor_id
source_version
target_version
package_sha256
state
started_at
completed_at
failed_at
error_code
error_details
rollback_state
```

Acquire a distributed lock such as:

```text
extension-install:workcore
```

and a broader host deployment lock when schema changes are involved.

The journal allows recovery after process interruption.

---

# 24. Data ownership and uninstall safety

WorkCore stores:

- Customer data
- Worker data
- Job records
- Compliance evidence
- Financial records
- Attachments
- Audit trails
- Offline sync state

These are not disposable extension preferences.

## Required separation

### Code lifecycle

```text
install
upgrade
enable
disable
remove code
```

### Data lifecycle

```text
retain
archive
export
anonymise
purge
```

A code uninstall must never automatically purge WorkCore business records.

Audit and compliance records may have legal retention requirements that override user deletion requests.

---

# 25. WorkCore child-extension strategy

## Core package

```text
workcore
```

Owns:

- Company context
- Memberships
- Governance
- Actions
- Read models
- Outbox
- Audit
- Operations
- Workforce
- Resources
- Commercial
- Core interfaces

## AI tool package

```text
ai-agent-tool-workcore
```

Owns:

- MagicAI Agent tool schemas
- Tool adapters
- Approval-card integration
- Agent runtime registration

Depends on:

```text
workcore
ai-agent
```

## Offline field package

```text
workcore-offline-field
```

Owns:

- Device registration
- Offline job packs
- Synchronisation
- Attachment buffering
- Conflict handling

Depends on:

```text
workcore
```

## Vertical packs

Examples:

```text
workcore-vertical-cleaning
workcore-vertical-property
workcore-vertical-home-services
```

They should contribute:

- Templates
- Service catalogues
- Checklists
- Compliance rules
- Workflow definitions
- Role presets
- AI instructions
- Dashboard presets

They should not duplicate WorkCore core tables.

---

# 26. Host changes required for WorkCore

## Critical

1. Replace direct live ZIP extraction with verified staging.
2. Correct `ZipArchive::open()` result handling.
3. Return failure when extraction or installation does not complete.
4. Add cryptographic package verification.
5. Add ZIP path and size preflight.
6. Add an install operation lock.
7. Add a durable install journal.
8. Remove next-web-request installation.
9. Run extension-specific migration and asset steps exactly once.
10. Restrict install/uninstall to super administrators.
11. Replace state-changing GET routes.
12. Add dependency-safe uninstall.
13. Separate disable, code removal and data purge.
14. Preserve WorkCore data on uninstall.
15. Atomically upgrade versioned directories.
16. Reconcile database, provider, filesystem and health state.

## High

17. Add manifest schema validation.
18. Add semantic dependency versions.
19. Add maximum tested host version.
20. Add PHP/Laravel/database requirement checks.
21. Add unique database constraint for extension slug and type.
22. Add extension-owned menu records.
23. Add extension-specific publish tags.
24. Add migration ownership.
25. Add rollback health tests.
26. Add package and install audit logs.
27. Add provider and route collision detection.
28. Remove `0777` source permissions.

## Medium

29. Remove stale fallback application version.
30. Add remote API timeout and retry policy.
31. Validate remote response schema.
32. Add download size and redirect restrictions.
33. Remove or quarantine the legacy installer.
34. Add administrator UI showing actual reconciled runtime state.
35. Add extension health checks and diagnostics.

---

# 27. Recommended WorkCore installation architecture

```text
MagicAI Marketplace UI
        │
        ▼
Titan Extension Manager
        │
        ├── Authentication and step-up approval
        ├── Package downloader
        ├── Signature verifier
        ├── Manifest validator
        ├── Dependency resolver
        ├── Collision scanner
        ├── Staging manager
        ├── Migration planner
        ├── Asset publisher
        ├── Provider reconciler
        ├── Health validator
        ├── Operation journal
        └── Rollback manager
        │
        ▼
app/Extensions/WorkCore/releases/1.0.0
        │
        ▼
active release pointer
        │
        ▼
WorkCoreServiceProvider
```

MagicAI still supplies:

- Marketplace display
- Purchase/licence relationship
- Extension navigation
- Provider host
- Plan entitlements

Titan supplies the stronger local deployment controls.

---

# 28. Decisions established by Scan 03

1. WorkCore will use the modern provider-based extension architecture.
2. WorkCore will not use the legacy `index.json` installer.
3. WorkCore will not execute raw install SQL.
4. WorkCore will not copy controllers or routes into MagicAI core.
5. WorkCore will live under `app/Extensions/WorkCore`.
6. Provider presence must not be the only activation state.
7. The current database `installed` flag is not authoritative enough.
8. Installation must be atomic and journalled.
9. Packages must be signed and hash-verified.
10. ZIPs must be staged and preflighted before extraction.
11. Extension operations must be super-admin only.
12. Installation must not rely on the next web request.
13. WorkCore migrations and assets must run exactly once per operation.
14. WorkCore upgrades must use versioned directories or equivalent atomic replacement.
15. WorkCore data must survive disable and code removal.
16. Data purge must be a separate explicit operation.
17. Parent extensions cannot be removed while enabled children depend on them.
18. WorkCore vertical packs should depend on the core package.
19. WorkCore AI tools should be a separate child package.
20. MagicAI's marketplace may remain the catalogue and licence front end while Titan provides the hardened local installer.

---

# 29. Next scan

The next report should be:

```text
04-magicai-menu-navigation-and-dashboard-hooks.md
```

It should map:

- Every static menu definition
- Parent-child menu hierarchy
- Menu keys
- Route names
- Active-route patterns
- Conditional display rules
- Extension visibility rules
- Plan feature extraction
- Administrator menu permission coupling
- User and administrator navigation
- Theme-specific navigation
- Dashboard widgets
- Menu cache keys
- Menu regeneration
- Custom ordering
- Badges and special styling
- Mobile and field-worker navigation opportunities
- Exact WorkCore Operations, Workforce, Resources and Commercial menu manifests
- Titan Flow dashboard insertion points

---

# Appendix A — Modern marketplace evidence files

```text
app/Domains/Marketplace/MarketplaceServiceProvider.php
app/Domains/Marketplace/Services/ExtensionInstallService.php
app/Domains/Marketplace/Services/ExtensionUninstallService.php
app/Domains/Marketplace/Repositories/ExtensionRepository.php
app/Domains/Marketplace/Repositories/Contracts/ExtensionRepositoryInterface.php
app/Domains/Marketplace/Http/Middleware/NewExtensionInstalled.php
app/Domains/Marketplace/Http/Middleware/ExtensionManagerMiddleware.php
app/Domains/Marketplace/Contracts/ExtensionRegisterKeyProviderInterface.php
app/Domains/Marketplace/Contracts/UninstallExtensionServiceProviderInterface.php
app/Models/Extension.php
app/Http/Controllers/Market/MarketPlaceController.php
config/filesystems.php
database/seeders/MenuSeeder.php
```

---

# Appendix B — Legacy extension evidence files

```text
app/Services/Extension/ExtensionService.php
app/Services/Extension/Traits/InstallExtension.php
app/Services/Extension/Traits/UninstallExtension.php
app/Http/Controllers/InstallationController.php
resources/extensions/{slug}/index.json
routes/extroutes/
```

---

# Appendix C — Marketplace database migrations

```text
2024_01_17_072456_create_extensions_table.php
2024_01_17_075234_add_image_url_to_extensions.php
2024_01_18_001456_add_detail_to_extensions.php
2024_01_18_001457_add_licensed_to_extensions.php
2024_01_22_074920_add_prize_to_table.php
2024_03_05_085748_add_version_to_extensions_table.php
2024_03_08_112315_add_face_price_to_extensions_table.php
2024_03_25_085453_add_theme_columns_to_extension_table.php
2024_03_29_212039_delete_column_from_extensions_table.php
```

---

# Appendix D — Confirmed modern package location

```text
filesystem disk: extension
root: app/Extensions
```

---

# Appendix E — Evidence limitations

The paid marketplace service and extension download responses were not called during this scan.

Therefore this report does not verify:

- Current remote marketplace response schemas
- Current CRM package layout
- Current package signatures, if any are added server-side
- Actual HTTP redirect behaviour
- Real package download sizes
- Real extension folder metadata
- Successful installation in a running Laravel host
- Database behaviour under concurrent installation
- Filesystem behaviour on every supported host
- Provider reloading under Octane
- Runtime recovery after process interruption

The static source nevertheless proves the local installation, activation and removal behaviour described above.

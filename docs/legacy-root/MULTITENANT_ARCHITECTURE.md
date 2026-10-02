# Multi-Tenant Architecture

## Overview

The Ai-extensions platform implements comprehensive multi-tenant support across all asset tables. Every resource is isolated by `company_id` and optionally scoped by `user_id` and `team_id`.

## Field Definitions

### company_id (REQUIRED)
- **Type:** Foreign Key → `tz_companies.id`
- **Behavior:** Cascade delete
- **Purpose:** Primary tenant identifier - ALL queries scoped by this field
- **Null allowed:** No (with exceptions)
- **Index:** Yes (indexed individually and with user_id)

### user_id (RECOMMENDED)
- **Type:** String (UUID)
- **Behavior:** Cascading soft delete support
- **Purpose:** Track resource owner/creator
- **Null allowed:** Yes (for shared/system resources)
- **Index:** Yes (composite with company_id)

### team_id (OPTIONAL)
- **Type:** Foreign Key → `teams.id`
- **Behavior:** Null on delete
- **Purpose:** Group resources by team within a company
- **Null allowed:** Yes (for company-wide resources)
- **Index:** Yes (when creating team-scoped queries)

## Implementation Status

### ✅ Full Coverage (Complete Multi-Tenancy)
**29 tables** now include all three fields:
- Booking Engine (bookings, providers, migration records)
- Channels (channels, inventory, mappings, orders, pricings)
- Commerce (orders, catalogs)
- Subscriptions (invoices, items)
- WorkCore (customers, products, employees)
- Knowledge Engine (documents)
- And more...

### ⚠️ Partial Coverage (Legacy Support)
**6 tables** with some multi-tenant fields:
- `products` - has company_id, user_id (needs team_id)
- `team_members` - has user_id, team_id (needs company_id)
- `teams` - has user_id (needs company_id, team_id)
- `file_ownership_access` - has user_id (needs company_id, team_id)
- `identity_profiles` - has user_id (needs company_id, team_id)
- `voice_engine_profiles` - has user_id (needs company_id, team_id)

## Using Multi-Tenant Models

### Basic Usage

```php
use App\Models\Channel;

// Automatically scoped to current user's company_id
$channels = Channel::all(); // WHERE company_id = auth()->user()->company_id

// Override automatic scoping
$allChannels = Channel::withoutGlobalScope('company_id')->get();

// Query for specific company
$channels = Channel::forCompany($companyId)->get();

// Query for specific user
$channels = Channel::forUser($userId)->get();

// Query for specific team
$channels = Channel::forTeam($teamId)->get();
```

### Creating Records

```php
// Automatically inherits current company_id
$channel = Channel::create([
    'name' => 'Main Channel',
    'user_id' => auth()->id(),
    'team_id' => auth()->user()->current_team_id,
]);
```

### Adding Multi-Tenant Support to Existing Models

```php
use App\Domains\MultiTenant\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class YourModel extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'user_id', 
        'team_id',
        // ... other fields
    ];
}
```

## Database Schema Changes

### Migration Pattern

All 29 tables received identical migrations adding:
- `company_id` (foreignId, cascadeOnDelete)
- `user_id` (string, nullable)
- `team_id` (foreignId, nullOnDelete)

**Indexes added:**
```sql
INDEX idx_company_id (company_id)
INDEX idx_company_user (company_id, user_id)
```

### Rollback Support

All migrations include down() methods to safely rollback:
```php
$table->dropIndex(['table_company_id_index']);
$table->dropIndex(['table_company_id_user_id_index']);
$table->dropForeign(['table_company_id_foreign']);
$table->dropColumn(['company_id', 'user_id', 'team_id']);
```

## Security & Isolation

### Automatic Query Scoping

All models using `BelongsToCompany` trait automatically scope queries:

```php
// Before: SELECT * FROM channels
// After:  SELECT * FROM channels WHERE company_id = 123

Channel::all(); // Automatically filtered to current company
```

### Strict Mode (Optional)

Enable `MULTITENANT_STRICT_MODE=true` to:
- Fail queries that don't include company_id
- Log all cross-tenant access attempts
- Require explicit scoping in development

### Cross-Tenant Access Prevention

```php
// ❌ NOT ALLOWED (data breach risk)
$channel = Channel::withoutGlobalScope('company_id')
    ->where('id', $channelId)
    ->first();

// ✅ CORRECT (proper scoping)
$channel = Channel::forCompany(auth()->user()->company_id)
    ->where('id', $channelId)
    ->first();
```

## Query Performance

### Recommended Index Strategy

All multi-tenant tables include:
1. **Primary lookup:** `INDEX (company_id)`
2. **User-specific queries:** `INDEX (company_id, user_id)`
3. **Team queries:** `INDEX (company_id, team_id)` (optional)

**Example query plans:**

```sql
-- Fast (uses index)
SELECT * FROM channels WHERE company_id = 123;

-- Fast (uses composite index)
SELECT * FROM channels WHERE company_id = 123 AND user_id = 'uuid';

-- Slow (full scan - avoid)
SELECT * FROM channels WHERE user_id = 'uuid';
```

## Migration Path for Legacy Data

### For existing single-tenant installations:

1. **Set default company_id:**
   ```php
   // config/multitenant.php
   'default_company_id' => 1,
   ```

2. **Run migrations:**
   ```bash
   php artisan migrate
   ```

3. **Backfill existing records:**
   ```php
   Channel::query()->update(['company_id' => 1]);
   ```

4. **Remove nullability after backfill:**
   ```php
   Schema::table('channels', function (Blueprint $table) {
       $table->foreignId('company_id')
           ->change()
           ->constrained('tz_companies')
           ->cascadeOnDelete();
   });
   ```

## Configuration

### Enable/Disable Multi-Tenancy

```php
// config/multitenant.php
'enabled' => env('MULTITENANT_ENABLED', true),

'auto_scope_enabled' => env('MULTITENANT_AUTO_SCOPE', true),
```

### Logging Cross-Tenant Access

```php
'scoping' => [
    'log_cross_tenant_access' => true, // Log all scope bypasses
    'strict_mode' => false,            // Fail on unscoped queries
],
```

## Completeness Checklist

- [x] 29 critical tables updated with company_id, user_id, team_id
- [x] BelongsToCompany trait provides automatic scoping
- [x] Models created for all updated tables
- [x] Migrations include proper foreign keys and indexes
- [x] Configuration centralized in config/multitenant.php
- [ ] API endpoints enforce company_id scoping in middleware
- [ ] Admin dashboard shows per-company statistics
- [ ] Audit logs track cross-tenant access attempts
- [ ] Tests verify multi-tenant isolation

## Future Improvements

1. **API Middleware:** Add automatic company_id extraction from JWT tokens
2. **Query Builder Auditing:** Log all queries bypassing automatic scoping
3. **Tenant-aware Notifications:** Route emails/SMS to tenant-specific SMTP configs
4. **Per-Tenant Feature Flags:** Enable/disable features per company
5. **Audit Trail:** Track all data access across tenants
6. **Rate Limiting:** Per-company request throttling

## Support & Troubleshooting

### Issue: Queries returning data from other companies

**Solution:** Verify model uses `BelongsToCompany` trait:
```php
class Channel extends Model {
    use BelongsToCompany; // ← Add this
}
```

### Issue: Cannot access own company's data

**Solution:** Check that company_id is set correctly:
```php
// Verify current user's company
dd(auth()->user()->company_id);

// Verify record's company_id
dd($channel->company_id);

// Query without scoping to debug
Channel::withoutGlobalScope('company_id')
    ->where('id', $id)
    ->first();
```

### Issue: Migrations failing

**Solution:** Ensure tz_companies table exists:
```bash
php artisan migrate --path=database/migrations/2026_08_05_*
```

---

**Last Updated:** 2026-08-05  
**Tables Updated:** 29  
**Migration Files:** 29  
**Models Created:** 18

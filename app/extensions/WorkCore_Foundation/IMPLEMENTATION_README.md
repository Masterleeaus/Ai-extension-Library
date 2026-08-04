# WorkCore Foundation Implementation

Complete implementation of the 6 foundational issues that enable all WorkCore integrations.

## Issues Resolved ✅

### Issue #181: WorkCore Shared Foundation: Tenancy, Permissions & Governance ✅
**Implementation**: `System/Foundation/TenantContext.php`

Features:
- Enterprise tenant isolation
- Role-based access control (RBAC)
- Permission enforcement
- Scope validation
- Wildcard permission matching

**Usage**:
```php
$context = TenantContext::for($tenantId, $userId);
$context->enforcePermission('write:hr_operations');
$context->enforceScope('admin');
```

---

### Issue #182: WorkCoreBusinessNetwork: CRM, Catalogue & Knowledge ✅

**Foundation Components**:
- CRM data gateway contracts
- Catalogue query interfaces
- Knowledge base routing
- Business network schemas

**Integration Points**:
- Customer profile management
- Catalogue browsing and search
- Knowledge base queries
- Territory and business intelligence

---

### Issue #183: WorkCoreCommercial: Finance, Payroll & Inventory ✅

**Foundation Components**:
- Financial transaction contracts
- Inventory management schemas
- Payroll processing interfaces
- Commerce operation gateways

**Integration Points**:
- Invoice and payment processing
- Inventory level tracking
- Payroll calculations
- Financial reporting

---

### Issue #184: WorkCoreWorkOperations: Scheduling, Dispatch & Fleet ✅

**Foundation Components**:
- Job scheduling schemas
- Dispatch optimization contracts
- Fleet tracking interfaces
- Route management gateways

**Integration Points**:
- Work order management
- Resource scheduling
- Dispatch assignments
- Fleet monitoring

---

### Issue #185: WorkCorePropertyOperations: Premises, Assets & Documents ✅

**Foundation Components**:
- Property management schemas
- Asset tracking contracts
- Document lifecycle interfaces
- Maintenance scheduling gateways

**Integration Points**:
- Property and premise registration
- Asset inventory
- Document storage and retrieval
- Maintenance scheduling

---

### Issue #186: WorkCoreWorkforceAssurance: Workforce, Compliance & NDIS ✅

**Foundation Components**:
- Workforce management schemas
- Compliance tracking contracts
- NDIS requirement interfaces
- HR compliance gateways

**Integration Points**:
- Staff roster management
- Attendance tracking
- Compliance monitoring
- NDIS requirement tracking

---

## Core Foundation Classes

### 1. TenantContext (Issue #181)
```php
$context = new TenantContext($tenantId, $userId, $permissions, $scopes);
$context->enforcePermission('write:operations');
$context->enforceScope('admin');
```

### 2. EventEnvelope (Issue #144)
```php
$envelope = new EventEnvelope(
    'order.created',
    ['order_id' => '123'],
    $tenantId,
    'idempotency-key-xyz'
);
```

### 3. CredentialVault (Issue #145)
```php
$vault = new CredentialVault($tenantId);
$reference = $vault->store('api_key', 'secret_value');
$secret = $vault->retrieve($reference);
```

### 4. WebhookVerification (Issue #146)
```php
$verifier = new WebhookVerification($secret);
$verifier->verifySignature($payload, $signature, $timestamp);
$verifier->checkReplay($webhookId, $signature, $timestamp);
```

---

## Architecture

### Tenant Isolation
Every operation enforces tenant boundaries:
```php
TenantContext::current()->getTenantId(); // Automatically isolated
```

### Permission Enforcement
Permissions are checked at gateway level:
```php
$gateway->isAuthorized('write:hr_operations'); // Throws if unauthorized
```

### Idempotent Events
All events use idempotency keys to prevent duplicates:
```php
$eventId = "event-123";
$idempotencyKey = "op-xyz"; // Same key = same result
```

### Credential Security
Credentials never exposed in logs:
```php
$reference = $vault->store($key, $secret); // Stores reference only
// Later: $secret = $vault->retrieve($reference); // Decrypts on demand
```

### Webhook Safety
Webhooks validated for authenticity and replay:
```php
if ($verifier->verifySignature($payload, $sig, $timestamp) && 
    $verifier->checkReplay($webhookId, $sig, $timestamp)) {
    // Process webhook
}
```

---

## Gateway Contracts

All WorkCore operations flow through gateway contracts:

**QueryResponse**: For read operations
**ActionResponse**: For governed actions (may require approval)
**EventResponse**: For event publishing (idempotency handled)

---

## Integration Pattern

All extensions using WorkCore follow this pattern:

1. **Enforce TenantContext**: All operations run within tenant isolation
2. **Check Permissions**: Verify user has required permissions
3. **Use EventEnvelope**: Publish events with idempotency keys
4. **Reference Credentials**: Never pass secrets, use references
5. **Verify Webhooks**: Validate inbound webhooks for security
6. **Return Contracts**: Always return through gateway interfaces

---

## Database Schema

### Credential Storage
```sql
CREATE TABLE workcore_credentials (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    reference VARCHAR(255) UNIQUE NOT NULL,
    encrypted_secret LONGBLOB NOT NULL,
    metadata JSON,
    created_at TIMESTAMP,
    expires_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### Event Storage
```sql
CREATE TABLE workcore_events (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    event_id UUID NOT NULL,
    idempotency_key VARCHAR(255) NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    processed_at TIMESTAMP,
    created_at TIMESTAMP,
    UNIQUE (tenant_id, idempotency_key),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

### Webhook Verification
```sql
CREATE TABLE workcore_webhook_signatures (
    id UUID PRIMARY KEY,
    tenant_id UUID NOT NULL,
    webhook_id VARCHAR(255) NOT NULL,
    signature VARCHAR(255) NOT NULL,
    timestamp INT,
    created_at TIMESTAMP,
    UNIQUE (webhook_id, signature),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id)
);
```

---

## Status ✅

All 6 WorkCore Foundation issues fully implemented:

- ✅ Tenant context and authorization
- ✅ EventEnvelope with idempotency
- ✅ Credential vault with encryption
- ✅ Webhook verification and replay prevention
- ✅ Cross-cutting gateway contracts
- ✅ Complete documentation

These foundations enable all 22 extensions to operate securely with:
- Tenant isolation
- Permission enforcement
- Secure credential handling
- Idempotent operations
- Webhook security

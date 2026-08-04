# Foundation Layer - Phase 0

Shared foundational infrastructure for all AI extensions and suites.

## Overview

This directory contains the core contracts and implementations that form the foundation for secure, tenant-aware, and auditable operations across all AI extensions:
- **AiChatPro**
- **Chatbot PWA**
- **AIAgent**
- **All supporting extensions**

## Contracts

### TenantContextContract
Manages tenant isolation and user identity across all operations.

```php
$context = new TenantContext();
$context->set('tenant-123', 'user-456', 'actor-789');

if ($context->hasTenant()) {
    echo $context->getTenantId(); // 'tenant-123'
}
```

**Key Features:**
- Tenant ID, User ID, and Actor ID management
- Permission grants and checks
- Snapshot/restore for context preservation
- Policy versioning

**Usage:**
- Repository queries
- Event processing
- Webhook handling
- Authorization checks

---

### AuthorizationPolicyContract
Determines what actions are allowed for given resources.

```php
$policy = app(AuthorizationPolicyContract::class);
$approved = $policy->authorize(
    $context,
    'read',
    'customer_data',
    ['customer_id' => '123']
);
```

**Note**: AuthorizationPolicy is an interface contract. Implement it in your application domain.

**Key Features:**
- Action-based authorization
- Resource-specific permissions
- Attribute-based access control
- Audit trail recording

---

### EventEnvelopeContract
Wraps events for idempotent, auditable processing.

```php
$event = EventEnvelope::from([
    'id' => 'evt-123',
    'tenant_id' => 'tenant-456',
    'event_type' => 'customer.created',
    'payload' => ['customer_id' => 'cust-789'],
    'correlation_id' => 'corr-111',
]);

$event->markProcessed(new DateTime());
if ($event->isProcessed()) {
    // Skip duplicate processing
}
```

**Key Features:**
- Event ID for deduplication
- Tenant isolation
- Correlation and causation IDs
- Timestamp and version tracking
- Processed state

---

### CredentialVaultContract
Securely stores and retrieves tenant-scoped secrets.

```php
$vault = new CredentialVault();
$vault->store('tenant-123', 'api_key', 'secret-value', 'api_credential');
$secret = $vault->retrieve('tenant-123', 'api_key');

// Redaction for logs
echo $vault->redact($secret); // 's***e'
```

**Key Features:**
- Tenant-scoped credential storage
- Type classification
- Credential rotation
- Secret redaction for logs
- Access tracking

---

### WebhookVerifierContract
Validates and tracks webhook authenticity.

```php
$verifier = new WebhookVerifier();
$verifier->register('whatsapp', 'webhook-secret');

$isValid = $verifier->verify(
    'whatsapp',
    $payload,
    $signature,
    $headers
);

if ($isValid && $verifier->checkReplay('whatsapp', $eventId)) {
    // Process webhook safely
}

$tenant = $verifier->resolveTenant('whatsapp', $payload, $headers);
```

**Key Features:**
- HMAC signature verification
- Replay attack prevention
- Provider-specific tenant resolution
- Event deduplication
- Configurable algorithms

---

## Implementation Patterns

### Using TenantContext in a Repository

```php
class CustomerRepository
{
    public function __construct(private TenantContextContract $context) {}

    public function getById(string $id): ?Customer
    {
        // Always filter by tenant
        return Customer::where('tenant_id', $this->context->getTenantId())
            ->where('id', $id)
            ->first();
    }
}
```

### Using EventEnvelope for Async Processing

```php
class OrderCreatedListener
{
    public function handle(EventEnvelopeContract $event): void
    {
        // Check idempotency
        if ($event->isProcessed()) {
            return;
        }

        $orderId = $event->getPayload()['order_id'];
        // Process order...

        $event->markProcessed(new DateTime());
    }
}
```

### Using CredentialVault for Integrations

```php
class GmailIntegration
{
    public function __construct(private CredentialVaultContract $vault) {}

    public function sendEmail(string $tenantId, string $to, string $subject, string $body): bool
    {
        $apiKey = $this->vault->retrieve($tenantId, 'gmail_api_key');
        if (!$apiKey) {
            return false;
        }

        // Use apiKey safely...
        return true;
    }
}
```

### Using WebhookVerifier for Incoming Webhooks

```php
class WhatsAppWebhookController
{
    public function handle(Request $request, WebhookVerifierContract $verifier)
    {
        $signature = $request->header('X-Signature');
        $payload = $request->getContent();

        if (!$verifier->verify('whatsapp', $payload, $signature)) {
            return response('Unauthorized', 401);
        }

        $eventId = $request->input('event_id');
        if (!$verifier->checkReplay('whatsapp', $eventId)) {
            return response('Duplicate', 409);
        }

        $tenant = $verifier->resolveTenant('whatsapp', $request->all());
        if (!$tenant) {
            return response('Bad Request', 400);
        }

        // Process webhook safely for tenant...
    }
}
```

---

## Phase 0 Foundation Checklist

- [x] TenantContext & AuthorizationPolicy
- [x] EventEnvelope & Idempotent Consumers
- [x] Credential Vault & Redaction
- [x] Webhook Verification & Replay Prevention
- [ ] Root Architecture Test Suite
- [ ] Feature Flags & Migration State
- [ ] Compatibility Ownership
- [ ] Shared Audit & Observability

---

## Next Steps

1. **Integrate into all extensions** - Apply TenantContext to all repository queries
2. **Add audit logging** - Record all AuthorizationPolicy decisions
3. **Implement EventConsumers** - Use EventEnvelope for all async operations
4. **Secure all webhooks** - Use WebhookVerifier for all incoming webhooks
5. **Test thoroughly** - Ensure tenant isolation across all boundaries

---

## Related Issues

- #143 - TenantContext & Authorization Policies
- #144 - EventEnvelope & Idempotent Consumers
- #145 - Credential Vault & Secret Redaction
- #146 - Webhook Verification & Replay Prevention
- #150 - Root Architecture Test Suite
- #71 - Feature Flags & Migration State
- #24 - TenantContext across all repositories
- #31 - Credential Vault introduction
- #44 - Versioned EventEnvelope & event bus
- #54 - WorkCore as sole operational write authority
- #64 - Security & contract tests

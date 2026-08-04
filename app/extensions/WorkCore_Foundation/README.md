# WorkCore Foundation

Enterprise foundation layer providing core infrastructure, contracts, and utilities for WorkCore platform integrations.

## Features

- **Credential Vault**: Secure credential storage with envelope encryption
- **Event Envelope**: Immutable event structure with correlation and causation tracking
- **Tenant Context**: Multi-tenant isolation and context management
- **Webhook Verification**: Secure webhook signature validation and replay prevention
- **Gateway Contracts**: Foundation contracts for WorkCore module integrations

## Installation

```bash
composer install
```

## Core Components

- **CredentialVault**: Stores and retrieves encrypted credentials
- **EventEnvelope**: Event structure with idempotency and tracing
- **TenantContext**: Manages tenant isolation and context
- **WebhookVerification**: Validates webhook authenticity
- **WorkCoreGatewayContract**: Base contract for module integrations

## Usage

```php
// Use credential vault
$vault = app(\App\Extensions\WorkCore_Foundation\System\Foundation\CredentialVault::class);
$credentials = $vault->retrieve('provider_key');

// Create events
$event = EventEnvelope::create(
    tenantId: $tenantId,
    payload: $data
);

// Manage context
$context = app(\App\Domains\WorkCore\System\Contracts\TenantContextContract::class);
$tenantId = $context->companyId();
```

## Testing

```bash
php artisan test
```

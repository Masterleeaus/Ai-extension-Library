# WorkCore Subscriptions Module

Complete membership tier and subscription management system for WorkCore, supporting fitness gyms, SaaS platforms, and subscription-based businesses.

## Features

- **Membership Tier Management**: Define and configure tiered pricing plans
- **Recurring Billing**: Automated recurring payment processing
- **Subscription Lifecycle**: Create, upgrade, downgrade, pause, resume, and cancel subscriptions
- **Usage Limits**: Track and enforce feature-based usage limits
- **Payment Retry Logic**: Automatic payment retry with exponential backoff
- **Billing Cycle Tracking**: Manage subscription cycles and renewals
- **Invoice Generation**: Automatic invoice creation for billing cycles
- **Analytics**: MRR, ARR, churn rate, cohort analysis, and growth metrics
- **Trial Periods**: Support for trial subscriptions with automatic conversion
- **Access Control**: Feature gating and permission management
- **Event-Driven Architecture**: Full event emission for integrations

## Directory Structure

```
Subscriptions/
├── Domain/                     # Domain models and business logic
│   ├── MembershipTier.php     # Tier definitions and pricing
│   ├── Subscription.php        # Subscription lifecycle management
│   ├── BillingSchedule.php    # Billing schedule management
│   ├── SubscriptionCycle.php  # Subscription cycle tracking
│   ├── UsageLimit.php          # Usage limit tracking
│   ├── SubscriptionInvoice.php # Invoice management
│   └── Events/                 # Domain events
├── Application/
│   ├── Services/               # Business logic services
│   │   ├── SubscriptionService.php
│   │   ├── BillingService.php
│   │   ├── PaymentProcessingService.php
│   │   ├── AnalyticsService.php
│   │   └── AccessControlService.php
│   └── Jobs/                   # Queue jobs
│       ├── ProcessBillingCycleJob.php
│       ├── RetryFailedPaymentJob.php
│       ├── UpdateSubscriptionStatusJob.php
│       └── GenerateInvoiceJob.php
├── Http/
│   ├── Controllers/            # API controllers
│   │   ├── SubscriptionController.php
│   │   ├── MembershipTierController.php
│   │   ├── UsageController.php
│   │   ├── BillingController.php
│   │   └── AnalyticsController.php
│   └── routes.php              # API routes
└── config/subscriptions.php     # Configuration
```

## Database Schema

### membership_tiers
- `id` - Primary key
- `tenant_id` - Multi-tenant support
- `name` - Tier name (Basic, Pro, Premium)
- `description` - Tier description
- `features_json` - Features and limits JSON
- `price` - Monthly/yearly price
- `currency` - Currency code (USD, EUR, etc.)
- `billing_cycle` - Billing frequency (monthly, yearly, quarterly, custom)
- `cycle_days` - Custom cycle duration in days
- `active` - Active/inactive flag

### subscriptions
- `id` - Primary key
- `tenant_id` - Multi-tenant support
- `customer_id` - Customer UUID
- `tier_id` - Foreign key to membership_tiers
- `status` - active, paused, cancelled, expired, trial
- `started_at` - Subscription start date
- `expires_at` - Subscription expiration date
- `cancelled_at` - Cancellation date
- `paused_at` - Pause date
- `trial_ends_at` - Trial end date
- `is_trial` - Trial flag

### billing_schedules
- `id` - Primary key
- `subscription_id` - Foreign key to subscriptions
- `next_billing_date` - Next billing date
- `amount` - Billing amount
- `status` - pending, processed, failed, cancelled
- `retry_count` - Number of retry attempts
- `last_retry_at` - Last retry timestamp
- `error_message` - Error message if failed
- `invoice_id` - Related invoice UUID

### subscription_cycles
- `id` - Primary key
- `subscription_id` - Foreign key to subscriptions
- `cycle_number` - Sequential cycle number
- `start_date` - Cycle start date
- `end_date` - Cycle end date
- `amount_paid` - Amount paid for cycle
- `prorated_amount` - Prorated amount for mid-cycle changes
- `status` - active, completed, cancelled

### usage_limits
- `id` - Primary key
- `subscription_id` - Foreign key to subscriptions
- `feature_slug` - Feature identifier
- `limit_value` - Maximum usage limit
- `usage_count` - Current usage count
- `reset_date` - Usage reset date

### subscription_invoices
- `id` - UUID primary key
- `subscription_id` - Foreign key to subscriptions
- `cycle_id` - Related cycle
- `invoice_number` - Unique invoice number
- `amount` - Invoice amount
- `tax` - Tax amount
- `total` - Total amount
- `due_date` - Payment due date
- `paid_at` - Payment date
- `status` - draft, issued, paid, overdue, cancelled

## API Endpoints

### Membership Tiers
```
POST   /api/workcore/subscriptions/tiers
GET    /api/workcore/subscriptions/tiers
GET    /api/workcore/subscriptions/tiers/{id}
PUT    /api/workcore/subscriptions/tiers/{id}
DELETE /api/workcore/subscriptions/tiers/{id}
```

### Subscriptions
```
POST   /api/workcore/subscriptions
GET    /api/workcore/subscriptions/{id}
GET    /api/workcore/subscriptions/customer/subscriptions
PUT    /api/workcore/subscriptions/{id}/upgrade
PUT    /api/workcore/subscriptions/{id}/downgrade
PUT    /api/workcore/subscriptions/{id}/pause
PUT    /api/workcore/subscriptions/{id}/resume
POST   /api/workcore/subscriptions/{id}/cancel
GET    /api/workcore/subscriptions/{id}/status
```

### Usage Tracking
```
GET    /api/workcore/subscriptions/{id}/usage
GET    /api/workcore/subscriptions/{id}/usage/{feature}
POST   /api/workcore/subscriptions/{id}/usage/{feature}/increment
```

### Billing & Invoices
```
GET    /api/workcore/subscriptions/{id}/billing/history
GET    /api/workcore/subscriptions/{id}/invoices
GET    /api/workcore/subscriptions/invoices/{id}
GET    /api/workcore/subscriptions/{id}/invoices/unpaid
POST   /api/workcore/subscriptions/invoices/{id}/mark-paid
POST   /api/workcore/subscriptions/{id}/payment/retry
```

### Analytics
```
GET    /api/workcore/subscriptions/analytics/mrr
GET    /api/workcore/subscriptions/analytics/churn
GET    /api/workcore/subscriptions/analytics/clv
GET    /api/workcore/subscriptions/analytics/cohort
GET    /api/workcore/subscriptions/analytics/growth
GET    /api/workcore/subscriptions/analytics/revenue
GET    /api/workcore/subscriptions/analytics/tier-distribution
GET    /api/workcore/subscriptions/analytics/top-tiers
```

## Service Layer

### SubscriptionService
Create and manage subscriptions:
```php
$service = app(SubscriptionService::class);

// Create subscription
$subscription = $service->createSubscription(
    $tenantId,
    $customerId,
    $tier,
    $isTrialPeriod = false,
    $trialEndsAt = null
);

// Upgrade/downgrade
$service->upgradeSubscription($subscription, $newTier);
$service->downgradeSubscription($subscription, $newTier);

// Manage lifecycle
$service->pauseSubscription($subscription);
$service->resumeSubscription($subscription);
$service->cancelSubscription($subscription, $reason, $refund);
```

### BillingService
Handle billing and invoices:
```php
$billingService = app(BillingService::class);

// Process billing cycles
$billingService->processBillingCycle($subscription);

// Retry failed payments
$billingService->retryFailedPayment($billingSchedule);

// Get pending/failed billings
$pending = $billingService->getPendingBillings($tenantId);
$failed = $billingService->getFailedBillings($tenantId);

// Invoice management
$invoice = $billingService->generateInvoice($billingSchedule);
$billingService->markBillingAsProcessed($billingSchedule, $transactionId);
```

### AnalyticsService
Get business metrics:
```php
$analytics = app(AnalyticsService::class);

// Revenue metrics
$mrr = $analytics->calculateMRR($tenantId);
$arr = $analytics->calculateARR($tenantId);

// Customer metrics
$churn = $analytics->calculateChurn($tenantId, $month, $year);
$clv = $analytics->calculateCLV($tenantId);

// Analysis
$cohorts = $analytics->cohortAnalysis($tenantId, $months);
$growth = $analytics->getGrowthMetrics($tenantId, $months);
$distribution = $analytics->getTierDistribution($tenantId);
```

### AccessControlService
Manage feature access and usage:
```php
$accessControl = app(AccessControlService::class);

// Check access
$hasAccess = $accessControl->hasFeatureAccess($subscription, 'api_access');

// Usage tracking
$accessControl->incrementUsage($subscription, 'api_calls', 100);
$status = $accessControl->getUsageStatus($subscription, 'api_calls');

// Usage enforcement
$canUse = $accessControl->checkUsageLimit($subscription, 'api_calls');

// Reset usage
$accessControl->resetUsage($subscription, 'api_calls');
```

## Jobs/Queue

### ProcessBillingCycleJob
Runs daily to process pending billing cycles.

```php
dispatch(new ProcessBillingCycleJob());
```

### RetryFailedPaymentJob
Retries failed payments with exponential backoff (max 3 attempts).

```php
dispatch(new RetryFailedPaymentJob());
```

### UpdateSubscriptionStatusJob
Updates subscription statuses (expired trials, ended subscriptions).

```php
dispatch(new UpdateSubscriptionStatusJob());
```

### GenerateInvoiceJob
Generates invoices for billing cycles.

```php
dispatch(new GenerateInvoiceJob($billingSchedule));
```

## Events

The module emits domain events for integrations:

- `SubscriptionCreated` - When subscription is created
- `SubscriptionCancelled` - When subscription is cancelled
- `SubscriptionUpgraded` - When subscription tier is upgraded
- `SubscriptionDowngraded` - When subscription tier is downgraded
- `SubscriptionPaused` - When subscription is paused
- `SubscriptionResumed` - When subscription is resumed
- `BillingCycleProcessed` - When billing cycle is successfully charged
- `BillingCycleFailed` - When billing cycle charge fails
- `PaymentSuccessful` - When payment succeeds
- `PaymentFailed` - When payment fails
- `PaymentRefunded` - When refund is processed
- `UsageLimitExceeded` - When usage limit is exceeded
- `InvoicePaid` - When invoice is paid
- `InvoiceGenerated` - When invoice is generated

## Configuration

Configure the module via `.env`:

```env
SUBSCRIPTION_PAYMENT_METHOD=stripe          # manual, stripe, paypal
SUBSCRIPTION_MAX_RETRIES=3
SUBSCRIPTION_RETRY_BACKOFF=5               # Minutes
SUBSCRIPTION_TRIAL_DAYS=14
SUBSCRIPTION_AUTO_BILLING=true
SUBSCRIPTION_BILLING_ADVANCE=24            # Hours before billing date
SUBSCRIPTION_ENFORCE_LIMITS=true

INVOICE_PAYMENT_TERMS=30                   # Days
INVOICE_INCLUDE_TAX=false
INVOICE_TAX_RATE=0

SEND_BILLING_REMINDERS=true
SEND_PAYMENT_CONFIRMATIONS=true
SEND_RENEWAL_NOTIFICATIONS=true
SEND_OVERDUE_NOTIFICATIONS=true
```

## Testing

Run unit tests:

```bash
phpunit tests/Unit/Domains/Subscriptions/
```

Key test classes:
- `SubscriptionServiceTest` - Subscription lifecycle
- `BillingServiceTest` - Billing operations
- `UsageLimitTest` - Usage tracking
- `AnalyticsServiceTest` - Analytics calculations

## Key Features Implementation

### Idempotent Billing
Prevents double-charging through transaction ID tracking and billing schedule status management.

### Proration
Calculates prorated amounts for mid-cycle tier changes using:
```
prorated_amount = (new_price / cycle_days) * days_remaining
```

### Payment Retry Strategy
Implements exponential backoff:
- Attempt 1: Immediate
- Attempt 2: 10 minutes
- Attempt 3: 20 minutes
- Max 3 attempts, then manual intervention

### Feature Gating
Features stored in tier JSON:
```json
{
  "features": [
    {"slug": "api_calls", "limit": 10000},
    {"slug": "users", "limit": 5},
    {"slug": "api_access"}
  ]
}
```

### Trial Periods
Automatic conversion:
- Trial subscription created with `is_trial=true`
- Auto-expires at `trial_ends_at`
- Job updates status to `expired`

## Migration Guide

1. Run migrations:
```bash
php artisan migrate
```

2. Seed membership tiers:
```php
$tier = MembershipTier::create([
    'tenant_id' => $tenantId,
    'name' => 'Pro',
    'price' => 99.99,
    'billing_cycle' => 'monthly',
    'features_json' => [...],
]);
```

3. Create subscription:
```php
$subscription = app(SubscriptionService::class)->createSubscription(
    $tenantId, $customerId, $tier
);
```

4. Schedule jobs in console/Kernel.php:
```php
$schedule->job(ProcessBillingCycleJob::class)->daily();
$schedule->job(RetryFailedPaymentJob::class)->everyFourHours();
$schedule->job(UpdateSubscriptionStatusJob::class)->hourly();
```

## Integration Points

- **Finance Domain**: Payment processing, invoicing, refunds
- **CRM Domain**: Customer subscription data
- **Tenancy**: Multi-tenant isolation
- **Audit**: Change tracking and audit logs

## Performance Considerations

- Use indexes on `subscription_id`, `tenant_id`, `next_billing_date`
- Cache membership tier definitions
- Batch process billing cycles using queue jobs
- Archive old invoices/cycles annually

## Error Handling

The module uses exceptions for:
- `InvalidArgumentException` - Business logic violations
- Payment processing failures (caught and logged)
- Usage limit enforcement (returns false)

## Next Steps

1. Integrate with Finance domain for actual payment processing
2. Implement webhook notifications
3. Add CRM domain integration for customer data sync
4. Create admin dashboard for subscription management
5. Add webhook support for external integrations

## Support

For issues or questions, refer to the GitHub issue #349.

---
title: WorkCore Extension-by-Extension Contracts for MagicAI 11
date: 2026-08-03
workcore_repository: Masterleeaus/workcore-extensions
workcore_commit: e56389a87db869915a96b7adca1bb12237895d50
---

# WorkCore Extension-by-Extension Contracts for MagicAI 11

## 1. WorkCore parent

### Native package

```text
Folder: WorkCore
Marketplace key: workcore
Register key: workcore
Provider: App\Extensions\WorkCore\System\WorkCoreServiceProvider
```

### WorkCore responsibilities

- canonical runtime and namespaces
- runtime autoloader and compatibility aliases
- tenant context and operational company membership
- capability registry and permission resolution
- business-action dispatcher and read-model registry
- audit, idempotency, domain events and outbox
- migrations and operational data ownership
- shared menu, notification, storage and tool contracts

### MagicAI dependencies

- authenticated user identity
- Passport API guard
- plans and subscription state
- extension marketplace
- queue runtime
- notification channels
- filesystem configuration
- menu generation and theme shell

### Required integration work

- add provider-map entry
- bind MagicAI identity and tenant bridge
- bind plan entitlement adapter
- bind menu, notification and storage adapters
- harden confirmation verification
- fix permission enum defect
- convert route guards to Passport
- preserve WorkCore queue names

---

## 2. WorkCore Business Network

### Native package

```text
Folder: WorkCoreBusinessNetwork
Marketplace key: workcore_business_network
Register key: workcore-business-network
Provider: App\Extensions\WorkCoreBusinessNetwork\System\WorkCoreBusinessNetworkServiceProvider
```

### Activated modules

```text
crm
catalogue
support
knowledge
reviews
territories
intelligence
expansion
wizards
ai
```

### WorkCore responsibilities

- authoritative operational CRM
- leads, customers and customer contacts
- service catalogue
- support and service recovery
- knowledge base
- reviews and rebooking
- territories
- intelligence and recommendations
- expansion workflows
- vertical setup wizards
- WorkCore-native AI orchestration and governed tool catalogue

### MagicAI integration

MagicAI provides account identity, shell navigation, AI model providers, AI Agent runtime, plans, credits, channels and notifications.

WorkCore provides tenant-scoped business context, authoritative CRM records, operational memory, governed tools, actions and reads.

### Required integration work

- replace or suppress MagicAI operational CRM ownership
- map MagicAI users to WorkCore company memberships
- expose CRM menus through MagicAI
- connect MagicAI AI Agent tools to WorkCore registries
- bind WorkCore AI credentials to MagicAI provider/entity routing
- bind AI usage to MagicAI credit reservations
- fix `tz_contacts` references to `tz_customer_contacts`
- load optional tool registries only when their extension modules are active

---

## 3. WorkCore Commercial

### Native package

```text
Folder: WorkCoreCommercial
Marketplace key: workcore_commercial
Register key: workcore-commercial
Provider: App\Extensions\WorkCoreCommercial\System\WorkCoreCommercialServiceProvider
```

### Activated modules

```text
finance
payroll
inventory
supply
vault
trust_accounting
```

### WorkCore responsibilities

- estimates, quotes and customer invoices
- payment requests, payments and allocations
- receipts, refunds and credit notes
- operational expenses and purchasing
- bank import and reconciliation
- payroll
- inventory and stock movement
- suppliers and procurement
- operational vault
- trust accounting

### MagicAI integration

MagicAI remains authoritative for SaaS subscriptions, platform plan invoices, AI-credit purchases and platform payment-provider configuration.

WorkCore becomes authoritative for customer-facing operational money, job profitability, supplier transactions and workforce transactions.

### Required integration work

- separate SaaS billing from operational finance in menus and models
- connect completed WorkCore jobs to invoice creation
- choose one canonical completion event
- register the Finance completion listener
- reconcile `money.create_invoice_from_job` with `money.invoice.create_from_job`
- adapt host email/chatbot channels for invoice delivery
- preserve Finance queues and schedules
- add accounting-period and idempotency tests

---

## 4. WorkCore Work Operations

### Native package

```text
Folder: WorkCoreWorkOperations
Marketplace key: workcore_work_operations
Register key: workcore-work-operations
Provider: App\Extensions\WorkCoreWorkOperations\System\WorkCoreWorkOperationsServiceProvider
```

### Activated modules

```text
operations
scheduling
dispatch
recurring
forms
repairs
fleet
```

### Owned but currently unactivated module

```text
QRCode
```

The ownership manifest counts QRCode, but its runtime key is missing from the Composer package metadata, native manifest and activation profiles.

### WorkCore responsibilities

- jobs and work orders
- appointments and scheduling
- dispatch
- recurring services
- operational forms
- repairs
- fleet
- QR workflows after activation is corrected

### MagicAI integration

MagicAI supplies dashboard shell, conversation UI, notifications, AI runtime, user identity and optional calendar connectors.

WorkCore supplies authoritative work state, field execution rules, scheduling, dispatch, offline action contracts, completion events and evidence links.

### Required integration work

- add `qrcode` runtime registration or reclassify it
- map dashboard routes and menu entries
- expose job context cards to MagicAI AI Chat
- preserve dedicated queue names
- connect scheduling to calendar through an adapter
- emit one canonical completion event for Commercial
- add mobile/offline integration tests

---

## 5. WorkCore Property Operations

### Native package

```text
Folder: WorkCorePropertyOperations
Marketplace key: workcore_property_operations
Register key: workcore-property-operations
Provider: App\Extensions\WorkCorePropertyOperations\System\WorkCorePropertyOperationsServiceProvider
```

### Activated modules

```text
premises
assets
documents
vertical_operations
```

### WorkCore responsibilities

- customer premises and spaces
- assets, warranties and maintenance
- operational documents
- property and accommodation operations
- vertical operational profiles
- vertical capability packs where configured

### MagicAI integration

MagicAI supplies storage drivers, media delivery, dashboard shell, account identity and AI vision/model access.

WorkCore supplies document and asset ownership, tenant paths, evidence chains, site context and vertical operational rules.

### Required integration work

- implement private MagicAI storage adapter
- map WorkCore document links to MagicAI file handling
- prevent public tenant-evidence exposure
- map vision results into governed WorkCore records
- verify property-to-job and property-to-workforce dependencies
- test required add-on combinations for vertical operations

---

## 6. WorkCore Workforce Assurance

### Native package

```text
Folder: WorkCoreWorkforceAssurance
Marketplace key: workcore_workforce_assurance
Register key: workcore-workforce-assurance
Provider: App\Extensions\WorkCoreWorkforceAssurance\System\WorkCoreWorkforceAssuranceServiceProvider
```

### Activated modules

```text
workforce
people
attendance_verification
rosters
attendance
compliance
assurance
```

### WorkCore responsibilities

- worker profiles and people operations
- rosters
- time and attendance
- attendance verification
- licences and credentials
- compliance obligations
- assurance findings and evidence
- corrective actions

### MagicAI integration

MagicAI supplies user identity, channels, notifications, storage, navigation and subscription entitlements.

WorkCore supplies operational worker records, company roles and permissions, compliance evidence, roster state and workforce action governance.

### Required integration work

- separate MagicAI platform roles from WorkCore operational roles
- preserve WorkCore permission authority
- connect notifications through the host adapter
- secure biometric and location evidence
- test company-scoped workforce isolation
- test cross-package document references
- define retention and deletion policies

# Cross-extension dependency rules

## Mandatory

Every add-on requires the WorkCore parent.

## Effective product dependencies

```text
Business Network + Work Operations
    customer-to-job and support-to-job conversion

Work Operations + Property Operations
    jobs against premises/assets and vertical compliance

Work Operations + Workforce Assurance
    dispatch, rosters, attendance and assigned workers

Commercial + Work Operations
    estimate/job/invoice/payment lifecycle

Commercial + Business Network
    customer account, collections and communication

Property Operations + Workforce Assurance
    evidence, compliance and vertical operations
```

Marketplace metadata must express effective dependencies, or every optional cross-package call must fail safely through capability checks.

# Packaging conclusion

The six-extension family is valid, but marketplace manifests must describe effective runtime contributions and real cross-extension dependencies rather than only the small activation wrapper.

# WorkCore Integration

## Authority

WorkCore is the operational authority for customers, contacts, leads, providers, contractors, suppliers, properties, jobs, invoices, and related records. Titan Maps Intelligence stores external observations and candidate workflow state only.

## Required contracts

### `WorkCoreCandidateLookup`

```php
public function find(string $companyId, string $entityType, string $entityId): ?array;
```

The adapter must enforce WorkCore tenancy and return a normalised comparison projection. It must not return unrestricted internal records.

### `WorkCoreCandidateGateway`

```php
public function promote(
    string $companyId,
    string $targetType,
    array $fields,
    array $context,
): array;
```

The gateway must execute a WorkCore service/command, including validation, permissions, transaction, audit, and domain event. Expected result fields are:

```php
[
    'entity_type' => 'provider',
    'entity_id' => '...',
    'command_id' => '...',
    'event_id' => '...',
]
```

## Promotion constraints

Promotion requires:

- `candidate.promote` permission
- candidate in the same company
- approved review status
- no unresolved ambiguous match
- supported target type
- at least one explicitly accepted allowlisted field

The extension never writes WorkCore tables directly. `candidate_promotions` stores lineage to the WorkCore command and event.

## Recommended WorkCore commands

- `CreateLeadFromExternalCandidate`
- `CreateProviderFromExternalCandidate`
- `CreateSupplierFromExternalCandidate`
- `CreateContractorFromExternalCandidate`
- `CreateCustomerFromExternalCandidate`

Each command should retain external source references and distinguish imported public data from later verified operational data.

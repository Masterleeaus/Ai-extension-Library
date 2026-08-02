# Testing Guide

## Dependency-free suite

```bash
php tests/run.php
```

This executes pure behavioural and static-contract tests for DTO validation, provider request construction, provenance, canonicalisation, matching, ranking, lifecycle transitions, promotion, suppression, event catalogue, API protection, tool registration, processing governance, export writers, Meetup UI surfaces, and security markers.

## PHP syntax

```bash
find src tests database routes scripts -name '*.php' -print0 | xargs -0 -n1 php -l
```

## SDK validation

From the Titan SDK package:

```bash
php tests/generator_test.php
```

## Laravel host tests

`tests/Feature` contains host integration assertions for migrated schema, protected API, and queued search execution. Run them after installing the extension into a host with all required adapters:

```bash
php artisan test app/Extensions/TitanMapsIntelligence/tests/Feature
```

Skipped host assertions name the exact missing fixture or adapter rather than reporting a false pass.

## Required integrated-host additions

- Company A cannot read or mutate Company B searches, candidates, usage, or provider connections.
- A queued job with a mismatched company/search pair fails.
- A forged request `company_id` is rejected.
- Vault values never appear in logs, queue payloads, exceptions, or audit data.
- WorkCore promotion produces an operational event and extension lineage.

## Export package validation

The dependency-free suite confirms CSV and JSON content and checks that XLSX output is a ZIP-based Open XML package. In an integrated host, also verify signed-link expiry, private object ACLs, audit records, and cross-company denial.

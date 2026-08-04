# Upgrade and Migration Guide

## Versioning

Use semantic extension versions. Database migrations are append-only. Never edit a migration already applied to customer databases.

## Provider additions

A new provider should add an adapter, company connection configuration, capability declaration, normaliser tests, terms checklist, and usage mapping. Existing canonical records should not be rewritten merely because a provider is added.

## Schema changes

- add nullable columns first
- backfill through company-scoped queue jobs
- add constraints only after verification
- preserve field observations and promotion lineage
- support SQLite local mode and PostgreSQL cloud mode

## Replacing host adapters

Adapters may change internally if their contracts remain stable. During migration, verify active-company enforcement and compare WorkCore command/event output before enabling the new adapter.

## Removing a provider

Disable company connections, allow queued runs to reach a terminal state, retain permitted lineage, apply provider retention rules, and remove the registry factory in a later version.

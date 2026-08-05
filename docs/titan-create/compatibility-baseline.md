# Titan Create Compatibility Baseline

## Protected upgrade invariants

The following CreativeSuite behaviours and stored data are protected until compatibility tests prove a migration is safe:

- existing documents open, render, save, duplicate, rename and delete under correct ownership scope;
- legacy Konva scene JSON reconstructs without silent mutation;
- previews and uploaded assets remain resolvable;
- existing route names and configuration keys remain valid;
- existing migrations are not rewritten;
- user projects remain associated with their original owners and tenants.

## Versioning strategy

Add a versioned scene envelope around new semantic capabilities. Legacy scenes remain readable as their original schema and are upgraded through explicit, reversible migrators. Never overwrite a stored legacy scene merely because it was opened.

## Migration boundaries

Before retiring donor routes or tables:

1. introduce the canonical contract;
2. add an adapter for the donor;
3. dual-read where necessary;
4. migrate records with idempotent batches and audit events;
5. verify counts, ownership and representative outputs;
6. switch writes to the canonical owner;
7. retain rollback aliases during the defined compatibility window;
8. remove duplicate authority only after passing tests.

## Initial compatibility test inventory

- legacy CreativeSuite project serialisation and reconstruction;
- document ownership and tenant isolation;
- preview and asset reference preservation;
- route/config compatibility;
- semantic scene migration round-trip;
- generation-task donor adapter mapping;
- gallery/asset migration idempotency;
- rollback from canonical writes to donor-compatible reads.

## Rollback points

Each migration phase must include:

- a pre-migration database backup or reversible migration;
- an idempotent record map between donor and canonical IDs;
- feature flags for canonical reads/writes;
- documented commands to disable the new path;
- verification queries for record counts and orphaned relationships.

## Deferred from Issue #287

Issue #287 establishes contracts and compatibility only. Production schema changes, security fixes, service implementations and UI modifications are handled by subsequent Titan Create issues.

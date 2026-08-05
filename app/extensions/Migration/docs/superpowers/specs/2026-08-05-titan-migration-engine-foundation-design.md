# Titan Migration Engine Foundation Design

## Purpose

Import the existing Migration extension without changing its installed identity, then add the smallest stable abstraction required for future universal source connectors.

## Boundaries

The legacy driver layer remains responsible for the current Davinci migration flow. The new connector layer owns source metadata and future discovery/extraction contracts. Destination mapping, persistence, queues and UI are intentionally deferred to later issues.

## Components

- `ConnectorDefinition`: immutable, validated connector metadata.
- `MigrationSourceConnectorInterface`: source-facing connection, discovery, estimation, streaming, incremental and redaction contract.
- `ConnectorRegistry`: deterministic key-based registration and lookup.
- `LegacyDavinciConnector`: advertises Davinci through the new registry while refusing unsupported new-pipeline operations explicitly.
- `MigrationServiceProvider`: registers the new registry and keeps the old `MigrationService` singleton.

## Compatibility

The folder, namespace, service provider, config namespace, route names, database migrations, driver enum and Davinci driver remain intact. Customer-facing manifest copy changes to Titan Migration Engine.

## Verification

A dependency-free PHP test covers registration, lookup, deterministic ordering, duplicate rejection and unknown-key rejection. Every PHP file is syntax checked.

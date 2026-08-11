# Migration Engine Generic Connectors Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver safe, read-only, streaming source connectors and common schema discovery for files, databases and APIs while routing the preserved Davinci surface through the same extraction layer.

**Architecture:** Extend the existing `MigrationSourceConnectorInterface` with shared safety/discovery primitives rather than source-specific ad hoc logic. File connectors use generators or bounded chunk readers, database connectors use PDO with catalog-only discovery and guarded SELECT extraction, API connectors allow only read semantics, and all discovery results pass through a common profiler/fingerprint/masking layer. The legacy Davinci connector delegates SQL extraction to the new SQL dump connector while the existing `DavinciDriver` remains untouched.

**Tech Stack:** PHP 8.2+, Laravel 10, PDO, ZipArchive, XMLReader, PhpSpreadsheet already present in the host application, GitHub Actions.

## Global Constraints

- Preserve `app/extensions/Migration`, `App\Extensions\Migration`, `MigrationServiceProvider`, config namespace `migration`, legacy route names and `davinci` source value.
- Source discovery and extraction are read-only; source connectors must never expose a write primitive.
- Discovery samples and connector configuration must redact secrets and sensitive values.
- Large row-oriented sources must stream or chunk with bounded memory.
- Archive traversal, decompression bombs and unsafe nested export paths must be rejected.
- Schema fingerprints must be deterministic and breaking changes must be detectable before execution.
- Do not write directly to Titan destination models in this pass.

---

### Task 1: Lock the connector safety contract

**Files:**
- Create: `app/extensions/Migration/tests/MigrationGenericConnectorContractTest.php`
- Create: `.github/workflows/migration-engine-connectors.yml`

**Interfaces:**
- Consumes: existing `MigrationSourceConnectorInterface` and `ConnectorDefinition`.
- Produces: executable acceptance contract covering connector catalog, read-only behavior, redaction, schema change detection and representative streaming fixtures.

- [ ] Write the contract before production code.
- [ ] Verify CI fails because the new connector/safety classes do not exist.
- [ ] Preserve the failing run as RED evidence in the PR.

### Task 2: Add shared safety and discovery primitives

**Files:**
- Create: `System/Connectors/AbstractSourceConnector.php`
- Create: `System/Connectors/BuiltinConnectorCatalog.php`
- Create: `System/Discovery/DiscoveryProfiler.php`
- Create: `System/Discovery/SchemaFingerprint.php`
- Create: `System/Discovery/SchemaChangeDetector.php`
- Create: `System/Discovery/TypeInferrer.php`
- Create: `System/Security/ConnectorConfigurationRedactor.php`
- Create: `System/Security/SensitiveValueMasker.php`
- Create: `System/Security/ReadOnlySqlGuard.php`
- Create: `System/Security/GraphQlReadOnlyGuard.php`
- Create: `System/Security/ArchiveSafetyGuard.php`
- Modify: `System/Connectors/ConnectorDefinition.php`

**Interfaces:**
- `ConnectorDefinition` gains backward-compatible `readOnly` and `streamingMode` metadata.
- `DiscoveryProfiler::profile(iterable $rows, string $entity, int $sampleLimit = 5): array` returns count, fields, masked samples and inferred key/incremental/relationship/attachment hints.
- `SchemaFingerprint::fromDiscovery(array $discovery): string` returns deterministic SHA-256 without sample values.
- `SchemaChangeDetector::compare(array $before, array $after): array` returns `breaking` and change details.

- [ ] Implement redaction recursively for passwords, secrets, tokens, keys, authorization headers and cookies.
- [ ] Implement sample masking for email, phone, credentials, tokens and identity-like fields.
- [ ] Implement SQL guard permitting one read-only `SELECT`/read CTE and rejecting writes, locking clauses, file output and multiple statements.
- [ ] Implement GraphQL guard permitting query/introspection only and rejecting mutation/subscription.
- [ ] Implement archive entry validation for traversal, absolute paths, entry count, per-entry size, total expanded size and compression ratio.
- [ ] Implement deterministic discovery profiling and schema change classification.

### Task 3: Build streaming file connectors

**Files:**
- Create: `System/Connectors/File/AbstractFileConnector.php`
- Create: `System/Connectors/File/FileConnectorFactory.php`
- Create: `System/Connectors/File/CsvConnector.php`
- Create: `System/Connectors/File/JsonConnector.php`
- Create: `System/Connectors/File/JsonLinesConnector.php`
- Create: `System/Connectors/File/XmlConnector.php`
- Create: `System/Connectors/File/XlsxConnector.php`
- Create: `System/Connectors/File/XlsxChunkReadFilter.php`
- Create: `System/Connectors/File/ZipBundleConnector.php`
- Create: `System/Connectors/File/SqlDumpConnector.php`
- Create: `System/Discovery/StreamingJsonArrayReader.php`
- Create: `System/Discovery/SqlDumpScanner.php`

**Interfaces:**
- All connectors implement `MigrationSourceConnectorInterface`.
- Configuration uses `path`; ZIP additionally accepts `entry`; XML accepts optional `record_element`; XLSX accepts optional `sheet` and `chunk_size`.
- `stream()` always returns an iterable that reads rows lazily or in bounded chunks.

- [ ] CSV uses `fgetcsv` and generator iteration.
- [ ] JSONL reads one line at a time; JSON top-level arrays use a streaming parser instead of whole-document decode.
- [ ] XML uses `XMLReader` record iteration.
- [ ] XLSX uses PhpSpreadsheet chunk filters and never materializes all rows as a PHP array.
- [ ] ZIP validates every entry before extraction and delegates a selected supported entry through `FileConnectorFactory`.
- [ ] SQL dump scanner discovers MySQL-style table definitions and streams INSERT rows without executing SQL.

### Task 4: Build read-only database connectors

**Files:**
- Create: `System/Connectors/Database/AbstractPdoConnector.php`
- Create: `System/Connectors/Database/MySqlConnector.php`
- Create: `System/Connectors/Database/PostgreSqlConnector.php`
- Create: `System/Connectors/Database/SqlServerConnector.php`
- Create: `System/Connectors/Database/SqliteConnector.php`

**Interfaces:**
- Configuration accepts host/port/database/schema/user/password as appropriate; SQLite accepts a path.
- Discovery queries system catalogs only.
- `stream()` accepts entity/table, selected fields, optional incremental field and checkpoint and executes a guarded SELECT only.

- [ ] MySQL requests a read-only transaction/session before extraction.
- [ ] PostgreSQL enables `default_transaction_read_only` before extraction.
- [ ] SQL Server exposes client-enforced read-only capability and executes only generated catalog/SELECT statements.
- [ ] SQLite opens using URI read-only mode and never issues DDL/DML.
- [ ] Validate identifiers before interpolation and bind all values.

### Task 5: Build read-only API connectors

**Files:**
- Create: `System/Connectors/Api/RestConnector.php`
- Create: `System/Connectors/Api/GraphqlConnector.php`
- Create: `System/Connectors/Api/OpenApiConnector.php`

**Interfaces:**
- REST discovery/extraction uses GET/HEAD resources only.
- GraphQL uses validated query/introspection documents and rejects mutation/subscription.
- OpenAPI accepts a spec array, local JSON/YAML document when parser support exists, or a GET URL; only GET operations become migratable resources.

- [ ] Bound page sizes and page counts.
- [ ] Redact authorization/cookie/query credential configuration.
- [ ] Feed response samples through common masking/profiling.
- [ ] Never expose generic arbitrary-method request execution.

### Task 6: Register built-ins and adapt Davinci

**Files:**
- Modify: `System/MigrationServiceProvider.php`
- Modify: `System/Connectors/Legacy/LegacyDavinciConnector.php`
- Modify: `config/migration.php`

**Interfaces:**
- `BuiltinConnectorCatalog::classes()` enumerates generic connector classes without duplicates.
- Provider registers generic connectors plus preserved `davinci` key.
- Davinci delegates `testConnection`, `discover`, `estimate` and `stream` to `SqlDumpConnector` using the legacy `sql_file` configuration while retaining the `DavinciDriver` for the old workflow.

- [ ] Register all connectors through the existing `ConnectorRegistry`.
- [ ] Keep `davinci` key and legacy driver route behavior unchanged.
- [ ] Advertise Davinci discovery/streaming only after delegation exists.

### Task 7: Verify and integrate

**Files:**
- Modify only if verification exposes a defect in files above.

- [ ] Run `php app/extensions/Migration/tests/MigrationGenericConnectorContractTest.php` and require PASS.
- [ ] Run PHP lint across `app/extensions/Migration` and require zero syntax errors.
- [ ] Require existing `Migration Engine Foundation` and `Migration Engine Tenancy` workflows to remain green.
- [ ] Inspect final branch diff for unrelated drift.
- [ ] Mark PR ready, merge to `main`, confirm issue #379 closes as completed, and update epic #376 progress.
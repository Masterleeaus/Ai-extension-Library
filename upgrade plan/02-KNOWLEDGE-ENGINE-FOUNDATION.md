# Titan Knowledge Engine Foundation

## Objective

Create one tenant-safe, reusable knowledge domain for all extension training and retrieval while preserving existing extension routes, views, and marketplace identities during migration.

The Knowledge Engine owns documents, versions, chunks, embeddings, ingestion state, assignments, retrieval, and citations. UnifiedMemory may receive derived facts and source references only.

## First production vertical slice

```text
Submit one plain-text source
→ authorize tenant and user
→ validate and deduplicate
→ create logical source and immutable document version
→ chunk and keyword-index
→ assign one knowledge base to two agents
→ retrieve evidence through search_knowledge
→ return stable citations
→ retain legacy controller response shape
```

PDF, website crawling, spreadsheets, vector search, and bulk migration are added after this path proves tenancy, contracts, jobs, assignments, retrieval, citations, and compatibility.

## Canonical schema

Every authoritative table includes `tenant_id`. UUIDs are used at API and event boundaries; numeric IDs may remain internal.

### `knowledge_bases`

- `id`
- `uuid`
- `tenant_id`
- `name`
- `description`
- `status`
- `default_language`
- `retrieval_config_json`
- `created_by`
- timestamps and soft delete

### `knowledge_sources`

Represents a logical user or integration source.

- `id`
- `uuid`
- `tenant_id`
- `knowledge_base_id`
- `source_type`
- `source_identity_hash`
- `original_name`
- `original_uri`
- `canonical_uri`
- `status`
- `metadata_json`
- `created_by`
- timestamps and soft delete

Suggested source types:

- text
- file
- website
- sitemap
- API
- database
- integration

### `knowledge_source_fetches`

Records URL fetch and security decisions.

- `tenant_id`
- `source_id`
- requested and final URI
- resolved IP
- HTTP status and content type
- content length
- redirect count
- fetch timestamps
- security decision
- failure code

### `knowledge_documents`

Stable logical document identity. One website source may produce many documents.

- `id`
- `uuid`
- `tenant_id`
- `knowledge_source_id`
- `canonical_uri`
- `title`
- `language`
- `latest_version_id`
- `status`
- `metadata_json`
- timestamps and soft delete

### `knowledge_document_versions`

Immutable extracted content versions.

- `id`
- `tenant_id`
- `knowledge_document_id`
- `version_number`
- `raw_storage_path`
- `normalised_storage_path`
- `extracted_text_preview`
- `raw_content_hash`
- `normalised_content_hash`
- `parser_key`
- `parser_version`
- `character_count`
- `token_count`
- `created_at`

Large source and normalized content remains in private object storage. Small plain-text submissions may use a controlled inline storage policy.

### `knowledge_chunks`

- `id`
- `uuid`
- `tenant_id`
- `knowledge_document_version_id`
- `chunk_index`
- `content`
- `chunk_content_hash`
- `token_count`
- `section_path`
- `page_number`
- start and end offsets
- `metadata_json`
- `created_at`

### `knowledge_embeddings`

- `id`
- `tenant_id`
- `knowledge_chunk_id`
- `provider`
- `model`
- `dimensions`
- `vector_store`
- `vector_namespace`
- `vector_reference`
- `embedding_input_hash`
- `status`
- timestamps

The primary production design uses a vector-store adapter such as pgvector, Qdrant, Pinecone, or Weaviate. JSON vector storage is a development or compatibility fallback only.

### `knowledge_assignments`

- `id`
- `tenant_id`
- `knowledge_base_id`
- `assignable_type`
- `assignable_id`
- `permissions_json`
- `retrieval_policy_json`
- `active`
- assigned by and timestamps

Assignable types may include agent, chatbot, voice agent, workflow, team, and user.

### `knowledge_access_rules`

Adds conditional access beyond assignments.

- `tenant_id`
- `knowledge_base_id`
- principal type and ID
- permission
- effect
- `conditions_json`
- timestamps

### `knowledge_ingestion_jobs`

- `id`
- `uuid`
- `tenant_id`
- `knowledge_source_id`
- status
- current stage
- stage status
- progress current and total
- progress unit
- status message
- attempt count
- idempotency key
- heartbeat, start, completion, failure, and cancellation timestamps
- error code and sanitized message
- `metadata_json`

Stages:

```text
queued
validating
quarantining
downloading
extracting
normalising
versioning
chunking
embedding
indexing
verifying
publishing
completed
failed
cancelled
```

### `knowledge_ingestion_events`

Immutable job timeline:

- tenant and job IDs
- sequence
- stage
- level
- event code
- sanitized message
- metadata
- timestamp

### `knowledge_query_logs`

- tenant, knowledge base, agent, user, and conversation IDs
- original and optionally rewritten query
- strategy
- candidate and selected counts
- latency
- provider usage
- redaction and retention state
- timestamp

Sensitive query text must follow explicit retention and redaction policy.

### `knowledge_citations`

- tenant and query log IDs
- chunk ID
- rank
- retrieval and rerank scores
- citation label
- bounded excerpt
- metadata
- timestamp

Citations remain attached to immutable document versions so newer versions do not invalidate old answer evidence.

## Hash and deduplication policy

Use separate hashes for separate identities:

- source identity hash;
- raw content hash;
- normalized content hash;
- document content hash;
- chunk content hash;
- embedding input hash.

Example logical uniqueness:

```text
tenant + knowledge base + source identity hash
tenant + document + version number
tenant + document version + chunk index
tenant + chunk + provider + model + embedding input hash
```

Cross-tenant physical deduplication may be explored later, but tenant-visible logical records must remain isolated.

## Typed commands

Do not pass generic source type/path/metadata arguments.

Use typed commands:

- `SubmitTextSourceCommand`
- `SubmitFileSourceCommand`
- `SubmitUrlSourceCommand`
- `SubmitWebsiteSourceCommand`
- `SubmitApiSourceCommand`
- `AssignKnowledgeBaseCommand`
- `DeleteKnowledgeSourceCommand`
- `ReingestKnowledgeSourceCommand`

Every command contains tenant-safe IDs, explicit source data, assignments, policy options, and an idempotency key.

## Core contracts

### ExecutionContext

Carries tenant, actor, agent, conversation, customer, correlation, causation, permissions, channel, locale, budget, and idempotency state.

### KnowledgeIngestionService

Responsibilities:

- authorize submission;
- create or resolve logical source;
- create durable ingestion job;
- dispatch stage pipeline;
- retry or cancel safely;
- expose job state.

### DocumentParserRegistry

Resolves parsers by capabilities, MIME, and source metadata. Parser outputs normalized document collections and diagnostics rather than writing directly to storage.

Initial parsers:

1. plain text;
2. PDF;
3. HTML/web page;
4. spreadsheet;
5. structured API payload.

### ChunkingStrategy

Returns stable ordered chunks with section, page, offsets, token counts, and metadata. Strategies are versioned so a document can be re-chunked without changing its source identity.

### EmbeddingProvider

Provides model identity, dimensions, batch embedding, usage, rate-limit state, retry classification, and safe error reporting.

### VectorStore

Provides upsert, delete, search, namespace, health, and index-version operations. Tenant filters are mandatory before similarity search results can become candidates.

### KnowledgeRetriever

Accepts a typed `KnowledgeSearchQuery` and returns evidence. It does not write the final conversational answer.

## Retrieval pipeline

```text
authorization and assignment filtering
→ query normalization
→ optional query rewriting
→ keyword candidate retrieval
+ vector candidate retrieval
→ metadata and access-rule filtering
→ reciprocal-rank or weighted fusion
→ optional reranking
→ context-budget selection
→ citation construction
→ query log
```

A keyword result followed by vector fallback is not hybrid retrieval. Both systems contribute candidates when hybrid mode is enabled.

## `search_knowledge` registry tool

### Purpose

Return ranked evidence from knowledge bases assigned to the calling agent or explicitly permitted by policy.

### Inputs

- query;
- optional knowledge base IDs;
- result limit;
- maximum context tokens;
- metadata filters;
- retrieval strategy;
- rerank flag.

### Outputs

- ranked matches;
- source and document IDs;
- immutable document-version reference;
- title and canonical URI;
- page or section location;
- bounded excerpt;
- citation handle;
- retrieval diagnostics;
- latency and usage.

The calling agent composes the answer. A future `answer_from_knowledge` capability may wrap retrieval and generation, but retrieval remains independently reusable.

## Queue pipeline

```text
ValidateSourceJob
→ QuarantineFileJob or FetchUrlJob when required
→ ParseDocumentJob
→ NormaliseDocumentJob
→ CreateDocumentVersionJob
→ ChunkDocumentJob
→ GenerateEmbeddingsJob
→ IndexChunksJob
→ VerifyKnowledgeSourceJob
→ PublishKnowledgeSourceJob
```

Requirements:

- unique submission lock;
- idempotent stage handlers;
- retry classification and exponential backoff;
- poison-job and dead-letter handling;
- heartbeat and stale-job recovery;
- cancellation checks between stages;
- tenant concurrency limits;
- provider rate limits;
- partial embedding-batch recovery;
- cleanup or compensation after failure;
- no single long-running transaction across the pipeline.

## Security controls

### URL and website ingestion

- HTTP and HTTPS only;
- reject embedded credentials;
- DNS resolution before connection;
- reject loopback, link-local, private, reserved, metadata, and internal ranges;
- protect against DNS rebinding;
- revalidate each redirect destination;
- cap redirects, response bytes, pages, crawl depth, connection time, and total time;
- validate content type;
- record requested/final URIs and resolved IPs;
- enforce tenant domain policy where configured;
- define robots and crawl behavior explicitly.

### File ingestion

- validate extension, MIME, and magic bytes;
- generate server-side names;
- quarantine before parsing;
- malware scan before promotion;
- reject unsupported encrypted or malformed documents;
- prevent archive traversal and decompression bombs;
- cap pages, worksheets, rows, cells, characters, and extracted media;
- never execute macros, embedded scripts, or binaries;
- isolate parsers with bounded CPU, memory, time, and output.

## Event model

Publish immutable EventEnvelope instances such as:

- `knowledge.source.submitted`;
- `knowledge.ingestion.progressed`;
- `knowledge.document.versioned`;
- `knowledge.source.ready`;
- `knowledge.source.failed`;
- `knowledge.query.completed`.

Payloads contain tenant-safe identifiers and compact metadata, not Eloquent models, source contents, vectors, credentials, or unredacted errors.

## Legacy controller adapter pattern

Existing routes stay operational. Controllers:

1. validate the legacy request;
2. resolve tenant and extension entity;
3. build a typed submission command;
4. call the shared ingestion service;
5. map the new job/source state into the exact legacy JSON response contract.

Initial adapter candidate: `PhoneCallAgentTrainController` or the ChatbotVoice training controller. Migrate one extension first and measure before broad rollout.

## Migration strategy

1. Add canonical schema and services behind feature flags.
2. Implement secure plain-text vertical slice.
3. Backfill one legacy extension.
4. Compare source, document, chunk, assignment, and permission counts.
5. Shadow-query old and new retrieval.
6. Evaluate result agreement, citation correctness, latency, and cost.
7. Use temporary dual writes only where rollback requires them.
8. Move one extension to canonical reads.
9. Observe, repair, and expand progressively.
10. Freeze legacy writes and archive after the retention window.

## Retrieval evaluation

Create a small labelled dataset for each migrated extension:

- question;
- expected source;
- relevant pages or sections;
- forbidden tenant/source;
- expected answerability;
- acceptable citation set.

Track:

- recall at K;
- mean reciprocal rank;
- citation precision;
- tenant-filter correctness;
- empty-result correctness;
- latency;
- token and provider cost;
- regression between releases.

## Definition of done

The first complete milestone requires:

- every authoritative query is tenant-filtered before candidate retrieval;
- authorization is checked before submission, assignment, deletion, and search;
- retries do not duplicate sources, versions, chunks, embeddings, or vectors;
- citations remain valid after newer document versions are added;
- removing one assignment does not delete shared knowledge;
- final-source deletion follows retention and audit policy;
- failed embedding batches resume without re-parsing documents;
- vector-provider outage degrades safely to keyword retrieval;
- stale jobs are detected and recovered;
- cancellation prevents future pipeline stages;
- secrets and raw credentials never enter logs, events, or queue diagnostics;
- legacy routes retain expected response contracts;
- migration remains reversible during the rollout window;
- retrieval quality passes the labelled evaluation set;
- tenant A cannot retrieve, cite, infer, or enumerate tenant B knowledge.

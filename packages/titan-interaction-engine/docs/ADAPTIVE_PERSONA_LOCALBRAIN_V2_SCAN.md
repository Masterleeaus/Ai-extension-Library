# Adaptive Persona Engine deep scan and LocalBrain v2 extraction

## Scope

This report covers the uploaded `Adaptive-Persona-Engine-Offline-Intent-Infrastructure-main.zip` and compares its claimed capabilities with the code actually present. The donor contains 399 lines of Python application code, a 12,655,350-byte conversation dataset, documentation, and no automated tests.

## Executive finding

The archive contains useful prototypes for persona metrics, a Multinomial Naive Bayes classifier and recency/emotion reranking. It does **not** contain the complete system described by its README.

The reusable ideas were adapted into LocalBrain v2. Incomplete or unsafe runtime choices were not copied.

## Claim-by-claim evidence

| Claimed capability | Code status | Finding |
|---|---|---|
| Sentiment and formality metrics | Present | `persona_engine.py` computes TextBlob polarity and a contraction ratio. Useful as a prototype, but not sufficient to infer a psychological persona. |
| Three-day rolling drift and >20% trigger | Missing | The README describes it, but `run_drift_detection()` only calculates each session independently. It does not calculate a rolling baseline, variance or threshold. RAKE runs for every session rather than only a drift event. |
| Offline MNB classifier | Present, incomplete | TF-IDF + `MultinomialNB` is implemented. The included training corpus has only 25 synthetic examples and no bundled model file or test set. |
| 1.8 MB model / 2.3 ms latency | Unsupported by archive | No model or benchmark output is included. Generating the donor model here produced an 11,891-byte pickle and approximately 0.197 ms mean inference across 10,000 predictions on this machine. Hardware differs, so these are local measurements, not universal guarantees. |
| Weighted semantic/recency/emotion reranking | Partial | Chroma performs semantic retrieval, but the final Python score ignores returned similarity/distance and uses 50% recency + 50% emotion. The README says 40/40/20 and the design document says 50/50. |
| Contradiction detection | Not actually implemented | Every result below rank one is placed in `contradictions`; no fact key, entity, value or logical contradiction test exists. |
| Vector clock sync | Missing | Vector clocks, LWW and causal ordering appear only in Markdown. No vector-clock type, compare, tick, merge or sync integration exists. |
| Local-first privacy | Partial | Processing is local, but the API initializes heavy global services, uses unscoped local paths, has no tenant/user isolation and returns raw exception details. |

## Donor runtime risks

1. `pickle.load()` executes Python object deserialization and must not accept untrusted model files.
2. ChromaDB, Sentence Transformers and RAKE/NLTK are required but not bundled. In this environment ChromaDB, Sentence Transformers and RAKE were absent, so the advertised API could not boot as supplied.
3. The classifier's synthetic examples confuse assistant-language with user intent. In a local benchmark it classified “Prepare a quote for carpet cleaning” as `action-item` and “I feel overwhelmed and need support” as `unknown`.
4. Re-running persona analysis inserts duplicate drift rows.
5. The SQLite schema has no tenant, user, device, idempotency or source lineage.
6. The RAG resolver creates memory IDs from wall-clock timestamps and has no deterministic idempotency key.
7. Generic exception strings are returned through HTTP 500 responses.
8. There are no unit, integration, benchmark or synchronization tests.

## What was extracted into LocalBrain v2

### Adaptive persona drift

New PHP:

- `src/LocalIntelligence/Persona/PersonaSignalAnalyzer.php`
- `src/LocalIntelligence/Persona/KeywordTriggerExtractor.php`
- `src/LocalIntelligence/Persona/BehavioralDriftTracker.php`

New device TypeScript:

- `resources/ts/offline/persona-drift.ts`

Improvements:

- Real rolling baseline with a configurable three-observation default.
- Configurable drift threshold with a 0.20 default.
- Trigger extraction only when drift is detected.
- Tenant/user persistence through `LocalIntelligenceMemoryStoreInterface`.
- Observation, time and device lineage.
- Explicit language that metrics are interaction signals, not psychological diagnosis.

### Offline MNB intent classification

New PHP:

- `src/LocalIntelligence/Language/NaiveBayesIntentClassifier.php`

New device TypeScript:

- `resources/ts/offline/naive-bayes-intent.ts`

Integration:

- `LocalLanguageEngine` now keeps deterministic business rules as the highest-confidence path and uses MNB as a local fallback.
- The corpus covers current WorkCore intents plus reminder, emotional support, action item and small talk categories.
- Conversational classifications cannot become unregistered WorkCore commands.
- Models are trained from reviewed arrays and never loaded from pickle.

Local benchmark on this machine after warm-up:

| Runtime | Predictions | Mean latency | Estimated model size |
|---|---:|---:|---:|
| PHP 8.4 CLI | 10,000 | ~0.0106 ms | 6,949 bytes |
| Node 22 | 10,000 | ~0.0059 ms | 6,964 bytes |

These numbers demonstrate the implementation is lightweight; they are not device-wide performance guarantees.

### Conflict-aware memory reranking

New PHP:

- `src/LocalIntelligence/Reasoning/WeightedMemoryReranker.php`

New device TypeScript:

- `resources/ts/offline/memory-reranker.ts`

Improvements:

- Implements the advertised 40% semantic + 40% recency + 20% emotional weighting.
- Requires the retrieval layer to provide semantic similarity rather than pretending emotion and age are semantic relevance.
- Labels a candidate a contradiction only when it shares a fact key and has a different fact value.
- Keeps unrelated memories as alternatives.
- Uses deterministic tie-breaking.

### Vector clock and causal conflict handling

The donor had no extractable implementation, so the documented design was converted into new tested code:

New PHP:

- `src/LocalIntelligence/Sync/VectorClock.php`
- `src/LocalIntelligence/Sync/CausalSyncResolver.php`

New device TypeScript:

- `resources/ts/offline/vector-clock.ts`

Capabilities:

- Tick, merge and compare.
- `before`, `after`, `equal` and `concurrent` relations.
- LWW only for an explicit allowlist of fields.
- Manual-review conflicts for other concurrent field changes.
- Merged vector-clock preservation.

## LocalBrain v2 integration

`LocalBrain` now identifies itself as `local-brain-v2` and adds a `persona` result while preserving the existing perception, decision, suggestions, prediction, confidence and audit response fields.

It also exposes `rankMemories()` for conflict-aware retrieval. Existing persistence and cognitive-event boundaries remain in place.

## Verification

Added:

- `tests/localbrain_v2_run.php`
- `tests/ts/localbrain-v2.test.js`

Coverage includes:

- Rolling persona drift.
- Sentiment and formality metrics.
- MNB business and conversational intents.
- Fail-closed local-only conversational intents.
- Weighted memory ranking and genuine contradiction detection.
- Vector-clock concurrency and merge.
- LWW allowlisting and explicit unresolved conflicts.
- Existing LocalBrain output compatibility.

## Remaining limitations

- Sentiment and formality are heuristic interaction signals, not clinical, personality or mental-health conclusions.
- The bundled MNB corpus is a safe baseline, not a production-quality universal language model. It should be improved using reviewed, anonymised, opt-in correction data.
- Semantic similarity must come from the Interaction Engine embedding/vector layer or another trusted local provider.
- Vector clocks only become effective when host storage and sync APIs persist and exchange them.
- Cross-device conflict policies must be defined per entity and field; LWW should remain exceptional rather than the default.

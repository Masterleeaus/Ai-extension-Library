# LocalBrain v2

LocalBrain v2 adds adaptive interaction signals, dependency-free offline intent classification, conflict-aware memory ranking and causal multi-device primitives without granting local intelligence permission to mutate WorkCore directly.

## Processing

```php
$brain = LocalBrain::createDefault();

$result = $brain->process('Prepare a quote for Alex tomorrow', [
    'tenant_id' => 'tenant-a',
    'user_id' => 'user-7',
    'device_id' => 'phone-1',
    'correlation_id' => 'corr-42',
    'observation_id' => 'message-109',
    'observed_at' => '2026-08-03T10:00:00+10:00',
]);
```

The result retains the v1 response and adds:

```json
{
  "model_version": "local-brain-v2",
  "persona": {
    "metrics": {
      "sentiment_polarity": 0.5,
      "formality": 0.72,
      "emotional_intensity": 0.35,
      "label": "positive & formal"
    },
    "baseline": null,
    "drift_event": false,
    "triggers": []
  }
}
```

## Memory reranking

```php
$ranking = $brain->rankMemories([
    [
        'id' => 'memory-a',
        'content' => 'The customer moved to Sydney.',
        'semantic_similarity' => 0.91,
        'timestamp' => '2026-08-01T10:00:00+10:00',
        'emotional_intensity' => 0.2,
        'fact_key' => 'customer_city',
        'fact_value' => 'Sydney',
    ],
]);
```

The reranker does not generate embeddings. It consumes trusted similarity scores from the retrieval layer.

## Device imports

```ts
import {
  BehavioralDriftTracker,
  NaiveBayesIntentClassifier,
  WeightedMemoryReranker,
  compareVectorClocks,
  mergeVectorClocks,
  resolveCausalState,
} from '@titanzero/interaction-engine-offline';
```

## Governance

- Persona signals are private user-level observations by default.
- Conversational intents do not become WorkCore commands.
- WorkCore mutations still require registered capabilities and authority policy.
- Concurrent fields use manual review unless explicitly approved for LWW.
- Do not use persona metrics for medical, employment, insurance or eligibility decisions.

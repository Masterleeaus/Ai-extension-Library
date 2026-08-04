<?php

declare(strict_types=1);

namespace TitanZero\Interaction\LocalIntelligence;

use TitanZero\Interaction\LocalIntelligence\Decision\DecisionTreeEngine;
use TitanZero\Interaction\LocalIntelligence\Language\LocalLanguageEngine;
use TitanZero\Interaction\LocalIntelligence\Memory\BehavioralMemory;
use TitanZero\Interaction\LocalIntelligence\Prediction\PredictiveCompletionEngine;
use TitanZero\Interaction\LocalIntelligence\Reasoning\HybridReasoner;
use TitanZero\Interaction\LocalIntelligence\Reasoning\WeightedMemoryReranker;
use TitanZero\Interaction\LocalIntelligence\Persona\BehavioralDriftTracker;
use TitanZero\Interaction\LocalIntelligence\Storage\LocalIntelligenceMemoryStoreInterface;
use TitanZero\Interaction\LocalIntelligence\Temporal\TemporalIntelligence;
use TitanZero\Interaction\Cognition\Decision\DecisionRecorder;
use TitanZero\Interaction\Cognition\Events\CognitiveEventStoreInterface;
use TitanZero\Interaction\Cognition\Events\InMemoryCognitiveEventStore;

final class LocalBrain
{
    public const MODEL_VERSION = 'local-brain-v2';

    public function __construct(
        private readonly LocalLanguageEngine $language,
        private readonly DecisionTreeEngine $decisionTree,
        private readonly BehavioralMemory $memory,
        private readonly HybridReasoner $reasoner,
        private readonly TemporalIntelligence $temporal,
        private readonly PredictiveCompletionEngine $completion,
        private readonly ?DecisionRecorder $decisionRecorder = null,
        private readonly BehavioralDriftTracker $persona = new BehavioralDriftTracker(),
        private readonly WeightedMemoryReranker $memoryReranker = new WeightedMemoryReranker(),
    ) {}

    public static function createDefault(): self
    {
        $reasoner = self::defaultReasoner();
        return new self(
            new LocalLanguageEngine(),
            new DecisionTreeEngine(),
            new BehavioralMemory(),
            $reasoner,
            new TemporalIntelligence(),
            new PredictiveCompletionEngine(),
            new DecisionRecorder(new InMemoryCognitiveEventStore()),
            new BehavioralDriftTracker(),
            new WeightedMemoryReranker(),
        );
    }

    /**
     * Real, persistence-backed construction used by the service provider.
     * createDefault() remains dependency-free for the standalone test harness.
     */
    public static function createWithPersistence(
        LocalIntelligenceMemoryStoreInterface $store,
        string $tenantId,
        ?CognitiveEventStoreInterface $cognitiveEvents = null,
    ): self {
        return new self(
            new LocalLanguageEngine(),
            new DecisionTreeEngine(),
            new BehavioralMemory($store, $tenantId),
            self::defaultReasoner(),
            new TemporalIntelligence($store, $tenantId),
            new PredictiveCompletionEngine($store, $tenantId),
            $cognitiveEvents === null ? null : new DecisionRecorder($cognitiveEvents),
            new BehavioralDriftTracker(store: $store, tenantId: $tenantId),
            new WeightedMemoryReranker(),
        );
    }

    private static function defaultReasoner(): HybridReasoner
    {
        $reasoner = new HybridReasoner();
        $reasoner->addRule('required_customer_for_transaction', static function (array $candidate, array $context): bool {
            $action = $candidate['action'] ?? '';
            if (!in_array($action, ['create_quote', 'schedule_job', 'create_invoice'], true)) {
                return true;
            }
            return !empty($context['entities']['customer']) || !empty($context['customer_id']);
        });
        return $reasoner;
    }

    public function process(string $input, array $context = []): array
    {
        $perception = $this->language->understand($input);
        $intent = $perception['intent'];
        $operationalIntents = ['schedule_job', 'create_quote', 'create_invoice', 'record_payment', 'create_customer', 'complete_job', 'report_damage'];
        $candidate = ['action' => in_array($intent, $operationalIntents, true) ? $intent : null, 'confidence' => $perception['confidence']];
        $reasoningContext = array_replace($context, ['entities' => $perception['entities']]);
        $decision = $this->reasoner->reason([$candidate], $reasoningContext);
        $userId = $context['user_id'] ?? 'anonymous';
        $prediction = $this->memory->predictNextAction($userId, $decision['action']);
        $suggestions = [];
        if (!in_array($intent, $operationalIntents, true)) {
            $suggestions[] = 'Intent classified locally; no WorkCore command was prepared.';
        } elseif ($decision['needs_clarification']) {
            $suggestions[] = 'Provide the missing customer or job details before execution.';
        } else {
            $suggestions[] = 'Open the matching wizard with extracted fields prefilled.';
        }
        if ($prediction['action'] !== null) {
            $suggestions[] = 'Likely next action: ' . $prediction['action'];
        }
        $tenantId = (string) ($context['tenant_id'] ?? 'local');
        if ($decision['action'] !== null && $this->decisionRecorder !== null) {
            $this->decisionRecorder->recordRecommendation(
                tenantId: $tenantId,
                proposedAction: (string) $decision['action'],
                confidence: (float) $decision['confidence'],
                scope: [
                    'user_id' => (string) $userId,
                    'device_id' => $context['device_id'] ?? null,
                    'team_id' => $context['team_id'] ?? null,
                    'subject_type' => $context['subject_type'] ?? null,
                    'subject_id' => $context['subject_id'] ?? null,
                    'correlation_id' => $context['correlation_id'] ?? null,
                    'model_version' => self::MODEL_VERSION,
                    'privacy_class' => $context['privacy_class'] ?? 'user_private',
                ],
                evidence: (array) ($decision['evidence'] ?? []),
            );
        }

        $observedAt = null;
        if (is_string($context['observed_at'] ?? null)) {
            try {
                $observedAt = new \DateTimeImmutable($context['observed_at']);
            } catch (\Throwable) {
                $observedAt = null;
            }
        }
        $persona = $this->persona->observe(
            $userId,
            $input,
            $observedAt,
            isset($context['observation_id']) ? (string) $context['observation_id'] : null,
            isset($context['device_id']) ? (string) $context['device_id'] : null,
        );

        return [
            'mode' => 'offline',
            'model_version' => self::MODEL_VERSION,
            'perception' => $perception,
            'persona' => $persona,
            'decision' => $decision,
            'suggestions' => $suggestions,
            'prediction' => $prediction,
            'confidence' => $decision['confidence'],
            'audit' => ['processed_at' => gmdate(DATE_ATOM), 'cloud_used' => false],
        ];
    }

    /** @param list<array<string, mixed>> $candidates @return array<string, mixed> */
    public function rankMemories(array $candidates, ?\DateTimeInterface $now = null, int $limit = 5): array
    {
        return $this->memoryReranker->rank($candidates, $now, $limit);
    }

    public function confirmAction(string|int $userId, string $action, array $context = []): void
    {
        $this->memory->recordAction($userId, $action, $context);
        if ($this->decisionRecorder !== null) {
            $this->decisionRecorder->recordUserDecision(
                tenantId: (string) ($context['tenant_id'] ?? 'local'),
                action: $action,
                approved: true,
                scope: [
                    'user_id' => (string) $userId,
                    'device_id' => $context['device_id'] ?? null,
                    'team_id' => $context['team_id'] ?? null,
                    'subject_type' => $context['subject_type'] ?? null,
                    'subject_id' => $context['subject_id'] ?? null,
                    'correlation_id' => $context['correlation_id'] ?? null,
                    'privacy_class' => $context['privacy_class'] ?? 'user_private',
                ],
            );
        }
    }

    public function memory(): BehavioralMemory
    {
        return $this->memory;
    }

    public function persona(): BehavioralDriftTracker
    {
        return $this->persona;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Events;

use InvalidArgumentException;

final readonly class WizardAnswerChanged
{
    /** @param list<string> $affectedSections */
    public function __construct(
        public string $eventPublicId,
        public int $companyId,
        public int $actorId,
        public string $runPublicId,
        public string $answerPublicId,
        public string $questionKey,
        public int $answerRevision,
        public array $affectedSections,
        public string $source,
        public float $confidence,
        public string $risk,
        public bool $confirmed,
        public string $idempotencyKey,
    ) {
        if (
            trim($eventPublicId) === '' || $companyId < 1 || $actorId < 1 ||
            trim($runPublicId) === '' || trim($answerPublicId) === '' ||
            trim($questionKey) === '' || $answerRevision < 1 ||
            trim($source) === '' || $confidence < 0.0 || $confidence > 1.0 ||
            trim($risk) === '' || trim($idempotencyKey) === ''
        ) {
            throw new InvalidArgumentException('Wizard answer change event is incomplete.');
        }
        if ($affectedSections === []) {
            throw new InvalidArgumentException('Wizard answer change must affect at least one section.');
        }
        foreach ($affectedSections as $section) {
            if (!is_string($section) || trim($section) === '') {
                throw new InvalidArgumentException('Wizard answer change contains an invalid affected section.');
            }
        }
    }

    public static function fromArray(array $data): self
    {
        $sections = array_values(array_unique(array_map(
            static fn (mixed $section): string => trim((string) $section),
            (array) ($data['affected_sections'] ?? []),
        )));
        sort($sections, SORT_STRING);

        return new self(
            eventPublicId: (string) ($data['event_public_id'] ?? ''),
            companyId: (int) ($data['company_id'] ?? 0),
            actorId: (int) ($data['actor_id'] ?? 0),
            runPublicId: (string) ($data['run_public_id'] ?? ''),
            answerPublicId: (string) ($data['answer_public_id'] ?? ''),
            questionKey: (string) ($data['question_key'] ?? ''),
            answerRevision: (int) ($data['answer_revision'] ?? 0),
            affectedSections: $sections,
            source: (string) ($data['source'] ?? ''),
            confidence: (float) ($data['confidence'] ?? 0.0),
            risk: (string) ($data['risk'] ?? 'low'),
            confirmed: (bool) ($data['confirmed'] ?? false),
            idempotencyKey: (string) ($data['idempotency_key'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'event_public_id' => $this->eventPublicId,
            'company_id' => $this->companyId,
            'actor_id' => $this->actorId,
            'run_public_id' => $this->runPublicId,
            'answer_public_id' => $this->answerPublicId,
            'question_key' => $this->questionKey,
            'answer_revision' => $this->answerRevision,
            'affected_sections' => $this->affectedSections,
            'source' => $this->source,
            'confidence' => $this->confidence,
            'risk' => $this->risk,
            'confirmed' => $this->confirmed,
            'idempotency_key' => $this->idempotencyKey,
        ];
    }
}

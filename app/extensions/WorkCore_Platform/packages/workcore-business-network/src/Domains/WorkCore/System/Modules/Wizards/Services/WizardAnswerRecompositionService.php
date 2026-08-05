<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardAIEnrichmentDispatcherContract;
use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardRecompositionRepositoryContract;
use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use InvalidArgumentException;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposal;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalBridge;

final class WizardAnswerRecompositionService
{
    public function __construct(
        private WizardVerticalContextAdapter $context,
        private WizardRecompositionRepositoryContract $proposals,
        private WizardAIEnrichmentDispatcherContract $enrichment,
    ) {}

    /** @return list<array<string,mixed>> */
    public function project(WizardAnswerChanged $event): array
    {
        $snapshot = $this->snapshot($event);
        $drafts = $this->proposals->recordDeterministicDrafts($event, $snapshot->toArray());
        $this->enrichment->dispatch($event, $drafts);

        return $drafts;
    }

    /** @return list<array<string,mixed>> */
    public function applyAI(WizardAnswerChanged $event, VerticalAIProposal $proposal): array
    {
        $snapshot = $this->snapshot($event);
        $proposalData = $proposal->toArray();
        if ($proposalData['fallback_used'] ?? false) {
            return [];
        }
        if (!hash_equals($snapshot->contextHash(), (string) ($proposalData['context_hash'] ?? ''))) {
            throw new InvalidArgumentException('AI proposal is stale for the current wizard context.');
        }

        return $this->proposals->recordAIRevisions($event, $proposalData);
    }

    /** @return list<array<string,mixed>> */
    public function enrich(WizardAnswerChanged $event, VerticalAIProposalBridge $bridge): array
    {
        $snapshot = $this->snapshot($event);
        $proposal = $bridge->propose($snapshot, [
            'purpose' => 'wizard_answer_recomposition',
            'wizard_answer_changed' => $event->toArray(),
        ]);

        return $this->applyAI($event, $proposal);
    }

    private function snapshot(WizardAnswerChanged $event): \TitanZero\Interaction\Vertical\DTO\VerticalContextSnapshot
    {
        $snapshot = $this->context->compose($event->runPublicId, $event->companyId, [
            'id' => $event->actorId,
            'source' => $event->source,
        ]);
        $data = $snapshot->toArray();
        if ((int) ($data['wizard']['answer_revision'] ?? -1) !== $event->answerRevision) {
            throw new InvalidArgumentException('Wizard answer change is stale for the current answer revision.');
        }

        return $snapshot;
    }
}

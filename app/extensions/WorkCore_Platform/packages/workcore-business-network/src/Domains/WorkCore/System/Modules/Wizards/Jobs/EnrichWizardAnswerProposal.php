<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Jobs;

use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerRecompositionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalBridge;

final class EnrichWizardAnswerProposal implements ShouldQueue
{
    public bool $afterCommit = true;
    public int $tries = 3;
    public int $timeout = 120;

    /** @param array<string,mixed> $event */
    public function __construct(public readonly array $event) {}

    public static function fromEvent(WizardAnswerChanged $event): self
    {
        return new self($event->toArray());
    }

    public function handle(
        WizardAnswerRecompositionService $recomposition,
        VerticalAIProposalBridge $bridge,
    ): void {
        $recomposition->enrich(WizardAnswerChanged::fromArray($this->event), $bridge);
    }

    public function uniqueId(): string
    {
        return hash('sha256', implode('|', [
            (string) ($this->event['company_id'] ?? ''),
            (string) ($this->event['run_public_id'] ?? ''),
            (string) ($this->event['event_public_id'] ?? ''),
            (string) ($this->event['answer_revision'] ?? ''),
        ]));
    }
}

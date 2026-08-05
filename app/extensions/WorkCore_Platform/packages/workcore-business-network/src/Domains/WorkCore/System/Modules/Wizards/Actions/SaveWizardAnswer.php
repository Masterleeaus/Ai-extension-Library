<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Actions;

use App\Domains\WorkCore\System\Actions\ActionHandlerResult;
use App\Domains\WorkCore\System\Actions\ActionRequest;
use App\Domains\WorkCore\System\Actions\Contracts\BusinessActionHandlerContract;
use App\Domains\WorkCore\System\Actions\PendingDomainEvent;
use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerRecompositionService;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRuntime;
use App\Domains\WorkCore\System\References\TypedReference;

final class SaveWizardAnswer implements BusinessActionHandlerContract
{
    public function __construct(
        private WizardRuntime $runtime,
        private ?WizardAnswerRecompositionService $recomposition = null,
    ) {}

    public function handle(ActionRequest $request): ActionHandlerResult
    {
        $origin = strtolower(trim($request->source));
        $isAI = in_array($origin, ['ai', 'agent', 'automation'], true);
        $record = $this->runtime->save(
            (string) $request->payload['run_public_id'],
            (string) $request->payload['question_key'],
            $request->payload['value'] ?? null,
            [
                'source' => $isAI ? 'ai_suggested' : 'user_entered',
                'confidence' => $isAI ? 0.5 : 1.0,
                'is_confirmed' => !$isAI,
                'agent_public_id' => null,
                'idempotency_key' => $request->idempotencyKey,
            ],
            $request->companyId,
            $request->actorId,
        );

        $aggregate = new TypedReference('wizard_answer', (string) ($record['public_id'] ?? $record['run_public_id']));
        if (!($record['changed'] ?? false)) {
            return new ActionHandlerResult($record, $aggregate, []);
        }

        $event = WizardAnswerChanged::fromArray((array) $record['change_event']);
        $record['recomposition'] = [
            'drafts' => $this->recomposition?->project($event) ?? [],
            'ai_enrichment' => $this->recomposition === null ? 'unavailable' : 'queued_after_commit',
        ];

        return new ActionHandlerResult($record, $aggregate, [
            new PendingDomainEvent('workcore.wizard.answer.changed', 1, ['change_event' => $event->toArray()]),
            new PendingDomainEvent('workcore.wizard.answer.saved', 1, ['record' => $record]),
        ]);
    }
}

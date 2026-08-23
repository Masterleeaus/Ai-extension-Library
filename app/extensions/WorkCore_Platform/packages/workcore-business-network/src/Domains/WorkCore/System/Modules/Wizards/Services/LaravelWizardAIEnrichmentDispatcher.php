<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardAIEnrichmentDispatcherContract;
use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;
use App\Domains\WorkCore\System\Modules\Wizards\Jobs\EnrichWizardAnswerProposal;
use Illuminate\Contracts\Bus\Dispatcher;

final class LaravelWizardAIEnrichmentDispatcher implements WizardAIEnrichmentDispatcherContract
{
    public function __construct(private Dispatcher $bus) {}

    public function dispatch(WizardAnswerChanged $event, array $drafts): void
    {
        if ($drafts === []) {
            return;
        }

        $this->bus->dispatchAfterResponse(EnrichWizardAnswerProposal::fromEvent($event));
    }
}

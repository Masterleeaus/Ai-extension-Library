<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Contracts;

use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;

interface WizardAIEnrichmentDispatcherContract
{
    /** @param list<array<string,mixed>> $drafts */
    public function dispatch(WizardAnswerChanged $event, array $drafts): void;
}

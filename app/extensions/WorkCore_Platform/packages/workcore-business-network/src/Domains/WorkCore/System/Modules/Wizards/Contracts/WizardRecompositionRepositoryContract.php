<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Contracts;

use App\Domains\WorkCore\System\Modules\Wizards\Events\WizardAnswerChanged;

interface WizardRecompositionRepositoryContract
{
    /** @return list<array<string,mixed>> */
    public function recordDeterministicDrafts(WizardAnswerChanged $event, array $context): array;

    /** @return list<array<string,mixed>> */
    public function recordAIRevisions(WizardAnswerChanged $event, array $proposal): array;
}

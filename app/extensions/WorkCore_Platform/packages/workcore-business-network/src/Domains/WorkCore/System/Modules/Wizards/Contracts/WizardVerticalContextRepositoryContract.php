<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Contracts;

interface WizardVerticalContextRepositoryContract
{
    public function snapshot(string $runPublicId, int $companyId): array;
}

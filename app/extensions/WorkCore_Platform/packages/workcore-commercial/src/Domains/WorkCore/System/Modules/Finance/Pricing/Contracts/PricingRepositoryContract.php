<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Finance\Pricing\Contracts;

use App\Domains\WorkCore\System\Modules\Finance\Pricing\DTO\PricingDecision;
use DateTimeInterface;

interface PricingRepositoryContract
{
    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function context(int $companyId, array $input, DateTimeInterface $at): array;

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function upsertRule(int $companyId, int $actorId, array $payload): array;

    /** @param array<string,mixed> $payload @return array<string,mixed> */
    public function recordSignal(int $companyId, int $actorId, array $payload): array;

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function recordDecision(int $companyId, int $actorId, array $input, PricingDecision $decision): array;

    /** @param array<string,mixed> $filters @return array<string,mixed> */
    public function analytics(int $companyId, array $filters): array;
}

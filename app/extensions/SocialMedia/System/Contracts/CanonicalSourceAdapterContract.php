<?php

declare(strict_types=1);

namespace App\Extensions\SocialMedia\System\Contracts;

use App\Models\User;

interface CanonicalSourceAdapterContract
{
    public function sourceKey(): string;

    public function available(User $user): bool;

    public function supports(string $recordType): bool;

    public function fetch(User $user, string $recordType, string $recordId): ?array;

    public function search(User $user, string $recordType, array $filters = [], int $limit = 50): array;

    public function health(User $user): array;

    public function handoff(User $user, string $handoffType, array $context): array;
}

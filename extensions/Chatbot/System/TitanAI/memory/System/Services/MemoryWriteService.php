<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Services;

use App\Extensions\SystemAIChatMemory\System\Models\SystemAIChatMemory;
use InvalidArgumentException;

final class MemoryWriteService
{
    public function remember(array $memory): SystemAIChatMemory
    {
        if (empty($memory['company_id']) || empty($memory['scope_type']) || empty($memory['type'])) {
            throw new InvalidArgumentException('Memory requires company, scope, and type.');
        }
        if (($memory['requires_consent'] ?? false) && empty($memory['consented_at'])) {
            throw new InvalidArgumentException('Consent is required before this memory can be stored.');
        }

        $memory['fingerprint'] ??= hash('sha256', json_encode([
            $memory['company_id'], $memory['scope_type'], $memory['scope_id'] ?? null,
            $memory['type'], $memory['content'] ?? [],
        ], JSON_THROW_ON_ERROR));

        return SystemAIChatMemory::query()->updateOrCreate(
            ['company_id' => $memory['company_id'], 'fingerprint' => $memory['fingerprint']],
            $memory,
        );
    }
}

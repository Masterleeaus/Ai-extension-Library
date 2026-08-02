<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatMemory\System\Services;

use App\Extensions\SystemAIChatMemory\System\Models\SystemAIChatMemory;
use Illuminate\Support\Collection;

final class MemoryRetrievalService
{
    public function recall(int $companyId, string $scopeType, string|int|null $scopeId, array $types = [], int $limit = 20): Collection
    {
        return SystemAIChatMemory::query()
            ->where('company_id', $companyId)
            ->where('scope_type', $scopeType)
            ->when($scopeId !== null, fn ($q) => $q->where('scope_id', (string) $scopeId))
            ->when($types !== [], fn ($q) => $q->whereIn('type', $types))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderByDesc('importance')->orderByDesc('last_recalled_at')
            ->limit(max(1, min($limit, 100)))
            ->get();
    }
}

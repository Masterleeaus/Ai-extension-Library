<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Suggestions;

use App\Extensions\Migration\System\Security\SensitiveValueMasker;

final class AdvisoryMappingSuggestionService
{
    public function __construct(private readonly SensitiveValueMasker $masker)
    {
    }

    /** @param array<string, mixed> $evidence */
    public function create(
        string $sourceField,
        string $targetField,
        float $confidence,
        array $evidence = [],
    ): MappingSuggestion {
        return new MappingSuggestion(
            sourceField: $sourceField,
            targetField: $targetField,
            confidence: $confidence,
            evidence: $this->masker->maskRow($evidence),
            status: 'advisory',
            requiresApproval: true,
            approvedBy: null,
            approvedAt: null,
        );
    }
}

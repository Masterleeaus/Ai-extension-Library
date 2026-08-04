<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization\Policies;

use App\Domains\WorkCore\System\Contracts\OperationContextContract;

interface KnowledgeIngestionPolicyContract
{
    public function canIngestKnowledge(
        string $knowledgeSourceName,
        OperationContextContract $context,
        array $sourceConfig = [],
    ): bool;

    public function canAccessKnowledgeSource(
        string $knowledgeSourceName,
        OperationContextContract $context,
    ): bool;

    public function getDenialReasonForKnowledge(
        string $knowledgeSourceName,
        OperationContextContract $context,
    ): ?string;
}

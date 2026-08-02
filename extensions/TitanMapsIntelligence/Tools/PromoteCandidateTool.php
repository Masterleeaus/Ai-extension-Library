<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Tools;

use App\Extensions\TitanMapsIntelligence\Contracts\AuthorisedCompanyContext;
use App\Extensions\TitanMapsIntelligence\Contracts\PermissionAuthorizer;
use App\Extensions\TitanMapsIntelligence\Services\CandidatePromotionService;

final class PromoteCandidateTool
{
    public function __construct(private readonly AuthorisedCompanyContext $context, private readonly PermissionAuthorizer $authorizer, private readonly CandidatePromotionService $promotion) {}
    public function execute(array $input): array
    {
        $companyId = $this->context->companyId();
        $this->authorizer->authorize($this->context->userId(), $companyId, 'titan-maps-intelligence.candidate.promote');
        $result = $this->promotion->promote($companyId, (string) ($input['candidate_id'] ?? ''), (string) ($input['target_type'] ?? ''), (array) ($input['accepted_fields'] ?? []), ['user_id' => $this->context->userId(), 'branch_id' => $this->context->branchId(), 'workspace_id' => $this->context->workspaceId(), 'agent_id' => $input['agent_id'] ?? null, 'conversation_id' => $input['conversation_id'] ?? null, 'correlation_id' => $input['correlation_id'] ?? null]);
        return ['ok' => true, 'data' => $result];
    }
}

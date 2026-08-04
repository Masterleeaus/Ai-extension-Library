<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence\Tools;

use App\Extensions\TitanMapsIntelligence\Contracts\AuthorisedCompanyContext;
use App\Extensions\TitanMapsIntelligence\Contracts\PermissionAuthorizer;
use App\Extensions\TitanMapsIntelligence\Services\CandidateReviewService;

final class RejectCandidateTool
{
    public function __construct(private readonly AuthorisedCompanyContext $context, private readonly PermissionAuthorizer $authorizer, private readonly CandidateReviewService $reviews) {}
    public function execute(array $input): array
    {
        $this->authorizer->authorize($this->context->userId(), $this->context->companyId(), 'titan-maps-intelligence.candidate.reject');
        $candidate = $this->reviews->reject((string) ($input['candidate_id'] ?? ''), (string) ($input['reason'] ?? ''), ['agent_id' => $input['agent_id'] ?? null, 'conversation_id' => $input['conversation_id'] ?? null]);
        return ['ok' => true, 'data' => ['candidate_id' => $candidate->getKey(), 'review_status' => $candidate->review_status]];
    }
}

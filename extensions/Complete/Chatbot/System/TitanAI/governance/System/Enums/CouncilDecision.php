<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Enums;

enum CouncilDecision: string
{
    case Approve = 'approve';
    case Reject = 'reject';
    case HumanReview = 'human_review';
    case InsufficientEvidence = 'insufficient_evidence';
}

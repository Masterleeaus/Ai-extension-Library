<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System\Council;

use App\Extensions\TitanAIGovernance\System\Contracts\ModelReviewer;
use App\Extensions\TitanAIGovernance\System\Enums\CouncilDecision;

final class OperationalCouncil
{
    /** @param array<int,ModelReviewer> $reviewers */
    public function __construct(private array $reviewers) {}

    public function deliberate(CouncilCase $case): array
    {
        if (! $case->risk->requiresCouncil()) {
            return $this->result(CouncilDecision::Approve, [], 100, false);
        }

        $required = $case->risk->minimumReviewers();
        $selected = array_slice($this->reviewers, 0, $required);
        if (count($selected) < $required) {
            return $this->result(CouncilDecision::InsufficientEvidence, [], 0, true);
        }

        $reviews = [];
        foreach ($selected as $reviewer) {
            try {
                $reviews[] = $reviewer->review($case->toArray());
            } catch (\Throwable $exception) {
                $reviews[] = [
                    'decision' => CouncilDecision::InsufficientEvidence->value,
                    'confidence' => 0,
                    'findings' => ['reviewer_error'],
                    'evidence' => [],
                    'error' => $exception->getMessage(),
                ];
            }
        }
        $rejects = array_filter($reviews, fn (array $r) => ($r['decision'] ?? '') === CouncilDecision::Reject->value);
        $approvals = array_filter($reviews, fn (array $r) => ($r['decision'] ?? '') === CouncilDecision::Approve->value);
        $confidence = $reviews === [] ? 0 : (int) floor(array_sum(array_column($reviews, 'confidence')) / count($reviews));

        if ($case->risk->requiresHumanApproval()) {
            return $this->result(CouncilDecision::HumanReview, $reviews, $confidence, true);
        }
        if ($rejects !== []) {
            return $this->result(CouncilDecision::Reject, $reviews, $confidence, false);
        }
        if (count($approvals) !== count($reviews) || $confidence < 70) {
            return $this->result(CouncilDecision::HumanReview, $reviews, $confidence, true);
        }

        return $this->result(CouncilDecision::Approve, $reviews, $confidence, false);
    }

    private function result(CouncilDecision $decision, array $reviews, int $confidence, bool $approvalRequired): array
    {
        return [
            'decision' => $decision->value,
            'confidence' => $confidence,
            'approval_required' => $approvalRequired,
            'reviews' => $reviews,
            'findings' => array_values(array_merge(...array_map(fn ($r) => (array) ($r['findings'] ?? []), $reviews ?: [[]]))),
        ];
    }
}

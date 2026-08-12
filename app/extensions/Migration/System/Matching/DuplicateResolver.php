<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Matching;

use InvalidArgumentException;

final readonly class DuplicateResolver
{
    public function __construct(public float $minimumFuzzyConfidence = 0.85)
    {
        $this->assertScore($this->minimumFuzzyConfidence, 'minimum fuzzy confidence');
    }

    public function resolve(
        DuplicatePolicy $policy,
        bool $exactIdentityMatch,
        ?float $similarity,
        float $risk,
        float $approvedRiskThreshold,
    ): DuplicateDecision {
        $this->assertScore($risk, 'risk');
        $this->assertScore($approvedRiskThreshold, 'approved risk threshold');
        if ($similarity !== null) {
            $this->assertScore($similarity, 'similarity');
        }

        if ($exactIdentityMatch) {
            return $this->followPolicy($policy, 'exact', 1.0, $risk, 'Exact canonical identity matched.');
        }

        $fuzzyMatch = $similarity !== null && $similarity >= $this->minimumFuzzyConfidence;
        if (! $fuzzyMatch) {
            return match ($policy) {
                DuplicatePolicy::SKIP => new DuplicateDecision('skip', false, 'No duplicate matched and policy requests skip.', 'none', $similarity, $risk),
                DuplicatePolicy::REVIEW, DuplicatePolicy::UPDATE => new DuplicateDecision('review', true, 'No trustworthy duplicate matched for a policy that requires an existing target.', 'none', $similarity, $risk),
                DuplicatePolicy::CREATE, DuplicatePolicy::MERGE => new DuplicateDecision('create', false, 'No trustworthy duplicate matched; create a new target.', 'none', $similarity, $risk),
            };
        }

        if ($risk > $approvedRiskThreshold && in_array($policy, [DuplicatePolicy::MERGE, DuplicatePolicy::UPDATE], true)) {
            return new DuplicateDecision(
                'review',
                true,
                'Fuzzy duplicate risk exceeds the approved automatic mutation threshold.',
                'fuzzy',
                $similarity,
                $risk,
            );
        }

        return $this->followPolicy($policy, 'fuzzy', $similarity, $risk, 'Fuzzy duplicate met the configured confidence and risk gates.');
    }

    private function followPolicy(
        DuplicatePolicy $policy,
        string $matchType,
        ?float $similarity,
        float $risk,
        string $reason,
    ): DuplicateDecision {
        return match ($policy) {
            DuplicatePolicy::CREATE => new DuplicateDecision('create', false, $reason, $matchType, $similarity, $risk),
            DuplicatePolicy::UPDATE => new DuplicateDecision('update', false, $reason, $matchType, $similarity, $risk),
            DuplicatePolicy::MERGE => new DuplicateDecision('merge', false, $reason, $matchType, $similarity, $risk),
            DuplicatePolicy::SKIP => new DuplicateDecision('skip', false, $reason, $matchType, $similarity, $risk),
            DuplicatePolicy::REVIEW => new DuplicateDecision('review', true, $reason, $matchType, $similarity, $risk),
        };
    }

    private function assertScore(float $score, string $label): void
    {
        if ($score < 0.0 || $score > 1.0) {
            throw new InvalidArgumentException(ucfirst($label) . ' must be between 0 and 1.');
        }
    }
}

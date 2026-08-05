<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

final class WizardQuestionPlanner
{
    public function __construct(private WizardBranchEvaluator $branches) {}

    /** @param array<string,mixed> $definition @param array<string,mixed> $answers @return list<array<string,mixed>> */
    public function plan(array $definition, array $answers): array
    {
        $plan = [];
        foreach ((array) ($definition['sections'] ?? []) as $section) {
            $sectionOrder = (int) ($section['order'] ?? 1000);
            foreach ((array) ($section['questions'] ?? []) as $question) {
                if (!$this->branches->matches((array) ($question['when'] ?? []), $answers)) {
                    continue;
                }
                $baseOrder = ($sectionOrder * 10000) + (int) ($question['order'] ?? 1000);
                $effectiveOrder = $baseOrder;
                $priority = (array) ($question['priority_when'] ?? []);
                if ($priority !== [] && $this->branches->matches((array) ($priority['condition'] ?? []), $answers)) {
                    $effectiveOrder -= max(0, (int) ($priority['boost'] ?? 0));
                }
                $plan[] = [
                    'section_key' => (string) ($section['key'] ?? ''),
                    'section_title' => (string) ($section['title'] ?? ''),
                    'question_key' => (string) ($question['key'] ?? ''),
                    'base_order' => $baseOrder,
                    'effective_order' => $effectiveOrder,
                    'question' => $question,
                ];
            }
        }

        usort($plan, static fn (array $left, array $right): int => [
            (int) $left['effective_order'],
            (int) $left['base_order'],
            (string) $left['question_key'],
        ] <=> [
            (int) $right['effective_order'],
            (int) $right['base_order'],
            (string) $right['question_key'],
        ]);

        return $plan;
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

use InvalidArgumentException;

final class WizardDependencyResolver
{
    /** @return array<string,mixed> */
    public function question(array $definition, string $questionKey): array
    {
        foreach ((array) ($definition['sections'] ?? []) as $section) {
            $sectionKey = trim((string) ($section['key'] ?? ''));
            foreach ((array) ($section['questions'] ?? []) as $question) {
                if ((string) ($question['key'] ?? '') !== $questionKey) {
                    continue;
                }

                $affected = $question['affects'] ?? $question['affected_sections'] ?? [$sectionKey];
                if (!is_array($affected)) {
                    $affected = [$affected];
                }
                $affected[] = $sectionKey;
                $affected = array_values(array_unique(array_filter(array_map(
                    static fn (mixed $value): string => trim((string) $value),
                    $affected,
                ), static fn (string $value): bool => $value !== '')));
                sort($affected, SORT_STRING);

                if ($affected === []) {
                    throw new InvalidArgumentException("Wizard question [{$questionKey}] has no valid dependency target.");
                }
                if (count($affected) > 20) {
                    throw new InvalidArgumentException("Wizard question [{$questionKey}] affects too many sections.");
                }

                return [
                    ...$question,
                    '_section_key' => $sectionKey,
                    '_affected_sections' => $affected,
                ];
            }
        }

        throw new InvalidArgumentException("Wizard question [{$questionKey}] was not found.");
    }
}

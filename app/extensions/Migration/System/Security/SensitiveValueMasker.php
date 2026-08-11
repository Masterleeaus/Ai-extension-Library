<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Security;

final class SensitiveValueMasker
{
    private const SENSITIVE_FIELD = '/(?:^|[_-])(email|e[_-]?mail|phone|mobile|telephone|password|passwd|secret|token|api[_-]?key|authorization|cookie|dob|date[_-]?of[_-]?birth|address|street|postcode|postal[_-]?code|ssn|tfn|medicare|passport)(?:$|[_-])/i';

    /** @param array<string, mixed> $row
     *  @return array<string, mixed>
     */
    public function maskRow(array $row): array
    {
        $masked = [];

        foreach ($row as $field => $value) {
            if (preg_match(self::SENSITIVE_FIELD, (string) $field) === 1) {
                $masked[$field] = $value === null ? null : '[MASKED]';
                continue;
            }

            $masked[$field] = is_array($value) ? $this->maskNested($value) : $value;
        }

        return $masked;
    }

    private function maskNested(array $value): array
    {
        $result = [];
        foreach ($value as $key => $nested) {
            if (preg_match(self::SENSITIVE_FIELD, (string) $key) === 1) {
                $result[$key] = $nested === null ? null : '[MASKED]';
            } else {
                $result[$key] = is_array($nested) ? $this->maskNested($nested) : $nested;
            }
        }

        return $result;
    }
}

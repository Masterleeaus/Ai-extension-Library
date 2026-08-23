<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Discovery;

final class SchemaFingerprint
{
    public function fromDiscovery(array $discovery): string
    {
        $normalised = $this->normalise($discovery);
        $json = json_encode($normalised, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return hash('sha256', $json);
    }

    private function normalise(mixed $value, ?string $key = null): mixed
    {
        if (in_array($key, ['samples', 'sample', 'fingerprint', 'generated_at', 'tested_at'], true)) {
            return null;
        }

        if (! is_array($value)) {
            return $value;
        }

        $isList = array_is_list($value);
        $result = [];

        foreach ($value as $childKey => $childValue) {
            if (in_array((string) $childKey, ['samples', 'sample', 'fingerprint', 'generated_at', 'tested_at'], true)) {
                continue;
            }
            $result[$childKey] = $this->normalise($childValue, (string) $childKey);
        }

        if ($isList) {
            if ($this->isNamedList($result)) {
                usort($result, static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')));
            }

            return array_values($result);
        }

        ksort($result);

        return $result;
    }

    private function isNamedList(array $items): bool
    {
        if ($items === []) {
            return false;
        }

        foreach ($items as $item) {
            if (! is_array($item) || ! array_key_exists('name', $item)) {
                return false;
            }
        }

        return true;
    }
}

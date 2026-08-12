<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Identity;

use InvalidArgumentException;

final class CanonicalChecksum
{
    public function hash(mixed $payload): string
    {
        $normalised = $this->normalise($payload);
        $encoded = json_encode($normalised, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);

        return hash('sha256', $encoded);
    }

    private function normalise(mixed $value): mixed
    {
        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value, SORT_STRING);
            }
            foreach ($value as $key => $nested) {
                $value[$key] = $this->normalise($nested);
            }

            return $value;
        }

        if (is_object($value)) {
            if ($value instanceof \JsonSerializable) {
                return $this->normalise($value->jsonSerialize());
            }
            if ($value instanceof \Stringable) {
                return (string) $value;
            }
            throw new InvalidArgumentException('Canonical checksum does not accept opaque objects.');
        }

        if (is_resource($value)) {
            throw new InvalidArgumentException('Canonical checksum does not accept resources.');
        }

        return $value;
    }
}

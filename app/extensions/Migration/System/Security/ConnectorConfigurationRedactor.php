<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Security;

final class ConnectorConfigurationRedactor
{
    private const SENSITIVE_KEY = '/(?:^|[_-])(password|passwd|secret|token|api[_-]?key|access[_-]?key|private[_-]?key|authorization|cookie)(?:$|[_-])/i';

    /** @return array<string|int, mixed> */
    public function redact(array $configuration): array
    {
        $redacted = [];

        foreach ($configuration as $key => $value) {
            $keyString = (string) $key;

            if (preg_match(self::SENSITIVE_KEY, $keyString) === 1) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            if (is_array($value)) {
                $redacted[$key] = $this->redact($value);
                continue;
            }

            if (is_string($value) && str_ends_with(strtolower($keyString), '_file')) {
                $redacted[$key] = basename($value);
                continue;
            }

            $redacted[$key] = $value;
        }

        return $redacted;
    }
}

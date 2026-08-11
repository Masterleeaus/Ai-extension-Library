<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Security;

use InvalidArgumentException;

final class GraphQlReadOnlyGuard
{
    public function assertReadOnly(string $document): string
    {
        $trimmed = trim($document);
        if ($trimmed === '') {
            throw new InvalidArgumentException('GraphQL document cannot be empty.');
        }

        $analysis = preg_replace('/#[^\r\n]*/', ' ', $trimmed) ?? $trimmed;
        $analysis = preg_replace('/""".*?"""/s', '""', $analysis) ?? $analysis;
        $analysis = preg_replace('/"(?:\\\\.|[^"])*"/s', '""', $analysis) ?? $analysis;

        if (preg_match('/\b(mutation|subscription)\b/i', $analysis) === 1) {
            throw new InvalidArgumentException('Only GraphQL query operations are allowed.');
        }

        if (! preg_match('/^(?:query\b|\{)/i', ltrim($analysis))) {
            throw new InvalidArgumentException('GraphQL document must be a query or shorthand query.');
        }

        return $trimmed;
    }
}

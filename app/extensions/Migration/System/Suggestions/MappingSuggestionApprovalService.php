<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Suggestions;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final class MappingSuggestionApprovalService
{
    public function approve(MappingSuggestion $suggestion, int $actorId): MappingSuggestion
    {
        if ($actorId <= 0) {
            throw new InvalidArgumentException('Mapping suggestion approval requires a positive actor ID.');
        }
        if ($suggestion->status !== 'advisory') {
            throw new InvalidArgumentException('Only advisory mapping suggestions can be approved.');
        }

        return $suggestion->approve(
            $actorId,
            (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format(DATE_ATOM),
        );
    }
}

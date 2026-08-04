<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use RuntimeException;

final class WorkerPermissionGuard
{
    /**
     * Enforces a supplied permission snapshot. When no snapshot is supplied,
     * host policies remain authoritative and execution continues unchanged.
     *
     * @param list<string> $required
     * @param array<string,mixed> $context
     */
    public function assertAllowed(array $required, array $context): void
    {
        if (! array_key_exists('available_permissions', $context)) return;
        $available = array_values(array_unique(array_map('strval', (array) $context['available_permissions'])));
        if (in_array('*', $available, true)) return;

        $missing = array_values(array_diff(array_values(array_unique($required)), $available));
        if ($missing !== []) {
            throw new RuntimeException('Titan AI permission check failed: '.implode(', ', $missing));
        }
    }
}

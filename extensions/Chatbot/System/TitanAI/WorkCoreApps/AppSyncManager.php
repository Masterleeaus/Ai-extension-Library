<?php
declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\WorkCoreApps;

use Closure;

/**
 * Small orchestration adapter over the chatbot's existing outbox/sync contracts.
 * It does not create a second sync database.
 */
final class AppSyncManager
{
    /** @param Closure(array<string,mixed>):mixed $dispatcher */
    public function __construct(private readonly Closure $dispatcher) {}

    /** @param array<string,mixed> $operation */
    public function dispatch(array $operation): mixed
    {
        $operation['source'] ??= 'titan-workcore-app';
        $operation['created_at'] ??= now()->toIso8601String();

        return ($this->dispatcher)($operation);
    }
}

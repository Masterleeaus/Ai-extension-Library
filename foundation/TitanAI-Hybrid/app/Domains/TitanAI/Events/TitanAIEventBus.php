<?php

declare(strict_types=1);

namespace App\Domains\TitanAI\Events;

use App\Domains\TitanAI\Diagnostics\TitanAIDiagnostics;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Failure-isolated event publisher and idempotent listener registrar.
 *
 * Cross-extension events are observability/integration signals. By default a
 * broken optional listener must never stop a native Chatbot, AI Agent, or
 * AIChatPro operation from completing.
 */
final class TitanAIEventBus
{
    /** @var array<string,true> */
    private array $delivered = [];

    /** @var list<string> */
    private array $deliveryOrder = [];

    /** @var array<string,true> */
    private array $listenerKeys = [];

    public function __construct(private readonly TitanAIDiagnostics $diagnostics) {}

    public function dispatch(object $event, ?string $idempotencyKey = null): bool
    {
        if (! (bool) config('titanai.events.enabled', true)
            || ! (bool) config('titanai.features.event_publishing', true)) {
            $this->diagnostics->recordEvent('disabled', $event, $idempotencyKey);
            return false;
        }

        $idempotencyKey = $this->normaliseKey($idempotencyKey);
        if ($idempotencyKey !== null && isset($this->delivered[$idempotencyKey])) {
            $this->diagnostics->recordEvent('duplicate', $event, $idempotencyKey);
            return false;
        }

        if ($idempotencyKey !== null) {
            $this->rememberDelivery($idempotencyKey);
        }

        try {
            Event::dispatch($event);
            $this->diagnostics->recordEvent('published', $event, $idempotencyKey);
            return true;
        } catch (Throwable $exception) {
            // Keep the idempotency key: Laravel listeners run sequentially, so a
            // listener may already have produced side effects before a later one
            // throws. Retrying the same event could duplicate those side effects.
            $this->diagnostics->recordEvent('failed', $event, $idempotencyKey, $exception);
            Log::warning('TitanAI event listener failed; native operation was isolated.', [
                'event' => $event::class,
                'idempotency_key' => $idempotencyKey,
                'exception' => $exception,
            ]);

            if ((bool) config('titanai.events.strict', false)) {
                throw $exception;
            }

            return false;
        }
    }

    public function listenOnce(string $listenerKey, string $eventClass, callable|string $listener): bool
    {
        $listenerKey = trim($listenerKey);
        if ($listenerKey === '') {
            throw new \InvalidArgumentException('TitanAI listener key cannot be empty.');
        }
        if (isset($this->listenerKeys[$listenerKey])) {
            return false;
        }

        $this->listenerKeys[$listenerKey] = true;
        try {
            Event::listen($eventClass, $listener);
            return true;
        } catch (Throwable $exception) {
            unset($this->listenerKeys[$listenerKey]);
            Log::warning('TitanAI event listener registration failed.', [
                'listener_key' => $listenerKey,
                'event' => $eventClass,
                'exception' => $exception,
            ]);
            if ((bool) config('titanai.events.strict', false)) {
                throw $exception;
            }
            return false;
        }
    }

    private function normaliseKey(?string $key): ?string
    {
        if ($key === null) return null;
        $key = trim($key);
        return $key === '' ? null : $key;
    }

    private function rememberDelivery(string $key): void
    {
        $this->delivered[$key] = true;
        $this->deliveryOrder[] = $key;

        $limit = max(1, (int) config('titanai.events.idempotency_cache_size', 1000));
        while (count($this->deliveryOrder) > $limit) {
            $oldest = array_shift($this->deliveryOrder);
            if ($oldest !== null) unset($this->delivered[$oldest]);
        }
    }
}

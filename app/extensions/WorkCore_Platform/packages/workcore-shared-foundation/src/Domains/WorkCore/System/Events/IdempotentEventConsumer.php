<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Events;

abstract class IdempotentEventConsumer
{
    public function __construct(
        protected IdempotencyLedgerContract $ledger,
    ) {}

    final public function handle(EventEnvelopeContract $event): mixed
    {
        $idempotencyKey = $event->idempotencyKey();
        $tenantId = $event->tenantId();

        if ($this->ledger->hasProcessed($idempotencyKey, $tenantId)) {
            return $this->ledger->getResult($idempotencyKey, $tenantId);
        }

        $result = $this->processEvent($event);

        $this->ledger->markProcessed($idempotencyKey, $tenantId, (array) $result);

        return $result;
    }

    abstract protected function processEvent(EventEnvelopeContract $event): mixed;

    protected function shouldHandle(EventEnvelopeContract $event): bool
    {
        return true;
    }
}

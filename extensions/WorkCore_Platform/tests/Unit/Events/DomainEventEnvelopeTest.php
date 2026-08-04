<?php

declare(strict_types=1);

namespace Tests\Unit\Events;

use App\Domains\WorkCore\System\Events\DomainEventEnvelope;
use App\Domains\WorkCore\System\References\TypedReference;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DomainEventEnvelopeTest extends TestCase
{
    public function test_domain_event_envelope_creation(): void
    {
        $reference = new TypedReference('order', '123');
        $envelope = new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
            causationId: 'caus-001',
            payload: ['order_id' => '123'],
        );

        $this->assertSame('evt-001', $envelope->eventId);
        $this->assertSame('OrderCreated', $envelope->eventName);
        $this->assertSame(1, $envelope->eventVersion);
        $this->assertSame(456, $envelope->companyId);
        $this->assertSame(789, $envelope->actorId);
        $this->assertSame('2026-08-04T12:00:00Z', $envelope->occurredAt);
        $this->assertSame('corr-001', $envelope->correlationId);
        $this->assertSame('caus-001', $envelope->causationId);
    }

    public function test_domain_event_envelope_requires_event_id(): void
    {
        $reference = new TypedReference('order', '123');

        $this->expectException(InvalidArgumentException::class);
        new DomainEventEnvelope(
            eventId: '',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
        );
    }

    public function test_domain_event_envelope_requires_event_name(): void
    {
        $reference = new TypedReference('order', '123');

        $this->expectException(InvalidArgumentException::class);
        new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: '',
            eventVersion: 1,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
        );
    }

    public function test_domain_event_envelope_requires_occurred_at(): void
    {
        $reference = new TypedReference('order', '123');

        $this->expectException(InvalidArgumentException::class);
        new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '',
            correlationId: 'corr-001',
        );
    }

    public function test_domain_event_envelope_requires_correlation_id(): void
    {
        $reference = new TypedReference('order', '123');

        $this->expectException(InvalidArgumentException::class);
        new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: '',
        );
    }

    public function test_domain_event_envelope_requires_valid_version(): void
    {
        $reference = new TypedReference('order', '123');

        $this->expectException(InvalidArgumentException::class);
        new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 0,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
        );
    }

    public function test_domain_event_envelope_requires_valid_company_id(): void
    {
        $reference = new TypedReference('order', '123');

        $this->expectException(InvalidArgumentException::class);
        new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 0,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
        );
    }

    public function test_domain_event_envelope_to_array(): void
    {
        $reference = new TypedReference('order', '123');
        $envelope = new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 456,
            actorId: 789,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
            causationId: 'caus-001',
            payload: ['order_id' => '123'],
        );

        $array = $envelope->toArray();

        $this->assertArrayHasKey('event_id', $array);
        $this->assertArrayHasKey('event_name', $array);
        $this->assertArrayHasKey('company_id', $array);
        $this->assertArrayHasKey('correlation_id', $array);
        $this->assertSame('evt-001', $array['event_id']);
        $this->assertSame('OrderCreated', $array['event_name']);
        $this->assertSame(456, $array['company_id']);
    }

    public function test_domain_event_envelope_with_null_actor_id(): void
    {
        $reference = new TypedReference('order', '123');
        $envelope = new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 456,
            actorId: null,
            aggregate: $reference,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
        );

        $this->assertNull($envelope->actorId);
    }

    public function test_domain_event_envelope_cross_tenant_isolation(): void
    {
        $reference1 = new TypedReference('order', '123');
        $reference2 = new TypedReference('order', '456');

        $envelope1 = new DomainEventEnvelope(
            eventId: 'evt-001',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 100,
            actorId: 10,
            aggregate: $reference1,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-001',
        );

        $envelope2 = new DomainEventEnvelope(
            eventId: 'evt-002',
            eventName: 'OrderCreated',
            eventVersion: 1,
            companyId: 200,
            actorId: 20,
            aggregate: $reference2,
            occurredAt: '2026-08-04T12:00:00Z',
            correlationId: 'corr-002',
        );

        // Verify tenant isolation
        $this->assertSame(100, $envelope1->companyId);
        $this->assertSame(200, $envelope2->companyId);
        $this->assertNotSame($envelope1->companyId, $envelope2->companyId);
    }
}

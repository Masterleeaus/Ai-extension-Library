<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\DTO\JobManagement;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class BookJobRequest
{
    /** @param list<array<string,mixed>> $serviceItems */
    public function __construct(
        public int $customerId,
        public int $propertyId,
        public string $serviceType,
        public DateTimeImmutable $scheduledStart,
        public int $durationMinutes = 120,
        public array $serviceItems = [],
        public ?string $notes = null,
        public ?string $idempotencyKey = null,
        public ?string $confirmationId = null,
    ) {
        if ($customerId < 1 || $propertyId < 1) throw new InvalidArgumentException('Customer and property are required.');
        if ($serviceType === '') throw new InvalidArgumentException('Service type is required.');
        if ($durationMinutes < 15 || $durationMinutes > 1440) throw new InvalidArgumentException('Duration must be between 15 and 1440 minutes.');
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'customer_id'=>$this->customerId,'property_id'=>$this->propertyId,'service_type'=>$this->serviceType,
            'scheduled_start'=>$this->scheduledStart->format(DATE_ATOM),'duration_minutes'=>$this->durationMinutes,
            'service_items'=>$this->serviceItems,'notes'=>$this->notes,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Services\AI\Tier3\Agents\DTO\Quotation;

use InvalidArgumentException;

final readonly class CreateQuoteRequest
{
    /** @param list<array<string,mixed>> $lineItems */
    public function __construct(
        public int $customerId,
        public int $propertyId,
        public string $serviceType,
        public array $lineItems,
        public int $validityDays = 14,
        public string $currency = 'AUD',
        public ?string $notes = null,
        public ?string $pricingPolicyId = null,
        public ?string $idempotencyKey = null,
        public ?string $confirmationId = null,
    ) {
        if ($customerId < 1 || $propertyId < 1) throw new InvalidArgumentException('Customer and property are required.');
        if ($serviceType === '' || $lineItems === []) throw new InvalidArgumentException('Service type and line items are required.');
        if ($validityDays < 1 || $validityDays > 90) throw new InvalidArgumentException('Validity must be between 1 and 90 days.');
        if ($currency !== 'AUD') throw new InvalidArgumentException('This cleaning package defaults to AUD.');
    }
    /** @return array<string,mixed> */
    public function toArray(): array { return [
        'customer_id'=>$this->customerId,'property_id'=>$this->propertyId,'service_type'=>$this->serviceType,
        'line_items'=>$this->lineItems,'validity_days'=>$this->validityDays,'currency'=>$this->currency,
        'notes'=>$this->notes,'pricing_policy_id'=>$this->pricingPolicyId,
    ]; }
}

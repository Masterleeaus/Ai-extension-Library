<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Payments;

class PaymentResult
{
    public function __construct(
        public bool $success,
        public string $transactionId,
        public ?float $amount = null,
        public ?string $status = null,
        public ?string $error = null,
        public array $gatewayResponse = [],
        public ?string $authorizationId = null,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isFailed(): bool
    {
        return !$this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'amount' => $this->amount,
            'status' => $this->status,
            'error' => $this->error,
            'authorization_id' => $this->authorizationId,
            'gateway_response' => $this->gatewayResponse,
        ];
    }
}

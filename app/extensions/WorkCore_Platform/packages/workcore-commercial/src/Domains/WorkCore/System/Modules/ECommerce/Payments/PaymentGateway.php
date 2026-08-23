<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Payments;

abstract class PaymentGateway
{
    protected string $apiKey;
    protected string $apiSecret;
    protected bool $testMode = true;

    public function __construct(string $apiKey, string $apiSecret, bool $testMode = true)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->testMode = $testMode;
    }

    abstract public function charge(float $amount, array $paymentDetails, array $metadata = []): PaymentResult;

    abstract public function authorize(float $amount, array $paymentDetails, array $metadata = []): PaymentResult;

    abstract public function capture(string $authorizationId, float $amount): PaymentResult;

    abstract public function refund(string $transactionId, ?float $amount = null): PaymentResult;

    abstract public function validate(array $paymentDetails): array;

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function setTestMode(bool $testMode): void
    {
        $this->testMode = $testMode;
    }
}

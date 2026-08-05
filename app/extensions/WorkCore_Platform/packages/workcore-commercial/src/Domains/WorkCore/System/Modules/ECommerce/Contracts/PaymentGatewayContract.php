<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Contracts;

use WorkCore\Domains\WorkCore\System\Modules\ECommerce\Payments\PaymentResult;

interface PaymentGatewayContract
{
    public function charge(float $amount, array $paymentDetails, array $metadata = []): PaymentResult;

    public function authorize(float $amount, array $paymentDetails, array $metadata = []): PaymentResult;

    public function capture(string $authorizationId, float $amount): PaymentResult;

    public function refund(string $transactionId, ?float $amount = null): PaymentResult;

    public function validate(array $paymentDetails): array;
}

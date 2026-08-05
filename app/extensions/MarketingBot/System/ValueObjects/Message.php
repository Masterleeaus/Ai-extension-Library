<?php

declare(strict_types=1);


namespace App\Extensions\MarketingBot\System\ValueObjects;

use JsonSerializable;

class Message implements JsonSerializable
{
    public function __construct(public ?string $value = null) {}

    #[Override]
    public function jsonSerialize(): ?string
    {
        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

final readonly class IntentDecision
{
    public function __construct(
        public string $lane,
        public string $intent,
        public float $confidence,
        public ?string $manager = null,
        public ?string $assistant = null,
        public ?string $agent = null,
        public ?string $domain = null,
        public ?string $operation = null,
        public string $riskLevel = 'green',
        public array $permissions = [],
    ) {}

    public function isBusinessAction(): bool { return $this->lane === 'business_action'; }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}

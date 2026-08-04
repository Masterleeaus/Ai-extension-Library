<?php

declare(strict_types=1);
namespace App\Extensions\SystemAIChat\System\Capabilities;
final class CapabilityDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $description = '',
        public readonly array $metadata = [],
    ) {}
}

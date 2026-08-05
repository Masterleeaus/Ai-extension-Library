<?php

declare(strict_types=1);

namespace App\Extensions\TitanNova\System\Capabilities;

use App\Extensions\Chatbot\System\TitanAI\Runtime\WorkCoreToolMappingRegistry;
use App\Extensions\TitanNova\System\Capabilities\Contracts\NativeToolMappingContract;

final class TitanAINativeToolMappingAdapter implements NativeToolMappingContract
{
    public function __construct(private WorkCoreToolMappingRegistry $mappings) {}

    public function has(string $domain, string $operation): bool
    {
        return $this->mappings->has($domain, $operation);
    }
}

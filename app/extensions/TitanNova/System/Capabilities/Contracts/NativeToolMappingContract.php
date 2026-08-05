<?php

declare(strict_types=1);

namespace App\Extensions\TitanNova\System\Capabilities\Contracts;

interface NativeToolMappingContract
{
    public function has(string $domain, string $operation): bool;
}

<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Destination\Contracts;

use App\Extensions\Migration\System\Canonical\CanonicalEntityDefinition;
use App\Extensions\Migration\System\Destination\DestinationWriteContext;
use App\Extensions\Migration\System\Destination\DestinationWriteResult;

interface DestinationHandlerInterface
{
    /** @param array<string, mixed> $payload
     *  @return array<int, string>
     */
    public function validate(array $payload, CanonicalEntityDefinition $definition): array;

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    public function identity(array $payload, CanonicalEntityDefinition $definition): array;

    /** @param array<string, mixed> $payload */
    public function write(array $payload, DestinationWriteContext $context): DestinationWriteResult;
}

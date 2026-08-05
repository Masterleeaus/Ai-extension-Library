<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Exceptions;

use InvalidArgumentException;

final class UnknownConnectorException extends InvalidArgumentException
{
    public static function forKey(string $key): self
    {
        return new self("No migration connector is registered for key '{$key}'.");
    }
}

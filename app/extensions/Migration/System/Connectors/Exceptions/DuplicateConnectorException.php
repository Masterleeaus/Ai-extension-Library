<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Exceptions;

use LogicException;

final class DuplicateConnectorException extends LogicException
{
    public static function forKey(string $key): self
    {
        return new self("A migration connector is already registered for key '{$key}'.");
    }
}

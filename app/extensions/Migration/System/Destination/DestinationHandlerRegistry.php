<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Destination;

use App\Extensions\Migration\System\Destination\Contracts\DestinationHandlerInterface;
use InvalidArgumentException;

final class DestinationHandlerRegistry
{
    /** @var array<string, DestinationHandlerInterface> */
    private array $handlers = [];

    public function register(string $entityKey, DestinationHandlerInterface $handler): void
    {
        $entityKey = trim($entityKey);
        if ($entityKey === '') {
            throw new InvalidArgumentException('Destination handler entity key cannot be empty.');
        }
        if (isset($this->handlers[$entityKey])) {
            throw new InvalidArgumentException("Destination handler for {$entityKey} is already registered.");
        }

        $this->handlers[$entityKey] = $handler;
        ksort($this->handlers);
    }

    public function has(string $entityKey): bool
    {
        return isset($this->handlers[$entityKey]);
    }

    public function get(string $entityKey): DestinationHandlerInterface
    {
        return $this->handlers[$entityKey]
            ?? throw new InvalidArgumentException("No destination handler registered for {$entityKey}.");
    }

    /** @return array<string, DestinationHandlerInterface> */
    public function all(): array
    {
        return $this->handlers;
    }
}

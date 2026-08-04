<?php

declare(strict_types=1);

namespace App\Extensions\AIChatPro\System\Connectors;

use App\Domains\TitanAI\Contracts\ConnectorDefinition as UnifiedConnectorDefinition;
use App\Extensions\AIChatPro\System\Connectors\Models\AIChatProConnector;
use InvalidArgumentException;
use LogicException;

/**
 * Discovery and tool-call bridge for independently installed AIChatPro connectors.
 */
final class UnifiedConnectorAdapter implements UnifiedConnectorDefinition
{
    public function __construct(private readonly ConnectorDefinition $connector) {}

    public function key(): string
    {
        return $this->connector->key();
    }

    public function name(): string
    {
        return $this->connector->label();
    }

    public function description(): string
    {
        return $this->connector->description();
    }

    public function metadata(): array
    {
        return [
            'source' => 'aichatpro',
            'icon' => $this->connector->icon(),
            'redirect_route' => $this->connector->redirectRoute(),
            'details' => $this->connector->details(),
            'permissions' => $this->connector->permissions(),
            'native_class' => $this->connector::class,
            'transport' => 'tool_call',
        ];
    }

    public function isConfigured(): bool
    {
        return $this->connector->isEnabled();
    }

    public function configSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'connector' => ['description' => AIChatProConnector::class],
                'function_name' => ['type' => 'string'],
                'arguments' => ['type' => 'object'],
            ],
            'required' => ['connector', 'function_name'],
        ];
    }

    public function send(array $data): mixed
    {
        $connector = $data['connector'] ?? null;
        $functionName = $data['function_name'] ?? null;
        if (! $connector instanceof AIChatProConnector || ! is_string($functionName) || $functionName === '') {
            throw new InvalidArgumentException('AIChatPro connector send requires connector and function_name.');
        }
        return $this->connector->handleToolCall(
            $functionName,
            is_array($data['arguments'] ?? null) ? $data['arguments'] : [],
            $connector,
        );
    }

    public function receive(): array
    {
        throw new LogicException('AIChatPro connectors are tool-call transports and do not support generic polling.');
    }
}

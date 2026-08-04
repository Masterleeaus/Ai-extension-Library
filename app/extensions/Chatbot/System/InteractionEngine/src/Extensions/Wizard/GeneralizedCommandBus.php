<?php

declare(strict_types=1);

namespace TitanZero\Interaction\Command;

use TitanZero\Interaction\Domain\Vertical\VerticalRegistry;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * GeneralizedCommandBus
 * 
 * Routes commands to the appropriate vertical adapter.
 * Replaces the hardcoded WorkCore command dispatcher.
 * 
 * Handles:
 * - Vertical routing
 * - Capability resolution
 * - Policy enforcement
 * - Audit logging
 * - Offline queuing
 */
final class GeneralizedCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly VerticalRegistry $verticalRegistry
    ) {}

    /**
     * Dispatch a command to the vertical adapter
     * 
     * @param array $command Command structure with 'capability' and 'payload'
     * @param array $context Execution context (user, tenant, device)
     * @return mixed Command result
     * @throws RuntimeException If capability not supported
     */
    public function dispatch(array $command, array $context = []): mixed
    {
        $capability = $command['capability'] ?? null;
        $payload = $command['payload'] ?? [];
        $metadata = $command['metadata'] ?? [];
        $verticalId = $metadata['vertical_id'] ?? $this->verticalRegistry->getActive();

        if (!$capability) {
            throw new RuntimeException('Command missing required capability field');
        }

        Log::info('Command dispatched', [
            'capability' => $capability,
            'vertical' => $verticalId,
            'correlation_id' => $metadata['correlation_id'] ?? null,
        ]);

        try {
            $adapter = $this->verticalRegistry->getAdapter($verticalId);
            
            // Merge metadata into context
            $executionContext = array_merge($context, $metadata);
            
            // Execute the capability through the vertical adapter
            $result = $adapter->executeCapability($capability, $payload, $executionContext);

            Log::info('Command executed successfully', [
                'capability' => $capability,
                'vertical' => $verticalId,
                'result_type' => gettype($result),
            ]);

            return $result;
        } catch (\Throwable $e) {
            Log::error('Command execution failed', [
                'capability' => $capability,
                'vertical' => $verticalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle offline command replay
     * 
     * When a PWA comes online, queued commands are replayed.
     * This method processes them with conflict detection.
     * 
     * @param array $offlineCommands Queued commands from device
     * @param array $context Execution context
     * @return array Replay result with conflicts
     */
    public function replayOfflineCommands(array $offlineCommands, array $context = []): array
    {
        $results = [];
        $conflicts = [];

        foreach ($offlineCommands as $command) {
            try {
                $result = $this->dispatch($command, $context);
                
                $results[] = [
                    'command_id' => $command['id'] ?? null,
                    'capability' => $command['capability'],
                    'status' => 'success',
                    'result' => $result,
                ];
            } catch (\Throwable $e) {
                // Check if this is a conflict (version mismatch, entity deleted, etc)
                $isConflict = $this->isConflictError($e, $command);
                
                if ($isConflict) {
                    $conflicts[] = [
                        'command_id' => $command['id'] ?? null,
                        'capability' => $command['capability'],
                        'error' => $e->getMessage(),
                        'entity_type' => $this->extractEntityType($command['capability']),
                    ];
                } else {
                    // Non-conflict error - hard failure
                    $results[] = [
                        'command_id' => $command['id'] ?? null,
                        'capability' => $command['capability'],
                        'status' => 'failed',
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }

        return [
            'total' => count($offlineCommands),
            'successful' => count(array_filter($results, fn($r) => $r['status'] === 'success')),
            'failed' => count(array_filter($results, fn($r) => $r['status'] === 'failed')),
            'conflicts' => count($conflicts),
            'results' => $results,
            'conflicts_detail' => $conflicts,
        ];
    }

    /**
     * Batch dispatch multiple commands
     * 
     * @param array<array> $commands Array of command structures
     * @param array $context Execution context
     * @return array Batch results
     */
    public function batch(array $commands, array $context = []): array
    {
        $results = [];

        foreach ($commands as $command) {
            try {
                $results[] = [
                    'command_id' => $command['id'] ?? null,
                    'capability' => $command['capability'],
                    'status' => 'success',
                    'result' => $this->dispatch($command, $context),
                ];
            } catch (\Throwable $e) {
                $results[] = [
                    'command_id' => $command['id'] ?? null,
                    'capability' => $command['capability'],
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Check if an error represents a conflict
     */
    private function isConflictError(\Throwable $e, array $command): bool
    {
        $message = $e->getMessage();
        
        return str_contains($message, 'conflict')
            || str_contains($message, 'version mismatch')
            || str_contains($message, 'entity deleted')
            || str_contains($message, 'constraint violation');
    }

    /**
     * Extract entity type from capability string
     * 
     * E.g., "jobs.create" -> "jobs", "properties.update" -> "properties"
     */
    private function extractEntityType(string $capability): string
    {
        return explode('.', $capability)[0] ?? 'unknown';
    }
}

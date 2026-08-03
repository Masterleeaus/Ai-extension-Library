<?php

declare(strict_types=1);

namespace TitanAI\Hybrid\Memory\Services;

use TitanAI\Hybrid\Diagnostics\TitanAIDiagnostics;
use TitanAI\Hybrid\Events\TitanAIEventBus;
use TitanAI\Hybrid\Memory\Enums\MemoryScope;
use TitanAI\Hybrid\UserMemoryUpdated;
use TitanAI\Hybrid\WorkflowMemoryUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use JsonException;
use stdClass;
use Stringable;

/**
 * Persistent shared memory storage for Chatbot, AI Agent, and AIChatPro.
 */
final class UnifiedMemoryRepository
{
    private const ENTITY_TYPE_MAX_LENGTH = 96;
    private const ENTITY_ID_MAX_LENGTH = 191;
    private const KEY_MAX_LENGTH = 191;
    private const SOURCE_MAX_LENGTH = 64;
    private const TTL_MAX_MINUTES = 4294967295;

    public function __construct(
        private readonly string $table = 'unified_memories',
        private readonly ?TitanAIEventBus $events = null,
        private readonly ?TitanAIDiagnostics $diagnostics = null,
    ) {}

    public function store(
        string $entityType,
        mixed $entityId,
        MemoryScope $scope,
        string $key,
        mixed $value,
        ?int $ttl = null,
        ?string $source = null,
    ): void {
        $identity = $this->identity($entityType, $entityId, $scope, $key);
        $effectiveTtl = $this->normaliseTtl($ttl ?? config('titanai.memory.default_ttl'));
        $source = trim((string) $source);
        $source = $source !== '' ? $source : 'titanai';
        $this->assertMaxLength($source, self::SOURCE_MAX_LENGTH, 'source');
        $now = now();

        DB::table($this->table)->upsert(
            [[
                ...$identity,
                'value' => self::encodeValue($value),
                'source' => $source,
                'ttl_minutes' => $effectiveTtl,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['scope', 'entity_type', 'entity_id', 'key'],
            ['value', 'source', 'ttl_minutes', 'updated_at'],
        );

        $this->dispatchUpdatedEvent($scope, $identity, $value, $source);
    }

    public function retrieve(
        string $entityType,
        mixed $entityId,
        MemoryScope $scope,
        string $key,
        mixed $default = null,
    ): mixed {
        $identity = $this->identity($entityType, $entityId, $scope, $key);
        $row = DB::table($this->table)
            ->where($identity)
            ->first();

        if (! $row instanceof stdClass) {
            return $default;
        }

        if ($this->isExpired($row)) {
            $this->forgetByIdentity($identity);
            return $default;
        }

        return $this->decode($row->value ?? null, $default);
    }

    public function forget(
        string $entityType,
        mixed $entityId,
        MemoryScope $scope,
        string $key,
    ): void {
        $this->forgetByIdentity($this->identity($entityType, $entityId, $scope, $key));
    }

    public function has(
        string $entityType,
        mixed $entityId,
        MemoryScope $scope,
        string $key,
    ): bool {
        $sentinel = new stdClass();
        return $this->retrieve($entityType, $entityId, $scope, $key, $sentinel) !== $sentinel;
    }

    /** @return array<string, mixed> */
    public function allForEntity(string $entityType, mixed $entityId, MemoryScope $scope): array
    {
        $entity = $this->normaliseEntity($entityType, $entityId);
        $rows = DB::table($this->table)
            ->where('scope', $scope->value)
            ->where('entity_type', $entity['entity_type'])
            ->where('entity_id', $entity['entity_id'])
            ->orderBy('key')
            ->get();

        $values = [];
        foreach ($rows as $row) {
            if ($this->isExpired($row)) {
                DB::table($this->table)->where('id', $row->id)->delete();
                continue;
            }
            $values[(string) $row->key] = $this->decode($row->value ?? null);
        }

        $this->diagnostics?->recordMemory('read', 'all_for_entity', [
            'entity_type' => $entity['entity_type'],
            'scope' => $scope->value,
        ]);

        return $values;
    }


    public function forgetAllForEntity(
        string $entityType,
        mixed $entityId,
        MemoryScope $scope,
        ?string $keyPrefix = null,
    ): int {
        $entity = $this->normaliseEntity($entityType, $entityId);
        $rows = DB::table($this->table)
            ->where('scope', $scope->value)
            ->where('entity_type', $entity['entity_type'])
            ->where('entity_id', $entity['entity_id'])
            ->get();

        $deleted = 0;
        foreach ($rows as $row) {
            $key = (string) ($row->key ?? '');
            if ($keyPrefix !== null && ! str_starts_with($key, $keyPrefix)) {
                continue;
            }
            $deleted += DB::table($this->table)->where('id', $row->id)->delete();
        }

        return $deleted;
    }

    public function purgeExpired(): int
    {
        $deleted = 0;
        $rows = DB::table($this->table)->whereNotNull('ttl_minutes')->get();
        foreach ($rows as $row) {
            if ($this->isExpired($row)) {
                $deleted += DB::table($this->table)->where('id', $row->id)->delete();
            }
        }
        return $deleted;
    }

    /** @return array{scope:string,entity_type:string,entity_id:string,key:string} */
    private function identity(string $entityType, mixed $entityId, MemoryScope $scope, string $key): array
    {
        $entity = $this->normaliseEntity($entityType, $entityId);
        $key = trim($key);
        if ($key === '') {
            throw new InvalidArgumentException('TitanAI memory key cannot be empty.');
        }
        $this->assertMaxLength($key, self::KEY_MAX_LENGTH, 'key');

        return [
            'scope' => $scope->value,
            ...$entity,
            'key' => $key,
        ];
    }

    /** @return array{entity_type:string,entity_id:string} */
    private function normaliseEntity(string $entityType, mixed $entityId): array
    {
        $entityType = trim($entityType);
        if ($entityType === '') {
            throw new InvalidArgumentException('TitanAI memory entity type cannot be empty.');
        }
        $this->assertMaxLength($entityType, self::ENTITY_TYPE_MAX_LENGTH, 'entity type');

        if (! is_string($entityId) && ! is_int($entityId) && ! $entityId instanceof Stringable) {
            throw new InvalidArgumentException('TitanAI memory entity id must be a non-empty string, integer, or Stringable object.');
        }
        $entityId = trim((string) $entityId);
        if ($entityId === '') {
            throw new InvalidArgumentException('TitanAI memory entity id cannot be empty.');
        }
        $this->assertMaxLength($entityId, self::ENTITY_ID_MAX_LENGTH, 'entity id');

        return ['entity_type' => $entityType, 'entity_id' => $entityId];
    }

    private function normaliseTtl(mixed $ttl): ?int
    {
        if ($ttl === null || $ttl === '') {
            return null;
        }
        if (! is_int($ttl) && ! (is_string($ttl) && ctype_digit($ttl))) {
            throw new InvalidArgumentException('TitanAI memory TTL must be a non-negative integer or null.');
        }

        $ttl = (int) $ttl;
        if ($ttl < 0) {
            throw new InvalidArgumentException('TitanAI memory TTL cannot be negative.');
        }
        if ($ttl > self::TTL_MAX_MINUTES) {
            throw new InvalidArgumentException('TitanAI memory TTL exceeds the storage column limit.');
        }
        return $ttl;
    }


    private function assertMaxLength(string $value, int $maximum, string $field): void
    {
        if (strlen($value) > $maximum) {
            throw new InvalidArgumentException("TitanAI memory {$field} exceeds {$maximum} characters.");
        }
    }

    /** @param array{scope:string,entity_type:string,entity_id:string,key:string} $identity */
    private function forgetByIdentity(array $identity): void
    {
        DB::table($this->table)->where($identity)->delete();
    }

    /** @param array{scope:string,entity_type:string,entity_id:string,key:string} $identity */
    private function dispatchUpdatedEvent(MemoryScope $scope, array $identity, mixed $value, string $source): void
    {
        if (! (bool) config('titanai.memory.emit_events', true)) {
            return;
        }

        $event = match ($scope) {
            MemoryScope::USER => new UserMemoryUpdated(
                $identity['entity_type'],
                $identity['entity_id'],
                $identity['key'],
                $value,
                $source,
            ),
            MemoryScope::WORKFLOW => new WorkflowMemoryUpdated(
                $identity['entity_type'],
                $identity['entity_id'],
                $identity['key'],
                $value,
                $source,
            ),
            default => null,
        };

        if ($event === null) return;

        $idempotencyKey = implode(':', [
            'memory',
            $scope->value,
            $identity['entity_type'],
            $identity['entity_id'],
            $identity['key'],
            hash('sha256', self::encodeValue($value) . '|' . $source),
        ]);

        if ($this->events !== null) {
            $this->events->dispatch($event, $idempotencyKey);
            return;
        }

        Event::dispatch($event);
    }

    private function isExpired(object $row): bool
    {
        if ($row->ttl_minutes === null) {
            return false;
        }
        $updatedAt = strtotime((string) $row->updated_at);
        return $updatedAt !== false && ($updatedAt + ((int) $row->ttl_minutes * 60)) <= time();
    }

    private static function encodeValue(mixed $value): string
    {
        return json_encode(['value' => $value], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function decode(mixed $encoded, mixed $default = null): mixed
    {
        if (! is_string($encoded)) {
            return $default;
        }
        try {
            $decoded = json_decode($encoded, true, flags: JSON_THROW_ON_ERROR);
            return is_array($decoded) && array_key_exists('value', $decoded) ? $decoded['value'] : $decoded;
        } catch (JsonException) {
            return $encoded;
        }
    }
}

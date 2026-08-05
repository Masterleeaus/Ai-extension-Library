<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Services;

use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use JsonException;

final class WizardDefinitionRegistry
{
    private array $system = [];

    public function __construct(private readonly ConnectionInterface $db)
    {
        foreach ((array) require __DIR__ . '/../config/definitions.php' as $definition) {
            $this->system[(string) $definition['key']] = $definition;
        }
    }

    public function all(?int $companyId = null): array
    {
        $all = $this->system;
        if ($companyId && $this->hasTable()) {
            foreach ($this->db->table('tz_wizard_definitions')
                ->where('company_id', $companyId)
                ->where('status', 'published')
                ->orderBy('version')
                ->get() as $row) {
                $definition = $this->decodeDefinition((string) $row->definition);
                $all[(string) $definition['key']] = $definition;
            }
        }
        return array_values($all);
    }

    public function get(string $key, ?int $companyId = null): array
    {
        if ($companyId && $this->hasTable()) {
            $row = $this->db->table('tz_wizard_definitions')
                ->where('company_id', $companyId)
                ->where('definition_key', $key)
                ->where('status', 'published')
                ->orderByDesc('version')
                ->first();
            if ($row) {
                return $this->decodeDefinition((string) $row->definition);
            }
        }

        return $this->system[$key]
            ?? throw new InvalidArgumentException("Wizard definition [{$key}] is not registered.");
    }

    public function getVersion(string $key, int $version, ?int $companyId = null): array
    {
        if ($version < 1) {
            throw new InvalidArgumentException('Wizard definition version must be positive.');
        }

        if ($companyId && $this->hasTable()) {
            $query = $this->db->table('tz_wizard_definitions')
                ->where('company_id', $companyId)
                ->where('definition_key', $key);

            $row = (clone $query)->where('version', $version)->first();
            if ($row) {
                $definition = $this->decodeDefinition((string) $row->definition);
                if ((int) ($definition['version'] ?? $version) !== $version) {
                    throw new InvalidArgumentException("Wizard definition [{$key}] version metadata is inconsistent.");
                }
                return $definition;
            }

            if ((clone $query)->exists()) {
                throw new InvalidArgumentException(
                    "Wizard definition [{$key}] version [{$version}] is unavailable for the active company."
                );
            }
        }

        $definition = $this->system[$key]
            ?? throw new InvalidArgumentException("Wizard definition [{$key}] is not registered.");
        if ((int) ($definition['version'] ?? 1) !== $version) {
            throw new InvalidArgumentException("Wizard definition [{$key}] version [{$version}] is unavailable.");
        }
        return $definition;
    }

    public function has(string $key, ?int $companyId = null): bool
    {
        try {
            $this->get($key, $companyId);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private function decodeDefinition(string $json): array
    {
        try {
            $definition = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Wizard definition JSON is invalid.', previous: $exception);
        }
        if (!is_array($definition)) {
            throw new InvalidArgumentException('Wizard definition must decode to an object.');
        }
        return $definition;
    }

    private function hasTable(): bool
    {
        return $this->db->getSchemaBuilder()->hasTable('tz_wizard_definitions');
    }
}

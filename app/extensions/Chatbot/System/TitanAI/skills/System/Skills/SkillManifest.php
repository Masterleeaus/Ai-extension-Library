<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Skills;

use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolSet;
use InvalidArgumentException;

final readonly class SkillManifest
{
    /**
     * @param array<string, mixed> $inputs
     * @param array<string, mixed> $outputs
     * @param list<string> $capabilities
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $name,
        public string $description,
        public string $instructions,
        public string $version = '1.0.0',
        public array $inputs = ['type' => 'object', 'properties' => []],
        public array $outputs = ['type' => 'object'],
        public SkillToolSet $tools = new SkillToolSet([]),
        public array $capabilities = [],
        public array $metadata = [],
    ) {
        if (trim($this->name) === '') {
            throw new InvalidArgumentException('Skill name cannot be empty.');
        }

        if (trim($this->instructions) === '') {
            throw new InvalidArgumentException('Skill instructions cannot be empty.');
        }

        if (! preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $this->version)) {
            throw new InvalidArgumentException("Invalid skill version [{$this->version}].");
        }
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $contract = [
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
            'tools' => $this->tools->toArray(),
            'capabilities' => $this->capabilities,
        ];

        $metadata = $this->metadata;
        $metadata['skill_contract'] = $contract;

        return [
            'name' => trim($this->name),
            'description' => trim($this->description),
            'instructions' => trim($this->instructions),
            'version' => $this->version,
            'inputs' => $this->inputs,
            'outputs' => $this->outputs,
            'tools' => $this->tools->toArray(),
            'capabilities' => $this->capabilities,
            'metadata' => $metadata,
        ];
    }
}

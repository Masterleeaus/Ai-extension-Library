<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System\Packages;

final readonly class SkillPackageFingerprint
{
    /** @param array<string, string> $resourceHashes */
    public function __construct(
        public string $packageHash,
        public string $manifestHash,
        public string $instructionsHash,
        public array $resourceHashes,
    ) {
    }

    /** @return array{package_hash: string, manifest_hash: string, instructions_hash: string, resource_hashes: array<string, string>} */
    public function toArray(): array
    {
        return [
            'package_hash' => $this->packageHash,
            'manifest_hash' => $this->manifestHash,
            'instructions_hash' => $this->instructionsHash,
            'resource_hashes' => $this->resourceHashes,
        ];
    }
}

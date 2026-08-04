<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface SkillRuntimeContract
{
    public function register(
        string $skillId,
        string $className,
        array $metadata = []
    ): bool;

    public function execute(
        string $tenantId,
        string $skillId,
        array $input,
        array $context = []
    ): array;

    public function getSkill(string $skillId): ?array;

    public function listSkills(string $tenantId, ?string $category = null): array;

    public function validateInput(string $skillId, array $input): bool;

    public function publishVersion(
        string $skillId,
        string $version,
        array $metadata = []
    ): bool;
}

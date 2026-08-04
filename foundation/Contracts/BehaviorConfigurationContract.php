<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface BehaviorConfigurationContract
{
    public function createBehaviorConfig(
        string $tenantId,
        string $configName,
        array $behaviors
    ): string;

    public function getBehaviorConfig(
        string $tenantId,
        string $configId
    ): ?array;

    public function updateBehaviorConfig(
        string $tenantId,
        string $configId,
        array $behaviors
    ): bool;

    public function addBehaviorRule(
        string $tenantId,
        string $configId,
        string $trigger,
        string $action,
        array $conditions
    ): bool;

    public function removeBehaviorRule(
        string $tenantId,
        string $configId,
        string $ruleId
    ): bool;

    public function testBehaviorRule(
        string $tenantId,
        string $ruleId,
        array $testData
    ): array;

    public function listBehaviorConfigs(
        string $tenantId
    ): array;

    public function applyBehaviorConfig(
        string $tenantId,
        string $configId,
        string $scope
    ): bool;
}

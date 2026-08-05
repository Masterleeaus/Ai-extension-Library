<?php

declare(strict_types=1);

namespace App\Extensions\Migration\System\Connectors\Contracts;

use App\Extensions\Migration\System\Connectors\ConnectorDefinition;

interface MigrationSourceConnectorInterface
{
    public function definition(): ConnectorDefinition;

    /** @return array<string, mixed> */
    public function testConnection(array $configuration): array;

    /** @return array<string, mixed> */
    public function discover(array $configuration): array;

    /** @return array<string, mixed> */
    public function estimate(array $project): array;

    /** @return iterable<int, array<string, mixed>> */
    public function stream(array $entityPlan, ?array $checkpoint = null): iterable;

    public function supportsIncrementalSync(): bool;

    /** @return iterable<int, array<string, mixed>> */
    public function readIncremental(array $entityPlan, array $checkpoint): iterable;

    public function fetchAttachment(array $attachment): mixed;

    /** @return array<string, mixed> */
    public function redactConfiguration(array $configuration): array;
}

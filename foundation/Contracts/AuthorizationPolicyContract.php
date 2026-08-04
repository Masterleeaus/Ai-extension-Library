<?php

declare(strict_types=1);

namespace Foundation\Contracts;

interface AuthorizationPolicyContract
{
    public function authorize(
        TenantContextContract $context,
        string $action,
        string $resource,
        array $attributes = []
    ): bool;

    public function getRequiredPermissions(string $action, string $resource): array;

    public function audit(
        TenantContextContract $context,
        string $action,
        string $resource,
        bool $approved,
        array $attributes = []
    ): void;
}

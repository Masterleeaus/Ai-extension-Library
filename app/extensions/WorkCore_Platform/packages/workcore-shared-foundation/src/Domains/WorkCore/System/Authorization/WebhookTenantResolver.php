<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Authorization;

use App\Domains\WorkCore\System\Context\TenantContextSnapshot;
use App\Domains\WorkCore\System\Contracts\TenantContextContract;
use RuntimeException;

final class WebhookTenantResolver
{
    public function resolveTenantFromWebhookPayload(
        array $payload,
        TenantContextContract $tenantContext,
    ): TenantContextSnapshot {
        $tenantId = $this->extractTenantId($payload);

        if ($tenantId === null) {
            throw new RuntimeException(
                'Webhook payload does not contain a valid tenant identifier. '
                . 'Ensure the payload includes a company_id or tenant_id field.'
            );
        }

        if ($tenantContext->hasTenant() && $tenantContext->companyId() !== $tenantId) {
            throw new RuntimeException(
                'Webhook tenant ID mismatch. Expected tenant ' . $tenantContext->companyId()
                . ' but received ' . $tenantId . '. Cross-tenant webhook execution is not allowed.'
            );
        }

        return new TenantContextSnapshot(
            $tenantId,
            $this->extractUserId($payload)
        );
    }

    public function verifyWebhookBelongsToTenant(
        array $payload,
        int $expectedTenantId,
    ): bool {
        $tenantId = $this->extractTenantId($payload);
        return $tenantId === $expectedTenantId;
    }

    private function extractTenantId(array $payload): ?int
    {
        $keys = ['company_id', 'tenant_id', 'tenantId', 'companyId'];
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_int($payload[$key])) {
                return $payload[$key];
            }
        }
        return null;
    }

    private function extractUserId(array $payload): ?int
    {
        $keys = ['user_id', 'actor_id', 'userId', 'actorId'];
        foreach ($keys as $key) {
            if (isset($payload[$key]) && is_int($payload[$key])) {
                return $payload[$key];
            }
        }
        return null;
    }
}

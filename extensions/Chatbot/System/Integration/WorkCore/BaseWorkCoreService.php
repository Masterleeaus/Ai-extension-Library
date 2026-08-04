<?php declare(strict_types=1);
namespace App\Extensions\Chatbot\System\Integration\WorkCore;

use App\Domains\WorkCore\System\Authorization\CompanyRecordAuthorizer;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

abstract class BaseWorkCoreService
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected CompanyRecordAuthorizer $authorizer,
    ) {}

    protected function getTenantId(): int
    {
        return $this->tenantContext->companyId();
    }

    protected function authorize(string $action, string $resource): bool
    {
        return $this->authorizer->isAllowed($this->getTenantId(), $action, $resource);
    }

    protected function query(string $queryName, array $params = []): mixed
    {
        if (!$this->authorize('read', $queryName)) {
            return null;
        }
        return null; // Placeholder for WorkCore query
    }
}

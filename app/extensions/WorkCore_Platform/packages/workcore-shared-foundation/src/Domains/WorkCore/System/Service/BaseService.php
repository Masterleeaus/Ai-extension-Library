<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Service;

use App\Domains\WorkCore\System\Context\TenantContextSnapshot;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Base Service Class
 * Provides common functionality for all WorkCore services:
 * - Tenant isolation
 * - Authorization checking
 * - Event publishing
 * - Error handling
 * - Transaction management
 */
abstract class BaseService
{
    protected TenantContextSnapshot $tenant;

    public function __construct(
        protected TenantContext $tenantContext,
    ) {
        $this->validateTenant();
        $this->tenant = $this->tenantContext->snapshot();
    }

    /**
     * Validate tenant context is set
     */
    protected function validateTenant(): void
    {
        if (!$this->tenantContext->hasTenant()) {
            throw new \RuntimeException('Tenant context not resolved');
        }
    }

    /**
     * Get current tenant
     */
    protected function getTenant(): TenantContextSnapshot
    {
        return $this->tenant;
    }

    /**
     * Get current company ID
     */
    protected function getCompanyId(): int
    {
        return $this->tenant->companyId;
    }

    /**
     * Get current user ID
     */
    protected function getUserId(): ?int
    {
        return $this->tenant->userId;
    }

    /**
     * Validate required fields in data array
     */
    protected function validateRequired(array $data, array $required): void
    {
        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Required fields missing: ' . implode(', ', $missing)
            );
        }
    }

    /**
     * Validate field is in allowed values
     */
    protected function validateEnum(string $field, string $value, array $allowed): void
    {
        if (!in_array($value, $allowed)) {
            throw new \InvalidArgumentException(
                "{$field} must be one of: " . implode(', ', $allowed)
            );
        }
    }

    /**
     * Validate email format
     */
    protected function validateEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Invalid email format: {$email}");
        }
    }

    /**
     * Validate phone format (basic)
     */
    protected function validatePhone(string $phone): void
    {
        if (preg_match('/^[\d\-\+\s\(\)]{7,}$/', $phone) === 0) {
            throw new \InvalidArgumentException("Invalid phone format: {$phone}");
        }
    }

    /**
     * Validate date format (YYYY-MM-DD)
     */
    protected function validateDateFormat(string $date): void
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException("Invalid date format (use YYYY-MM-DD): {$date}");
        }

        $parts = explode('-', $date);
        if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
            throw new \InvalidArgumentException("Invalid date: {$date}");
        }
    }

    /**
     * Validate positive integer
     */
    protected function validatePositiveInteger(string $field, mixed $value): void
    {
        if (!is_int($value) || $value <= 0) {
            throw new \InvalidArgumentException("{$field} must be a positive integer");
        }
    }

    /**
     * Validate numeric range
     */
    protected function validateRange(string $field, float $value, float $min, float $max): void
    {
        if ($value < $min || $value > $max) {
            throw new \InvalidArgumentException(
                "{$field} must be between {$min} and {$max}, got {$value}"
            );
        }
    }

    /**
     * Add audit fields to data (created_by, created_at, etc)
     */
    protected function addAuditFields(array $data, bool $isUpdate = false): array
    {
        $now = date('Y-m-d H:i:s');

        if (!$isUpdate) {
            $data['company_id'] = $this->getCompanyId();
            $data['created_by'] = $this->getUserId();
            $data['created_at'] = $now;
        }

        $data['updated_by'] = $this->getUserId();
        $data['updated_at'] = $now;

        return $data;
    }

    /**
     * Soft delete record (sets deleted_at)
     */
    protected function softDeleteRecord(array &$data): void
    {
        $data['deleted_by'] = $this->getUserId();
        $data['deleted_at'] = date('Y-m-d H:i:s');
    }

    /**
     * Handle concurrent update conflict (optimistic locking)
     */
    protected function validateVersion(int $expectedVersion, int $actualVersion, string $entity): void
    {
        if ($expectedVersion !== $actualVersion) {
            throw new \RuntimeException(
                "{$entity} was modified by another user. Please refresh and try again."
            );
        }
    }
}

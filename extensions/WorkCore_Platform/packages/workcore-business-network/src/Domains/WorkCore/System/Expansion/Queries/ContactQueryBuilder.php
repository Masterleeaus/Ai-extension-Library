<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Expansion\Queries;

use App\Domains\WorkCore\System\Query\BaseQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;

/**
 * Contact Query Builder
 * Provides fluent interface for querying contacts (people within organizations)
 */
final class ContactQueryBuilder extends BaseQueryBuilder
{
    public function __construct(TenantContext $tenantContext)
    {
        parent::__construct($tenantContext);
    }

    /**
     * Filter by customer (organization)
     */
    public function byCustomer(int $customerId): static
    {
        return $this->where('customer_id', $customerId);
    }

    /**
     * Filter by role/title
     */
    public function byRole(string $role): static
    {
        return $this->where('title', $role);
    }

    /**
     * Filter by department
     */
    public function byDepartment(string $department): static
    {
        return $this->where('department', $department);
    }

    /**
     * Filter by contact type (decision_maker, influencer, etc)
     */
    public function byType(string $type): static
    {
        return $this->where('contact_type', $type);
    }

    /**
     * Filter by email domain
     */
    public function byEmailDomain(string $domain): static
    {
        $this->filters[] = [
            'field' => 'email',
            'value' => "%@{$domain}",
            'operator' => 'LIKE',
        ];
        return $this;
    }

    /**
     * Filter by phone country code
     */
    public function byCountry(string $country): static
    {
        return $this->where('country', $country);
    }

    /**
     * Filter by engagement level
     */
    public function byEngagementLevel(string $level): static
    {
        return $this->where('engagement_level', $level);
    }

    /**
     * Filter by primary contact status
     */
    public function primary(): static
    {
        return $this->where('is_primary', true);
    }

    /**
     * Search by name
     */
    public function byName(string $name): static
    {
        return $this->search('name', $name);
    }

    /**
     * Search by email
     */
    public function byEmail(string $email): static
    {
        return $this->where('email', $email);
    }

    /**
     * Get all contacts
     */
    public function get(): array
    {
        return [];
    }

    /**
     * Get single contact
     */
    public function first(): ?array
    {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }

    /**
     * Count total matching contacts
     */
    public function count(): int
    {
        return 0;
    }

    /**
     * Get paginated results
     */
    public function paginate(): array
    {
        $total = $this->count();
        $items = $this->get();

        return [
            'data' => $items,
            'total' => $total,
            'limit' => $this->limit ?? 20,
            'offset' => $this->offset,
        ];
    }

    /**
     * Get contacts by customer with eager loading of interactions
     */
    public function withInteractions(): static
    {
        $this->filters[] = [
            'relation' => 'interactions',
            'eager' => true,
        ];
        return $this;
    }

    /**
     * Get contacts by customer with communication preferences
     */
    public function withPreferences(): static
    {
        $this->filters[] = [
            'relation' => 'communication_preferences',
            'eager' => true,
        ];
        return $this;
    }

    /**
     * Get contacts by department summary
     */
    public function summaryByDepartment(): array
    {
        return [];
    }

    /**
     * Get contacts by role/title
     */
    public function summaryByRole(): array
    {
        return [];
    }

    /**
     * Get decision-makers only
     */
    public function decisionMakers(): static
    {
        return $this->where('contact_type', 'decision_maker');
    }

    /**
     * Get recent interactions summary
     */
    public function recentInteractions(int $days = 30): array
    {
        return [];
    }
}

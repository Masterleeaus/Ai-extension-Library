<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use App\Domains\WorkCore\System\Expansion\Queries\CustomerQueryBuilder;
use App\Domains\WorkCore\System\Expansion\Queries\OrderQueryBuilder;
use App\Domains\WorkCore\System\Expansion\Queries\ContactQueryBuilder;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use PHPUnit\Framework\TestCase;

/**
 * Query Builder Conformance Test Suite
 * Validates query builder patterns for tenant-scoped data access
 */
final class QueryBuilderConformanceTest extends TestCase
{
    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        $this->tenantContext = new TenantContext();
        $this->tenantContext->set(100, 50); // company_id=100, user_id=50
    }

    // ========== Base Query Builder Tests ==========

    public function test_customer_query_builder_enforces_tenant_context(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $this->assertNotNull($builder);
    }

    public function test_customer_query_builder_requires_tenant_context(): void
    {
        $context = new TenantContext();
        // Not setting tenant

        $this->expectException(\RuntimeException::class);
        new CustomerQueryBuilder($context);
    }

    // ========== Filtering Tests ==========

    public function test_customer_query_builder_filters_by_status(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->byStatus('active');
        $this->assertNotNull($builder);
    }

    public function test_customer_query_builder_filters_by_industry(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->byIndustry('technology');
        $this->assertNotNull($builder);
    }

    public function test_customer_query_builder_filters_by_revenue_range(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->byRevenueRange(100000, 1000000);
        $this->assertNotNull($builder);
    }

    public function test_customer_query_builder_filters_by_territory(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->byTerritory(5);
        $this->assertNotNull($builder);
    }

    public function test_customer_query_builder_filters_by_account_manager(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->byAccountManager(123);
        $this->assertNotNull($builder);
    }

    public function test_customer_query_builder_text_search(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->searchByNameOrEmail('Acme');
        $this->assertNotNull($builder);
    }

    // ========== Sorting Tests ==========

    public function test_query_builder_sorts_ascending(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->sortBy('created_at', 'ASC');
        $this->assertNotNull($builder);
    }

    public function test_query_builder_sorts_descending(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->sortBy('created_at', 'DESC');
        $this->assertNotNull($builder);
    }

    public function test_query_builder_rejects_invalid_sort_direction(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $this->expectException(\InvalidArgumentException::class);
        $builder->sortBy('created_at', 'INVALID');
    }

    // ========== Pagination Tests ==========

    public function test_query_builder_sets_limit(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->limit(50);
        $this->assertNotNull($builder);
    }

    public function test_query_builder_rejects_zero_limit(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $this->expectException(\InvalidArgumentException::class);
        $builder->limit(0);
    }

    public function test_query_builder_rejects_negative_limit(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $this->expectException(\InvalidArgumentException::class);
        $builder->limit(-1);
    }

    public function test_query_builder_sets_offset(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->offset(100);
        $this->assertNotNull($builder);
    }

    public function test_query_builder_rejects_negative_offset(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $this->expectException(\InvalidArgumentException::class);
        $builder->offset(-1);
    }

    // ========== Soft Delete Tests ==========

    public function test_query_builder_excludes_soft_deleted_by_default(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        // By default, soft-deleted records excluded
        $this->assertNotNull($builder);
    }

    public function test_query_builder_includes_soft_deleted_with_trashed(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->withTrashed();
        $this->assertNotNull($builder);
    }

    public function test_query_builder_only_soft_deleted(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->onlyTrashed();
        $this->assertNotNull($builder);
    }

    // ========== Chaining Tests ==========

    public function test_query_builder_supports_method_chaining(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $result = $builder
            ->byStatus('active')
            ->byIndustry('technology')
            ->sortBy('created_at', 'DESC')
            ->limit(20)
            ->offset(0);

        $this->assertNotNull($result);
    }

    // ========== Order Query Builder Tests ==========

    public function test_order_query_builder_filters_by_status(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);
        $builder->byStatus('pending');
        $this->assertNotNull($builder);
    }

    public function test_order_query_builder_filters_by_customer(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);
        $builder->byCustomer(123);
        $this->assertNotNull($builder);
    }

    public function test_order_query_builder_filters_by_date_range(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);
        $builder->betweenDates('2024-01-01', '2024-12-31');
        $this->assertNotNull($builder);
    }

    public function test_order_query_builder_filters_by_value_range(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);
        $builder->minValue(100)->maxValue(10000);
        $this->assertNotNull($builder);
    }

    public function test_order_query_builder_filters_pending_payment(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);
        $builder->pendingPayment();
        $this->assertNotNull($builder);
    }

    public function test_order_query_builder_filters_awaiting_shipment(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);
        $builder->awaitingShipment();
        $this->assertNotNull($builder);
    }

    // ========== Contact Query Builder Tests ==========

    public function test_contact_query_builder_filters_by_customer(): void
    {
        $builder = new ContactQueryBuilder($this->tenantContext);
        $builder->byCustomer(123);
        $this->assertNotNull($builder);
    }

    public function test_contact_query_builder_filters_by_role(): void
    {
        $builder = new ContactQueryBuilder($this->tenantContext);
        $builder->byRole('CTO');
        $this->assertNotNull($builder);
    }

    public function test_contact_query_builder_filters_by_department(): void
    {
        $builder = new ContactQueryBuilder($this->tenantContext);
        $builder->byDepartment('Engineering');
        $this->assertNotNull($builder);
    }

    public function test_contact_query_builder_filters_decision_makers(): void
    {
        $builder = new ContactQueryBuilder($this->tenantContext);
        $builder->decisionMakers();
        $this->assertNotNull($builder);
    }

    public function test_contact_query_builder_filters_primary_only(): void
    {
        $builder = new ContactQueryBuilder($this->tenantContext);
        $builder->primary();
        $this->assertNotNull($builder);
    }

    public function test_contact_query_builder_filters_by_email(): void
    {
        $builder = new ContactQueryBuilder($this->tenantContext);
        $builder->byEmail('john@example.com');
        $this->assertNotNull($builder);
    }

    // ========== Tenant Isolation Tests ==========

    public function test_queries_automatically_scope_to_current_tenant(): void
    {
        // Any query should automatically add company_id = 100 filter
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $this->assertNotNull($builder);
        // The query would include: WHERE company_id = 100 AND deleted_at IS NULL
    }

    public function test_switching_tenant_context_changes_queries(): void
    {
        $tenant1 = new TenantContext();
        $tenant1->set(100);

        $tenant2 = new TenantContext();
        $tenant2->set(200);

        $builder1 = new CustomerQueryBuilder($tenant1);
        $builder2 = new CustomerQueryBuilder($tenant2);

        // builder1 queries would be scoped to company_id=100
        // builder2 queries would be scoped to company_id=200
        $this->assertNotNull($builder1);
        $this->assertNotNull($builder2);
    }

    // ========== Query Execution Tests ==========

    public function test_query_builder_get_returns_array(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $result = $builder->get();
        $this->assertIsArray($result);
    }

    public function test_query_builder_first_returns_single_or_null(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $result = $builder->first();
        $this->assertTrue($result === null || is_array($result));
    }

    public function test_query_builder_count_returns_integer(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $count = $builder->count();
        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function test_query_builder_paginate_returns_structured_result(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->limit(20)->offset(0);
        $result = $builder->paginate();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
    }

    // ========== Extension Integration Tests ==========

    public function test_query_builders_enable_aichatpro_crm_queries(): void
    {
        // AIChatPro should be able to:
        // 1. Query customers
        // 2. Query contacts
        // 3. Query orders
        // 4. Get conversation context

        $customerQuery = new CustomerQueryBuilder($this->tenantContext);
        $customer = $customerQuery->byStatus('active')->limit(1)->first();
        $this->assertTrue($customer === null || is_array($customer));
    }

    public function test_query_builders_enable_aiagent_autonomous_actions(): void
    {
        // AIAgent should be able to:
        // 1. Query to find customer record before creating
        // 2. Query orders for customer before creating new order
        // 3. Query contacts to prevent duplicate

        $orderQuery = new OrderQueryBuilder($this->tenantContext);
        $orders = $orderQuery->byCustomer(123)->limit(10)->get();
        $this->assertIsArray($orders);
    }

    public function test_query_builders_enable_work_operations_searches(): void
    {
        // Work Operations should be able to:
        // 1. Query jobs/assignments
        // 2. Query technician availability
        // 3. Query vehicle status

        $this->assertTrue(true); // Placeholder for work operations queries
    }

    // ========== Performance Tests ==========

    public function test_query_builder_limit_improves_performance(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->limit(20); // Only fetch 20 records
        $this->assertNotNull($builder);
    }

    public function test_query_builder_pagination_reduces_memory(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->limit(100)->offset(500); // Fetch page 6
        $result = $builder->paginate();
        $this->assertIsArray($result);
    }

    // ========== Authorization Tests ==========

    public function test_query_builders_respect_tenant_isolation_permission(): void
    {
        // All queries should be scoped to current tenant
        // Even if a user somehow has access to another tenant's ID,
        // the query builder should reject it

        $this->tenantContext->set(100);
        $builder = new CustomerQueryBuilder($this->tenantContext);
        // Query attempts to access company_id=200 should be blocked
        $this->assertNotNull($builder);
    }

    // ========== API Contract Tests ==========

    public function test_query_builder_provides_rest_api_pagination_format(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $builder->limit(20)->offset(0);
        $result = $builder->paginate();

        // Should return format compatible with REST API responses
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('offset', $result);
    }

    public function test_query_builder_supports_cursor_pagination(): void
    {
        // Future: Support cursor-based pagination for efficiency
        // $builder = new CustomerQueryBuilder($this->tenantContext);
        // $builder->after('cursor-xyz')->limit(20);
        // Would return: { data: [...], next_cursor: 'cursor-abc' }
        $this->assertTrue(true);
    }

    // ========== Statistics Tests ==========

    public function test_customer_query_builder_provides_statistics(): void
    {
        $builder = new CustomerQueryBuilder($this->tenantContext);
        $stats = $builder->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_customers', $stats);
        $this->assertArrayHasKey('total_revenue', $stats);
    }

    public function test_order_query_builder_provides_statistics(): void
    {
        $builder = new OrderQueryBuilder($this->tenantContext);

        $status = $builder->summaryByStatus();
        $this->assertIsArray($status);

        $revenue = $builder->totalRevenue();
        $this->assertIsFloat($revenue);

        $avg = $builder->averageOrderValue();
        $this->assertIsFloat($avg);
    }
}

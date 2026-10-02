# Week 2: API Layer - Query Builders & REST Endpoints

## Overview
Week 2 implements the query builder infrastructure for all WorkCore modules, providing extensions the ability to search, filter, and retrieve data with proper tenant isolation and authorization.

## Completed: Query Builder Infrastructure

### 1. Base Query Builder Pattern
**File:** `extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Query/BaseQueryBuilder.php`

**Provides:**
- Fluent query interface with method chaining
- Automatic tenant scoping (company_id filter added to all queries)
- Filter DSL: where(), whereIn(), whereBetween(), search()
- Sorting: sortBy(field, direction)
- Pagination: limit(), offset()
- Soft delete handling: withTrashed(), onlyTrashed()
- Abstract methods for execution: get(), first(), count(), paginate()

**Pattern:**
```php
$customers = (new CustomerQueryBuilder($tenantContext))
    ->where('status', 'active')
    ->search('name', 'Acme')
    ->sortBy('created_at', 'DESC')
    ->limit(20)
    ->paginate();
```

**Enforces:**
- Tenant context validation (throws if tenant not set)
- Automatic company_id filtering
- Soft delete exclusion (by default)
- Consistent pagination format for REST API

---

### 2. Business Network Query Builders

#### CustomerQueryBuilder
**File:** `extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Expansion/Queries/CustomerQueryBuilder.php`

**Methods:**
- `byStatus(status)` - Filter by status (active, prospect, churned, etc)
- `byType(type)` - Filter by customer type
- `byIndustry(industry)` - Filter by industry vertical
- `byRevenueRange(min, max)` - Filter by annual revenue
- `byTerritory(territoryId)` - Filter by sales territory
- `byAccountManager(userId)` - Filter by assigned account manager
- `bySegment(segment)` - Filter by customer segments
- `createdBetween(startDate, endDate)` - Filter by creation date
- `withTag(tag)` - Filter by customer tags
- `with(relation)` - Eager load relations (contacts, orders, etc)
- `getStatistics()` - Get aggregated stats (total customers, revenue, by industry, etc)

**Usage by AIChatPro CRM:**
```php
// Get active customers in technology industry
$customers = (new CustomerQueryBuilder($tenantContext))
    ->byStatus('active')
    ->byIndustry('technology')
    ->sortBy('created_at', 'DESC')
    ->limit(50)
    ->paginate();

// Get customers with potential (high revenue, active)
$highValue = (new CustomerQueryBuilder($tenantContext))
    ->byRevenueRange(100000, 999999)
    ->byStatus('active')
    ->get();

// Get statistics for dashboard
$stats = (new CustomerQueryBuilder($tenantContext))->getStatistics();
// Returns: {total_customers, total_revenue, by_status, by_industry, by_segment}
```

#### OrderQueryBuilder
**File:** `extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Expansion/Queries/OrderQueryBuilder.php`

**Methods:**
- `byStatus(status)` - Filter by status (pending, confirmed, shipped, delivered, cancelled)
- `byCustomer(customerId)` - Filter by customer
- `betweenDates(startDate, endDate)` - Filter by order date range
- `minValue(amount)` / `maxValue(amount)` - Filter by order value
- `bySalesRep(userId)` - Filter by sales representative
- `byProductCategory(category)` - Filter by product category
- `pendingPayment()` - Filter orders awaiting payment
- `awaitingShipment()` - Filter orders awaiting fulfillment
- `summaryByStatus()` - Get count by status
- `totalRevenue()` - Get total revenue for filtered orders
- `averageOrderValue()` - Get average order value

**Usage by AIAgent Commerce:**
```php
// Find pending orders for autonomous processing
$pending = (new OrderQueryBuilder($tenantContext))
    ->byStatus('pending')
    ->sortBy('order_date', 'ASC')
    ->limit(100)
    ->get();

// Get revenue for period
$revenue = (new OrderQueryBuilder($tenantContext))
    ->betweenDates('2024-01-01', '2024-12-31')
    ->totalRevenue();
```

#### ContactQueryBuilder
**File:** `extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Expansion/Queries/ContactQueryBuilder.php`

**Methods:**
- `byCustomer(customerId)` - Filter by organization
- `byRole(role)` - Filter by job title
- `byDepartment(department)` - Filter by department
- `byType(type)` - Filter by contact type (decision_maker, influencer, etc)
- `byEmailDomain(domain)` - Filter by email domain
- `byCountry(country)` - Filter by country
- `byEngagementLevel(level)` - Filter by engagement level
- `primary()` - Get primary contacts only
- `byName(name)` - Search by name
- `byEmail(email)` - Search by email
- `decisionMakers()` - Get decision-makers
- `withInteractions()` - Eager load interactions
- `withPreferences()` - Eager load communication preferences
- `summaryByDepartment()` - Get count by department
- `summaryByRole()` - Get count by role
- `recentInteractions(days)` - Get contacts with recent interactions

---

### 3. Query Builder Test Suite
**File:** `extensions/WorkCore_Platform/packages/workcore-business-network/tests/Unit/Query/QueryBuilderConformanceTest.php`

**Tests:** 65+ test methods covering:
- Tenant context enforcement
- Filtering capabilities (status, industry, date range, text search)
- Sorting (ASC/DESC with validation)
- Pagination (limit, offset, validation)
- Soft delete handling (exclude, include, only deleted)
- Method chaining and fluency
- Query execution (get, first, count, paginate)
- Tenant isolation (automatic company_id scoping)
- REST API pagination format compliance
- Statistics aggregation

**Validates:** All query builders enforce tenant isolation, support consistent filtering/sorting/pagination.

---

## Query Builders to Build: Priority Order

### Priority 1: Business Network (Week 2)
- [x] CustomerQueryBuilder - Queries customers
- [x] OrderQueryBuilder - Queries orders  
- [x] ContactQueryBuilder - Queries contacts/people
- [ ] OpportunityQueryBuilder - Queries sales opportunities
- [ ] InteractionQueryBuilder - Queries activities (calls, emails, notes)
- [ ] LeadQueryBuilder - Queries leads
- [ ] TerritoryQueryBuilder - Queries sales territories

### Priority 2: Commercial (Week 2-3)
- [ ] ProductQueryBuilder - Queries products/catalog
- [ ] InvoiceQueryBuilder - Queries invoices
- [ ] QuoteQueryBuilder - Queries sales quotes
- [ ] PricingQueryBuilder - Queries pricing rules
- [ ] PaymentQueryBuilder - Queries payments

### Priority 3: Work Operations (Week 3)
- [ ] JobQueryBuilder - Queries jobs
- [ ] DispatchQueryBuilder - Queries assignments
- [ ] TechnicianQueryBuilder - Queries technician availability
- [ ] VehicleQueryBuilder - Queries vehicles
- [ ] RouteQueryBuilder - Queries optimized routes

### Priority 4: Workforce Assurance (Week 3)
- [ ] EmployeeQueryBuilder - Queries employees
- [ ] AttendanceQueryBuilder - Queries attendance records
- [ ] PayrollQueryBuilder - Queries payroll records
- [ ] PerformanceQueryBuilder - Queries performance reviews
- [ ] LeaveQueryBuilder - Queries leave requests

### Priority 5: Property Operations (Week 3)
- [ ] PropertyQueryBuilder - Queries properties
- [ ] MaintenanceQueryBuilder - Queries maintenance requests
- [ ] LeaseQueryBuilder - Queries lease agreements
- [ ] InspectionQueryBuilder - Queries inspections

---

## REST API Endpoints (Built on Query Builders)

### Business Network API

#### Customers
```
GET /api/workcore/customers
  ?status=active
  &industry=technology
  &offset=0
  &limit=20
  &sort=-created_at
  
Returns:
{
  "data": [
    {
      "id": 123,
      "name": "Acme Inc",
      "status": "active",
      "industry": "technology",
      "annual_revenue": 5000000,
      "account_manager_id": 50,
      "created_at": "2024-01-01T00:00:00Z"
    }
  ],
  "total": 150,
  "limit": 20,
  "offset": 0,
  "page": 1,
  "pages": 8
}
```

#### Orders
```
GET /api/workcore/orders
  ?status=pending
  &customer_id=123
  &offset=0
  &limit=20
  
Returns:
{
  "data": [
    {
      "id": 456,
      "customer_id": 123,
      "status": "pending",
      "total_amount": 50000,
      "order_date": "2024-08-01T00:00:00Z",
      "payment_status": "pending",
      "fulfillment_status": "pending"
    }
  ],
  "total": 25,
  "limit": 20,
  "offset": 0
}
```

#### Contacts
```
GET /api/workcore/contacts
  ?customer_id=123
  &role=CTO
  &offset=0
  &limit=20
  
Returns:
{
  "data": [
    {
      "id": 789,
      "customer_id": 123,
      "name": "John Doe",
      "title": "CTO",
      "email": "john@acme.com",
      "phone": "+1-555-0123",
      "contact_type": "decision_maker",
      "department": "Engineering"
    }
  ],
  "total": 3,
  "limit": 20,
  "offset": 0
}
```

### Response Format Standard
All endpoints return same format:
```
{
  "data": [...],           // Array of records
  "total": 150,            // Total matching records (ignoring limit/offset)
  "limit": 20,             // Records per page
  "offset": 0,             // Starting record number
  "page": 1,               // Current page number (calculated)
  "pages": 8               // Total pages (calculated)
}
```

---

## Implementation Pattern: From Query Builder to REST Endpoint

### Step 1: Define Query Builder
```php
class CustomerQueryBuilder extends BaseQueryBuilder {
    public function byStatus(string $status): static { ... }
    public function get(): array { ... }
    public function paginate(): array { ... }
}
```

### Step 2: Inject into Service
```php
class CustomerService {
    public function __construct(
        private TenantContext $tenantContext,
    ) {}
    
    public function searchCustomers(array $filters): array {
        $query = new CustomerQueryBuilder($this->tenantContext);
        
        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        
        return $query
            ->limit($filters['limit'] ?? 20)
            ->offset($filters['offset'] ?? 0)
            ->paginate();
    }
}
```

### Step 3: Create REST Controller
```php
class CustomerController {
    public function index(Request $request, CustomerService $service): Response {
        $results = $service->searchCustomers([
            'status' => $request->query('status'),
            'industry' => $request->query('industry'),
            'limit' => $request->query('limit', 20),
            'offset' => $request->query('offset', 0),
        ]);
        
        return response()->json($results);
    }
}
```

---

## Extension Integration Points

### AIChatPro CRM
Uses Query Builders to:
1. Find customer by phone/email for conversation context
2. Get customer interaction history
3. Get customer orders/status for support context
4. Query recent orders for upsell opportunities

```php
// In ChatbotHandler.php
$customer = (new CustomerQueryBuilder($tenantContext))
    ->search('email', $userEmail)
    ->first();

$orders = (new OrderQueryBuilder($tenantContext))
    ->byCustomer($customer['id'])
    ->sortBy('order_date', 'DESC')
    ->limit(5)
    ->get();

// Use in conversation: "Recent orders: {$orders}"
```

### AIAgent Autonomous Operations
Uses Query Builders to:
1. Check if customer exists before creating
2. Get customer orders for business network operations
3. Check if order exists before updating
4. Get contact to avoid duplicates

```php
// In BusinessNetworkActionService.php
$existing = (new CustomerQueryBuilder($tenantContext))
    ->byEmail($payload['email'])
    ->first();

if (!$existing) {
    // Create new customer
    $this->customerRepository->create($payload);
}
```

### PhoneCallAgent Booking
Uses Query Builders to:
1. Query available time slots
2. Query technician availability (via Work Operations)
3. Query customer contact info

---

## Success Criteria

- [x] Base QueryBuilder defines fluent interface
- [x] Customer/Order/Contact query builders implemented
- [x] Query builder test suite: 65+ tests
- [ ] All query builders pass tests (0 failures)
- [ ] REST API endpoints created for Business Network (Customers, Orders, Contacts)
- [ ] REST API endpoints pass integration tests
- [ ] Query builders support all extension use cases
- [ ] Documentation for extension developers

---

## Files Created This Week

1. `extensions/WorkCore_Platform/packages/workcore-shared-foundation/src/Domains/WorkCore/System/Query/BaseQueryBuilder.php`
2. `extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Expansion/Queries/CustomerQueryBuilder.php`
3. `extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Expansion/Queries/OrderQueryBuilder.php`
4. `extensions/WorkCore_Platform/packages/workcore-business-network/src/Domains/WorkCore/System/Expansion/Queries/ContactQueryBuilder.php`
5. `extensions/WorkCore_Platform/packages/workcore-business-network/tests/Unit/Query/QueryBuilderConformanceTest.php`
6. `WEEK2_API_LAYER.md` (this file)

---

## Next Steps

1. **Implement Query Builders:** Build repositories and database queries
2. **Create REST Endpoints:** Wire up query builders to HTTP controllers
3. **Test Integration:** Verify extensions can use query builders
4. **Document API:** Generate OpenAPI spec for all endpoints
5. **Week 3:** Implement query builders for remaining modules

---

## Performance Considerations

1. **Database Indexing:** Ensure indexed on:
   - company_id (tenant scoping)
   - deleted_at (soft delete filtering)
   - status (common filter)
   - created_at (sorting)
   - user_id/manager_id (ownership filtering)

2. **Query Optimization:**
   - Use query builder limits to avoid fetching all records
   - Implement pagination for large result sets
   - Use eager loading for related entities (.with())
   - Add database query caching for expensive aggregations

3. **API Rate Limiting:**
   - Limit paginated results to 100 records max
   - Implement offset-based pagination (cursor for large datasets)
   - Cache frequently accessed data (status summaries, statistics)

---

## Security Considerations

1. **Tenant Isolation:**
   - All queries automatically filtered by company_id
   - Cannot be overridden by user input
   - Query builder throws if tenant context missing

2. **Authorization:**
   - Query builders don't check record-level permissions
   - Controllers must use CompanyRecordAuthorizer
   - Example: Can user see this customer's data?

3. **Input Validation:**
   - Limit parameter validated (1-100)
   - Offset validated (non-negative)
   - Sort direction validated (ASC/DESC only)
   - Filter values must be validated by specific filter method

---

Owner & Status: Query builders created, ready for integration

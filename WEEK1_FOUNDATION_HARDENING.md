# Week 1: Foundation Hardening - Conformance Test Suite & Services Layer Blueprint

## Overview
Week 1 establishes the foundation by creating comprehensive conformance test suites for all WorkCore modules and blueprinting the services layer structure. This unblocks Week 2 (API layer) and Week 3 (integration APIs).

## Completed: Conformance Test Suites

### 1. Shared Foundation Conformance Suite
**File:** `extensions/WorkCore_Platform/packages/workcore-shared-foundation/tests/Unit/Conformance/SharedFoundationConformanceTest.php`

**Tests:** 65+ test methods covering:
- 8 Gateway Contracts: PaymentGateway, MessagingGateway, FileStorageGateway, GeocodingGateway, CalendarGateway, DocumentSigningGateway, MalwareScannerGateway, WorkCoreActor
- 4 System Contracts: TenantContextContract, TenantResolverContract, PermissionResolverContract, OperationContextContract
- Tenant Context: Isolation, switching, snapshots, restoration
- Authorization Patterns: Permission resolution, record-level access
- Event Publishing: Causation tracking, versioning, backward compatibility
- Multi-tenancy: Data isolation, cross-tenant security

**Validates:** The 77 contracts in shared-foundation are properly defined and provide the foundation for all other modules.

---

### 2. Record Access Contracts Conformance Suite
**File:** `extensions/WorkCore_Platform/packages/workcore-shared-foundation/tests/Unit/RecordAccess/RecordAccessConformanceTest.php`

**Tests:** 40+ test methods covering:
- 87 Record Access Contracts: Customer, Order, Product, Invoice, Employee, Job, Property, etc.
- CRUD Authorization: Create/Read/Update/Delete permission patterns
- Tenant Isolation: Automatic query scoping, cross-tenant prevention
- Audit Trails: Tracking created_by, updated_by, timestamps
- Soft Delete Pattern: Deletion with audit trail, restoration
- Optimistic Locking: Version field for concurrent update safety
- Field-Level Permissions: Restricted admin fields, sensitive data
- Search/Filter/Pagination: Standardized query patterns
- Integration Points: Business Network, Commercial, Work Operations, Workforce, Property Ops

**Validates:** All 87 record contracts follow consistent authorization and isolation patterns.

---

### 3. Work Operations Conformance Suite
**File:** `extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Unit/Conformance/WorkOperationsConformanceTest.php`

**Tests:** 55+ test methods covering 7 sub-modules:

#### Scheduling Module (2 existing services)
- Job creation and scheduling
- Technician assignment with skill matching
- Route optimization algorithms
- SLA enforcement (response time, completion time)
- Time conflict prevention
- Mobile app integration

#### Dispatch Module (2 existing services)
- Real-time assignment creation
- GPS tracking and ETA calculations
- Job reassignment workflows
- Metric tracking (first-time fix, utilization)
- Conflict detection and fallback assignment

#### Fleet Module (0 services → needs 3-4 services)
- Vehicle management (create, assign, track)
- GPS tracking (location history, geofence, spoofing detection)
- Fuel management (consumption, cost per job, efficiency)
- Maintenance scheduling and enforcement

#### Repairs Module (0 services → needs 4-5 services)
- Intake and diagnostics workflow
- Quality control inspection
- Warranty tracking and claims
- Parts inventory integration

#### RecurringServices Module (2 existing services)
- Subscription management
- Auto-job generation
- Billing and invoice generation

#### Forms Module (2 existing services)
- Dynamic form creation
- Form submission capture
- Job form integration
- Signature capture

#### Operations Module (3 existing services)
- Workflow state management
- Permission enforcement per state
- Audit trail logging

**Current State:** 11 services exist (Scheduling:2, Forms:2, Dispatch:2, RecurringServices:2, Operations:3)
**Gap:** 15-20 additional services needed for Fleet, Repairs expansion

**Validates:** All 7 modules enforce tenant isolation, authorization, event publishing, and pagination.

---

### 4. Workforce Assurance Conformance Suite
**File:** `extensions/WorkCore_Platform/packages/workcore-workforce-assurance/tests/Unit/Conformance/WorkforceAssuranceConformanceTest.php`

**Tests:** 70+ test methods covering 6 modules:

#### Attendance Module (0 services → needs 3-4 services)
- Check-in/check-out recording
- Schedule validation (on-time, late, early departure)
- Exception detection (no-show, forgot clock-out, excessive overtime)
- Time-off integration (vacation, sick, personal)
- Overtime calculation
- Compliance reporting

#### Compliance Module (0 services → needs 3-4 services)
- Certification tracking and renewal alerts
- Credential management (licenses, training, certifications)
- Policy enforcement (background check, safety training, confidentiality)
- Audit process and reporting
- Violation recording and corrective action
- Compliance status dashboard

#### Payroll Module (0 services → needs 4-5 services)
- Pay calculation (salary, hourly, overtime premiums)
- Tax withholding (federal, state, FICA)
- Benefits administration (health plans, FSA/HSA, 401k)
- Deduction processing
- Direct deposit file generation
- Multi-state tax handling
- W-2 and 1099 generation

#### Performance Module (0 services → needs 3-4 services)
- Performance review workflow
- Rating and scoring
- Goal setting (SMART goals, OKRs, KPIs)
- 360-degree feedback collection
- High-potential identification
- Salary recommendation engine
- Trend analysis

#### Leave Management Module (0 services → needs 3-4 services)
- Leave request submission
- Manager approval workflow
- PTO accrual tracking
- Leave balance management
- Policy enforcement (notice period, coverage, blackout dates)
- Long-term leave handling
- Leave calendar and reporting

#### Employee Management Module (0 services → needs 3-4 services)
- New hire onboarding
- Role assignment and permission updates
- Department/organization structure
- Employment history tracking
- Termination workflow
- Contact information management

**Current State:** 0 services exist (complete gap)
**Need:** 19-25 services for complete HR functionality

**Validates:** All 6 modules enforce tenant isolation, authorization, data privacy, event publishing.

---

## Services Layer Blueprint

### Services Naming Convention
All services follow Laravel naming convention:
```
App\Domains\WorkCore\System\Modules\{ModuleName}\Services\{ServiceName}Service
```

### Service Interface Pattern
```php
interface {ServiceName}ServiceInterface
{
    public function {action}({params}): {result};
}

class {ServiceName}Service implements {ServiceName}ServiceInterface
{
    public function __construct(
        private TenantContext $tenantContext,
        private CompanyRecordAuthorizer $authorizer,
        private EventPublisher $eventPublisher,
        private {EntityRepository} $repository,
    ) {}

    public function {action}({params}): {result}
    {
        // 1. Validate tenant context
        if (!$this->tenantContext->hasTenant()) {
            throw new TenantNotResolved();
        }

        // 2. Check authorization
        $this->authorizer->authorize('action', $resource);

        // 3. Execute business logic
        $result = $this->repository->create(...);

        // 4. Publish domain event
        $this->eventPublisher->publish(
            new DomainEvent(
                aggregateId: $result->id,
                eventType: 'ActionCompleted',
                payload: [...],
            )
        );

        return $result;
    }
}
```

### Cross-Cutting Concerns (All Services)
1. **Tenant Isolation:** Inject TenantContext, validate on entry, scope queries
2. **Authorization:** Inject CompanyRecordAuthorizer, check permission before action
3. **Event Publishing:** Publish domain events for audit trail, async subscribers
4. **Error Handling:** Custom exceptions, proper HTTP status codes
5. **Audit Trail:** Automatic created_by, created_at, updated_by, updated_at
6. **Validation:** Business rule validation before state changes
7. **Transactions:** Database transactions for multi-step operations
8. **Logging:** Structured logging with context (tenant, user, operation)

---

## Services to Build: Priority Order

### Priority 1: Shared Foundation (Week 1)
- [x] Test suite created (65+ tests)
- [ ] Run tests to identify gaps
- [ ] Fix any contract definition gaps

### Priority 2: Work Operations (Week 1-2)
**Fleet Module Services (4 new):**
- [ ] VehicleService (create, list, update, delete vehicles)
- [ ] GPSTrackingService (record location, calculate ETA, geofence)
- [ ] FuelManagementService (track consumption, alert low fuel)
- [ ] MaintenanceService (schedule, enforce, track maintenance)

**Repairs Module Services (4 new):**
- [ ] RepairService (intake, diagnostics, repair execution)
- [ ] QualityControlService (inspection, sign-off, validation)
- [ ] WarrantyService (coverage, claims, processing)
- [ ] PartsService (inventory, ordering, tracking)

**Dispatch Module Expansion (1-2 additional):**
- [ ] RealTimeUpdateService (WebSocket/polling for live dispatch board)
- [ ] NotificationService (push notifications, SMS alerts)

### Priority 3: Workforce Assurance (Week 2)
**Attendance Module (4 services):**
- [ ] AttendanceService (checkin, checkout, validation)
- [ ] ScheduleService (shifts, coverage, conflicts)
- [ ] TimeTrackingService (overtime, exceptions, analytics)
- [ ] AttendanceReportService (compliance, analytics)

**Compliance Module (4 services):**
- [ ] ComplianceService (audit, violations, remediation)
- [ ] CertificationService (tracking, renewal, expiration)
- [ ] CredentialService (credentials, validation, storage)
- [ ] AuditReportService (reporting, dashboards)

**Payroll Module (5 services):**
- [ ] PayrollService (calculate pay, generate stubs)
- [ ] DeductionService (taxes, 401k, HSA, garnishments)
- [ ] BenefitService (health plans, FSA/HSA, 401k)
- [ ] DirectDepositService (batch creation, payment processing)
- [ ] TaxFormService (W-2, 1099, filing)

**Performance Module (4 services):**
- [ ] PerformanceService (reviews, ratings, scoring)
- [ ] GoalService (SMART goals, OKRs, tracking)
- [ ] FeedbackService (360 reviews, anonymous collection)
- [ ] AnalyticsService (KPIs, trends, recommendations)

**Leave Module (4 services):**
- [ ] LeaveService (request, approval, tracking)
- [ ] AccrualService (accrual calculation, carryover)
- [ ] PolicyService (policies, entitlements, enforcement)
- [ ] LeaveReportService (balance, analytics, trends)

**Employee Module (4 services):**
- [ ] EmployeeService (hire, onboard, terminate)
- [ ] RoleService (assignment, permissions, changes)
- [ ] DepartmentService (organization structure)
- [ ] ContactInfoService (personal info, emergency contacts)

---

## Testing Strategy

### Unit Tests
- Service methods with mocked dependencies
- Happy path and error cases
- Authorization checks
- Tenant isolation validation

### Integration Tests
- Service interaction with repositories
- Event publishing verification
- Database transaction handling
- Concurrent update handling

### Conformance Tests
- Run existing test suite (./vendor/bin/phpunit tests/Unit/Conformance)
- Verify all modules follow pattern
- Ensure consistency across all services

---

## Success Criteria

- [x] Shared Foundation test suite: 65+ tests, validates 77 contracts
- [x] Record Access test suite: 40+ tests, validates 87 contracts
- [x] Work Operations test suite: 55+ tests, validates 7 modules
- [x] Workforce Assurance test suite: 70+ tests, validates 6 modules
- [ ] All test suites run successfully (0 failures)
- [ ] Services layer blueprint documented
- [ ] Priority service list established
- [ ] Service interface patterns defined

---

## Next Steps

1. **Run Test Suites:** Verify all conformance tests pass
2. **Identify Gaps:** Document any missing contracts or patterns
3. **Week 2 Planning:** Begin implementing Priority 2 services (Fleet, Repairs)
4. **Documentation:** Add service docs to README
5. **Integration Points:** Map extension dependencies on services

---

## Files Created This Week

1. `extensions/WorkCore_Platform/packages/workcore-shared-foundation/tests/Unit/Conformance/SharedFoundationConformanceTest.php` (65+ tests)
2. `extensions/WorkCore_Platform/packages/workcore-shared-foundation/tests/Unit/RecordAccess/RecordAccessConformanceTest.php` (40+ tests)
3. `extensions/WorkCore_Platform/packages/workcore-work-operations/tests/Unit/Conformance/WorkOperationsConformanceTest.php` (55+ tests)
4. `extensions/WorkCore_Platform/packages/workcore-workforce-assurance/tests/Unit/Conformance/WorkforceAssuranceConformanceTest.php` (70+ tests)
5. `WEEK1_FOUNDATION_HARDENING.md` (this file)

**Total:** 230+ conformance tests validating foundation architecture

---

## Module Statistics

| Module | Contracts | Existing Services | Needed Services | Gap |
|--------|-----------|------------------|-----------------|-----|
| Shared Foundation | 77 | - | - | ✅ Contracts defined |
| Work Operations | - | 11 | 15-20 | Fleet (4), Repairs (4), Dispatch (+2) |
| Workforce Assurance | - | 0 | 19-25 | All 6 modules need services |
| **TOTAL** | **77** | **11** | **34-45** | **Critical gap** |

---

## Risk Mitigation

**Risk:** Without services layer, extensions can't query WorkCore
**Mitigation:** Week 2 implements Query APIs before CRUD services

**Risk:** Workforce module affects all HR workflows
**Mitigation:** Priority building Attendance (blocking) → Payroll (revenue-impacting) → Performance/Leave (compliance)

**Risk:** Dispatch board real-time updates need WebSocket support
**Mitigation:** Use polling first (simple), WebSocket as phase 2

**Risk:** Payroll tax calculations differ by state/locality
**Mitigation:** Use third-party tax library (Avalara TaxJar) or build modular per-state implementations

---

## Owner & Status
- Status: Test suites created, ready for Week 2 service implementation
- Next: Begin service implementation (Fleet, Repairs)

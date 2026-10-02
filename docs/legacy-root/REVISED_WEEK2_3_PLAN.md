# Revised Week 2-3 Plan: Full WorkCore Suite Extensions
## Complete Query Builders & Services for AIChatPro & AIAgent

---

## Overview: Revised Scope

**Previous Plan (Limited):**
- Week 2: 3 query builders (Business Network only)
- Week 3: 19-25 services (Workforce + Work Ops gaps)

**Revised Plan (Full Platform):**
- Week 2: 15 query builders (ALL modules)
- Week 3: 23 services (ALL CRUD operations)
- Week 4: Integration APIs (AIChatPro + AIAgent)

**Rationale:** Extensions need complete WorkCore access for real-world scenarios (multi-domain workflows, complex decision-making, autonomous operations across modules).

---

## Week 2: Complete Query Builder Infrastructure

### Query Builders by Module (15 Total)

#### Business Network Module (3 builders)
1. **CustomerQueryBuilder**
   - Filter: status, industry, type, revenue, territory, account_manager, segment, dates
   - Search: name, email
   - Tags: byTag()
   - Aggregates: getStatistics()
   - Usage: AIChatPro finding customers, AIAgent checking existence

2. **ContactQueryBuilder**
   - Filter: customer, role, department, type, email_domain, country, engagement
   - Special: primary(), decisionMakers()
   - Search: name, email
   - Aggregates: summaryByDepartment(), summaryByRole()
   - Usage: AIChatPro getting contact info, AIAgent finding right person to contact

3. **OrderQueryBuilder** (Business Network orders/opportunities)
   - Filter: customer, status, date_range, value_range
   - Aggregates: summaryByStatus()
   - Usage: AIChatPro showing customer order history, AIAgent checking for duplicates

#### Commercial Module (3 builders)
4. **ProductQueryBuilder**
   - Filter: category, in_stock, price_range, supplier, sku
   - Search: name, description
   - Aggregates: inventory_summary, pricing
   - Usage: AIChatPro checking availability, AIAgent validating before order creation

5. **InvoiceQueryBuilder**
   - Filter: customer, status, date_range, amount_range, payment_status
   - Special: overdue(), unpaid()
   - Aggregates: totalOutstanding(), summaryByStatus()
   - Usage: AIChatPro showing billing status, AIAgent processing payments

6. **PaymentQueryBuilder**
   - Filter: invoice, customer, date_range, method, status
   - Aggregates: totalCollected(), successRate()
   - Usage: AIChatPro payment history, AIAgent processing transactions

#### Work Operations Module (3 builders)
7. **JobQueryBuilder**
   - Filter: customer, type, status, date_range, technician, priority
   - Special: overdue(), needsDispatch()
   - Aggregates: summaryByStatus(), summaryByPriority()
   - Usage: AIChatPro showing job status, AIAgent finding unassigned jobs

8. **DispatchQueryBuilder**
   - Filter: job, technician, status, date_range, territory
   - Special: unassigned(), completed()
   - Aggregates: utilizationByTechnician(), SLACompliance()
   - Usage: AIChatPro showing ETA, AIAgent assigning work

9. **TechnicianQueryBuilder**
   - Filter: territory, skills, availability, status
   - Special: available(), qualified()
   - Aggregates: workloadSummary(), skillsInventory()
   - Usage: AIChatPro finding tech availability, AIAgent assigning jobs

#### Workforce Module (4 builders)
10. **EmployeeQueryBuilder**
    - Filter: department, role, status, hire_date, manager
    - Special: active(), compliant()
    - Search: name, email
    - Aggregates: summaryByDepartment(), summaryByRole()
    - Usage: AIChatPro employee info, AIAgent assigning roles/leave

11. **AttendanceQueryBuilder**
    - Filter: employee, date_range, status, type
    - Special: late(), absent(), overtime()
    - Aggregates: attendanceSummary(), overtimeSummary()
    - Usage: AIChatPro showing schedule, AIAgent recording attendance

12. **CertificationQueryBuilder**
    - Filter: employee, type, status, expiration_date
    - Special: expiring(), expired()
    - Aggregates: complianceSummary(), expiringAlert()
    - Usage: AIChatPro compliance check, AIAgent verifying qualifications

13. **PerformanceQueryBuilder**
    - Filter: employee, review_date, rating, manager
    - Aggregates: departmentAverages(), KPISummary()
    - Usage: AIChatPro showing performance, AIAgent making decisions

#### Property Operations Module (2 builders)
14. **PropertyQueryBuilder**
    - Filter: owner, tenant, status, location, type
    - Special: vacant(), needsMaintenance()
    - Search: address, unit_number
    - Aggregates: occupancySummary(), maintenanceSummary()
    - Usage: AIChatPro tenant info, AIAgent scheduling maintenance

15. **MaintenanceQueryBuilder**
    - Filter: property, status, type, priority, date_range, technician
    - Special: pending(), overdue(), completed()
    - Aggregates: summaryByStatus(), workloadByTechnician()
    - Usage: AIChatPro showing maintenance status, AIAgent dispatching work

### Implementation Priority

**Phase 1 (First 3 days):**
- Business Network: CustomerQueryBuilder, ContactQueryBuilder, OrderQueryBuilder
- Commercial: ProductQueryBuilder
- Work Operations: JobQueryBuilder
- Workforce: EmployeeQueryBuilder
- Property Operations: PropertyQueryBuilder

**Phase 2 (Days 4-7):**
- Commercial: InvoiceQueryBuilder, PaymentQueryBuilder
- Work Operations: DispatchQueryBuilder, TechnicianQueryBuilder
- Workforce: AttendanceQueryBuilder, CertificationQueryBuilder, PerformanceQueryBuilder
- Property Operations: MaintenanceQueryBuilder

### Test Suite (120+ tests)
- Tenant isolation: 15+ tests
- Filter capabilities: 40+ tests
- Sorting/pagination: 20+ tests
- Soft delete handling: 10+ tests
- Aggregation functions: 20+ tests
- Extension integration: 15+ tests

### Deliverables
- 15 query builder files (~50 lines each = 750 lines)
- 1 comprehensive test suite (120+ tests)
- API documentation for each builder
- Extension integration examples

---

## Week 3: Complete Services Layer

### Services by Module (23 Total)

#### Business Network Module (3 services)
1. **CustomerService**
   - create(data): Create customer, publish event, validate tenant
   - update(id, data): Update customer, version check, publish event
   - delete(id): Soft delete, audit trail
   - search(filters): Wrapper around CustomerQueryBuilder

2. **ContactService**
   - create(data): Create contact, verify customer exists
   - update(id, data): Update contact
   - delete(id): Soft delete with audit
   - search(filters): Wrapper around ContactQueryBuilder

3. **OrderService**
   - create(data): Create order, validate customer/products exist
   - update(id, data): Update order, status transitions
   - cancel(id): Cancel order with refund
   - search(filters): Wrapper around OrderQueryBuilder

#### Commercial Module (5 services)
4. **ProductService**
   - create(data): Create product, validate SKU unique
   - update(id, data): Update product, inventory
   - getAvailability(id): Check stock
   - search(filters): Wrapper around ProductQueryBuilder

5. **OrderService** (Commercial, different from BN)
   - create(data): Create commercial order
   - updateStatus(id, status): Order lifecycle
   - confirm(id): Order confirmation
   - search(filters): Wrapper around OrderQueryBuilder

6. **InvoiceService**
   - create(data): Create invoice from order
   - send(id): Send to customer
   - recordPayment(id, amount): Record payment
   - search(filters): Wrapper around InvoiceQueryBuilder

7. **PaymentService**
   - process(invoice_id, method, amount): Process payment
   - refund(id, amount): Issue refund
   - recordFailure(id): Log payment failure
   - search(filters): Wrapper around PaymentQueryBuilder

8. **PricingService**
   - applyDiscount(order_id, discount): Apply discount
   - checkPricing(product_id, quantity): Calculate price
   - applyPromotion(code): Apply promotional code

#### Work Operations Module (5 services)
9. **JobService**
   - create(data): Create job, validate customer/type
   - update(id, data): Update job details
   - updateStatus(id, status): Job lifecycle (new→assigned→in_progress→completed)
   - cancel(id): Cancel job with reason
   - search(filters): Wrapper around JobQueryBuilder

10. **DispatchService**
    - assign(job_id, technician_id): Assign technician
    - reassign(job_id, new_technician_id): Reassign with reason
    - updateStatus(id, status): Dispatch status changes
    - calculateETA(id): Calculate estimated arrival
    - search(filters): Wrapper around DispatchQueryBuilder

11. **TechnicianService**
    - getAvailability(technician_id, date): Check availability
    - checkSkills(technician_id, required_skills): Verify qualifications
    - getWorkload(technician_id): Current jobs and schedule
    - search(filters): Wrapper around TechnicianQueryBuilder

12. **RouteService**
    - optimize(jobs, technician, date): Optimize route
    - calculateDistance(from, to): Calculate travel distance
    - estimateTravelTime(from, to): ETA between locations

13. **FleetService**
    - createVehicle(data): Register vehicle
    - updateStatus(id, status): Vehicle status
    - trackGPS(vehicle_id, location): Record location
    - recordMaintenance(id, maintenance): Log maintenance

#### Workforce Module (6 services)
14. **EmployeeService**
    - create(data): Hire employee, create record
    - update(id, data): Update employee info
    - assignRole(id, role): Assign role, update permissions
    - terminate(id): Terminate employment
    - search(filters): Wrapper around EmployeeQueryBuilder

15. **AttendanceService**
    - checkIn(employee_id, timestamp, location): Record check-in
    - checkOut(employee_id, timestamp): Record check-out
    - recordException(employee_id, type): No-show, late, left early
    - getSchedule(employee_id, date): Get shift schedule
    - search(filters): Wrapper around AttendanceQueryBuilder

16. **PayrollService**
    - calculatePay(employee_id, period): Calculate paycheck
    - processPayroll(period): Process all employees
    - generatePaystub(employee_id, period): Create pay stub
    - reportPayroll(): Payroll summary and compliance

17. **BenefitService**
    - enrollEmployee(employee_id, plan): Enroll in health plan
    - processFSA(employee_id, amount): FSA transactions
    - manage401K(employee_id, contribution): 401K management

18. **CertificationService**
    - recordCertification(employee_id, cert_data): Add certification
    - verifyCertification(employee_id, cert_type): Check if current
    - alertExpiring(employee_id, days): Expiration notifications
    - search(filters): Wrapper around CertificationQueryBuilder

19. **PerformanceService**
    - createReview(employee_id, review_data): Conduct review
    - recordGoal(employee_id, goal_data): Set SMART goal
    - trackKPI(employee_id, kpi, value): Track metric
    - search(filters): Wrapper around PerformanceQueryBuilder

#### Property Operations Module (4 services)
20. **PropertyService**
    - create(data): Create property record
    - update(id, data): Update property info
    - assignTenant(id, tenant_id): Assign tenant
    - updateStatus(id, status): Occupancy status
    - search(filters): Wrapper around PropertyQueryBuilder

21. **MaintenanceService**
    - create(data): Create maintenance request
    - updateStatus(id, status): Maintenance lifecycle
    - assign(id, technician_id): Assign technician
    - scheduleWork(id, date_time): Schedule maintenance
    - search(filters): Wrapper around MaintenanceQueryBuilder

22. **LeaseService**
    - create(data): Create lease agreement
    - renew(id): Renew lease
    - terminate(id): Terminate lease
    - recordPayment(id, rent_amount): Record rent payment

23. **InspectionService**
    - create(data): Schedule inspection
    - record(id, findings): Record inspection results
    - assignInspector(id, inspector_id): Assign inspector
    - generateReport(id): Create inspection report

### Implementation Priority

**Phase 1 (First 3 days) - Critical Path:**
- CustomerService, OrderService (Business Network)
- ProductService, InvoiceService (Commercial)
- JobService, DispatchService (Work Operations)
- EmployeeService, AttendanceService (Workforce)
- PropertyService, MaintenanceService (Property Ops)

**Phase 2 (Days 4-5) - Secondary:**
- ContactService (Business Network)
- PaymentService, PricingService (Commercial)
- TechnicianService, RouteService, FleetService (Work Ops)
- PayrollService, BenefitService, CertificationService, PerformanceService (Workforce)
- LeaseService, InspectionService (Property Ops)

### Test Suite (220+ tests)
- Authorization checks: 40+ tests
- Tenant isolation: 30+ tests
- Business logic validation: 80+ tests
- Event publishing: 30+ tests
- Integration with query builders: 20+ tests
- Error handling: 20+ tests

### Deliverables
- 23 service files (~100 lines each = 2,300 lines)
- 1 comprehensive test suite (220+ tests)
- Service interface documentation
- Integration patterns for extensions

---

## Week 4: Integration APIs & Extension Testing

### AIChatPro Integration API
**Purpose:** Enable chatbot to query any WorkCore data

**Endpoints:**
- `GET /api/aichatpro/customer-context/{id}`
  - Returns: customer + orders + interactions + invoices + assigned jobs + open tickets
  
- `GET /api/aichatpro/employee-context/{id}`
  - Returns: employee + schedule + certifications + performance + assigned jobs + attendances
  
- `GET /api/aichatpro/property-context/{id}`
  - Returns: property + tenant info + leases + maintenance requests + open inspections

- `GET /api/aichatpro/search`
  - Query param: q (search string)
  - Returns: matched customers + employees + properties + products

- `POST /api/aichatpro/log-interaction`
  - Log conversation: customer_id, type, summary, timestamp

### AIAgent Integration API
**Purpose:** Enable autonomous operations across all modules

**Endpoints:**
- `POST /api/aiagent/execute`
  - Body: {action: "create_customer", params: {...}}
  - Returns: created_id, status, events_published

- `GET /api/aiagent/capabilities`
  - Returns: list of executable actions by module

- `GET /api/aiagent/query`
  - Query param: type (customer/order/job/employee/etc), filters
  - Returns: matching records for validation before operation

- `POST /api/aiagent/orchestrate`
  - Complex multi-step workflow
  - Example: Create customer → Create order → Create job → Assign technician

### Event Publishing (50+ Events)
**Business Network:**
- CustomerCreated, CustomerUpdated, CustomerDeleted
- ContactCreated, ContactUpdated, ContactDeleted
- OrderCreated, OrderUpdated, OrderCancelled

**Commercial:**
- ProductCreated, ProductInventoryChanged
- OrderConfirmed, OrderShipped, OrderDelivered
- InvoiceGenerated, PaymentProcessed, PaymentFailed

**Work Operations:**
- JobCreated, JobAssigned, JobStatusChanged, JobCompleted
- DispatchAssigned, DispatchReassigned, DispatchCompleted
- TechnicianAvailabilityChanged
- RouteOptimized

**Workforce:**
- EmployeeOnboarded, EmployeeTerminated, RoleAssigned
- AttendanceRecorded, LateArrival, AbsenceRecorded
- OverTimeRecorded
- CertificationExpiring, CertificationUpdated
- PayrollProcessed, BenefitEnrolled

**Property Operations:**
- PropertyCreated, TenantAssigned, TenantRemoved
- MaintenanceRequested, MaintenanceAssigned, MaintenanceCompleted
- LeaseCreated, LeaseRenewed, LeaseTerminated
- InspectionScheduled, InspectionCompleted

### Async Subscribers
- **Audit Trail:** Log all events with context (tenant, user, timestamp)
- **Search Index:** Update search indices for dashboard/search
- **Notifications:** Send messages to relevant parties
- **Analytics:** Aggregate metrics for KPI dashboards
- **Third-party:** Webhook integrations

### Test Suite (150+ tests)
- API endpoint tests: 60+ tests
- Event publishing verification: 30+ tests
- Extension integration: 40+ tests
- Performance tests: 20+ tests

---

## Summary: Files & Effort

### Week 2: Query Builders
- **Files:** 15 query builder classes + 1 test suite + 1 documentation
- **Lines:** ~800 LOC (builders) + 1,200 LOC (tests)
- **Time:** 5 days

### Week 3: Services
- **Files:** 23 service classes + 1 test suite + 1 documentation
- **Lines:** ~2,300 LOC (services) + 2,200 LOC (tests)
- **Time:** 5 days

### Week 4: APIs & Extension Testing
- **Files:** 2 integration API classes + 1 event system + 1 async processors + tests
- **Lines:** ~500 LOC (APIs) + ~1,000 LOC (events) + ~1,500 LOC (tests)
- **Time:** 5 days

### Total Week 2-4
- **450+ PHP classes & interfaces**
- **7,000+ lines of production code**
- **4,900+ lines of test code**
- **100+ conformance tests (Week 1) + 120+ (Week 2) + 220+ (Week 3) + 150+ (Week 4) = 590+ total tests**
- **15 weeks effort** (3 developers, 2 weeks each + QA overlap)

---

## Success Criteria

### Week 2 Complete
- [ ] 15 query builders working
- [ ] 120+ tests passing
- [ ] Extensions can query any WorkCore data
- [ ] REST API endpoints returning correct format

### Week 3 Complete
- [ ] 23 services implemented
- [ ] 220+ tests passing
- [ ] All CRUD operations working
- [ ] Event publishing for all operations
- [ ] Services properly scoped to tenant

### Week 4 Complete
- [ ] AIChatPro integration API working end-to-end
- [ ] AIAgent integration API working end-to-end
- [ ] 50+ events defined and publishing
- [ ] Async subscribers processing events
- [ ] Performance targets met (<500ms response)
- [ ] Full test coverage (590+ tests passing)

---

## Outcome

**By end of Week 4:**
- AIChatPro: Full WorkCore Knowledge Assistant
  - Query any domain data in real-time
  - Provide 360° customer/employee/property context
  - Support complex multi-domain conversations

- AIAgent: Full WorkCore Autonomous Operations Agent
  - Execute actions in any domain
  - Create/update records autonomously
  - Orchestrate multi-domain workflows
  - Complete audit trail via events

- WorkCore Platform: Fully operational with:
  - 15 query builders for data retrieval
  - 23 services for business logic
  - REST API for all operations
  - Event-driven architecture
  - 590+ conformance tests

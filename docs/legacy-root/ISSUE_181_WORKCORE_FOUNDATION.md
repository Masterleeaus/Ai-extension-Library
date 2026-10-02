# Issue #181: WorkCore Foundation - Implement TenantContext & Authorization Policies

## Overview
Implement WorkCore-specific TenantContext and Authorization Policies to enable multi-tenant business operations platform with role-based access control and vertical-specific permissions.

## Requirements

### WorkCore TenantContext
1. **Tenant Hierarchy**
   - Parent organization
   - Sub-organizations (branches)
   - Tenant isolation at all levels

2. **Roles & Permissions**
   - Admin (all operations)
   - Manager (team operations, read reporting)
   - User (assigned actions only)
   - Guest (read-only)
   - Custom roles

### WorkCore Authorization Policies
1. **Business Action Authorization**
   - Per-action permissions (e.g., invoice.create)
   - Role-based access
   - Approval requirements
   - Audit trail

2. **Vertical-Specific Permissions**
   - Health: patient records access
   - E-commerce: order access
   - Real Estate: property access
   - Field Services: job access

## Testing Requirements
- Unit tests for context propagation
- Tests for role-based access
- Tests for vertical permissions
- Integration tests

## Timeline
- **WorkCore Foundation**
- Start after: Phase 2 (#70, #71)
- Estimated effort: 2 days

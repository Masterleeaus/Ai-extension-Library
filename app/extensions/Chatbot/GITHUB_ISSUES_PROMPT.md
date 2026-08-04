# GitHub Issues - Chatbot

Issues to address for Chatbot extension.

## Issue #198: [Integration] WorkCoreWorkforceAssurance → Chatbot HR Assistant

**URL**: https://github.com/masterleeaus/ai-extensions/issues/198
**State**: OPEN
**Labels**: Chatbot, workcore, integration, hr

## Summary
Integrate WorkCoreWorkforceAssurance with Chatbot PWA to enable HR queries, attendance tracking, and compliance monitoring in conversational context.

## Scope
- **Source**: WorkCoreWorkforceAssurance (Issue #186)
- **Target**: Chatbot (PWA AI)
- **Integration Type**: Workforce and HR operations

## Features to Enable
- Staff roster queries in chat
- Attendance recording via chatbot
- Shift swap requests and approvals
- Compliance status visibility
- Credential verification queries
- HR policy access
- Leave request submission

## Acceptance Criteria
- [ ] Chatbot can query staff roster
- [ ] Attendance recordable via chat
- [ ] Shift requests processable
- [ ] Compliance information accessible
- [ ] Credentials verifiable in chat
- [ ] Leave requests submittable
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #186: WorkCoreWorkforceAssurance
- Issue #181: WorkCore Shared Foundation (dependency)

---

## Issue #197: [Integration] WorkCorePropertyOperations → Chatbot Property Assistant

**URL**: https://github.com/masterleeaus/ai-extensions/issues/197
**State**: OPEN
**Labels**: Chatbot, workcore, assets, integration

## Summary
Integrate WorkCorePropertyOperations with Chatbot PWA to enable property queries, asset information, and document access in conversational context.

## Scope
- **Source**: WorkCorePropertyOperations (Issue #185)
- **Target**: Chatbot (PWA AI)
- **Integration Type**: Property and asset information

## Features to Enable
- Property information queries in chat
- Asset details and maintenance status
- Document retrieval and preview in chat
- Maintenance request submission
- Vertical operation profile queries
- Property media access

## Acceptance Criteria
- [ ] Chatbot can query property data
- [ ] Asset information displayable in chat
- [ ] Documents accessible from chatbot
- [ ] Maintenance requests can be submitted
- [ ] Property media previews working
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #185: WorkCorePropertyOperations
- Issue #181: WorkCore Shared Foundation (dependency)
- Issue #83: TitanDocs (document integration)

---

## Issue #196: [Integration] WorkCoreWorkOperations → Chatbot Job & Dispatch Assistant

**URL**: https://github.com/masterleeaus/ai-extensions/issues/196
**State**: OPEN
**Labels**: Chatbot, workcore, operations, integration

## Summary
Integrate WorkCoreWorkOperations scheduling and dispatch with Chatbot PWA to enable job booking, status tracking, and dispatch visibility in conversational context.

## Scope
- **Source**: WorkCoreWorkOperations (Issue #184)
- **Target**: Chatbot (PWA AI)
- **Integration Type**: Job scheduling and dispatch

## Features to Enable
- Job booking via chatbot
- Job status tracking in conversation
- Dispatch updates and notifications
- Fleet location queries
- Recurring service scheduling
- Forms submission via chat
- Repair status tracking

## Acceptance Criteria
- [ ] Chatbot can book jobs
- [ ] Job status queryable in chat
- [ ] Dispatch updates received in conversation
- [ ] Fleet locations accessible
- [ ] Forms completable via chatbot
- [ ] Repairs trackable
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #184: WorkCoreWorkOperations
- Issue #181: WorkCore Shared Foundation (dependency)
- Issue #61: Connector Runtime (maps integration)

---

## Issue #195: [Integration] WorkCoreCommercial → Chatbot Commerce Operations

**URL**: https://github.com/masterleeaus/ai-extensions/issues/195
**State**: OPEN
**Labels**: Chatbot, workcore, integration, commerce

## Summary
Integrate WorkCoreCommercial financial and inventory operations with Chatbot PWA to enable commerce transactions, inventory queries, and pricing information.

## Scope
- **Source**: WorkCoreCommercial (Issue #183)
- **Target**: Chatbot (PWA AI)
- **Integration Type**: Commerce and financial operations

## Features to Enable
- Inventory level queries in chat
- Pricing and availability information
- Order processing and status tracking
- Payment processing via chatbot
- Procurement status visibility
- Financial transaction history

## Acceptance Criteria
- [ ] Chatbot can query inventory
- [ ] Pricing information available in conversation
- [ ] Orders can be placed via chatbot
- [ ] Order status trackable in chat
- [ ] Payment operations secured
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #183: WorkCoreCommercial
- Issue #181: WorkCore Shared Foundation (dependency)
- Issue #145: Credential Vault References

---

## Issue #194: [Integration] WorkCoreBusinessNetwork → Chatbot CRM Assistant

**URL**: https://github.com/masterleeaus/ai-extensions/issues/194
**State**: OPEN
**Labels**: Chatbot, crm, workcore, integration

## Summary
Integrate WorkCoreBusinessNetwork with Chatbot PWA to enable customer lookup, CRM insights, and catalogue information within conversational interactions.

## Scope
- **Source**: WorkCoreBusinessNetwork (Issue #182)
- **Target**: Chatbot (PWA AI)
- **Integration Type**: CRM and business data

## Features to Enable
- Customer profile lookup in conversations
- CRM data retrieval during chat
- Catalogue search and product info
- Knowledge base in chatbot context
- Review and feedback access
- Territory-specific recommendations

## Acceptance Criteria
- [ ] Chatbot can query customer data
- [ ] Catalogue information available in conversation
- [ ] Knowledge base searchable via chatbot
- [ ] Customer profiles display in chat
- [ ] Recommendations territory-aware
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #182: WorkCoreBusinessNetwork
- Issue #181: WorkCore Shared Foundation (dependency)

---

## Issue #193: [Integration] WorkCore Shared Foundation → Chatbot PWA

**URL**: https://github.com/masterleeaus/ai-extensions/issues/193
**State**: OPEN
**Labels**: Chatbot, foundation, workcore, integration

## Summary
Integrate WorkCore Shared Foundation with Chatbot PWA to enable enterprise tenancy, permissions, and governed actions within the progressive web app.

## Scope
- **Source**: WorkCore Shared Foundation (Issue #181)
- **Target**: Chatbot (PWA AI)
- **Integration Type**: Foundation layer integration

## Requirements
- [ ] TenantContext integration with Chatbot PWA
- [ ] Authorization policies applied to chatbot operations
- [ ] Governed actions for chatbot-specific operations
- [ ] Credential vault for chatbot secrets
- [ ] EventEnvelope for idempotent messaging
- [ ] Audit trails for conversational interactions

## Acceptance Criteria
- [ ] Chatbot PWA runs under TenantContext
- [ ] Authorization policies enforced in conversations
- [ ] Governed actions audit logging working
- [ ] Chatbot respects permission boundaries
- [ ] PWA offline capabilities preserved
- [ ] Tests passing
- [ ] Documentation updated

## Related Issues
- Issue #181: WorkCore Shared Foundation
- Issue #143: TenantContext & Authorization Policies
- Issue #144: EventEnvelope & Idempotent Event Consumers
- Issue #145: Credential Vault References

---


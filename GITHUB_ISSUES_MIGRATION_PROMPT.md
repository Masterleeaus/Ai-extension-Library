# GitHub Migration & Hardening Issues

System-wide issues affecting multiple extensions.

## Issue #70: [URGENT] [Phase 2] Migrate Chatbot Tier-3 and AIAgent operational actions to WorkCore gateways

**URL**: https://github.com/masterleeaus/ai-extensions/issues/70
**State**: OPEN

Parent: #6
Depends on: #24, #28, #44, #49, #54, #67

## Problem

Issue #54 defines WorkCore as the sole operational write authority and establishes governed gateway contracts. This issue tracks migration of concrete operational actions—Chatbot Tier-3 agents, AIAgent tools and compatibility bridges—onto those gateways without preserving direct operational model writes.

## Scope

- Inventory operational reads/writes across Chatbot Tier-3, AIAgent actions and bridge extensions.
- Map each action to a typed WorkCore read/action contract or classify it as non-operational.
- Replace direct model writes with governed gateway adapters.
- Preserve existing tool/action names and product behavior where compatible.
- Add proposal, approval, commit, conflict, receipt and rollback mappings.
- Propagate offline-sync updates after accepted WorkCore changes.
- Add deprecation telemetry for bypass paths and architecture rules preventing new bypasses.

## Acceptance criteria

- Every inventoried operational action has a declared WorkCore contract or explicit non-operational classification.
- Chatbot and AIAgent produce equivalent policy outcomes and receipts for the same WorkCore action.
- Duplicate idempotency keys cannot create duplicate operational records.
- Direct operational writes from migrated actions fail architecture tests.
- Cross-tenant and wrong-actor requests fail before loading WorkCore records.
- Existing action/tool surfaces remain compatible through adapters.
- Conflict, failure and rollback paths are tested end to end.

---

## Issue #67: [URGENT] [Phase 2] Harden the AIAgent Workflow Engine for durable production execution

**URL**: https://github.com/masterleeaus/ai-extensions/issues/67
**State**: OPEN

Parent: #6
Depends on: #8, #24, #28, #31, #44, #49, #51

## Problem

AIAgent is the correct authority for schedules, delayed work, triggers, branching and nested workflows, but it has no bundled automated test tree and lacks a complete shared policy for retries, timeouts, budgets, idempotency, action permissions and failure recovery.

## Scope

Harden AIAgent as the durable workflow authority and make Chatbot Tier-3 delegation use it for delayed or long-running work.

## Requirements

- Versioned workflow definitions and immutable run snapshots.
- Tenant-safe trigger, run, action and delayed-job records.
- Idempotent trigger and step execution.
- Retry classification with bounded backoff and maximum attempts.
- Step and workflow timeouts, cancellation and heartbeat.
- Stale-run recovery and dead-letter handling.
- Action-level permissions, risk, approval and budget enforcement through shared governance.
- Deterministic branching and nested-workflow cycle/depth protection.
- Durable schedule cursor handling and missed-run policy.
- Concurrency controls per tenant/workflow/resource.
- Redacted run logs, correlation traces and action receipts.
- Versioned adapters for current action and connector extensions.
- Delegation contract from Chatbot immediate orchestration to AIAgent durable execution.

## Acceptance criteria

- Retries cannot repeat completed side effects.
- Interrupted and stale runs resume or fail according to explicit policy.
- Cancellation stops future steps and propagates to cancellable work.
- Budget, permission, approval and timeout tests pass for every built-in action type.
- Nested workflows cannot recurse indefinitely.
- Schedule and webhook triggers produce one run per idempotency key.
- Chatbot can delegate a delayed task and receive traceable status/receipt updates.
- A conformance test suite covers built-in and add-on actions/connectors.

---


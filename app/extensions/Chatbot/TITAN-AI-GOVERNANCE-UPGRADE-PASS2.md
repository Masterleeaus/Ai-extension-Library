# Titan AI Governance Upgrade — Pass 2

## Added

- Durable human approval queue backed by Laravel database tables.
- Explicit `GovernanceBlocked` exception carrying approval ID and decision.
- Action receipts for blocked, approval-required, and executed operations.
- Rollback contract metadata for reversible WorkCore actions.
- Fail-closed Model Council behaviour when a reviewer/provider errors.
- Governance migrations with complete rollback.
- Pass 2 static regression contract.

## Execution behaviour

- Green actions may execute without a council when policy allows.
- Amber actions require the configured independent reviewers.
- Red actions that need human approval are queued and not executed.
- Critical actions always create an approval request.
- Reviewer outages produce `insufficient_evidence`; they do not permit execution.
- Successful WorkCore actions return the canonical result plus `_action_receipt`.

## Host requirements

Run the extension migrations and configure the model router. Approval UI/API endpoints remain a later pass; this pass provides the durable domain and persistence layer.

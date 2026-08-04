# Runtime Wiring Repair — Pass 3

This pass adds an explicit and auditable Tier 2 to Tier 3 delegation layer.

## Added

- `AssistantDelegationGraph`: derives registry-backed assistant-to-agent edges from shared domains, capabilities, operations and permissions.
- `AssistantChainPlanner`: produces a manager → assistant → agent chain and ranked fallback workers.
- `ConfidenceFallbackPolicy`: defines tier-specific confidence thresholds and recommends manual review for uncertain high-risk work.
- Runtime diagnostics for delegation coverage, invalid edges and confidence thresholds.
- Worker-chain metadata in governed execution context and action results.

## Behaviour

- Explicit valid route IDs remain preferred.
- An assistant may only delegate to registered Tier 3 workers present in its delegation graph.
- Missing or incompatible agent IDs fall back to the highest-ranked reachable agent.
- Low-confidence routes include fallback metadata instead of silently inventing workers.
- Red or critical actions below the review threshold are marked as requiring manual review in the chain plan.
- All final actions still pass through the existing permission guard, execution-path resolver and governed tool executor.

## Boundaries

This pass does not add parallel execution, transaction sagas, rollback orchestration, long-term memory or service-worker changes.

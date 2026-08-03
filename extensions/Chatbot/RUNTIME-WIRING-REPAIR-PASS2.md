# Titan Zero Runtime Wiring Repair — Pass 2

This pass adds evidence-based dynamic worker selection and validates that every routed Tier 3 agent has a usable execution path.

## Behaviour

- Explicit registered route IDs remain authoritative.
- Missing or renamed route IDs fall back to a registered worker selected from tier, domain, operation, permissions and capability metadata.
- Tier 3 workers are classified as either native `ExecutableAgent` implementations or governed WorkCore delegates.
- Declarative agents without WorkCore authority are rejected before tool execution.
- The chosen execution path is attached to governance context and action receipts.

No worker performs direct model/database writes through this resolver. Operational mutations continue through governed WorkCore tools.

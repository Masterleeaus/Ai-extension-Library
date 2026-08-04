# Deep Audit Repair Pass 7 — WorkCore Tool Reachability

## Confirmed root cause

The active intent routes used 14 domain/operation pairs, while tool mapping validation happened only inside the final WorkCore gateway. This allowed an invalid or drifted operation to pass worker selection and governance before failing late.

## Repair

- Added `WorkCoreToolMappingRegistry` as the single operation-to-tool mapping authority.
- The five-tier orchestrator now validates the selected agent, domain, and operation together before governance execution.
- `WorkCoreAccessGateway` uses the same registry instead of duplicating mapping lookup logic.
- Runtime diagnostics expose mapping count, invalid keys, and duplicate tool targets.
- Intent definitions are now inspectable for contract tests and diagnostics.

## Current operational boundary

Fourteen business operations have concrete native WorkCore tool mappings. Other Tier 3 agents remain discoverable and governed, but are not claimed as callable business actions until a real WorkCore tool and authoritative host action handler exist. Missing mappings fail closed; no simulated success is introduced.

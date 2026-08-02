# Deep Audit Repair Pass 6

## Confirmed defects repaired

1. **Permission enforcement was not connected to authenticated-user permissions.**
   The controller accepted client-supplied `tool_permissions`, while `WorkerPermissionGuard` expected a separate `available_permissions` context field that was never supplied. The controller now derives an authoritative permission snapshot from the authenticated user and passes it separately from requested tool permissions.

2. **Worker-chain context was lost before WorkCore execution.**
   Manager, assistant, agent, workflow, conversation and channel metadata were added to the governance envelope so `WorkCoreRuntimeClient` receives the actual selected worker identity instead of the generic fallback identity.

3. **Execution-path diagnostics overstated native execution.**
   Although some agents implement `ExecutableAgent`, the active orchestrator executes approved actions through `GovernedToolExecutor` and WorkCore. Diagnostics now report the real active mode as `governed_workcore` while separately reporting whether a native implementation exists.

4. **Action receipts lacked device and worker-chain audit context.**
   A backward-compatible additive migration now records device, manager, assistant, agent, conversation, workflow, channel and idempotency identifiers on action receipts.

## Remaining limitations

- Native `ExecutableAgent::execute()` methods remain available implementations but are not directly invoked by the active orchestrator. Direct invocation requires typed input adapters and must not bypass governance.
- Live route resolution, migration execution and WorkCore tool mapping still require the host Laravel application.

# Issue #67: Harden AIAgent Workflow Engine for Durable Production Execution

**Status:** URGENT | Phase 2  
**Priority:** Urgent  
**Effort:** 2-3 weeks  
**Depends on:** #143, #144, #145, #146  
**Blocks:** #70, #21, #60, #61  

## Problem Statement

AIAgent Workflow Engine lacks production-ready hardening for:
- Error recovery and retry strategies
- Durable state management across service restarts
- Timeout and budget enforcement
- Action-level permission checks
- Secret reference handling
- Comprehensive error context

## Solution Requirements

Harden the workflow execution engine for production use with guaranteed reliability, security, and observability.

## Key Deliverables

### 1. Error Recovery & Retry Strategy

```php
// app/Domains/AIAgent/Workflow/ErrorRecovery/RetryStrategy.php

class RetryStrategy {
    public enum BackoffType {
        LINEAR,      // 1s, 2s, 3s, 4s, 5s
        EXPONENTIAL, // 1s, 2s, 4s, 8s, 16s, 32s
        FIBONACCI    // 1s, 1s, 2s, 3s, 5s, 8s, 13s
    }
    
    public string $backoffType;
    public int $maxRetries;
    public int $baseDelaySeconds;
    public int $maxDelaySeconds;
    public array $retryableErrors;  // Error classes that can be retried
    public array $fatalErrors;      // Errors that fail immediately
    
    public function getNextRetryDelay(int $attemptNumber): int {
        return match ($this->backoffType) {
            BackoffType::LINEAR => $this->baseDelaySeconds * $attemptNumber,
            BackoffType::EXPONENTIAL => min(
                $this->baseDelaySeconds * (2 ** $attemptNumber),
                $this->maxDelaySeconds
            ),
            BackoffType::FIBONACCI => $this->fibonacci($attemptNumber),
        };
    }
    
    public function shouldRetry(\Throwable $error, int $attemptNumber): bool {
        if ($attemptNumber > $this->maxRetries) {
            return false;
        }
        
        if (in_array($error::class, $this->fatalErrors)) {
            return false;
        }
        
        return in_array($error::class, $this->retryableErrors) ||
               $this->isTransientError($error);
    }
}

// Apply to action execution
class ActionDispatcher {
    public function executeWithRetry(
        WorkflowAction $action,
        array $context,
        RetryStrategy $strategy
    ): ActionResult {
        $attempt = 0;
        $lastException = null;
        
        while ($attempt < $strategy->maxRetries) {
            try {
                return $this->execute($action, $context);
            } catch (\Throwable $e) {
                $lastException = $e;
                $attempt++;
                
                if (!$strategy->shouldRetry($e, $attempt)) {
                    throw $e;
                }
                
                $delay = $strategy->getNextRetryDelay($attempt);
                $this->scheduleRetry($action, $context, $delay, $attempt);
                
                // Log retry attempt
                $this->auditLog->info("Action retry scheduled", [
                    'action_id' => $action->id,
                    'attempt' => $attempt,
                    'delay_seconds' => $delay,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        throw new WorkflowExecutionException(
            "Action failed after $attempt retries",
            previous: $lastException
        );
    }
}
```

### 2. Durable State Management

```php
// app/Domains/AIAgent/Workflow/State/DurableWorkflowState.php

class DurableWorkflowState {
    public string $workflowRunId;
    public string $workflowId;
    public string $tenantId;
    
    // Current execution state
    public enum Status {
        CREATED,
        RUNNING,
        PAUSED,
        COMPLETED,
        FAILED,
        ROLLED_BACK
    }
    
    public Status $status;
    public array $completedActions;     // { action_id: result }
    public array $pendingActions;       // Actions queued
    public array $failedActions;        // { action_id: error }
    public ?string $pausedAtActionId;
    public array $variables;            // Workflow context variables
    public \DateTimeImmutable $startedAt;
    public ?\DateTimeImmutable $completedAt;
    
    // Recovery information
    public array $checkpoints;          // Saved state at key points
    public string $lastCheckpointId;
    
    /**
     * Persist state atomically to database
     */
    public function persist(): void {
        // Store in workflow_runs table with version number
        DB::transaction(function () {
            $this->validateStateConsistency();
            
            DB::table('workflow_runs')->updateOrInsert(
                ['id' => $this->workflowRunId, 'version' => $this->version],
                [
                    'status' => $this->status->value,
                    'state_json' => json_encode([
                        'completed_actions' => $this->completedActions,
                        'pending_actions' => $this->pendingActions,
                        'variables' => $this->variables,
                        'checkpoints' => $this->checkpoints,
                    ]),
                    'version' => $this->version + 1,
                    'updated_at' => now(),
                ]
            );
        });
    }
    
    /**
     * Recover from checkpoint on service restart
     */
    public static function recoverFromCheckpoint(
        string $workflowRunId,
        string $checkpointId
    ): self {
        $state = DB::table('workflow_runs')
            ->where('id', $workflowRunId)
            ->firstOrFail();
        
        $stateData = json_decode($state->state_json, true);
        
        return new self(
            workflowRunId: $workflowRunId,
            completedActions: $stateData['completed_actions'],
            pendingActions: $stateData['pending_actions'],
            variables: $stateData['variables'],
            checkpoints: $stateData['checkpoints']
        );
    }
}

// Middleware to recover state on startup
class WorkflowRecoveryMiddleware {
    public function handle(): void {
        $pendingRuns = DB::table('workflow_runs')
            ->where('status', 'RUNNING')
            ->where('updated_at', '<', now()->subMinutes(5))
            ->get();
        
        foreach ($pendingRuns as $run) {
            // Recover and resume
            $state = DurableWorkflowState::recoverFromCheckpoint($run->id, $run->last_checkpoint_id);
            $this->workflowEngine->resume($state);
        }
    }
}
```

### 3. Timeout & Budget Enforcement

```php
// app/Domains/AIAgent/Workflow/Limits/WorkflowLimits.php

class WorkflowLimits {
    public int $maxExecutionSeconds;    // Total workflow timeout
    public int $maxActionSeconds;       // Per-action timeout
    public int $maxMemoryMB;            // Memory limit
    public int $maxToolCalls;           // Max tool invocations
    public int $maxSteps;               // Max workflow steps
    public decimal $budgetUsd;          // API cost budget
    
    public function validate(WorkflowRunContext $context): void {
        // Check execution time
        if ($context->elapsedSeconds > $this->maxExecutionSeconds) {
            throw new WorkflowTimeoutException("Workflow exceeded max execution time");
        }
        
        // Check memory
        if ($context->memoryUsageMB > $this->maxMemoryMB) {
            throw new WorkflowMemoryExceededException("Workflow exceeded memory limit");
        }
        
        // Check tool calls
        if ($context->toolCallCount > $this->maxToolCalls) {
            throw new WorkflowToolLimitExceededException("Max tool calls exceeded");
        }
        
        // Check budget
        if ($context->estimatedCostUsd > $this->budgetUsd) {
            throw new WorkflowBudgetExceededException("Workflow would exceed budget");
        }
    }
}

// Enforce limits during execution
class LimitEnforcingActionDispatcher {
    public function execute(
        WorkflowAction $action,
        WorkflowRunContext $context,
        WorkflowLimits $limits
    ): ActionResult {
        // Validate before executing
        $limits->validate($context);
        
        // Set timeout on action
        $result = $this->executeWithTimeout(
            $action,
            $limits->maxActionSeconds
        );
        
        // Update context
        $context->recordExecution($result);
        
        // Validate after executing
        $limits->validate($context);
        
        return $result;
    }
    
    private function executeWithTimeout(WorkflowAction $action, int $timeoutSeconds): ActionResult {
        // Set maximum execution time
        set_time_limit($timeoutSeconds);
        
        try {
            return $this->dispatcher->execute($action);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'time')) {
                throw new ActionTimeoutException("Action exceeded timeout");
            }
            throw $e;
        }
    }
}
```

### 4. Action-Level Authorization

```php
// app/Domains/AIAgent/Workflow/Authorization/ActionAuthorization.php

interface ActionAuthorizationPolicy {
    public function canExecuteAction(
        TenantContext $context,
        WorkflowAction $action
    ): bool;
}

// Usage in dispatcher
class AuthorizingActionDispatcher {
    public function execute(
        WorkflowAction $action,
        WorkflowRunContext $context,
        ActionAuthorizationPolicy $policy
    ): ActionResult {
        // 1. Authorize the action
        if (!$policy->canExecuteAction($this->tenantContext, $action)) {
            throw new UnauthorizedException(
                "Not authorized to execute action: {$action->type}"
            );
        }
        
        // 2. Check if action requires approval
        if ($action->requiresApproval()) {
            // Route to approval system
            $approval = $this->approvalService->requestApproval($action);
            
            if (!$approval->isApproved()) {
                throw new ActionRejected("Action rejected by reviewer");
            }
        }
        
        // 3. Execute with audit trail
        $result = $this->executeAction($action, $context);
        
        // 4. Record execution for compliance
        $this->auditLog->record(ActionExecuted(
            action_id: $action->id,
            result: $result,
            timestamp: now(),
            actor: $this->tenantContext->getActor()
        ));
        
        return $result;
    }
}
```

### 5. Secret Reference Handling

```php
// Updated to use CredentialVaultReference from #145

class ActionWithSecrets {
    private string $actionName;
    private array $parameters;  // Contains credential references
    
    public function resolveSecrets(): array {
        $resolved = [];
        
        foreach ($this->parameters as $key => $value) {
            if ($value instanceof CredentialVaultReference) {
                // Retrieve from vault
                $resolved[$key] = $this->vault->retrieve($value);
            } else {
                $resolved[$key] = $value;
            }
        }
        
        // Never log resolved secrets
        return $resolved;
    }
}
```

### 6. Comprehensive Error Context

```php
// app/Domains/AIAgent/Workflow/Error/WorkflowErrorContext.php

class WorkflowErrorContext {
    public string $workflowRunId;
    public string $failedActionId;
    public string $errorType;           // Class name
    public string $errorMessage;        // User-friendly message
    public ?string $errorDetails;       // Technical details (never in logs)
    public array $actionInputs;         // Redacted
    public array $actionOutputs;        // Redacted
    public array $contextVariables;     // Redacted
    public array $stackTrace;           // Limited depth
    public \DateTimeImmutable $occurredAt;
    public int $attemptNumber;
    public bool $isRetryable;
    
    /**
     * Create error context without sensitive data
     */
    public static function fromException(
        \Throwable $e,
        WorkflowAction $action,
        int $attemptNumber
    ): self {
        return new self(
            errorType: $e::class,
            errorMessage: $this->getSafeMessage($e),
            errorDetails: $this->getSafeDetails($e),
            actionInputs: $this->redactArray($action->parameters),
            stackTrace: $this->limitStackTrace($e->getTrace(), depth: 3),
            isRetryable: $this->isTransientError($e),
            attemptNumber: $attemptNumber
        );
    }
}
```

## Exit Criteria (All must pass)

- ✅ Workflow engine handles errors with configurable retry
- ✅ State persisted durably between restarts
- ✅ Timeout enforced per action and workflow
- ✅ Budget limits enforced during execution
- ✅ Authorization checked before action execution
- ✅ Secrets handled via vault references only
- ✅ Error context comprehensive but never exposes secrets
- ✅ Service restart recovers and resumes workflows
- ✅ All production readiness tests pass

## Testing Requirements

1. **Error Recovery:**
   - Transient errors trigger retry
   - Exponential backoff works correctly
   - Fatal errors fail immediately
   - Max retries respected

2. **State Durability:**
   - State survives service restart
   - Recovery resumes from checkpoint
   - No duplicate action execution
   - Version conflicts handled

3. **Limits:**
   - Execution timeout enforced
   - Memory limit monitored
   - Tool call limit respected
   - Budget limit checked

4. **Authorization:**
   - Unauthorized actions rejected
   - Approval flow works
   - Audit trail recorded

5. **Security:**
   - No secrets logged
   - Vault references used
   - Error messages safe
   - Stack traces limited

## Files to Create/Modify

```
app/Domains/AIAgent/Workflow/
  ├── ErrorRecovery/
  │   ├── RetryStrategy.php (NEW)
  │   └── ErrorRecoveryHandler.php (NEW)
  ├── State/
  │   ├── DurableWorkflowState.php (NEW)
  │   └── StateRecovery.php (NEW)
  ├── Limits/
  │   ├── WorkflowLimits.php (NEW)
  │   └── LimitEnforcingDispatcher.php (NEW)
  ├── Authorization/
  │   ├── ActionAuthorizationPolicy.php (NEW)
  │   └── AuthorizingActionDispatcher.php (NEW)
  ├── Security/
  │   └── SecretResolution.php (NEW)
  └── Engine/
      └── WorkflowEngine.php (MODIFY)

database/migrations/
  ├── 2026_08_04_add_workflow_durability.php (NEW)
  └── 2026_08_04_add_workflow_limits.php (NEW)

tests/Feature/AIAgent/
  ├── WorkflowErrorRecoveryTest.php (NEW)
  ├── WorkflowStateDurabilityTest.php (NEW)
  ├── WorkflowLimitsTest.php (NEW)
  └── WorkflowAuthorizationTest.php (NEW)
```

## Acceptance Criteria Checklist

- [ ] RetryStrategy implemented with configurable backoff
- [ ] DurableWorkflowState persists and recovers
- [ ] Timeout and budget limits enforced
- [ ] Authorization checked on all actions
- [ ] Secrets handled via vault references
- [ ] Error context comprehensive but safe
- [ ] State recovery works on service restart
- [ ] No regression in existing functionality
- [ ] All production readiness tests passing
- [ ] Documentation updated

## Related Issues

- #143: TenantContext & Authorization Policies
- #144: EventEnvelope & Idempotency
- #145: Credential Vault References
- #146: Webhook Verification
- #70: Migrate Chatbot Tier-3 actions
- #71: Feature flags and rollback

## Next Steps

1. Implement error recovery and retry strategy
2. Implement durable state management
3. Implement timeout and budget enforcement
4. Implement action-level authorization
5. Implement secret reference handling
6. Write comprehensive tests
7. Merge to main

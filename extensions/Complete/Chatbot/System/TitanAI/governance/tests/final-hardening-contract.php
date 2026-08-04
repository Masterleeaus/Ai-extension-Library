<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$read = static fn (string $path): string => file_get_contents($root.'/'.$path) ?: '';
$checks = [
    'approval execution is retryable after WorkCore failure' => str_contains($read('System/Approvals/ApprovalExecutionService.php'), 'failExecution') && str_contains($read('System/Approvals/DatabaseApprovalQueue.php'), 'execution_failed'),
    'approval execution uses a locked claim state' => str_contains($read('System/Approvals/DatabaseApprovalQueue.php'), 'claimForExecution') && str_contains($read('System/Approvals/DatabaseApprovalQueue.php'), "'status' => 'executing'"),
    'stale executions recover after a lease' => str_contains($read('System/Approvals/DatabaseApprovalQueue.php'), 'execution_lease_minutes'),
    'governance views require governor permission' => substr_count($read('System/Http/Controllers/GovernanceApprovalController.php').$read('System/Http/Controllers/GovernanceConfigurationController.php'), "can('govern-ai-actions')") >= 2,
    'tool execution requires tenant and user context' => str_contains($read('System/Tools/GovernedToolExecutor.php'), 'missing_actor_context'),
    'idempotent tools require stable keys' => str_contains($read('System/Tools/GovernedToolExecutor.php'), 'missing_idempotency_key'),
    'declared tool permissions are enforced' => str_contains($read('System/Tools/GovernedToolExecutor.php'), 'permission_denied'),
    'rollback only accepts successful executions' => str_contains($read('System/Rollback/RollbackExecutionService.php'), "['executed', 'executed_after_approval']"),
    'rollback has stable idempotency' => str_contains($read('System/Rollback/RollbackExecutionService.php'), "'idempotency_key' => 'rollback:'.\$receiptId"),
    'hardening migration has complete rollback' => str_contains($read('database/migrations/2026_07_27_000006_harden_governance_execution.php'), "dropColumn(['execution_attempts', 'execution_started_at', 'last_execution_error'])"),
];
$failed = array_keys(array_filter($checks, static fn (bool $passed): bool => ! $passed));
if ($failed !== []) {
    fwrite(STDERR, "Final hardening contracts failed:\n- ".implode("\n- ", $failed)."\n");
    exit(1);
}
echo 'PASS '.count($checks)." final hardening contracts\n";

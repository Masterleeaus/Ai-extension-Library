<?php
$root = dirname(__DIR__);
$checks = [
    'approval routes' => str_contains(file_get_contents($root.'/routes/web.php'), 'approvals/{approvalId}/approve'),
    'rollback route' => str_contains(file_get_contents($root.'/routes/web.php'), 'receipts/{receiptId}/rollback'),
    'tenant scope' => str_contains(file_get_contents($root.'/System/Approvals/DatabaseApprovalQueue.php'), "where('tenant_id', \$tenantId)"),
    'row locking' => str_contains(file_get_contents($root.'/System/Approvals/DatabaseApprovalQueue.php'), 'lockForUpdate()'),
    'expiry handling' => str_contains(file_get_contents($root.'/System/Approvals/DatabaseApprovalQueue.php'), "'status' => 'expired'"),
    'approval execution' => str_contains(file_get_contents($root.'/System/Approvals/ApprovalExecutionService.php'), 'executed_after_approval'),
    'rollback execution' => str_contains(file_get_contents($root.'/System/Rollback/RollbackExecutionService.php'), "'status' => 'rolled_back'"),
    'permissions' => str_contains(file_get_contents($root.'/System/Http/Controllers/GovernanceApprovalController.php'), "can('govern-ai-actions')"),
    'provider routes' => str_contains(file_get_contents($root.'/System/TitanAIGovernanceServiceProvider.php'), 'loadRoutesFrom'),
];
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "FAIL: $name\n"); exit(1); } }
echo 'PASS '.count($checks)." governance pass 3 contracts\n";

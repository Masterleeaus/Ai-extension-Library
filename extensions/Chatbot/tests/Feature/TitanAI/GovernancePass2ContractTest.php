<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
$required = [
    'System/TitanAI/governance/System/Approvals/ApprovalQueue.php',
    'System/TitanAI/governance/System/Approvals/DatabaseApprovalQueue.php',
    'System/TitanAI/governance/System/Receipts/ActionReceipt.php',
    'System/TitanAI/governance/System/Exceptions/GovernanceBlocked.php',
    'System/TitanAI/governance/database/migrations/2026_07_27_000001_create_titan_ai_governance_tables.php',
];
foreach ($required as $path) {
    if (! is_file($root.'/'.$path)) {
        fwrite(STDERR, "Missing Pass 2 file: {$path}\n");
        exit(1);
    }
}
$executor = file_get_contents($root.'/System/TitanAI/governance/System/Tools/GovernedToolExecutor.php');
foreach (['ApprovalQueue', 'ActionReceipt', 'approval_required', 'GovernanceBlocked'] as $needle) {
    if (! str_contains($executor, $needle)) {
        fwrite(STDERR, "Executor missing Pass 2 behaviour: {$needle}\n");
        exit(1);
    }
}
$council = file_get_contents($root.'/System/TitanAI/governance/System/Council/OperationalCouncil.php');
if (! str_contains($council, 'reviewer_error')) {
    fwrite(STDERR, "Council does not fail closed on reviewer errors.\n");
    exit(1);
}
echo "Titan AI governance Pass 2 contracts passed.\n";

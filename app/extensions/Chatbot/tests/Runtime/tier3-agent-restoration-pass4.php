<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$actions = glob($root.'/System/TitanAI/tier3/Actions/*.php') ?: [];
if (count($actions) !== 62) {
    fwrite(STDERR, 'Expected 62 canonical Tier 3 action files, found '.count($actions).PHP_EOL);
    exit(1);
}

$required = [
    'CreateCustomerAgent.php','UpdateCustomerAgent.php','ApplyCustomerTagAgent.php','CustomerLoyaltyAgent.php',
    'CreateQuoteAgent.php','SendQuoteAgent.php','AcceptQuoteAgent.php','CreateLeadAgent.php',
    'BookJobAgent.php','CreateRecurringJobAgent.php','CancelJobAgent.php','RescheduleJobAgent.php',
    'AutomatedRescheduleAgent.php','MarkJobCompleteAgent.php','UploadJobEvidenceAgent.php','RecordInspectionAgent.php',
    'AssignCleanerAgent.php','ReassignCleanerAgent.php','WorkforceAvailabilityAgent.php','NotifyCleanerAgent.php',
    'RecordIncidentAgent.php','CreateInvoiceAgent.php','SendInvoiceAgent.php','RecordPaymentAgent.php',
    'SendPaymentReminderAgent.php','UpdateStockAgent.php','CreatePurchaseOrderAgent.php','AllocateEquipmentAgent.php',
    'OptimiseRouteAgent.php','ResourceAllocationAgent.php','BulkOperationsAgent.php','PerformanceMetricsAgent.php',
    'CostAnalysisAgent.php','FeedbackAnalysisAgent.php','ComplianceAuditAgent.php','ConversationSummaryAgent.php',
    'GenerateBlogDraftAgent.php','PublishBlogPostAgent.php','ScheduleSocialPostAgent.php','PublishSocialPostAgent.php',
    'GeneratePhotoShootAgent.php','GenerateProductShotAgent.php','EditImageAgent.php','GenerateMarketingImageAgent.php',
    'SendCustomerMessageAgent.php','RequestReviewAgent.php','ArchiveConversationAgent.php','PredictiveMaintenanceAgent.php',
    'CreateFollowUpAgent.php','PauseRecurringServiceAgent.php',
];
foreach ($required as $file) {
    if (! is_file($root.'/System/TitanAI/tier3/Actions/'.$file)) {
        fwrite(STDERR, "Missing specified Tier 3 agent {$file}\n");
        exit(1);
    }
}

$catalog = file_get_contents($root.'/System/TitanAI/Runtime/Tier3AgentCapabilityCatalog.php');
foreach (['follow_up_chain','idempotency_required','workcore_api_only','requires_human_approval'] as $needle) {
    if (! str_contains($catalog, $needle)) {
        fwrite(STDERR, "Catalog missing {$needle}\n");
        exit(1);
    }
}

echo "Tier 3 restoration contract passed: 62 canonical agents and enriched runtime metadata.\n";

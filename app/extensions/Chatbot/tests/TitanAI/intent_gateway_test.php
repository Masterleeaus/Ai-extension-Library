<?php
require __DIR__.'/../../System/TitanAI/Runtime/IntentDecision.php';
require __DIR__.'/../../System/TitanAI/Runtime/IntentGateway.php';
use App\Extensions\Chatbot\System\TitanAI\Runtime\IntentGateway;
$gateway = new IntentGateway();
$cases = [
 ['Create an invoice for job 42','create_invoice','finance-manager'],
 ['Please book a cleaning for Friday','book_job','operations-manager'],
 ['Assign cleaner Mia to this job','assign_cleaner','field-staff-manager'],
 ['How do I clean marble?','knowledge_query',null],
 ['Hello there','general_conversation',null],
];
foreach ($cases as [$message,$intent,$manager]) {
 $decision=$gateway->classify($message);
 if ($decision->intent!==$intent || $decision->manager!==$manager) { fwrite(STDERR,"FAIL {$message}\n"); exit(1); }
}
echo "intent_gateway_test: PASS\n";

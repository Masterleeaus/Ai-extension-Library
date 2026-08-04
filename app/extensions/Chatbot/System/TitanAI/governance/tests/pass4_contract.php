<?php
$root = dirname(__DIR__);
$checks = [
    'responses adapter exists' => is_file($root.'/System/Inference/OpenAIResponsesClient.php'),
    'strict json schema enabled' => str_contains(file_get_contents($root.'/System/Inference/OpenAIResponsesClient.php'), "'strict' => true"),
    'response storage disabled' => str_contains(file_get_contents($root.'/System/Inference/OpenAIResponsesClient.php'), "'store' => false"),
    'timeouts configured' => str_contains(file_get_contents($root.'/System/Inference/OpenAIResponsesClient.php'), '->timeout($timeout)'),
    'retries configured' => str_contains(file_get_contents($root.'/System/Inference/OpenAIResponsesClient.php'), '->retry($retries'),
    'usage accounting present' => str_contains(file_get_contents($root.'/System/Inference/OpenAIResponsesClient.php'), "'input_tokens'"),
    'budget guard present' => is_file($root.'/System/Inference/ModelBudgetGuard.php'),
    'distinct models required' => str_contains(file_get_contents($root.'/System/Council/OperationalCouncil.php'), 'require_distinct_models'),
    'configured reviewer uses inference client' => str_contains(file_get_contents($root.'/System/Council/ConfiguredModelReviewer.php'), 'ModelInferenceClient'),
    'api key is environmental' => str_contains(file_get_contents($root.'/config/titan-ai-governance.php'), "env('OPENAI_API_KEY')"),
];
$failed = array_keys(array_filter($checks, fn($ok) => !$ok));
foreach ($checks as $name => $ok) echo ($ok ? '[PASS] ' : '[FAIL] ').$name.PHP_EOL;
exit($failed ? 1 : 0);

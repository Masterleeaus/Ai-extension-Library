<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/extensions/Chatbot/System/Http/Controllers/ChatbotTrainController.php';
$requestPath = $root . '/extensions/Chatbot/System/Http/Requests/Train/EmbedingRequest.php';

function failContract(string $message): never
{
    fwrite(STDERR, "Chatbot embedding ownership contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failContract('unable to read ChatbotTrainController.php');
}

if (! preg_match(
    '/public function generateEmbedding\(.*?\n    public function trainText/s',
    $controller,
    $matches,
)) {
    failContract('unable to isolate generateEmbedding()');
}

$method = $matches[0];

if (str_contains($method, 'ChatbotEmbedding::query()')) {
    failContract('generateEmbedding() uses a global embedding query');
}

if (! preg_match('/\$embeddings\s*=\s*\$chatbot->embeddings\(\)/', $method)) {
    failContract('generateEmbedding() does not select embeddings through the authorized chatbot relationship');
}

$request = file_get_contents($requestPath);
if ($request === false) {
    failContract('unable to read EmbedingRequest.php');
}

if (! str_contains($request, 'Rule::exists')) {
    failContract('embedding ID validation is not relationship-scoped');
}

if (! str_contains($request, "where('chatbot_id', \$this->input('id'))")) {
    failContract('embedding ID validation does not constrain records to the submitted chatbot');
}

echo "Chatbot embedding ownership contract passed.\n";

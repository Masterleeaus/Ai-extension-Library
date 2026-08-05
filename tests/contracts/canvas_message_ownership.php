<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/Canvas/System/Http/Controllers/CanvasController.php';

function failCanvasOwnershipContract(string $message): never
{
    fwrite(STDERR, "Canvas message ownership contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failCanvasOwnershipContract('unable to read CanvasController.php');
}

if (str_contains($controller, 'UserOpenaiChatMessage::find(')) {
    failCanvasOwnershipContract('controller still performs an unscoped message lookup');
}

if (! str_contains($controller, "where('user_id', auth()->id())")) {
    failCanvasOwnershipContract('message lookup is not constrained to the authenticated owner');
}

if (! str_contains($controller, '->findOrFail($id)')) {
    failCanvasOwnershipContract('missing or inaccessible messages do not fail closed');
}

if (substr_count($controller, '$this->ownedMessage(') < 2) {
    failCanvasOwnershipContract('both content and title mutations must use the owned message resolver');
}

foreach (['storeContent', 'saveTitle'] as $methodName) {
    if (! preg_match(
        '/public function ' . preg_quote($methodName, '/') . '\(.*?\n    }/s',
        $controller,
        $matches,
    )) {
        failCanvasOwnershipContract("unable to isolate {$methodName}()");
    }

    $method = $matches[0];
    $lookupPosition = strpos($method, '$this->ownedMessage(');
    $tryPosition = strpos($method, 'try {');

    if ($lookupPosition === false) {
        failCanvasOwnershipContract("{$methodName}() does not resolve an owned message");
    }

    if ($tryPosition !== false && $lookupPosition > $tryPosition) {
        failCanvasOwnershipContract("{$methodName}() masks ownership failures inside the generic exception handler");
    }
}

if (! str_contains($controller, "'message_id' => 'required|integer'")) {
    failCanvasOwnershipContract('message IDs are not validated as integers');
}

if (! str_contains($controller, "'type'       => 'required|string|in:input,output'")) {
    failCanvasOwnershipContract('Canvas content type is not restricted to input or output');
}

echo "Canvas message ownership contract passed.\n";

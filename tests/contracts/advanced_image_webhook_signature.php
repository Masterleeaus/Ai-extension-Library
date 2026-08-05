<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$controllerPath = $root . '/app/extensions/AdvancedImage/System/Http/Controllers/AdvancedImageWebhookController.php';

function failAdvancedImageWebhookSignatureContract(string $message): never
{
    fwrite(STDERR, "AdvancedImage webhook authentication contract failed: {$message}\n");
    exit(1);
}

$controller = file_get_contents($controllerPath);
if ($controller === false) {
    failAdvancedImageWebhookSignatureContract('unable to read AdvancedImageWebhookController.php');
}

foreach (['webhook-id', 'webhook-timestamp', 'webhook-signature'] as $header) {
    if (! str_contains($controller, $header)) {
        failAdvancedImageWebhookSignatureContract("Freepik {$header} is not required");
    }
}

if (! str_contains($controller, "hash_hmac('sha256'")) {
    failAdvancedImageWebhookSignatureContract('Freepik signature is not generated with HMAC-SHA256');
}

if (! str_contains($controller, 'base64_encode(')) {
    failAdvancedImageWebhookSignatureContract('Freepik signature is not Base64 encoded');
}

if (! str_contains($controller, 'hash_equals(')) {
    failAdvancedImageWebhookSignatureContract('Freepik signature comparison is not timing safe');
}

if (! str_contains($controller, 'MAX_WEBHOOK_AGE_SECONDS')) {
    failAdvancedImageWebhookSignatureContract('webhook timestamp freshness is not enforced');
}

if (! str_contains($controller, 'Cache::add(')) {
    failAdvancedImageWebhookSignatureContract('webhook IDs are not atomically reserved against replay');
}

if (! str_contains($controller, "config('services.freepik.webhook_secret')")) {
    failAdvancedImageWebhookSignatureContract('Freepik webhook secret is not read from configuration');
}

if (! str_contains($controller, "setting('freepik_webhook_secret')")) {
    failAdvancedImageWebhookSignatureContract('Freepik webhook secret cannot use the existing settings store');
}

if (! str_contains($controller, "where('is_advanced_image', true)")) {
    failAdvancedImageWebhookSignatureContract('callback task lookup is not restricted to AdvancedImage tasks');
}

if (! str_contains($controller, "where('payload->model', 'freepik')")) {
    failAdvancedImageWebhookSignatureContract('callback task lookup is not restricted to Freepik tasks');
}

if (! str_contains($controller, "get('request_id')") || ! str_contains($controller, "get('task_id')")) {
    failAdvancedImageWebhookSignatureContract('Freepik request_id and task_id payload compatibility is incomplete');
}

$authenticationPosition = strpos($controller, 'assertValidFreepikWebhook($request)');
$taskLookupPosition = strpos($controller, 'UserOpenai::query()');

if ($authenticationPosition === false || $taskLookupPosition === false || $authenticationPosition > $taskLookupPosition) {
    failAdvancedImageWebhookSignatureContract('Freepik authentication is not completed before task lookup');
}

if (! str_contains($controller, "if ($model === 'novita')")) {
    failAdvancedImageWebhookSignatureContract('Novita callbacks do not have an explicit fail-closed branch');
}

if (! str_contains($controller, 'abort(403')) {
    failAdvancedImageWebhookSignatureContract('unauthenticated provider callbacks are not rejected');
}

echo "AdvancedImage webhook authentication contract passed.\n";

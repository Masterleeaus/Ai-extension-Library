<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$providerPath = $root . '/app/extensions/AdvancedImage/System/AdvancedImageServiceProvider.php';
$controllerPath = $root . '/app/extensions/AdvancedImage/System/Http/Controllers/AdvancedImageController.php';
$webhookControllerPath = $root . '/app/extensions/AdvancedImage/System/Http/Controllers/AdvancedImageWebhookController.php';
$freepikPath = $root . '/app/extensions/AdvancedImage/System/Services/AdvancedFreepikService.php';
$novitaPath = $root . '/app/extensions/AdvancedImage/System/Services/AdvancedNovitaService.php';

function failAdvancedImageWebhookSignatureContract(string $message): never
{
    fwrite(STDERR, "AdvancedImage webhook signature contract failed: {$message}\n");
    exit(1);
}

$provider = file_get_contents($providerPath);
$controller = file_get_contents($controllerPath);
$webhookController = file_get_contents($webhookControllerPath);
$freepik = file_get_contents($freepikPath);
$novita = file_get_contents($novitaPath);

foreach (
    [
        'AdvancedImageServiceProvider.php' => $provider,
        'AdvancedImageController.php' => $controller,
        'AdvancedImageWebhookController.php' => $webhookController,
        'AdvancedFreepikService.php' => $freepik,
        'AdvancedNovitaService.php' => $novita,
    ] as $file => $contents
) {
    if ($contents === false) {
        failAdvancedImageWebhookSignatureContract("unable to read {$file}");
    }
}

if (! str_contains($provider, "->middleware(['api', 'signed'])")) {
    failAdvancedImageWebhookSignatureContract('webhook route does not enforce API and signed middleware');
}

if (! str_contains($controller, "'advanced_image_webhook_token'")) {
    failAdvancedImageWebhookSignatureContract('generation flow does not create a task-specific webhook token');
}

if (! str_contains($controller, 'Str::random(64)')) {
    failAdvancedImageWebhookSignatureContract('webhook token is not generated with sufficient entropy');
}

if (! str_contains($controller, "'webhookToken'")) {
    failAdvancedImageWebhookSignatureContract('task payload does not retain the webhook token');
}

if (! str_contains($freepik, "URL::signedRoute('webhook.advanced-image'")) {
    failAdvancedImageWebhookSignatureContract('Freepik submissions do not receive a signed callback URL');
}

if (! str_contains($freepik, "'model' => 'freepik'")) {
    failAdvancedImageWebhookSignatureContract('Freepik signed callback is not bound to the provider model');
}

if (! str_contains($novita, "URL::signedRoute('webhook.advanced-image'")) {
    failAdvancedImageWebhookSignatureContract('Novita submissions do not receive a signed callback URL');
}

if (! str_contains($novita, "'model' => 'novita'")) {
    failAdvancedImageWebhookSignatureContract('Novita signed callback is not bound to the provider model');
}

if (str_contains($freepik, "config('app.url') . '/api/webhook/advanced-image/")) {
    failAdvancedImageWebhookSignatureContract('Freepik still emits an unsigned callback URL');
}

if (str_contains($novita, "config('app.url') . '/api/webhook/advanced-image/")) {
    failAdvancedImageWebhookSignatureContract('Novita still emits an unsigned callback URL');
}

if (! str_contains($webhookController, "query('token')")) {
    failAdvancedImageWebhookSignatureContract('webhook controller does not require the signed task token');
}

if (! str_contains($webhookController, "where('payload->webhookToken', \$token)")) {
    failAdvancedImageWebhookSignatureContract('task lookup is not bound to the signed task token');
}

$tokenPosition = strpos($webhookController, "query('token')");
$taskLookupPosition = strpos($webhookController, 'UserOpenai::query()');

if ($tokenPosition === false || $taskLookupPosition === false || $tokenPosition > $taskLookupPosition) {
    failAdvancedImageWebhookSignatureContract('task token is not validated before task lookup');
}

echo "AdvancedImage webhook signature contract passed.\n";

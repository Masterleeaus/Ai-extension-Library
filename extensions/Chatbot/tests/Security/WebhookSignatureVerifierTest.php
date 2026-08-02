<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/System/Contracts/ChannelProviderInterface.php';
require_once $root . '/System/Security/WebhookSignatureVerifier.php';
require_once $root . '/System/TitanAI/channels/messaging-core/runtime/System/Providers/MessengerChannelProvider.php';
require_once $root . '/System/TitanAI/channels/messaging-core/runtime/System/Providers/InstagramChannelProvider.php';
require_once $root . '/System/TitanAI/channels/messaging-core/runtime/System/Providers/TelegramChannelProvider.php';

use App\Extensions\ChatbotMessenger\System\Providers\MessengerChannelProvider;
use App\Extensions\ChatbotInstagram\System\Providers\InstagramChannelProvider;
use App\Extensions\ChatbotTelegram\System\Providers\TelegramChannelProvider;

$body = '{"event":"message"}';
$secret = 'test-secret';
$metaSignature = 'sha256=' . hash_hmac('sha256', $body, $secret);

assert((new MessengerChannelProvider())->verifyWebhook([], $body) === false);
assert((new InstagramChannelProvider())->verifyWebhook([], $body) === false);
assert((new TelegramChannelProvider())->verifyWebhook([], $body) === false);
assert((new MessengerChannelProvider(['app_secret' => $secret]))->verifyWebhook(['X-Hub-Signature-256' => $metaSignature], $body));
assert((new InstagramChannelProvider(['app_secret' => $secret]))->verifyWebhook(['x-hub-signature-256' => $metaSignature], $body));
assert((new TelegramChannelProvider(['webhook_secret' => $secret]))->verifyWebhook(['X-Telegram-Bot-Api-Secret-Token' => $secret], $body));
assert(!(new TelegramChannelProvider(['webhook_secret' => $secret]))->verifyWebhook(['X-Telegram-Bot-Api-Secret-Token' => 'wrong'], $body));

echo "Webhook signature verification contract passed.\n";

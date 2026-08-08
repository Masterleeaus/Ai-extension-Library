<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$extensions = dirname($root);
$failures = [];

$source = static function (string $relative) use ($root): string {
    $path = $root . '/' . ltrim($relative, '/');

    return is_file($path) ? (string) file_get_contents($path) : '';
};

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$assertContains = static function (string $needle, string $haystack, string $message) use ($assert): void {
    $assert(str_contains($haystack, $needle), $message);
};

$assertNotContains = static function (string $needle, string $haystack, string $message) use ($assert): void {
    $assert(! str_contains($haystack, $needle), $message);
};

$config = $source('config/social-media.php');
$engagement = $source('config/engagement.php');
$verticals = $source('config/vertical-distribution.php');
$provider = $source('System/SocialMediaServiceProvider.php');
$service = $source('System/Services/EngagementGovernanceService.php');
$controller = $source('System/Http/Controllers/EngagementController.php');
$automation = (string) @file_get_contents($extensions . '/SocialMediaAutomation/System/Services/AutomationExecutionService.php');
$webhook = (string) @file_get_contents($extensions . '/SocialMediaAutomation/System/Services/WebhookProcessor.php');
$facebookOauth = $source('System/Http/Controllers/Oauth/FacebookController.php');

$assertContains("'providers' => [", $engagement, 'Missing provider engagement capability catalogue.');
foreach (['facebook', 'instagram', 'youtube', 'linkedin', 'x', 'tiktok'] as $platform) {
    $assertContains("'{$platform}' => [", $engagement, "Missing {$platform} engagement definition.");
}
$assertNotContains("'comment.list'", $config, 'TikTok commercial OAuth must not request unsupported comment.list.');
$assertNotContains("'comment.create'", $config, 'TikTok commercial OAuth must not request unsupported comment.create.');
$assertContains('commercial_comment_management_unavailable', $engagement, 'TikTok engagement must fail closed with an explicit reason.');

$assertContains("'generic-business' => [", $engagement, 'Missing generic engagement policy fallback.');
$assert(substr_count($engagement, "'auto_send_allowed' => false") >= 10, 'Generic policy plus all nine vertical policies must disable auto-send.');
foreach (['field-home-services', 'accommodation', 'real-estate', 'salons-personal-care', 'fitness-membership', 'automotive-services', 'ecommerce-retail', 'hire-rental', 'booking-capacity'] as $vertical) {
    $assertContains("'{$vertical}' => [", $engagement, "Missing engagement policy for {$vertical}.");
    $assertContains("'{$vertical}' => [", $verticals, "Missing canonical #337 vertical {$vertical}.");
}
$assertContains("'facilities-maintenance'", $verticals, 'Facilities maintenance must remain under field-home-services.');

$assert($service !== '', 'Missing EngagementGovernanceService.');
foreach (['capabilities', 'inbox', 'proposeReply', 'sendReply', 'privateReply', 'editReply', 'deleteReply', 'handoff', 'ingestWebhookEvent'] as $method) {
    $assertContains("function {$method}", $service, "EngagementGovernanceService missing {$method}().");
}
foreach (['resolveVerticalProfile', 'engagement_policy', 'quiet_period', 'suppression', 'duplicate', 'approved', 'human_handoff', 'ext_social_media_distribution_audits', 'Cache::lock'] as $needle) {
    $assertContains($needle, $service, "Engagement governance missing {$needle} boundary.");
}

$helpers = [
    'Facebook.php' => ['comments', 'replyToComment', 'privateReply'],
    'Instagram.php' => ['comments', 'replyToComment', 'privateReply'],
    'Youtube.php' => ['commentThreads', 'comments', 'replyToComment', 'updateComment', 'deleteComment'],
    'Linkedin.php' => ['organizationAcls', 'comments', 'replyToComment', 'updateComment', 'deleteComment'],
    'X.php' => ['mentions', 'replyToPost', 'deletePost'],
];
foreach ($helpers as $file => $methods) {
    $helper = $source('System/Helpers/' . $file);
    foreach ($methods as $method) {
        $assertContains("function {$method}", $helper, "{$file} missing {$method}().");
    }
}

$assert($controller !== '', 'Missing EngagementController.');
$assertContains("->where('user_id', Auth::id())", $controller, 'Engagement controller must tenant-scope accounts.');
$assertContains('Cache::lock', $controller, 'Engagement mutations require per-account/per-engagement locks.');
foreach (['engagement/{account}/capabilities', 'engagement/{account}/inbox', 'engagement/{account}/proposal', 'engagement/{account}/reply', 'engagement/{account}/private-reply', 'engagement/{account}/edit', 'engagement/{account}/delete', 'engagement/{account}/handoff'] as $route) {
    $assertContains($route, $provider, "Missing governed route {$route}.");
}

$assertContains('stageGovernedProposal', $automation, 'Automation must stage governed proposals instead of auto-sending replies.');
$assertNotContains('https://open.tiktokapis.com/v2/comment/reply/create/', $automation, 'Bogus TikTok commercial reply endpoint must be removed.');
$assertNotContains("'payload' => \$request->json()->all()", $facebookOauth, 'Facebook webhook must not log raw payloads.');
$assertNotContains("'text'       => \$payload['text']", $automation, 'Automation debug logs must not write raw engagement text.');
$assertNotContains("Log::debug('Facebook comment event extracted', \$commentData)", $webhook, 'Webhook processor must not log raw normalized comment payloads.');

if ($failures !== []) {
    fwrite(STDERR, "Issue #273 contract failed:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Issue #273 engagement contract passed.\n");

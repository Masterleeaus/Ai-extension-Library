<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$repo = dirname($root, 3);
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
$verticals = $source('config/vertical-distribution.php');
$provider = $source('System/SocialMediaServiceProvider.php');
$service = $source('System/Services/EngagementGovernanceService.php');
$controller = $source('System/Http/Controllers/EngagementController.php');
$automation = (string) @file_get_contents($repo . '/SocialMediaAutomation/System/Services/AutomationExecutionService.php');
$webhook = (string) @file_get_contents($repo . '/SocialMediaAutomation/System/Services/WebhookProcessor.php');
$facebookOauth = $source('System/Http/Controllers/Oauth/FacebookController.php');

$assertContains("'engagement' => [", $config, 'Missing provider engagement capability catalogue.');
foreach (['facebook', 'instagram', 'youtube', 'linkedin', 'x', 'tiktok'] as $platform) {
    $assertContains("'{$platform}' => [", $config, "Missing {$platform} engagement definition.");
}
$assertNotContains("'comment.list'", $config, 'TikTok commercial OAuth must not request unsupported comment.list.');
$assertNotContains("'comment.create'", $config, 'TikTok commercial OAuth must not request unsupported comment.create.');
$assertContains('commercial_comment_management_unavailable', $config, 'TikTok engagement must fail closed with an explicit reason.');

$assert($service !== '', 'Missing EngagementGovernanceService.');
foreach (['capabilities', 'inbox', 'proposeReply', 'sendReply', 'privateReply', 'editReply', 'deleteReply', 'handoff', 'ingestWebhookEvent'] as $method) {
    $assertContains("function {$method}", $service, "EngagementGovernanceService missing {$method}().");
}
foreach (['resolveVerticalProfile', 'engagement_policy', 'quiet_period', 'suppression', 'duplicate', 'approved', 'human_handoff', 'ext_social_media_distribution_audits', 'Cache::lock'] as $needle) {
    $assertContains($needle, $service, "Engagement governance missing {$needle} boundary.");
}
$assertContains("'auto_send_allowed' => false", $verticals, 'Generic/vertical engagement policy must disable auto-send.');
$assert(substr_count($verticals, "'engagement_policy' => [") >= 10, 'Generic profile plus all nine verticals require engagement policies.');
foreach (['field-home-services', 'accommodation', 'real-estate', 'salons-personal-care', 'fitness-membership', 'automotive-services', 'ecommerce-retail', 'hire-rental', 'booking-capacity'] as $vertical) {
    $assertContains("'{$vertical}' => [", $verticals, "Missing canonical vertical {$vertical}.");
}
$assertContains("'facilities-maintenance'", $verticals, 'Facilities maintenance must remain under field-home-services.');

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
$assertNotContains("'comment_text'", $webhook, 'Webhook processor must not log/store raw comment text outside governed records.');

if ($failures !== []) {
    fwrite(STDERR, "Issue #273 contract failed:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Issue #273 engagement contract passed.\n");

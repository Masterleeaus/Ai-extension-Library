<?php

declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    $prefix = 'TitanZero\\Interaction\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = $root . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

use TitanZero\Interaction\Vertical\AI\VerticalAIProposal;
use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;
use TitanZero\Interaction\Vertical\Planning\VerticalPreviewPlanner;
use TitanZero\Interaction\Vertical\Planning\VerticalPreviewProfileCatalogue;
use TitanZero\Interaction\Vertical\Planning\VerticalPreviewValidator;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

$tests = [];
$test = static function (string $name, callable $fn) use (&$tests): void { $tests[$name] = $fn; };
$assert = static function (bool $condition, string $message = 'Assertion failed'): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

$makeSnapshot = static function (string $profile, array $values = []) {
    return (new VerticalContextComposer())->compose([
        VerticalContextLayer::fromArray([
            'id' => 'subtype.' . $profile,
            'kind' => 'subtype',
            'version' => '1.0.0',
            'values' => $values,
            'generated_at' => '2026-08-05T00:00:00+00:00',
        ]),
    ], ['company_id' => 42]);
};

$makeProposal = static function ($snapshot, array $updates, float $confidence = 0.82): VerticalAIProposal {
    return VerticalAIProposal::create(
        updates: [
            'terminology' => (array) ($updates['terminology'] ?? []),
            'context' => (array) ($updates['context'] ?? []),
            'questions' => [],
        ],
        confidence: $confidence,
        rationale: 'Approved preview refinements.',
        provenance: [[
            'source' => 'wizard.answer.preview',
            'path' => '/preview',
            'reason' => 'Approved business-language preference.',
        ]],
        contextHash: $snapshot->contextHash(),
        providerId: 'test-provider',
        modelId: 'test-model',
        attempts: 1,
        fallbackUsed: false,
        audit: [
            'schema_version' => '1.0.0',
            'prompt_hash' => str_repeat('a', 64),
            'response_hash' => str_repeat('b', 64),
            'validation_errors' => [],
        ],
    );
};

$current = [
    'terminology' => [
        'entity.customer.singular' => 'Customer',
        'entity.work.singular' => 'Work item',
    ],
    'theme' => [
        'density' => 'comfortable',
        'surface_emphasis' => 'balanced',
        'corner_style' => 'standard',
    ],
    'navigation' => [
        'home' => ['label' => 'Home', 'order' => 10, 'visible' => true, 'roles' => ['owner', 'staff'], 'devices' => ['desktop', 'mobile', 'tablet', 'field']],
        'work' => ['label' => 'Work', 'order' => 20, 'visible' => true, 'roles' => ['owner', 'staff'], 'devices' => ['desktop', 'mobile', 'tablet', 'field']],
        'customers' => ['label' => 'Customers', 'order' => 30, 'visible' => true, 'roles' => ['owner', 'staff'], 'devices' => ['desktop', 'mobile', 'tablet']],
        'reports' => ['label' => 'Reports', 'order' => 70, 'visible' => true, 'roles' => ['owner'], 'devices' => ['desktop', 'tablet']],
    ],
    'workspaces' => [
        'owner.overview' => ['label' => 'Business overview', 'order' => 10, 'visible' => true, 'roles' => ['owner'], 'devices' => ['desktop', 'tablet']],
        'staff.today' => ['label' => 'Today', 'order' => 20, 'visible' => true, 'roles' => ['staff'], 'devices' => ['mobile', 'field', 'tablet']],
    ],
    'guidance' => [],
    'announcements' => [],
    'feature_flags' => [
        'customer_portal' => false,
        'online_booking' => false,
        'inventory' => false,
        'asset_tracking' => false,
    ],
    'public_content' => [
        'booking_heading' => 'Book with us',
        'service_intro' => 'Tell us what you need.',
    ],
];

$planner = new VerticalPreviewPlanner(
    VerticalPreviewProfileCatalogue::defaults(),
    new VerticalPreviewValidator(),
);

$test('generic profile produces typed deterministic defaults and auditable diffs', function () use ($planner, $makeSnapshot, $current, $assert): void {
    $snapshot = $makeSnapshot('unrecognised-business');
    $first = $planner->plan($snapshot, $current, null, ['roles' => ['owner'], 'device' => 'desktop'])->toArray();
    $second = $planner->plan($snapshot, array_reverse($current, true), null, ['device' => 'desktop', 'roles' => ['owner']])->toArray();

    $assert($first === $second, 'Equivalent input did not produce the same plan.');
    $assert($first['profile_id'] === 'generic');
    $assert($first['context_hash'] === $snapshot->contextHash());
    $assert(strlen($first['plan_id']) === 64);
    $assert(array_keys($first['sections']) === [
        'terminology', 'theme', 'navigation', 'workspaces',
        'guidance', 'announcements', 'feature_flags', 'public_content',
    ]);
    $assert($first['protected_surfaces'] === [
        'authentication', 'canonical_routes', 'compliance_state', 'offline_runtime',
        'permissions', 'platform_shell', 'safety_state',
    ]);
    foreach ($first['diffs'] as $diff) {
        $assert(isset($diff['path'], $diff['source'], $diff['confidence'], $diff['risk'], $diff['affected_surfaces']));
    }
});

$test('one planner supports every required vertical fixture', function () use ($planner, $makeSnapshot, $current, $assert): void {
    $profiles = ['cleaning', 'accommodation', 'salon', 'fitness', 'automotive', 'retail', 'hire-rental'];
    foreach ($profiles as $profile) {
        $plan = $planner->plan($makeSnapshot($profile), $current, null, ['roles' => ['owner'], 'device' => 'desktop'])->toArray();
        $assert($plan['profile_id'] === $profile, "Profile {$profile} did not resolve.");
        $assert($plan['sections']['terminology'] !== [], "Profile {$profile} has no terminology defaults.");
        $assert($plan['sections']['guidance'] !== [], "Profile {$profile} has no guidance defaults.");
    }
});

$test('validated AI proposal layers after deterministic defaults without changing identifiers', function () use ($planner, $makeSnapshot, $makeProposal, $current, $assert): void {
    $snapshot = $makeSnapshot('cleaning');
    $proposal = $makeProposal($snapshot, [
        'terminology' => ['entity.customer.singular' => 'Client'],
        'context' => [
            'theme' => ['density' => 'field'],
            'navigation' => [
                'work' => ['label' => 'Jobs', 'order' => 20, 'visible' => true],
            ],
            'workspaces' => [
                'staff.today' => ['label' => 'Today’s jobs', 'order' => 20, 'visible' => true],
            ],
            'ai' => [
                'guidance' => ['first_job' => 'Create your first cleaning job.'],
                'announcements' => ['launch_tip' => 'Your cleaning workspace is ready to review.'],
            ],
            'capabilities' => ['customer_portal' => true, 'online_booking' => true],
            'activation' => [
                'public_content' => ['booking_heading' => 'Book a clean'],
            ],
        ],
    ]);
    $plan = $planner->plan($snapshot, $current, $proposal, ['roles' => ['staff'], 'device' => 'field'])->toArray();

    $assert($plan['sections']['terminology']['entity.customer.singular'] === 'Client');
    $assert($plan['sections']['navigation']['work']['label'] === 'Jobs');
    $assert($plan['sections']['workspaces']['staff.today']['label'] === 'Today’s jobs');
    $assert($plan['sections']['guidance']['first_job'] === 'Create your first cleaning job.');
    $assert($plan['sections']['announcements']['launch_tip'] === 'Your cleaning workspace is ready to review.');
    $assert($plan['sections']['feature_flags']['online_booking'] === true);
    $assert($plan['sections']['public_content']['booking_heading'] === 'Book a clean');
    $assert($plan['sources']['/terminology/entity.customer.singular']['source'] === $proposal->toArray()['proposal_id']);
});

$test('platform shell routes authentication permissions and safety states cannot be patched', function () use ($planner, $makeSnapshot, $makeProposal, $current, $assert): void {
    $snapshot = $makeSnapshot('cleaning');
    foreach ([
        ['context' => ['navigation' => ['work' => ['route' => 'admin.users']]]],
        ['context' => ['theme' => ['platform_shell' => 'custom']]],
        ['context' => ['workspaces' => ['staff.today' => ['permissions' => ['admin']]]]],
        ['context' => ['activation' => ['safety_state' => 'disabled']]],
    ] as $updates) {
        try {
            $planner->plan($snapshot, $current, $makeProposal($snapshot, $updates), ['roles' => ['owner'], 'device' => 'desktop']);
            $assert(false, 'Protected technical surface was patched.');
        } catch (InvalidArgumentException) {
        }
    }
});

$test('raw CSS and executable presentation values are rejected', function () use ($planner, $makeSnapshot, $makeProposal, $current, $assert): void {
    $snapshot = $makeSnapshot('salon');
    foreach (['body { display:none; }', 'var(--secret)', 'url(javascript:alert(1))', '<style>body{}</style>'] as $value) {
        try {
            $planner->plan($snapshot, $current, $makeProposal($snapshot, ['context' => ['theme' => ['density' => $value]]]), ['roles' => ['owner'], 'device' => 'desktop']);
            $assert(false, 'Raw CSS or executable presentation value was accepted.');
        } catch (InvalidArgumentException) {
        }
    }
});

$test('navigation conflicts and unknown technical keys fail closed', function () use ($planner, $makeSnapshot, $makeProposal, $current, $assert): void {
    $snapshot = $makeSnapshot('retail');
    $conflict = $makeProposal($snapshot, ['context' => ['navigation' => [
        'work' => ['order' => 10, 'visible' => true],
    ]]]);
    try {
        $planner->plan($snapshot, $current, $conflict, ['roles' => ['owner'], 'device' => 'desktop']);
        $assert(false, 'Duplicate visible navigation order was accepted.');
    } catch (InvalidArgumentException) {
    }

    $unknown = $makeProposal($snapshot, ['context' => ['navigation' => [
        'invented.admin.route' => ['label' => 'Secret admin', 'order' => 5, 'visible' => true],
    ]]]);
    try {
        $planner->plan($snapshot, $current, $unknown, ['roles' => ['owner'], 'device' => 'desktop']);
        $assert(false, 'AI introduced an unknown navigation identifier.');
    } catch (InvalidArgumentException) {
    }
});

$test('role and device overlays expose only applicable navigation and workspaces', function () use ($planner, $makeSnapshot, $current, $assert): void {
    $snapshot = $makeSnapshot('automotive');
    $owner = $planner->plan($snapshot, $current, null, ['roles' => ['owner'], 'device' => 'desktop'])->toArray();
    $field = $planner->plan($snapshot, $current, null, ['roles' => ['staff'], 'device' => 'field'])->toArray();

    $assert(isset($owner['sections']['navigation']['reports']));
    $assert(!isset($field['sections']['navigation']['reports']));
    $assert(isset($owner['sections']['workspaces']['owner.overview']));
    $assert(!isset($field['sections']['workspaces']['owner.overview']));
    $assert(isset($field['sections']['workspaces']['staff.today']));
    $assert($owner['plan_id'] !== $field['plan_id']);
});

$test('proposal context mismatch and fallback proposal are handled safely', function () use ($planner, $makeSnapshot, $makeProposal, $current, $assert): void {
    $cleaning = $makeSnapshot('cleaning');
    $salon = $makeSnapshot('salon');
    try {
        $planner->plan($cleaning, $current, $makeProposal($salon, ['terminology' => ['entity.work.singular' => 'Appointment']]), ['roles' => ['owner'], 'device' => 'desktop']);
        $assert(false, 'Proposal from another context was accepted.');
    } catch (InvalidArgumentException) {
    }

    $fallback = VerticalAIProposal::create(
        updates: ['terminology' => [], 'context' => [], 'questions' => []],
        confidence: 0.0,
        rationale: 'No proposal.',
        provenance: [['source' => 'fallback', 'path' => '/', 'reason' => 'No proposal']],
        contextHash: $cleaning->contextHash(),
        providerId: 'test-provider', modelId: 'test-model', attempts: 2, fallbackUsed: true,
        audit: ['schema_version' => '1.0.0', 'prompt_hash' => str_repeat('c', 64), 'response_hash' => str_repeat('d', 64), 'validation_errors' => []],
    );
    $without = $planner->plan($cleaning, $current, null, ['roles' => ['owner'], 'device' => 'desktop'])->toArray();
    $withFallback = $planner->plan($cleaning, $current, $fallback, ['roles' => ['owner'], 'device' => 'desktop'])->toArray();
    $assert($without === $withFallback, 'Fallback proposal changed preview output.');
});

$failed = 0;
foreach ($tests as $name => $fn) {
    try {
        $fn();
        echo "PASS {$name}\n";
    } catch (Throwable $exception) {
        $failed++;
        echo "FAIL {$name}: {$exception->getMessage()}\n";
    }
}
exit($failed === 0 ? 0 : 1);

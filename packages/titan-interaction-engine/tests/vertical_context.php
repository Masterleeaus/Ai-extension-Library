<?php

declare(strict_types=1);

$standalone = !isset($test, $assert, $tests);
if ($standalone) {
    $root = dirname(__DIR__);
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'TitanZero\\Interaction\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = $root . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    });
    $tests = [];
    $test = static function (string $name, callable $fn) use (&$tests): void { $tests[$name] = $fn; };
    $assert = static function (bool $condition, string $message = 'Assertion failed'): void {
        if (!$condition) { throw new RuntimeException($message); }
    };
}

use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;
use TitanZero\Interaction\Vertical\GenericVerticalBaseProvider;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

$test('vertical context resolves deterministic precedence and provenance', function () use ($assert): void {
    $composer = new VerticalContextComposer();
    $snapshot = $composer->compose([
        VerticalContextLayer::fromArray([
            'id' => 'vertical.cleaning',
            'kind' => 'subtype',
            'version' => '1.0.0',
            'values' => ['terminology' => ['entity.work.singular' => 'Job']],
        ]),
        VerticalContextLayer::fromArray([
            'id' => 'tenant.customisation',
            'kind' => 'tenant',
            'version' => '1.0.0',
            'values' => ['terminology' => ['entity.customer.singular' => 'Client']],
            'provenance' => ['source_type' => 'user_entered', 'revision' => 3],
        ]),
    ], ['company_id' => 42]);

    $data = $snapshot->toArray();
    $assert($data['resolved']['terminology']['entity.work.singular'] === 'Job');
    $assert($data['resolved']['terminology']['entity.customer.singular'] === 'Client');
    $assert($data['sources']['/resolved/terminology/entity.work.singular']['source'] === 'vertical.cleaning');
    $assert($data['sources']['/resolved/terminology/entity.customer.singular']['source_type'] === 'user_entered');
    $assert($data['sources']['/resolved/terminology/entity.customer.singular']['revision'] === 3);
    $assert($data['layers'][0]['id'] === 'platform.generic-business');
});

$test('operational state always overrides tenant branding regardless of input order', function () use ($assert): void {
    $composer = new VerticalContextComposer();
    $snapshot = $composer->compose([
        VerticalContextLayer::fromArray([
            'id' => 'state.safety',
            'kind' => 'operational_state',
            'version' => '1.0.0',
            'values' => ['theme' => ['state.danger' => 'system-danger']],
        ]),
        VerticalContextLayer::fromArray([
            'id' => 'tenant.brand',
            'kind' => 'tenant',
            'version' => '1.0.0',
            'values' => ['theme' => ['state.danger' => 'pink']],
        ]),
    ], ['company_id' => 42]);

    $assert($snapshot->toArray()['resolved']['theme']['state.danger'] === 'system-danger');
    $assert($snapshot->toArray()['sources']['/resolved/theme/state.danger']['source'] === 'state.safety');
});

$test('equivalent associative input produces the same context hash', function () use ($assert): void {
    $composer = new VerticalContextComposer();
    $first = VerticalContextLayer::fromArray([
        'id' => 'vertical.cleaning',
        'kind' => 'subtype',
        'version' => '1.0.0',
        'generated_at' => '2026-08-05T10:00:00+10:00',
        'values' => ['theme' => ['accent' => '#123456', 'density' => 'field']],
    ]);
    $second = VerticalContextLayer::fromArray([
        'id' => 'vertical.cleaning',
        'kind' => 'subtype',
        'version' => '1.0.0',
        'generated_at' => '2026-08-05T11:00:00+10:00',
        'values' => ['theme' => ['density' => 'field', 'accent' => '#123456']],
    ]);

    $one = $composer->compose([$first], ['company_id' => 42, 'actor' => ['roles' => ['owner'], 'id' => 7]]);
    $two = $composer->compose([$second], ['actor' => ['id' => 7, 'roles' => ['owner']], 'company_id' => 42]);

    $assert($one->contextHash() === $two->contextHash());
    $assert($one->toArray()['context_id'] === $two->toArray()['context_id']);
});

$test('duplicate layer IDs are rejected', function () use ($assert): void {
    $layer = VerticalContextLayer::fromArray([
        'id' => 'vertical.cleaning',
        'kind' => 'subtype',
        'version' => '1.0.0',
        'values' => [],
    ]);

    try {
        (new VerticalContextComposer())->compose([$layer, $layer], ['company_id' => 42]);
        $assert(false, 'Duplicate layer ID was accepted');
    } catch (\InvalidArgumentException) {
        $assert(true);
    }
});

$test('invalid layer version, precedence and value sections are rejected', function () use ($assert): void {
    foreach ([
        ['id' => 'bad.version', 'kind' => 'subtype', 'version' => 'latest', 'values' => []],
        ['id' => 'bad.precedence', 'kind' => 'tenant', 'version' => '1.0.0', 'precedence' => 999, 'values' => []],
        ['id' => 'bad.section', 'kind' => 'tenant', 'version' => '1.0.0', 'values' => ['permissions' => ['admin' => true]]],
    ] as $definition) {
        try {
            VerticalContextLayer::fromArray($definition);
            $assert(false, 'Invalid layer was accepted: ' . $definition['id']);
        } catch (\InvalidArgumentException) {
            $assert(true);
        }
    }
});

$test('generic base supplies neutral semantic defaults without permissions', function () use ($assert): void {
    $layer = (new GenericVerticalBaseProvider())->layer([])->toArray();

    $assert($layer['id'] === 'platform.generic-business');
    $assert($layer['values']['terminology']['entity.customer.singular'] === 'Customer');
    $assert($layer['values']['terminology']['entity.work.singular'] === 'Work item');
    $assert(isset($layer['values']['theme']['state.danger']));
    $assert(!array_key_exists('permissions', $layer['values']));
});

if ($standalone) {
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
}

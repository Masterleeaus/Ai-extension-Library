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

use TitanZero\Interaction\AI\AIServiceInterface;
use TitanZero\Interaction\Vertical\AI\DeterministicVerticalAIProposalFallback;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalBridge;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalSanitizer;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalValidator;
use TitanZero\Interaction\Vertical\DTO\VerticalContextLayer;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

final class QueueVerticalProposalAIService implements AIServiceInterface
{
    /** @var list<string|Throwable> */
    private array $responses;
    public array $calls = [];

    /** @param list<string|Throwable> $responses */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function generate(string $prompt, array $options = []): string
    {
        $this->calls[] = ['prompt' => $prompt, 'options' => $options];
        $response = array_shift($this->responses);
        if ($response instanceof Throwable) {
            throw $response;
        }
        return (string) $response;
    }
}

$makeSnapshot = static function () {
    return (new VerticalContextComposer())->compose([
        VerticalContextLayer::fromArray([
            'id' => 'vertical.cleaning',
            'kind' => 'subtype',
            'version' => '1.0.0',
            'values' => [
                'terminology' => ['entity.work.singular' => 'Job'],
                'capabilities' => ['booking' => true],
            ],
        ]),
    ], ['company_id' => 42, 'actor' => ['id' => 7, 'roles' => ['owner']]]);
};

$validResponse = static function (array $overrides = []): string {
    $payload = [
        'updates' => [
            'terminology' => ['entity.customer.singular' => 'Client'],
            'context' => ['theme' => ['density' => 'field']],
            'questions' => [[
                'id' => 'service_type',
                'prompt' => 'Which service do you provide?',
                'type' => 'single_select',
                'required' => true,
                'options' => [
                    ['value' => 'cleaning', 'label' => 'Cleaning'],
                    ['value' => 'maintenance', 'label' => 'Maintenance'],
                ],
            ]],
        ],
        'confidence' => 0.88,
        'rationale' => 'The answers indicate service terminology and a field-friendly layout.',
        'provenance' => [[
            'source' => 'wizard.answer.service_type',
            'path' => '/resolved/capabilities/booking',
            'reason' => 'The selected service requires booking terminology.',
        ]],
    ];
    $payload = array_replace_recursive($payload, $overrides);
    return json_encode($payload, JSON_THROW_ON_ERROR);
};

$makeBridge = static function (AIServiceInterface $service, int $maxAttempts = 2): VerticalAIProposalBridge {
    return new VerticalAIProposalBridge(
        ai: $service,
        validator: new VerticalAIProposalValidator(),
        sanitizer: new VerticalAIProposalSanitizer(),
        fallback: new DeterministicVerticalAIProposalFallback(),
        providerId: 'openai-compatible',
        modelId: 'test-model',
        maxAttempts: $maxAttempts,
    );
};

$test('valid AI output becomes an auditable proposal without mutating context', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $snapshot = $makeSnapshot();
    $before = $snapshot->toArray();
    $service = new QueueVerticalProposalAIService([$validResponse()]);

    $proposal = $makeBridge($service)->propose($snapshot, ['goal' => 'customise onboarding']);
    $data = $proposal->toArray();

    $assert($data['updates']['terminology']['entity.customer.singular'] === 'Client');
    $assert($data['updates']['context']['theme']['density'] === 'field');
    $assert($data['updates']['questions'][0]['id'] === 'service_type');
    $assert($data['confidence'] === 0.88);
    $assert($data['context_hash'] === $snapshot->contextHash());
    $assert($data['provider_id'] === 'openai-compatible');
    $assert($data['model_id'] === 'test-model');
    $assert($data['attempts'] === 1);
    $assert($data['fallback_used'] === false);
    $assert(strlen($data['proposal_id']) === 64);
    $assert(strlen($data['audit']['prompt_hash']) === 64);
    $assert(strlen($data['audit']['response_hash']) === 64);
    $assert($snapshot->toArray() === $before, 'Proposal bridge mutated the context snapshot');
    $assert(str_contains($service->calls[0]['prompt'], $snapshot->contextHash()));
    $assert($service->calls[0]['options']['response_format'] === 'json');
});

$test('malformed first response is retried and valid second response succeeds', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $service = new QueueVerticalProposalAIService(['not-json', $validResponse()]);
    $data = $makeBridge($service, 2)->propose($makeSnapshot())->toArray();

    $assert(count($service->calls) === 2);
    $assert($data['attempts'] === 2);
    $assert($data['fallback_used'] === false);
    $assert(count($data['audit']['validation_errors']) === 1);
});

$test('executable HTML or JavaScript is rejected and produces no-change fallback', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $malicious = $validResponse(['rationale' => '<script>alert(1)</script>']);
    $service = new QueueVerticalProposalAIService([$malicious, $malicious]);
    $data = $makeBridge($service, 2)->propose($makeSnapshot())->toArray();

    $assert($data['fallback_used'] === true);
    $assert($data['updates'] === ['terminology' => [], 'context' => [], 'questions' => []]);
    $assert($data['confidence'] === 0.0);
    $assert(count($data['audit']['validation_errors']) === 2);
});

$test('non-HTTPS URLs are rejected while HTTPS URLs remain valid', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $bad = $validResponse(['rationale' => 'Read http://example.test/help before continuing.']);
    $good = $validResponse(['rationale' => 'Read https://example.test/help before continuing.']);
    $service = new QueueVerticalProposalAIService([$bad, $good]);
    $data = $makeBridge($service, 2)->propose($makeSnapshot())->toArray();

    $assert($data['fallback_used'] === false);
    $assert($data['attempts'] === 2);
    $assert($data['rationale'] === 'Read https://example.test/help before continuing.');
});

$test('unsupported context sections are rejected by the vertical context schema', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $invalid = $validResponse(['updates' => ['context' => ['permissions' => ['admin' => true]]]]);
    $service = new QueueVerticalProposalAIService([$invalid]);
    $data = $makeBridge($service, 1)->propose($makeSnapshot())->toArray();

    $assert($data['fallback_used'] === true);
    $assert(str_contains($data['audit']['validation_errors'][0], 'Unsupported vertical context value section'));
});

$test('invalid question types and oversized labels are rejected', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $invalidType = $validResponse(['updates' => ['questions' => [[
        'id' => 'unsafe',
        'prompt' => 'Run this?',
        'type' => 'php',
        'required' => false,
        'options' => [],
    ]]]]);
    $oversized = $validResponse(['updates' => ['questions' => [[
        'id' => 'long_label',
        'prompt' => str_repeat('x', 501),
        'type' => 'text',
        'required' => false,
        'options' => [],
    ]]]]);
    $service = new QueueVerticalProposalAIService([$invalidType, $oversized]);
    $data = $makeBridge($service, 2)->propose($makeSnapshot())->toArray();

    $assert($data['fallback_used'] === true);
    $assert(count($data['audit']['validation_errors']) === 2);
});

$test('provider failures exhaust retries and return deterministic replay metadata', function () use ($assert, $makeSnapshot, $makeBridge): void {
    $snapshot = $makeSnapshot();
    $service = new QueueVerticalProposalAIService([
        new RuntimeException('provider unavailable'),
        new RuntimeException('provider unavailable'),
    ]);
    $first = $makeBridge($service, 2)->propose($snapshot)->toArray();

    $secondService = new QueueVerticalProposalAIService([
        new RuntimeException('provider unavailable'),
        new RuntimeException('provider unavailable'),
    ]);
    $second = $makeBridge($secondService, 2)->propose($snapshot)->toArray();

    $assert($first['fallback_used'] === true);
    $assert($first['attempts'] === 2);
    $assert($first['proposal_id'] === $second['proposal_id']);
    $assert($first['context_hash'] === $snapshot->contextHash());
    $assert($first['audit']['validation_errors'] === $second['audit']['validation_errors']);
});

$test('raw schema rejects unknown keys invalid confidence and missing provenance', function () use ($assert, $makeSnapshot, $validResponse, $makeBridge): void {
    $unknown = json_decode($validResponse(), true, 512, JSON_THROW_ON_ERROR);
    $unknown['execute'] = 'delete everything';
    $invalidConfidence = json_decode($validResponse(), true, 512, JSON_THROW_ON_ERROR);
    $invalidConfidence['confidence'] = 2;
    $missingProvenance = json_decode($validResponse(), true, 512, JSON_THROW_ON_ERROR);
    unset($missingProvenance['provenance']);

    $service = new QueueVerticalProposalAIService([
        json_encode($unknown, JSON_THROW_ON_ERROR),
        json_encode($invalidConfidence, JSON_THROW_ON_ERROR),
        json_encode($missingProvenance, JSON_THROW_ON_ERROR),
    ]);
    $data = $makeBridge($service, 3)->propose($makeSnapshot())->toArray();

    $assert($data['fallback_used'] === true);
    $assert(count($data['audit']['validation_errors']) === 3);
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

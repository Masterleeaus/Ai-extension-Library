<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\WorkCoreApps;

use InvalidArgumentException;
use RuntimeException;

final class WorkCoreAppBridge
{
    /** @var array<string,array<string,mixed>> */
    private array $apps = [];

    public function __construct(private readonly ?object $runtime = null) { $this->loadManifests(); }
    /** @return array<string,array<string,mixed>> */ public function apps(): array { return $this->apps; }
    /** @return array<string,mixed>|null */ public function app(string $slug): ?array { return $this->apps[$slug] ?? null; }
    public function runtimeAvailable(): bool { return $this->runtime !== null; }

    /** @param array<string,mixed> $input */
    public function execute(string $tool, array $input, object $context, ?string $aiRunPublicId = null, ?string $confirmationId = null, ?string $idempotencyKey = null): mixed
    {
        if ($this->runtime === null || ! method_exists($this->runtime, 'executeTool')) throw new RuntimeException('The WorkCore AI runtime is not available.');
        return $this->runtime->executeTool($tool, $input, $context, $aiRunPublicId, $confirmationId, $idempotencyKey);
    }

    /** @param array<string,mixed> $input */
    public function executeForApp(string $app, string $tool, array $input, object $context, ?string $aiRunPublicId = null, ?string $confirmationId = null, ?string $idempotencyKey = null): mixed
    {
        $definition = $this->app($app) ?? throw new InvalidArgumentException("Unknown Titan app [{$app}].");
        $wc = (array) ($definition['workcore'] ?? []);
        $allowed = [...(array)($wc['read_tools'] ?? []), ...(array)($wc['action_tools'] ?? [])];
        if (! in_array($tool, $allowed, true)) throw new InvalidArgumentException("WorkCore tool [{$tool}] is not assigned to Titan app [{$app}].");
        $input['_titan_app'] = $app;
        return $this->execute($tool, $input, $context, $aiRunPublicId, $confirmationId, $idempotencyKey);
    }

    private function loadManifests(): void
    {
        foreach (glob(__DIR__ . '/config/*-workcore.json') ?: [] as $file) {
            $payload = json_decode((string) file_get_contents($file), true);
            if (! is_array($payload) || !($payload['workcore_enabled'] ?? false)) continue;
            $slug = (string) ($payload['app_slug'] ?? $payload['slug'] ?? basename($file, '-workcore.json'));
            $this->apps[$slug] = $payload;
        }
        ksort($this->apps);
    }
}

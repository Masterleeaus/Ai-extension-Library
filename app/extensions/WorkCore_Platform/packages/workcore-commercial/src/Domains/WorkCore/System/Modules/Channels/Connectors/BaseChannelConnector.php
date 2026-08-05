<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Connectors;

use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseChannelConnector
{
    protected Channel $channel;
    protected int $maxRetries = 3;
    protected int $retryDelay = 1000; // milliseconds
    protected float $backoffMultiplier = 2.0;

    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    abstract public function authenticate(): bool;
    abstract public function test(): array;
    abstract public function syncInventory(): array;
    abstract public function syncOrders(): array;
    abstract public function syncPricing(): array;

    protected function getAuth(): array
    {
        return [
            'api_token' => $this->channel->api_token,
            'api_secret' => $this->channel->api_secret,
            'credentials' => $this->channel->credentials,
        ];
    }

    protected function makeRequest(string $method, string $url, array $options = []): array
    {
        $attempt = 0;
        $delay = $this->retryDelay;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders($this->getDefaultHeaders())
                    ->{$method}($url, $options);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                if ($response->status() >= 500 || $response->status() === 429) {
                    $attempt++;
                    if ($attempt < $this->maxRetries) {
                        usleep($delay * 1000);
                        $delay = (int)($delay * $this->backoffMultiplier);
                        continue;
                    }
                }

                Log::error("Channel API Error", [
                    'channel' => $this->channel->type,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['error' => $response->body()];
            } catch (\Exception $e) {
                $attempt++;
                if ($attempt < $this->maxRetries) {
                    usleep($delay * 1000);
                    $delay = (int)($delay * $this->backoffMultiplier);
                    continue;
                }

                Log::error("Channel Request Exception", [
                    'channel' => $this->channel->type,
                    'url' => $url,
                    'error' => $e->getMessage(),
                ]);

                return ['error' => $e->getMessage()];
            }
        }

        return ['error' => 'Max retries exceeded'];
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }

    protected function log(string $message, array $context = []): void
    {
        Log::info("Channel [{$this->channel->type}]: {$message}", [
            'channel_id' => $this->channel->id,
            'tenant_id' => $this->channel->tenant_id,
            ...$context,
        ]);
    }

    protected function logError(string $message, array $context = []): void
    {
        Log::error("Channel [{$this->channel->type}]: {$message}", [
            'channel_id' => $this->channel->id,
            'tenant_id' => $this->channel->tenant_id,
            ...$context,
        ]);
    }

    protected function exponentialBackoff(int $attempt, int $baseDelay = 1000): int
    {
        $delay = $baseDelay * pow($this->backoffMultiplier, $attempt);
        // Add jitter: random value between 0 and delay
        return (int)($delay + (mt_rand(0, 1000) / 1000 * $delay));
    }
}

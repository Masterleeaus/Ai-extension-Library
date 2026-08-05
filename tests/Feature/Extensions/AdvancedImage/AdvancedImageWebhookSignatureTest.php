<?php

declare(strict_types=1);

use App\Extensions\AdvancedImage\System\AdvancedImageServiceProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['services.freepik.webhook_secret' => 'test-freepik-secret']);
    Cache::flush();
    $this->app->register(AdvancedImageServiceProvider::class);

    if (! Schema::hasTable('user_openai')) {
        Schema::create('user_openai', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('openai_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('request_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->text('input')->nullable();
            $table->text('response')->nullable();
            $table->text('output')->nullable();
            $table->text('hash')->nullable();
            $table->string('credits')->nullable();
            $table->string('words')->nullable();
            $table->string('storage')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->nullable();
            $table->string('model')->nullable();
            $table->string('engine')->nullable();
            $table->boolean('is_advanced_image')->default(true);
            $table->timestamps();
        });
    }
});

function advancedImageWebhookTaskFor(User $user, string $provider = 'freepik'): int
{
    return (int) DB::table('user_openai')->insertGetId([
        'user_id' => $user->getKey(),
        'request_id' => "{$provider}-task-{$user->getKey()}",
        'title' => 'Webhook task',
        'slug' => "webhook-task-{$user->getKey()}",
        'input' => 'Private image prompt',
        'response' => 'CD',
        'output' => null,
        'hash' => "hash-{$user->getKey()}",
        'credits' => '1',
        'words' => '0',
        'storage' => 'public',
        'payload' => json_encode([
            'taskId' => "{$provider}-task-{$user->getKey()}",
            'model' => $provider,
        ], JSON_THROW_ON_ERROR),
        'status' => 'IN_PROGRESS',
        'model' => $provider,
        'engine' => $provider,
        'is_advanced_image' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function signedFreepikWebhook(array $payload, string $webhookId, int $timestamp, string $secret = 'test-freepik-secret'): array
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $content = "{$webhookId}.{$timestamp}.{$body}";
    $signature = base64_encode(hash_hmac('sha256', $content, $secret, true));

    return [$body, [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_ACCEPT' => 'application/json',
        'HTTP_WEBHOOK_ID' => $webhookId,
        'HTTP_WEBHOOK_TIMESTAMP' => (string) $timestamp,
        'HTTP_WEBHOOK_SIGNATURE' => 'v1,' . $signature,
    ]];
}

test('unsigned Freepik callbacks are rejected before task mutation', function (): void {
    $owner = User::factory()->create();
    $taskId = advancedImageWebhookTaskFor($owner);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;

    $this->postJson('/api/webhook/advanced-image/freepik', [
        'request_id' => $requestId,
        'status' => 'IN_PROGRESS',
    ])->assertForbidden();

    expect(DB::table('user_openai')->find($taskId)->status)->toBe('IN_PROGRESS');
});

test('invalid Freepik signatures are rejected', function (): void {
    $payload = ['task_id' => 'forged-task', 'status' => 'IN_PROGRESS'];
    [$body, $server] = signedFreepikWebhook($payload, 'webhook-invalid', now()->timestamp, 'wrong-secret');

    $this->call('POST', '/api/webhook/advanced-image/freepik', [], [], [], $server, $body)
        ->assertForbidden();
});

test('stale Freepik callbacks are rejected', function (): void {
    $payload = ['task_id' => 'stale-task', 'status' => 'IN_PROGRESS'];
    [$body, $server] = signedFreepikWebhook($payload, 'webhook-stale', now()->subMinutes(10)->timestamp);

    $this->call('POST', '/api/webhook/advanced-image/freepik', [], [], [], $server, $body)
        ->assertForbidden();
});

test('a valid Freepik webhook ID can only be processed once', function (): void {
    $owner = User::factory()->create();
    $taskId = advancedImageWebhookTaskFor($owner);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;
    $payload = ['task_id' => $requestId, 'status' => 'IN_PROGRESS'];
    [$body, $server] = signedFreepikWebhook($payload, 'webhook-replay', now()->timestamp);

    $this->call('POST', '/api/webhook/advanced-image/freepik', [], [], [], $server, $body)
        ->assertOk();

    $this->call('POST', '/api/webhook/advanced-image/freepik', [], [], [], $server, $body)
        ->assertStatus(409);
});

test('a valid Freepik callback accepts the current task_id field', function (): void {
    $owner = User::factory()->create();
    $taskId = advancedImageWebhookTaskFor($owner);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;
    $payload = ['task_id' => $requestId, 'status' => 'IN_PROGRESS'];
    [$body, $server] = signedFreepikWebhook($payload, 'webhook-valid', now()->timestamp);

    $this->call('POST', '/api/webhook/advanced-image/freepik', [], [], [], $server, $body)
        ->assertOk();
});

test('authenticated Freepik callbacks cannot target non-Freepik tasks', function (): void {
    $owner = User::factory()->create();
    $taskId = advancedImageWebhookTaskFor($owner, 'novita');
    $requestId = DB::table('user_openai')->find($taskId)->request_id;
    $payload = ['request_id' => $requestId, 'status' => 'IN_PROGRESS'];
    [$body, $server] = signedFreepikWebhook($payload, 'webhook-wrong-provider', now()->timestamp);

    $this->call('POST', '/api/webhook/advanced-image/freepik', [], [], [], $server, $body)
        ->assertNotFound();
});

test('Novita callbacks fail closed and use status polling instead', function (): void {
    $this->postJson('/api/webhook/advanced-image/novita', [
        'payload' => [
            'task' => ['task_id' => 'novita-task'],
            'images' => [],
        ],
    ])->assertForbidden();
});

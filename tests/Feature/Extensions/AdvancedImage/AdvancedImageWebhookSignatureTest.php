<?php

declare(strict_types=1);

use App\Extensions\AdvancedImage\System\AdvancedImageServiceProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function (): void {
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

function advancedImageWebhookTaskFor(User $user, string $provider, string $token): int
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
            'webhookToken' => $token,
        ], JSON_THROW_ON_ERROR),
        'status' => 'IN_PROGRESS',
        'model' => $provider,
        'engine' => $provider,
        'is_advanced_image' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('unsigned AdvancedImage callbacks are rejected before task mutation', function (): void {
    $owner = User::factory()->create();
    $token = str_repeat('a', 64);
    $taskId = advancedImageWebhookTaskFor($owner, 'freepik', $token);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;

    $this->postJson('/api/webhook/advanced-image/freepik', [
        'request_id' => $requestId,
        'status' => 'IN_PROGRESS',
    ])->assertForbidden();

    expect(DB::table('user_openai')->find($taskId)->status)->toBe('IN_PROGRESS');
});

test('tampering with the signed provider model invalidates the callback', function (): void {
    $token = str_repeat('b', 64);
    $signedUrl = URL::signedRoute('webhook.advanced-image', [
        'model' => 'freepik',
        'token' => $token,
    ]);
    $tamperedUrl = str_replace('/freepik?', '/novita?', $signedUrl);

    $this->postJson($tamperedUrl, [
        'payload' => ['task' => ['task_id' => 'forged-task']],
    ])->assertForbidden();
});

test('a signed callback cannot be replayed against a task with another token', function (): void {
    $owner = User::factory()->create();
    $signedToken = str_repeat('c', 64);
    $otherToken = str_repeat('d', 64);
    $taskId = advancedImageWebhookTaskFor($owner, 'freepik', $otherToken);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;
    $signedUrl = URL::signedRoute('webhook.advanced-image', [
        'model' => 'freepik',
        'token' => $signedToken,
    ]);

    $this->postJson($signedUrl, [
        'request_id' => $requestId,
        'status' => 'IN_PROGRESS',
    ])->assertNotFound();
});

test('a valid task-bound Freepik callback reaches the existing handler', function (): void {
    $owner = User::factory()->create();
    $token = str_repeat('e', 64);
    $taskId = advancedImageWebhookTaskFor($owner, 'freepik', $token);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;
    $signedUrl = URL::signedRoute('webhook.advanced-image', [
        'model' => 'freepik',
        'token' => $token,
    ]);

    $this->postJson($signedUrl, [
        'request_id' => $requestId,
        'status' => 'IN_PROGRESS',
    ])->assertOk();
});

test('a valid task-bound Novita callback reaches the existing handler', function (): void {
    $owner = User::factory()->create();
    $token = str_repeat('f', 64);
    $taskId = advancedImageWebhookTaskFor($owner, 'novita', $token);
    $requestId = DB::table('user_openai')->find($taskId)->request_id;
    $signedUrl = URL::signedRoute('webhook.advanced-image', [
        'model' => 'novita',
        'token' => $token,
    ]);

    $this->postJson($signedUrl, [
        'payload' => [
            'task' => ['task_id' => $requestId],
            'images' => [],
        ],
    ])->assertOk();
});

<?php

declare(strict_types=1);

use App\Extensions\CreativeSuite\System\CreativeSuiteServiceProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->register(CreativeSuiteServiceProvider::class);

    if (! Schema::hasTable('user_openai')) {
        Schema::create('user_openai', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('openai_id')->nullable();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('request_id')->nullable();
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
            $table->timestamps();
        });
    }
});

function creativeSuiteAITaskFor(User $user, array $attributes = []): int
{
    return (int) DB::table('user_openai')->insertGetId(array_merge([
        'user_id' => $user->getKey(),
        'request_id' => 'task-' . $user->getKey(),
        'title' => 'Creative task',
        'slug' => 'creative-task-' . $user->getKey(),
        'input' => 'Private prompt',
        'response' => 'CD',
        'output' => null,
        'hash' => 'hash-' . $user->getKey(),
        'credits' => '1',
        'words' => '0',
        'storage' => 'public',
        'payload' => json_encode([
            'taskId' => 'task-' . $user->getKey(),
            'tool' => 'edit_with_ai',
            'model' => 'flux-pro/kontext',
        ], JSON_THROW_ON_ERROR),
        'status' => 'COMPLETED',
        'model' => 'flux-pro/kontext',
        'engine' => 'fal-ai',
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

test('a user cannot inspect another users CreativeSuite AI task', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $taskId = creativeSuiteAITaskFor($owner);

    $this->actingAs($attacker)
        ->get("/dashboard/user/creative-suite/ai/editor/{$taskId}/status")
        ->assertNotFound();
});

test('a cross-user status request does not mutate the CreativeSuite AI task', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $taskId = creativeSuiteAITaskFor($owner, [
        'status' => 'COMPLETED',
        'output' => '/uploads/creative-suite/private.png',
    ]);

    $before = DB::table('user_openai')->find($taskId);

    $this->actingAs($attacker)
        ->get("/dashboard/user/creative-suite/ai/editor/{$taskId}/status")
        ->assertNotFound();

    $after = DB::table('user_openai')->find($taskId);

    expect($after->status)->toBe($before->status)
        ->and($after->output)->toBe($before->output)
        ->and($after->updated_at)->toBe($before->updated_at);
});

test('missing CreativeSuite AI task IDs fail closed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/user/creative-suite/ai/editor/999999/status')
        ->assertNotFound();
});

test('an owner can inspect their completed CreativeSuite AI task', function (): void {
    $owner = User::factory()->create();
    $taskId = creativeSuiteAITaskFor($owner);

    $this->actingAs($owner)
        ->get("/dashboard/user/creative-suite/ai/editor/{$taskId}/status")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $taskId)
        ->assertJsonPath('data.user_id', $owner->getKey());
});

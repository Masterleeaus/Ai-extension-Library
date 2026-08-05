<?php

declare(strict_types=1);

use App\Extensions\AdvancedImage\System\AdvancedImageServiceProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->register(AdvancedImageServiceProvider::class);

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

function advancedImageTaskFor(User $user, array $attributes = []): int
{
    return (int) DB::table('user_openai')->insertGetId(array_merge([
        'user_id' => $user->getKey(),
        'request_id' => 'advanced-image-' . $user->getKey(),
        'title' => 'Advanced image task',
        'slug' => 'advanced-image-task-' . $user->getKey(),
        'input' => 'Private image prompt',
        'response' => 'CD',
        'output' => null,
        'hash' => 'hash-' . $user->getKey(),
        'credits' => '1',
        'words' => '0',
        'storage' => 'public',
        'payload' => json_encode([
            'taskId' => 'advanced-image-' . $user->getKey(),
            'model' => 'freepik',
        ], JSON_THROW_ON_ERROR),
        'status' => 'COMPLETED',
        'model' => 'freepik',
        'engine' => 'freepik',
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

test('a user cannot inspect another users AdvancedImage task', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $taskId = advancedImageTaskFor($owner);

    $this->actingAs($attacker)
        ->get("/dashboard/user/advanced-image/editor/{$taskId}/status")
        ->assertNotFound();
});

test('a cross-user status request does not mutate an AdvancedImage task', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $taskId = advancedImageTaskFor($owner, [
        'output' => '/uploads/advanced-image/private.png',
    ]);

    $before = DB::table('user_openai')->find($taskId);

    $this->actingAs($attacker)
        ->get("/dashboard/user/advanced-image/editor/{$taskId}/status")
        ->assertNotFound();

    $after = DB::table('user_openai')->find($taskId);

    expect($after->status)->toBe($before->status)
        ->and($after->output)->toBe($before->output)
        ->and($after->updated_at)->toBe($before->updated_at);
});

test('missing AdvancedImage task IDs fail closed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard/user/advanced-image/editor/999999/status')
        ->assertNotFound();
});

test('an owner can inspect their completed AdvancedImage task', function (): void {
    $owner = User::factory()->create();
    $taskId = advancedImageTaskFor($owner);

    $this->actingAs($owner)
        ->get("/dashboard/user/advanced-image/editor/{$taskId}/status")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $taskId)
        ->assertJsonPath('data.user_id', $owner->getKey());
});

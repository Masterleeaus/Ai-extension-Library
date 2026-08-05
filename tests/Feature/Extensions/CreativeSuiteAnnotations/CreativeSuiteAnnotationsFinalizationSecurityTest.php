<?php

declare(strict_types=1);

use App\Extensions\CreativeSuiteAnnotations\System\CreativeSuiteAnnotationsServiceProvider;
use App\Extensions\CreativeSuiteAnnotations\System\Jobs\ProcessAnnotationEditJob;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->register(CreativeSuiteAnnotationsServiceProvider::class);

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

function creativeSuiteAnnotationsTaskFor(User $user, array $attributes = []): int
{
    return (int) DB::table('user_openai')->insertGetId(array_merge([
        'user_id' => $user->getKey(),
        'request_id' => 'annotations-' . $user->getKey(),
        'title' => 'Annotation task',
        'slug' => 'annotation-' . $user->getKey() . '-annotation',
        'input' => 'Private annotation prompt',
        'response' => 'CD',
        'output' => '/uploads/creative-suite-annotations/result.png',
        'hash' => 'annotations-hash-' . $user->getKey(),
        'credits' => '1',
        'words' => '0',
        'storage' => 'public',
        'payload' => json_encode([
            'taskId' => 'annotations-' . $user->getKey(),
            'model' => 'gpt-image-2',
            'credit_cost' => 0,
        ], JSON_THROW_ON_ERROR),
        'status' => 'COMPLETED',
        'model' => 'gpt-image-2',
        'engine' => 'openai',
        'created_at' => now(),
        'updated_at' => now(),
    ], $attributes));
}

test('a user cannot poll another users annotation task', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $taskId = creativeSuiteAnnotationsTaskFor($owner);

    $this->actingAs($attacker)
        ->get("/dashboard/user/creative-suite-annotations/edit/{$taskId}/status")
        ->assertNotFound();
});

test('an owner can poll their completed annotation task', function (): void {
    $owner = User::factory()->create();
    $taskId = creativeSuiteAnnotationsTaskFor($owner);

    $this->actingAs($owner)
        ->get("/dashboard/user/creative-suite-annotations/edit/{$taskId}/status")
        ->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.id', $taskId)
        ->assertJsonPath('data.user_id', $owner->getKey());
});

test('the queued job captures the authenticated owner without changing existing call sites', function (): void {
    $owner = User::factory()->create();
    $this->actingAs($owner);

    $job = new ProcessAnnotationEditJob(
        123,
        'prompt',
        'gpt-image-2',
        '',
        null,
        0,
    );

    expect($job->userId)->toBe((int) $owner->getKey());
});

test('a mismatched queued owner cannot fail another users annotation task', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $taskId = creativeSuiteAnnotationsTaskFor($owner);
    $before = DB::table('user_openai')->find($taskId);

    $job = new ProcessAnnotationEditJob(
        $taskId,
        'prompt',
        'gpt-image-2',
        '',
        null,
        0,
        (int) $attacker->getKey(),
    );

    $job->failed(new RuntimeException('forged failure'));

    $after = DB::table('user_openai')->find($taskId);

    expect($after->status)->toBe($before->status)
        ->and($after->payload)->toBe($before->payload)
        ->and($after->updated_at)->toBe($before->updated_at);
});

test('the matching queued owner can mark their annotation task failed', function (): void {
    $owner = User::factory()->create();
    $taskId = creativeSuiteAnnotationsTaskFor($owner, [
        'status' => 'IN_PROGRESS',
        'output' => null,
    ]);

    $job = new ProcessAnnotationEditJob(
        $taskId,
        'prompt',
        'gpt-image-2',
        '',
        null,
        0,
        (int) $owner->getKey(),
    );

    $job->failed(new RuntimeException('provider failed'));

    $task = DB::table('user_openai')->find($taskId);
    $payload = json_decode((string) $task->payload, true, flags: JSON_THROW_ON_ERROR);

    expect($task->status)->toBe('FAILED')
        ->and($payload['error_message'])->toBe('provider failed');
});

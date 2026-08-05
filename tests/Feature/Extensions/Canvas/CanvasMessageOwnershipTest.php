<?php

declare(strict_types=1);

use App\Extensions\Canvas\System\CanvasServiceProvider;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->register(CanvasServiceProvider::class);

    if (! Schema::hasTable('user_openai_chat_messages')) {
        Schema::create('user_openai_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_openai_chat_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('input')->nullable();
            $table->text('response')->nullable();
            $table->text('output')->nullable();
            $table->text('hash')->nullable();
            $table->string('credits')->nullable();
            $table->string('words')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('user_tiptap_contents')) {
        Schema::create('user_tiptap_contents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('save_contentable_id');
            $table->string('save_contentable_type');
            $table->string('title')->nullable();
            $table->text('input')->nullable();
            $table->text('output')->nullable();
            $table->timestamps();
        });
    }
});

function canvasMessageFor(User $user): int
{
    return (int) DB::table('user_openai_chat_messages')->insertGetId([
        'user_id' => $user->getKey(),
        'input' => 'Original message',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('a user cannot write Canvas content to another users message', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $messageId = canvasMessageFor($owner);

    $this->actingAs($attacker)
        ->post('/tiptap-content-store', [
            'message_id' => $messageId,
            'type' => 'input',
            'content' => '<p>Hijacked</p>',
        ])
        ->assertNotFound();

    expect(DB::table('user_tiptap_contents')->count())->toBe(0);
});

test('a user cannot title Canvas content attached to another users message', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $messageId = canvasMessageFor($owner);

    $this->actingAs($attacker)
        ->post('/tiptap-title-save', [
            'message_id' => $messageId,
            'title' => 'Hijacked title',
        ])
        ->assertNotFound();

    expect(DB::table('user_tiptap_contents')->count())->toBe(0);
});

test('missing Canvas message IDs fail closed', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/tiptap-content-store', [
            'message_id' => 999999,
            'type' => 'output',
            'content' => '<p>Missing</p>',
        ])
        ->assertNotFound();
});

test('an owner can save input and output Canvas content', function (): void {
    $owner = User::factory()->create();
    $messageId = canvasMessageFor($owner);

    $this->actingAs($owner)
        ->post('/tiptap-content-store', [
            'message_id' => $messageId,
            'type' => 'input',
            'content' => '<p>Owner input</p>',
        ])
        ->assertOk();

    $this->actingAs($owner)
        ->post('/tiptap-content-store', [
            'message_id' => $messageId,
            'type' => 'output',
            'content' => '<p>Owner output</p>',
        ])
        ->assertOk();

    $content = DB::table('user_tiptap_contents')->sole();

    expect((int) $content->user_id)->toBe($owner->getKey())
        ->and($content->input)->toBe('<p>Owner input</p>')
        ->and($content->output)->toBe('<p>Owner output</p>');
});

test('an owner can save a Canvas title', function (): void {
    $owner = User::factory()->create();
    $messageId = canvasMessageFor($owner);

    $this->actingAs($owner)
        ->post('/tiptap-title-save', [
            'message_id' => $messageId,
            'title' => 'Owner title',
        ])
        ->assertOk();

    $content = DB::table('user_tiptap_contents')->sole();

    expect((int) $content->user_id)->toBe($owner->getKey())
        ->and($content->title)->toBe('Owner title');
});

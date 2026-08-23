<?php

declare(strict_types=1);

use App\Extensions\CreativeSuite\System\CreativeSuiteServiceProvider;
use App\Extensions\CreativeSuite\System\Models\CreativeSuiteDocument;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->register(CreativeSuiteServiceProvider::class);

    if (! Schema::hasTable('ext_creative_suite_documents')) {
        Schema::create('ext_creative_suite_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->string('preview')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    Storage::fake('uploads');
});

function creativeSuiteDocumentFor(User $user, array $attributes = []): CreativeSuiteDocument
{
    return CreativeSuiteDocument::query()->create(array_merge([
        'user_id' => $user->getKey(),
        'uuid' => (string) Str::uuid(),
        'name' => 'Owner document',
        'preview' => null,
        'payload' => json_encode(['version' => 1], JSON_THROW_ON_ERROR),
    ], $attributes));
}

test('a user cannot read another users CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($attacker)
        ->get("/dashboard/user/creative-suite/document/{$document->getKey()}")
        ->assertNotFound();
});

test('a user cannot update another users CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($attacker)
        ->post('/dashboard/user/creative-suite/document', [
            'id' => $document->getKey(),
            'name' => 'Hijacked',
            'payload' => json_encode(['version' => 2], JSON_THROW_ON_ERROR),
            'preview' => UploadedFile::fake()->image('preview.png'),
        ])
        ->assertNotFound();

    expect($document->fresh()->name)->toBe('Owner document');
});

test('a user cannot duplicate another users CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($attacker)
        ->post('/dashboard/user/creative-suite/document/duplicate', ['id' => $document->getKey()])
        ->assertNotFound();

    expect(CreativeSuiteDocument::query()->count())->toBe(1);
});

test('a user cannot rename another users CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($attacker)
        ->post('/dashboard/user/creative-suite/document/name', [
            'id' => $document->getKey(),
            'name' => 'Hijacked',
        ])
        ->assertNotFound();

    expect($document->fresh()->name)->toBe('Owner document');
});

test('a user cannot delete another users CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($attacker)
        ->post('/dashboard/user/creative-suite/document/delete', ['id' => $document->getKey()])
        ->assertNotFound();

    expect($document->fresh())->not->toBeNull();
});

test('an owner can read and rename their CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($owner)
        ->get("/dashboard/user/creative-suite/document/{$document->getKey()}")
        ->assertOk();

    $this->actingAs($owner)
        ->post('/dashboard/user/creative-suite/document/name', [
            'id' => $document->getKey(),
            'name' => 'Renamed by owner',
        ])
        ->assertOk();

    expect($document->fresh()->name)->toBe('Renamed by owner');
});

test('an owner can update their CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($owner)
        ->post('/dashboard/user/creative-suite/document', [
            'id' => $document->getKey(),
            'name' => 'Updated by owner',
            'payload' => json_encode(['version' => 2], JSON_THROW_ON_ERROR),
            'preview' => UploadedFile::fake()->image('preview.png'),
        ])
        ->assertOk();

    expect($document->fresh()->name)->toBe('Updated by owner');
});

test('an owner can duplicate their CreativeSuite document and retains ownership', function (): void {
    $owner = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($owner)
        ->post('/dashboard/user/creative-suite/document/duplicate', ['id' => $document->getKey()])
        ->assertOk();

    $copy = CreativeSuiteDocument::query()
        ->whereKeyNot($document->getKey())
        ->sole();

    expect($copy->user_id)->toBe($owner->getKey())
        ->and($copy->name)->toBe('Owner document (Copy)');
});

test('an owner can delete their CreativeSuite document', function (): void {
    $owner = User::factory()->create();
    $document = creativeSuiteDocumentFor($owner);

    $this->actingAs($owner)
        ->post('/dashboard/user/creative-suite/document/delete', ['id' => $document->getKey()])
        ->assertOk();

    expect(CreativeSuiteDocument::query()->find($document->getKey()))->toBeNull();
});

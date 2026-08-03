<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unified_memories', function (Blueprint $table): void {
            $table->id();
            $table->string('scope', 32);
            $table->string('entity_type', 96);
            $table->string('entity_id', 191);
            $table->string('key', 191);
            $table->longText('value')->nullable();
            $table->string('source', 64)->nullable();
            $table->unsignedInteger('ttl_minutes')->nullable();
            $table->timestamps();

            $table->unique(
                ['scope', 'entity_type', 'entity_id', 'key'],
                'unified_memories_identity_unique',
            );
            $table->index('source', 'unified_memories_source_index');
            $table->index(['ttl_minutes', 'updated_at'], 'unified_memories_expiry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unified_memories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_catalog_categories')) {
            return;
        }

        Schema::create('workcore_catalog_categories', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('workcore_catalog_categories')->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'parent_id', 'is_active']);
            $table->unique(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_catalog_categories');
    }
};

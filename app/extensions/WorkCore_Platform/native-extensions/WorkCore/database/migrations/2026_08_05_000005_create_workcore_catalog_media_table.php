<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_catalog_media')) {
            return;
        }

        Schema::create('workcore_catalog_media', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('workcore_catalog_products')->cascadeOnDelete();
            $table->string('media_type', 50); // image, video, document
            $table->string('url', 500);
            $table->string('alt_text', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'media_type']);
            $table->index(['company_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_catalog_media');
    }
};

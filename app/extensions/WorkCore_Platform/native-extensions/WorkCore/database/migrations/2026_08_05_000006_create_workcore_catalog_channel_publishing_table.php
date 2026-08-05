<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_catalog_channel_publishing')) {
            return;
        }

        Schema::create('workcore_catalog_channel_publishing', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('workcore_catalog_products')->cascadeOnDelete();
            $table->string('channel_name', 100); // web, app, pos, marketplace
            $table->boolean('is_published')->default(false);
            $table->decimal('channel_price', 12, 2)->nullable();
            $table->string('channel_sku', 100)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('unpublished_at')->nullable();
            $table->json('channel_metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'channel_name']);
            $table->unique(['product_id', 'channel_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_catalog_channel_publishing');
    }
};

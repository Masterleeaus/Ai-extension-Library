<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_catalog_product_variants')) {
            return;
        }

        Schema::create('workcore_catalog_product_variants', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('workcore_catalog_products')->cascadeOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('variant_name', 255);
            $table->json('variant_attributes')->nullable(); // e.g., {size: 'M', color: 'red'}
            $table->decimal('price_modifier', 8, 2)->default(0);
            $table->decimal('cost_modifier', 8, 2)->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'is_active']);
            $table->index('sku');
            $table->unique(['product_id', 'variant_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_catalog_product_variants');
    }
};

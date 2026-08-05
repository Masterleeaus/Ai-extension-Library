<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_catalog_products')) {
            return;
        }

        Schema::create('workcore_catalog_products', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('workcore_catalog_categories')->nullOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('sku', 100)->unique();
            $table->decimal('base_price', 12, 2);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('AUD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->integer('stock_quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->string('product_type', 50)->default('standard'); // standard, variable, bundle, service
            $table->json('attributes')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['company_id', 'is_active', 'is_published']);
            $table->index(['category_id', 'is_active']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_catalog_products');
    }
};

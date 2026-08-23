<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_catalog_service_packages')) {
            return;
        }

        Schema::create('workcore_catalog_service_packages', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2);
            $table->string('currency', 3)->default('AUD');
            $table->boolean('is_active')->default(true);
            $table->json('services')->nullable(); // Array of service/product IDs
            $table->json('addons')->nullable(); // Array of optional add-on services
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_catalog_service_packages');
    }
};

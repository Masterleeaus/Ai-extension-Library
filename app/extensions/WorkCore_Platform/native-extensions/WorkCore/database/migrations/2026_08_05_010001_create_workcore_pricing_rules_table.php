<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_pricing_rules')) {
            return;
        }

        Schema::create('workcore_pricing_rules', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->json('conditions'); // JSON structure for rule conditions
            $table->json('adjustments'); // JSON structure for price adjustments
            $table->integer('priority')->default(100);
            $table->boolean('is_active')->default(true);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->string('rule_type', 50); // demand, seasonal, occupancy, time_based, custom
            $table->decimal('min_adjustment', 10, 2)->nullable();
            $table->decimal('max_adjustment', 10, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['company_id', 'is_active', 'priority']);
            $table->index(['rule_type', 'is_active']);
            $table->index('priority');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_pricing_rules');
    }
};

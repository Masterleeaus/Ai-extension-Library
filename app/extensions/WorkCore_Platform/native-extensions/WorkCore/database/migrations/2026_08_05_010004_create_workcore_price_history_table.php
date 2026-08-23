<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_price_history')) {
            return;
        }

        Schema::create('workcore_price_history', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->morphs('resource'); // Polymorphic for different resource types
            $table->decimal('original_price', 12, 2);
            $table->decimal('adjusted_price', 12, 2);
            $table->decimal('adjustment_amount', 12, 2);
            $table->decimal('adjustment_percentage', 5, 2);
            $table->string('reason', 100); // demand, seasonal, occupancy, manual, etc.
            $table->json('rule_details')->nullable(); // Which rule applied
            $table->json('factors')->nullable(); // Contributing factors
            $table->dateTime('effective_from');
            $table->dateTime('effective_to')->nullable();
            $table->string('status', 30)->default('active'); // active, expired, superseded
            $table->foreignId('applied_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'created_at']);
            $table->index(['resource_type', 'resource_id', 'created_at']);
            $table->index(['reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_price_history');
    }
};

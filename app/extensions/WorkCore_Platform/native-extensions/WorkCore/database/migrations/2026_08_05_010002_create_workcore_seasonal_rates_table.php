<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_seasonal_rates')) {
            return;
        }

        Schema::create('workcore_seasonal_rates', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->string('season_name', 100);
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('multiplier', 5, 2); // e.g., 1.5 for 50% increase
            $table->json('special_dates')->nullable(); // Override specific dates
            $table->boolean('is_active')->default(true);
            $table->string('season_type', 50)->default('custom'); // peak, off_season, holiday, custom
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_seasonal_rates');
    }
};

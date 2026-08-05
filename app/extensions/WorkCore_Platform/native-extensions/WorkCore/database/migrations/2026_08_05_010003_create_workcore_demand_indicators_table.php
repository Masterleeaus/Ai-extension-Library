<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_demand_indicators')) {
            return;
        }

        Schema::create('workcore_demand_indicators', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->morphs('resource'); // Polymorphic for different resource types
            $table->integer('booking_count')->default(0);
            $table->integer('search_count')->default(0);
            $table->integer('inquiry_count')->default(0);
            $table->decimal('cancellation_rate', 5, 2)->default(0); // Percentage
            $table->decimal('booking_velocity', 8, 2)->default(0); // Bookings per hour
            $table->decimal('demand_score', 5, 2)->default(0); // 0-100 scale
            $table->string('demand_level', 30)->default('normal'); // low, normal, high, critical
            $table->dateTime('recorded_at');
            $table->dateTime('calculation_at')->nullable();
            $table->json('factors')->nullable(); // Detailed breakdown
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'recorded_at']);
            $table->index(['resource_type', 'resource_id', 'recorded_at']);
            $table->index('demand_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_demand_indicators');
    }
};

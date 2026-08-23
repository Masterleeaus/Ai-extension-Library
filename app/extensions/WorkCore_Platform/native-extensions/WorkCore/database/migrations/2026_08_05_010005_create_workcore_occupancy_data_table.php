<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workcore_occupancy_data')) {
            return;
        }

        Schema::create('workcore_occupancy_data', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained('tz_companies')->cascadeOnDelete();
            $table->morphs('resource'); // Polymorphic for different resource types
            $table->integer('current_occupancy');
            $table->integer('capacity');
            $table->decimal('occupancy_percentage', 5, 2);
            $table->integer('available_units');
            $table->integer('reserved_units')->default(0);
            $table->integer('pending_bookings')->default(0);
            $table->dateTime('recorded_at');
            $table->string('occupancy_level', 30)->default('normal'); // low, normal, high, critical
            $table->json('forecast')->nullable(); // Next 7/30 days forecast
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'recorded_at']);
            $table->index(['resource_type', 'resource_id', 'recorded_at']);
            $table->index('occupancy_percentage');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_occupancy_data');
    }
};

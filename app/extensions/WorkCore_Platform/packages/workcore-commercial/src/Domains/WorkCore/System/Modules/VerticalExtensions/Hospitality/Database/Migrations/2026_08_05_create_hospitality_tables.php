<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guest Profiles table
        Schema::create('hospitality_guest_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->json('preferences')->nullable();
            $table->json('allergies')->nullable();
            $table->json('dietary_restrictions')->nullable();
            $table->json('special_requests')->nullable();
            $table->string('language_preference')->default('en');
            $table->string('communication_preference')->default('email');
            $table->string('loyalty_program_id')->nullable();
            $table->string('guest_type')->default('individual');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'customer_id']);
        });

        // Room Inventories table
        Schema::create('hospitality_room_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('room_number')->unique();
            $table->string('room_type');
            $table->integer('floor')->nullable();
            $table->integer('capacity')->default(1);
            $table->json('amenities')->nullable();
            $table->json('features')->nullable();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cleaning_cost', 10, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->string('status')->default('available');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'room_type']);
            $table->index(['is_available', 'status']);
        });

        // Accommodation Stays table
        Schema::create('hospitality_accommodation_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_inventory_id')->constrained('hospitality_room_inventories')->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained('customers')->cascadeOnDelete();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('guest_count')->default(1);
            $table->decimal('price_per_night', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->string('status')->default('confirmed');
            $table->string('channel_source')->nullable();
            $table->string('reservation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'check_in_date', 'check_out_date']);
            $table->index(['room_inventory_id', 'status']);
            $table->index(['guest_id', 'check_in_date']);
        });

        // Cleaning Schedules table
        Schema::create('hospitality_cleaning_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_inventory_id')->constrained('hospitality_room_inventories')->cascadeOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained('workers')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->string('cleaning_type')->default('turnover');
            $table->string('status')->default('scheduled');
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['scheduled_date', 'status']);
            $table->index(['room_inventory_id', 'scheduled_date']);
        });

        // Channel Mappings table
        Schema::create('hospitality_channel_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_inventory_id')->constrained('hospitality_room_inventories')->cascadeOnDelete();
            $table->string('channel_name');
            $table->string('channel_property_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('sync_enabled')->default(true);
            $table->dateTime('last_sync_at')->nullable();
            $table->text('credentials')->nullable();
            $table->string('rate_strategy')->default('mirror');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['channel_name', 'channel_property_id']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitality_channel_mappings');
        Schema::dropIfExists('hospitality_cleaning_schedules');
        Schema::dropIfExists('hospitality_accommodation_stays');
        Schema::dropIfExists('hospitality_room_inventories');
        Schema::dropIfExists('hospitality_guest_profiles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Availability Rules table
        Schema::create('booking_availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('resource_id');
            $table->string('rule_type');
            $table->integer('capacity_limit')->nullable();
            $table->integer('buffer_time_minutes')->default(0);
            $table->integer('prep_time_minutes')->default(0);
            $table->json('blocked_dates')->nullable();
            $table->json('blocked_times')->nullable();
            $table->integer('advance_booking_days')->nullable();
            $table->integer('max_booking_duration')->nullable();
            $table->integer('min_booking_duration')->nullable();
            $table->json('rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'resource_id']);
            $table->index(['is_active']);
        });

        // Waitlist Entries table
        Schema::create('booking_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('resource_id');
            $table->date('desired_date');
            $table->time('desired_time')->nullable();
            $table->string('customer_contact')->nullable();
            $table->integer('position_in_queue');
            $table->string('status')->default('waiting');
            $table->dateTime('added_date');
            $table->dateTime('auto_filled_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'resource_id', 'status']);
            $table->index(['customer_id', 'desired_date']);
        });

        // Reservation Reminders table
        Schema::create('booking_reservation_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('reservation_id');
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('reminder_type');
            $table->integer('reminder_time_minutes_before');
            $table->dateTime('scheduled_send_time');
            $table->dateTime('sent_time')->nullable();
            $table->string('communication_channel')->default('email');
            $table->string('status')->default('scheduled');
            $table->boolean('is_sent')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'reservation_id']);
            $table->index(['is_sent', 'scheduled_send_time']);
        });

        // No Show Tracking table
        Schema::create('booking_no_show_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('reservation_id');
            $table->dateTime('expected_datetime');
            $table->dateTime('no_show_datetime')->nullable();
            $table->string('cancellation_status')->nullable();
            $table->string('no_show_reason')->nullable();
            $table->integer('no_show_count_customer')->default(0);
            $table->decimal('penalty_amount', 10, 2)->nullable();
            $table->boolean('penalty_applied')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'customer_id']);
            $table->index(['expected_datetime', 'no_show_datetime']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_no_show_tracking');
        Schema::dropIfExists('booking_reservation_reminders');
        Schema::dropIfExists('booking_waitlist_entries');
        Schema::dropIfExists('booking_availability_rules');
    }
};

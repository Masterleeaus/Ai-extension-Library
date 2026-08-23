<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Membership Tiers table
        Schema::create('fitness_membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('tier_name')->unique();
            $table->string('tier_level')->index();
            $table->decimal('monthly_price', 10, 2);
            $table->decimal('annual_price', 10, 2)->nullable();
            $table->json('features')->nullable();
            $table->json('class_access')->nullable();
            $table->integer('trainer_sessions_included')->default(0);
            $table->json('gym_access_hours')->nullable();
            $table->integer('guest_passes_monthly')->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });

        // Class Schedules table
        Schema::create('fitness_class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('class_name');
            $table->string('class_type');
            $table->foreignId('trainer_id')->nullable()->constrained('workers')->cascadeOnDelete();
            $table->date('scheduled_date')->index();
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('capacity')->default(30);
            $table->integer('current_enrollment')->default(0);
            $table->integer('waitlist_count')->default(0);
            $table->string('difficulty_level')->nullable();
            $table->string('status')->default('scheduled');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'scheduled_date']);
            $table->index(['class_type', 'status']);
        });

        // Attendance Tracking table
        Schema::create('fitness_attendance_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('class_schedule_id')->nullable()->constrained('fitness_class_schedules')->cascadeOnDelete();
            $table->dateTime('check_in_time')->index();
            $table->dateTime('check_out_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('attendance_status')->default('present');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'member_id', 'check_in_time']);
        });

        // Workout Programs table
        Schema::create('fitness_workout_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained('workers')->cascadeOnDelete();
            $table->string('program_name');
            $table->string('program_type');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('duration_weeks')->nullable();
            $table->string('frequency')->nullable();
            $table->text('goal_description')->nullable();
            $table->json('current_progress')->nullable();
            $table->json('exercises')->nullable();
            $table->string('status')->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'member_id']);
            $table->index(['trainer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_workout_programs');
        Schema::dropIfExists('fitness_attendance_tracking');
        Schema::dropIfExists('fitness_class_schedules');
        Schema::dropIfExists('fitness_membership_tiers');
    }
};

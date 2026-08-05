<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Job Sites table
        Schema::create('field_services_job_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('address');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('site_type')->default('residential');
            $table->text('access_instructions')->nullable();
            $table->text('hazard_notes')->nullable();
            $table->text('parking_instructions')->nullable();
            $table->string('gate_code')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('site_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'customer_id']);
            $table->index(['latitude', 'longitude']);
        });

        // Service Visits table
        Schema::create('field_services_service_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_site_id')->constrained('field_services_job_sites')->cascadeOnDelete();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('status')->default('scheduled');
            $table->date('scheduled_date');
            $table->dateTime('arrival_time')->nullable();
            $table->dateTime('completion_time')->nullable();
            $table->integer('duration_minutes')->nullable();
            $table->string('customer_signature_url')->nullable();
            $table->text('notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['job_site_id', 'worker_id']);
            $table->index(['scheduled_date', 'status']);
        });

        // Service Visit Photos table
        Schema::create('field_services_service_visit_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_visit_id')->constrained('field_services_service_visits')->cascadeOnDelete();
            $table->string('photo_type'); // before, after, during, damage
            $table->string('photo_url');
            $table->text('description')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->dateTime('timestamp');
            $table->timestamps();
            $table->index(['service_visit_id', 'photo_type']);
        });

        // Service Checklists table
        Schema::create('field_services_service_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_visit_id')->constrained('field_services_service_visits')->cascadeOnDelete();
            $table->string('item_name');
            $table->text('item_description')->nullable();
            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['service_visit_id', 'is_completed']);
        });

        // Worker Skills table
        Schema::create('field_services_worker_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('skill_category');
            $table->string('certification_name');
            $table->string('certification_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->string('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'skill_category']);
            $table->index(['company_id', 'is_verified']);
            $table->unique(['worker_id', 'certification_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_services_worker_skills');
        Schema::dropIfExists('field_services_service_checklists');
        Schema::dropIfExists('field_services_service_visit_photos');
        Schema::dropIfExists('field_services_service_visits');
        Schema::dropIfExists('field_services_job_sites');
    }
};

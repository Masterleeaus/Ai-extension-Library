<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vehicle Service History table
        Schema::create('automotive_vehicle_service_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('vehicle_vin')->unique();
            $table->string('vehicle_make');
            $table->string('vehicle_model');
            $table->string('vehicle_year');
            $table->integer('mileage')->nullable();
            $table->date('last_service_date')->nullable();
            $table->json('service_records')->nullable();
            $table->json('maintenance_schedule')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'customer_id']);
        });

        // Service Packages table
        Schema::create('automotive_service_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('package_name');
            $table->string('package_type');
            $table->json('service_items')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('estimated_duration_hours', 10, 2)->nullable();
            $table->integer('warranty_period')->nullable();
            $table->json('parts_included')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });

        // Warranty Tracking table
        Schema::create('automotive_warranty_tracking', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('service_record_id');
            $table->string('warranty_type');
            $table->integer('warranty_period_months');
            $table->date('start_date');
            $table->date('end_date');
            $table->json('parts_covered')->nullable();
            $table->boolean('labor_covered')->default(true);
            $table->decimal('warranty_amount', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });

        // Technician Skills table
        Schema::create('automotive_technician_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('skill_name');
            $table->string('certification_level')->nullable();
            $table->string('certification_issuer')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->boolean('verified')->default(false);
            $table->integer('years_of_experience')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'skill_name']);
            $table->index(['company_id', 'verified']);
        });

        // Parts Inventory table
        Schema::create('automotive_parts_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('part_number')->unique();
            $table->string('part_name');
            $table->string('supplier_id')->nullable();
            $table->integer('quantity_on_hand')->default(0);
            $table->integer('reorder_level')->default(10);
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('markup_percentage', 5, 2)->default(30);
            $table->json('compatibility')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'part_number']);
            $table->index(['quantity_on_hand', 'reorder_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automotive_parts_inventory');
        Schema::dropIfExists('automotive_technician_skills');
        Schema::dropIfExists('automotive_warranty_tracking');
        Schema::dropIfExists('automotive_service_packages');
        Schema::dropIfExists('automotive_vehicle_service_history');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rental Agreements table
        Schema::create('hire_rental_rental_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('rental_item_id');
            $table->date('rental_start_date');
            $table->date('rental_end_date');
            $table->decimal('daily_rate', 10, 2);
            $table->integer('total_rental_days');
            $table->decimal('total_rental_price', 10, 2);
            $table->decimal('security_deposit_amount', 10, 2)->nullable();
            $table->decimal('insurance_amount', 10, 2)->nullable();
            $table->json('terms_and_conditions')->nullable();
            $table->string('customer_signature_url')->nullable();
            $table->string('company_signature_url')->nullable();
            $table->string('status')->default('active');
            $table->string('document_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'customer_id']);
            $table->index(['rental_start_date', 'rental_end_date']);
        });

        // Damage Assessments table
        Schema::create('hire_rental_damage_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_agreement_id')->constrained('hire_rental_rental_agreements')->cascadeOnDelete();
            $table->string('assessment_type');
            $table->date('assessment_date');
            $table->text('damage_description')->nullable();
            $table->json('damage_photos')->nullable();
            $table->decimal('estimated_repair_cost', 10, 2)->nullable();
            $table->decimal('actual_repair_cost', 10, 2)->nullable();
            $table->string('assessed_by')->nullable();
            $table->string('status')->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['rental_agreement_id', 'assessment_date']);
        });

        // Late Fee Calculations table
        Schema::create('hire_rental_late_fee_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_agreement_id')->constrained('hire_rental_rental_agreements')->cascadeOnDelete();
            $table->date('return_date');
            $table->integer('days_late')->default(0);
            $table->decimal('daily_late_fee_rate', 10, 2);
            $table->decimal('total_late_fees', 10, 2);
            $table->decimal('max_late_fee_cap', 10, 2)->nullable();
            $table->decimal('final_late_fee', 10, 2);
            $table->string('status')->default('calculated');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['rental_agreement_id', 'return_date']);
        });

        // Insurance Options table
        Schema::create('hire_rental_insurance_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('insurance_name');
            $table->string('coverage_type');
            $table->decimal('daily_premium', 10, 2);
            $table->decimal('max_coverage_amount', 10, 2)->nullable();
            $table->decimal('deductible_amount', 10, 2)->nullable();
            $table->json('coverage_details')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hire_rental_insurance_options');
        Schema::dropIfExists('hire_rental_late_fee_calculations');
        Schema::dropIfExists('hire_rental_damage_assessments');
        Schema::dropIfExists('hire_rental_rental_agreements');
    }
};

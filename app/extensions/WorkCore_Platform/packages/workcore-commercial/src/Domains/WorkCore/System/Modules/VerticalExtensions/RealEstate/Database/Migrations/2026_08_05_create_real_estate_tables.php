<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Property Listings table
        Schema::create('real_estate_property_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('property_address')->index();
            $table->string('property_type');
            $table->integer('bedrooms')->nullable();
            $table->decimal('bathrooms', 3, 1)->nullable();
            $table->float('floor_area')->nullable();
            $table->float('lot_size')->nullable();
            $table->longText('description')->nullable();
            $table->json('features')->nullable();
            $table->decimal('rental_price', 10, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->string('status')->default('available');
            $table->date('listing_date');
            $table->date('expiry_date')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->json('images')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
            $table->index(['property_type', 'listing_date']);
        });

        // Tenant Screenings table
        Schema::create('real_estate_tenant_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('real_estate_property_listings')->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('customers')->cascadeOnDelete();
            $table->date('application_date');
            $table->string('status')->default('pending');
            $table->json('background_check')->nullable();
            $table->json('credit_report')->nullable();
            $table->json('references')->nullable();
            $table->json('income_verification')->nullable();
            $table->json('employment_verification')->nullable();
            $table->decimal('screening_score', 5, 2)->nullable();
            $table->string('approved_by')->nullable();
            $table->date('approval_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['property_listing_id', 'status']);
            $table->index(['applicant_id', 'status']);
        });

        // Lease Agreements table
        Schema::create('real_estate_lease_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('real_estate_property_listings')->cascadeOnDelete();
            $table->foreignId('tenant_id')->constrained('customers')->cascadeOnDelete();
            $table->date('lease_start_date');
            $table->date('lease_end_date');
            $table->decimal('monthly_rent', 10, 2);
            $table->decimal('security_deposit', 10, 2)->nullable();
            $table->json('lease_terms')->nullable();
            $table->string('tenant_signature_url')->nullable();
            $table->string('landlord_signature_url')->nullable();
            $table->string('status')->default('draft');
            $table->string('document_url')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['property_listing_id', 'status']);
            $table->index(['tenant_id', 'lease_start_date']);
        });

        // Maintenance Requests table
        Schema::create('real_estate_maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_listing_id')->constrained('real_estate_property_listings')->cascadeOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('request_type');
            $table->text('description');
            $table->string('severity')->default('normal');
            $table->string('status')->default('open');
            $table->date('requested_date');
            $table->date('completed_date')->nullable();
            $table->string('assigned_to')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['property_listing_id', 'status']);
            $table->index(['severity', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_estate_maintenance_requests');
        Schema::dropIfExists('real_estate_lease_agreements');
        Schema::dropIfExists('real_estate_tenant_screenings');
        Schema::dropIfExists('real_estate_property_listings');
    }
};

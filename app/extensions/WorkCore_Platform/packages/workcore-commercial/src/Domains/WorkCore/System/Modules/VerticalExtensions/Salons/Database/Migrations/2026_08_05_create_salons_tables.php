<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stylist Specialties table
        Schema::create('salons_stylist_specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('worker_id')->constrained('workers')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('specialty_name');
            $table->string('certification_level')->nullable();
            $table->integer('years_of_experience')->nullable();
            $table->json('service_categories')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['worker_id', 'specialty_name']);
            $table->index(['company_id', 'is_available']);
        });

        // Service Add-Ons table
        Schema::create('salons_service_add_ons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('add_on_name');
            $table->string('service_category');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('duration_minutes')->nullable();
            $table->string('product_required')->nullable();
            $table->decimal('product_cost', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('order_priority')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'service_category']);
            $table->index(['is_active', 'order_priority']);
        });

        // Client Hair Profiles table
        Schema::create('salons_client_hair_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('hair_type')->nullable();
            $table->string('hair_texture')->nullable();
            $table->string('hair_color')->nullable();
            $table->json('color_history')->nullable();
            $table->string('scalp_condition')->nullable();
            $table->json('allergies')->nullable();
            $table->json('preferences')->nullable();
            $table->json('service_history')->nullable();
            $table->foreignId('preferred_stylist_id')->nullable()->constrained('workers')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'customer_id']);
        });

        // Loyalty Programs table
        Schema::create('salons_loyalty_programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('program_name');
            $table->string('tier_level')->default('bronze');
            $table->integer('points_balance')->default(0);
            $table->integer('points_earned')->default(0);
            $table->integer('points_redeemed')->default(0);
            $table->json('rewards_claimed')->nullable();
            $table->date('enrollment_date');
            $table->date('last_activity_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'customer_id']);
            $table->index(['tier_level', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salons_loyalty_programs');
        Schema::dropIfExists('salons_client_hair_profiles');
        Schema::dropIfExists('salons_service_add_ons');
        Schema::dropIfExists('salons_stylist_specialties');
    }
};

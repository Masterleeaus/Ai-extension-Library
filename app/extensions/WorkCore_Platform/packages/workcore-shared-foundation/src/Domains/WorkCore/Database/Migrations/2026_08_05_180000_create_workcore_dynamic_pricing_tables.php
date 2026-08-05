<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tz_pricing_rules', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id')->index(); $table->string('public_id', 26);
            $table->string('name', 160); $table->string('target_type', 80)->nullable()->index(); $table->string('target_reference', 160)->nullable()->index();
            $table->unsignedInteger('priority')->default(100); $table->string('adjustment_type', 40); $table->decimal('adjustment_value', 14, 4);
            $table->json('conditions')->nullable(); $table->timestamp('starts_at')->nullable(); $table->timestamp('ends_at')->nullable(); $table->boolean('is_active')->default(true)->index();
            $table->unsignedBigInteger('created_by'); $table->unsignedBigInteger('updated_by'); $table->timestamps();
            $table->unique(['company_id','public_id']); $table->index(['company_id','target_type','target_reference','is_active'], 'tz_pricing_rules_target_idx');
        });
        Schema::create('tz_seasonal_rates', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id')->index(); $table->string('public_id', 26); $table->string('name', 160);
            $table->string('target_type', 80)->nullable()->index(); $table->string('target_reference', 160)->nullable()->index(); $table->date('starts_on'); $table->date('ends_on');
            $table->decimal('multiplier', 8, 4); $table->unsignedInteger('priority')->default(100); $table->boolean('is_active')->default(true)->index(); $table->unsignedBigInteger('created_by'); $table->timestamps();
            $table->unique(['company_id','public_id']); $table->index(['company_id','starts_on','ends_on','is_active'], 'tz_seasonal_rates_dates_idx');
        });
        Schema::create('tz_demand_indicators', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id')->index(); $table->string('public_id', 26); $table->string('target_type', 80)->index(); $table->string('target_reference', 160)->index();
            $table->string('indicator_type', 80); $table->decimal('score', 5, 2); $table->unsignedBigInteger('quantity')->default(0); $table->string('source', 80); $table->unsignedBigInteger('recorded_by');
            $table->timestamp('recorded_at')->index(); $table->json('metadata')->nullable(); $table->timestamps(); $table->unique(['company_id','public_id']);
            $table->index(['company_id','target_type','target_reference','recorded_at'], 'tz_demand_target_time_idx');
        });
        Schema::create('tz_occupancy_snapshots', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id')->index(); $table->string('public_id', 26); $table->string('target_type', 80)->index(); $table->string('target_reference', 160)->index();
            $table->unsignedInteger('capacity'); $table->unsignedInteger('occupied'); $table->decimal('occupancy_percentage', 5, 2); $table->string('source', 80); $table->unsignedBigInteger('recorded_by');
            $table->timestamp('recorded_at')->index(); $table->json('metadata')->nullable(); $table->timestamps(); $table->unique(['company_id','public_id']);
            $table->index(['company_id','target_type','target_reference','recorded_at'], 'tz_occupancy_target_time_idx');
        });
        Schema::create('tz_competitor_price_snapshots', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id')->index(); $table->string('public_id', 26); $table->string('target_type', 80)->index(); $table->string('target_reference', 160)->index();
            $table->string('competitor_name', 160); $table->unsignedBigInteger('observed_price_minor'); $table->char('currency', 3); $table->text('source_url')->nullable(); $table->string('source', 80); $table->unsignedBigInteger('recorded_by');
            $table->timestamp('recorded_at')->index(); $table->json('metadata')->nullable(); $table->timestamps(); $table->unique(['company_id','public_id']);
            $table->index(['company_id','target_type','target_reference','recorded_at'], 'tz_competitor_target_time_idx');
        });
        Schema::create('tz_price_history', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('company_id')->index(); $table->string('public_id', 26); $table->string('target_type', 80)->index(); $table->string('target_reference', 160)->index();
            $table->unsignedBigInteger('base_price_minor'); $table->unsignedBigInteger('final_price_minor'); $table->char('currency', 3); $table->json('factors'); $table->char('decision_hash', 64)->index();
            $table->unsignedBigInteger('calculated_by'); $table->timestamp('calculated_at')->index(); $table->timestamps(); $table->unique(['company_id','public_id']);
            $table->index(['company_id','target_type','target_reference','calculated_at'], 'tz_price_history_target_time_idx');
        });
    }

    public function down(): void
    {
        foreach (['tz_price_history','tz_competitor_price_snapshots','tz_occupancy_snapshots','tz_demand_indicators','tz_seasonal_rates','tz_pricing_rules'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

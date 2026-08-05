<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_tiers', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->json('features_json')->nullable();
            $table->decimal('price', 12, 2);
            $table->string('currency')->default('USD');
            $table->enum('billing_cycle', ['monthly', 'yearly', 'quarterly', 'custom'])->default('monthly');
            $table->integer('cycle_days')->nullable()->comment('For custom billing cycles');
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['tenant_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_tiers');
    }
};

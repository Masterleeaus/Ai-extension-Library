<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_cycles', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->unsignedBigInteger('subscription_id')->index();
            $table->integer('cycle_number');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('prorated_amount', 12, 2)->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->unique(['subscription_id', 'cycle_number']);
            $table->index(['tenant_id', 'subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_cycles');
    }
};

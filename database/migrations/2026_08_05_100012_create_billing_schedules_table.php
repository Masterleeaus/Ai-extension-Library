<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_schedules', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->unsignedBigInteger('subscription_id')->index();
            $table->timestamp('next_billing_date')->index();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'processed', 'failed', 'cancelled'])->default('pending')->index();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();
            $table->text('error_message')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->index(['tenant_id', 'next_billing_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_schedules');
    }
};

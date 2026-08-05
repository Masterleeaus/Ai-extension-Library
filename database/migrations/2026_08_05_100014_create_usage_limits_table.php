<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_limits', function (Blueprint $table) {
            $table->id();
            $table->uuid('tenant_id')->index();
            $table->unsignedBigInteger('subscription_id')->index();
            $table->string('feature_slug')->index();
            $table->integer('limit_value');
            $table->integer('usage_count')->default(0);
            $table->timestamp('reset_date')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('subscription_id')->references('id')->on('subscriptions')->onDelete('cascade');
            $table->unique(['subscription_id', 'feature_slug']);
            $table->index(['tenant_id', 'subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_limits');
    }
};

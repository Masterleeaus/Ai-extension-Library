<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_pricings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_id');
            $table->string('product_id');
            $table->decimal('channel_price', 12, 2);
            $table->string('currency')->default('USD');
            $table->decimal('local_price', 12, 2)->nullable(); // Reference local price
            $table->json('pricing_rules')->nullable(); // Store pricing rule metadata
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->index(['tenant_id', 'channel_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->unique(['channel_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_pricings');
    }
};

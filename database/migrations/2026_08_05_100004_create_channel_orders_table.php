<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_id');
            $table->string('order_id_remote'); // Remote order ID from channel
            $table->string('local_order_id')->nullable(); // Local order ID after sync
            $table->enum('status', ['pending', 'imported', 'syncing', 'synced', 'error', 'cancelled'])->default('pending');
            $table->json('order_data')->nullable(); // Store raw order data
            $table->text('error_message')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->index(['tenant_id', 'channel_id']);
            $table->index(['tenant_id', 'local_order_id']);
            $table->unique(['channel_id', 'order_id_remote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_orders');
    }
};

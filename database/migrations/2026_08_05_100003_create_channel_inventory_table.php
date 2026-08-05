<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_inventory', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_id');
            $table->string('product_id'); // Could be local or mapping
            $table->integer('available_qty')->default(0);
            $table->integer('reserved_qty')->default(0);
            $table->integer('channel_qty')->nullable(); // Quantity on the channel
            $table->timestamp('last_sync')->nullable();
            $table->json('sync_metadata')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->index(['tenant_id', 'channel_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->unique(['channel_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_inventory');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('channel_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_id');
            $table->string('local_id'); // UUID or local identifier
            $table->string('channel_id_remote'); // Remote channel ID
            $table->enum('sync_status', ['pending', 'syncing', 'synced', 'error'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->index(['tenant_id', 'channel_id']);
            $table->unique(['channel_id', 'local_id']);
            $table->unique(['channel_id', 'channel_id_remote']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_mappings');
    }
};

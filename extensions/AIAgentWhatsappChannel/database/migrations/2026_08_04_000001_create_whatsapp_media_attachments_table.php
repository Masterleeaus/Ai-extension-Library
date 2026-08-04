<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_media_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('channel_id')->index();
            $table->string('provider_media_id')->unique('whatsapp_provider_media_id_unique');
            $table->string('source_identifier')->index();
            $table->string('filename');
            $table->string('mime_type');
            $table->string('detected_mime_type')->nullable();
            $table->string('file_hash', 128)->index();
            $table->unsignedBigInteger('file_size');
            $table->unsignedBigInteger('byte_count');
            $table->string('storage_path')->nullable();
            $table->enum('status', ['pending', 'verified', 'quarantined', 'malicious', 'deleted'])->default('pending')->index();
            $table->json('validation_errors')->nullable();
            $table->json('scan_result')->nullable();
            $table->string('correlation_id')->index();
            $table->timestamp('retention_until')->nullable()->index();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('channel_id')->references('id')->on('ai_agent_channels')->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
            $table->index(['retention_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_media_attachments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_envelope_events', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('event_type');
            $table->integer('version');
            $table->json('payload');
            $table->string('idempotency_key');
            $table->string('correlation_id')->nullable();
            $table->string('causation_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_envelope_events');
    }
};
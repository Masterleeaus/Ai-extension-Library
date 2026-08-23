<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_security_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('endpoint_id');
            $table->string('event_type');
            $table->json('payload');
            $table->string('status');
            $table->text('response')->nullable();
            $table->integer('retries')->default(false);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_security_deliveries');
    }
};
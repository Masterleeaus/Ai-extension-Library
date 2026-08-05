<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_engine_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('customer_id');
            $table->string('service_id');
            $table->string('status');
            $table->timestamp('scheduled_at');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_engine_bookings');
    }
};
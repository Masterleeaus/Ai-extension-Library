<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_migration_records', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('source_id');
            $table->string('destination_id');
            $table->string('status');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_migration_records');
    }
};
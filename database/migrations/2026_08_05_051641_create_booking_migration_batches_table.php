<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_migration_batches', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->integer('batch_number');
            $table->integer('total_records');
            $table->integer('processed')->default(false);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_migration_batches');
    }
};
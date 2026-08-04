<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workcore_idempotency_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('idempotency_key')->index();
            $table->longText('result')->nullable();
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamps();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_idempotency_ledger');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_contract_orders', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->decimal('total', 10, 2);
            $table->string('status');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_contract_orders');
    }
};
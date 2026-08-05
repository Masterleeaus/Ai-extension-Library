<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workcore_commercial_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('sku');
            $table->integer('quantity');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_commercial_inventory');
    }
};
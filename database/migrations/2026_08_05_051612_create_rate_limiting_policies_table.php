<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limiting_policies', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('endpoint');
            $table->integer('limit');
            $table->integer('window');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limiting_policies');
    }
};
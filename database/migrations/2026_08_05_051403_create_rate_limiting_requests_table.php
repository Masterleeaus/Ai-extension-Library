<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_limiting_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('endpoint');
            $table->string('client_id');
            $table->integer('count');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_limiting_requests');
    }
};
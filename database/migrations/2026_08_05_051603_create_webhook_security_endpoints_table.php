<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_security_endpoints', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->text('url');
            $table->text('secret');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_security_endpoints');
    }
};
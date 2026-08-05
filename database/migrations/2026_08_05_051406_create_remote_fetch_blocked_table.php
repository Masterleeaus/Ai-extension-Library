<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remote_fetch_blocked', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('pattern');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remote_fetch_blocked');
    }
};
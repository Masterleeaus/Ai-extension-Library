<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_control_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('role_id');
            $table->string('permission_id');
            $table->timestamps();
            $table->timestamps();
            $table->index('tenant_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_control_role_permissions');
    }
};
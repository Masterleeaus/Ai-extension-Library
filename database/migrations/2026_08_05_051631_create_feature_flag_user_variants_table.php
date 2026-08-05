<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_flag_user_variants', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->string('flag_id');
            $table->string('user_id');
            $table->string('variant');
            $table->timestamps();
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flag_user_variants');
    }
};
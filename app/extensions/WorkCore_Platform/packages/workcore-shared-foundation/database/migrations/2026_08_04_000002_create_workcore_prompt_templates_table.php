<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workcore_prompt_templates', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('name')->index();
            $table->string('category')->index();
            $table->text('description')->nullable();
            $table->longText('template');
            $table->json('variables')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name', 'version']);
            $table->index(['tenant_id', 'category', 'is_active']);
            $table->foreign('tenant_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workcore_prompt_templates');
    }
};

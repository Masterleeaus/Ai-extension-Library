<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_photo_studio_backgrounds', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
            $table->text('description')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('ai_photo_studio_backgrounds', function (Blueprint $table) {
            $table->dropColumn(['category', 'description']);
        });
    }
};

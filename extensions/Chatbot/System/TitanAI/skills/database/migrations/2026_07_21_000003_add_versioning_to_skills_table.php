<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->string('version', 64)->default('1.0.0')->after('status');
            $table->string('content_hash', 64)->nullable()->after('version')->index();
            $table->json('metadata')->nullable()->after('content_hash');
        });
    }

    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex(['content_hash']);
            $table->dropColumn(['version', 'content_hash', 'metadata']);
        });
    }
};

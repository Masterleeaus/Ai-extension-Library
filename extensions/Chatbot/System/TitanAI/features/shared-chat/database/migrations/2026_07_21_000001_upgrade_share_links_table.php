<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('share_links', function (Blueprint $table): void {
            if (! Schema::hasColumn('share_links', 'user_id')) $table->unsignedBigInteger('user_id')->nullable()->index();
            if (! Schema::hasColumn('share_links', 'token_hash')) $table->string('token_hash', 64)->nullable()->index();
            if (! Schema::hasColumn('share_links', 'expires_at')) $table->timestamp('expires_at')->nullable()->index();
            if (! Schema::hasColumn('share_links', 'revoked_at')) $table->timestamp('revoked_at')->nullable()->index();
            if (! Schema::hasColumn('share_links', 'access_count')) $table->unsignedBigInteger('access_count')->default(0);
            if (! Schema::hasColumn('share_links', 'last_accessed_at')) $table->timestamp('last_accessed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('share_links', function (Blueprint $table): void {
            foreach (['user_id','token_hash','expires_at','revoked_at','access_count','last_accessed_at'] as $column) {
                if (Schema::hasColumn('share_links', $column)) $table->dropColumn($column);
            }
        });
    }
};

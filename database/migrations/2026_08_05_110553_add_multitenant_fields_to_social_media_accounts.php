<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_media_accounts', function (Blueprint $table) {
            // Add multi-tenant fields if they don't exist
            if (!Schema::hasColumn('social_media_accounts', 'company_id')) {
                $table->foreignId('company_id')->nullable()->constrained('tz_companies')->cascadeOnDelete()->after('id');
            }
            if (!Schema::hasColumn('social_media_accounts', 'user_id')) {
                $table->string('user_id')->nullable()->after('company_id');
            }
            if (!Schema::hasColumn('social_media_accounts', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete()->after('user_id');
            }
            
            // Add indexes for multi-tenant queries
            if (!Schema::hasColumn('social_media_accounts', 'company_id')) {
                $table->index(['company_id']);
            }
            $table->index(['company_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('social_media_accounts', function (Blueprint $table) {
            $table->dropIndex(['social_media_accounts_company_id_index']);
            $table->dropIndex(['social_media_accounts_company_id_user_id_index']);
            $table->dropForeign(['social_media_accounts_company_id_foreign']);
            $table->dropColumn(['company_id', 'user_id', 'team_id']);
        });
    }
};
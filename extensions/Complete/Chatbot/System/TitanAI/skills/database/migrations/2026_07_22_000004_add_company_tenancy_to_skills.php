<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable()->after('user_id')->index();
            $table->string('ownership_type', 32)->default('platform')->after('company_id')->index();
            $table->string('visibility', 32)->default('platform_private')->after('ownership_type')->index();
            $table->foreignId('created_by')->nullable()->after('visibility')->constrained('users')->nullOnDelete();
            $table->foreignId('managed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->string('scope_key', 96)->default('platform:0')->after('managed_by');
        });

        DB::table('skills')->orderBy('id')->chunkById(200, function ($skills): void {
            foreach ($skills as $skill) {
                $isUserOwned = $skill->user_id !== null;
                DB::table('skills')->where('id', $skill->id)->update([
                    'ownership_type' => $isUserOwned ? 'user' : 'platform',
                    'visibility' => $isUserOwned
                        ? 'private_user'
                        : ((bool) $skill->is_public ? 'platform_public' : 'platform_private'),
                    'created_by' => $skill->user_id,
                    'managed_by' => $skill->user_id,
                    'scope_key' => $isUserOwned ? 'user:' . $skill->user_id : 'platform:0',
                ]);
            }
        });

        Schema::table('skills', function (Blueprint $table): void {
            $table->dropUnique(['slug', 'user_id']);
            $table->unique(['slug', 'scope_key']);
        });

        Schema::create('company_skills', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->boolean('auto_use')->default(false);
            $table->json('allowed_roles')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'skill_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_skills');

        Schema::table('skills', function (Blueprint $table): void {
            $table->dropUnique(['slug', 'scope_key']);
            $table->unique(['slug', 'user_id']);
            $table->dropConstrainedForeignId('managed_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'company_id',
                'ownership_type',
                'visibility',
                'scope_key',
            ]);
        });
    }
};

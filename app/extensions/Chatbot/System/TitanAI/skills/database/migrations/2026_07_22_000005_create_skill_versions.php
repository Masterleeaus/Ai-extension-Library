<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->string('version', 64);
            $table->string('lifecycle_status', 32)->default('draft')->index();
            $table->string('name');
            $table->text('description');
            $table->longText('instructions');
            $table->json('bundled_resources')->nullable();
            $table->json('metadata')->nullable();
            $table->string('content_hash', 64)->index();
            $table->string('source', 32)->default('created');
            $table->string('source_url')->nullable();
            $table->text('release_notes')->nullable();
            $table->foreignId('based_on_version_id')->nullable()->constrained('skill_versions')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deprecated_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->unique(['skill_id', 'version']);
        });

        Schema::table('skills', function (Blueprint $table): void {
            $table->unsignedBigInteger('current_version_id')->nullable()->after('metadata')->index();
            $table->unsignedBigInteger('latest_published_version_id')->nullable()->after('current_version_id')->index();
        });

        Schema::table('user_skills', function (Blueprint $table): void {
            $table->foreignId('skill_version_id')->nullable()->after('skill_id')->constrained('skill_versions')->nullOnDelete();
            $table->string('update_policy', 32)->default('follow_compatible')->after('skill_version_id')->index();
        });

        Schema::table('company_skills', function (Blueprint $table): void {
            $table->foreignId('skill_version_id')->nullable()->after('skill_id')->constrained('skill_versions')->nullOnDelete();
            $table->string('update_policy', 32)->default('follow_compatible')->after('skill_version_id')->index();
        });

        DB::table('skills')->orderBy('id')->chunkById(100, function ($skills): void {
            foreach ($skills as $skill) {
                $version = is_string($skill->version ?? null) && $skill->version !== '' ? $skill->version : '1.0.0';
                $contentHash = is_string($skill->content_hash ?? null) && strlen($skill->content_hash) === 64
                    ? $skill->content_hash
                    : hash('sha256', implode("\n", [
                        (string) $skill->name,
                        (string) $skill->description,
                        (string) $skill->instructions,
                        (string) ($skill->bundled_resources ?? '[]'),
                    ]));

                $versionId = DB::table('skill_versions')->insertGetId([
                    'skill_id' => $skill->id,
                    'version' => $version,
                    'lifecycle_status' => 'published',
                    'name' => $skill->name,
                    'description' => $skill->description,
                    'instructions' => $skill->instructions,
                    'bundled_resources' => $skill->bundled_resources,
                    'metadata' => $skill->metadata ?? null,
                    'content_hash' => $contentHash,
                    'source' => $skill->source,
                    'source_url' => $skill->source_url,
                    'release_notes' => 'Migrated from the legacy mutable skill record.',
                    'created_by' => $skill->created_by ?? $skill->user_id,
                    'published_by' => $skill->managed_by ?? $skill->created_by ?? $skill->user_id,
                    'published_at' => $skill->created_at ?? now(),
                    'created_at' => $skill->created_at ?? now(),
                    'updated_at' => $skill->updated_at ?? now(),
                ]);

                DB::table('skills')->where('id', $skill->id)->update([
                    'current_version_id' => $versionId,
                    'latest_published_version_id' => $versionId,
                ]);

                DB::table('user_skills')->where('skill_id', $skill->id)->update([
                    'skill_version_id' => $versionId,
                    'update_policy' => 'follow_compatible',
                ]);

                DB::table('company_skills')->where('skill_id', $skill->id)->update([
                    'skill_version_id' => $versionId,
                    'update_policy' => 'follow_compatible',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_skills', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('skill_version_id');
            $table->dropColumn('update_policy');
        });

        Schema::table('user_skills', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('skill_version_id');
            $table->dropColumn('update_policy');
        });

        Schema::table('skills', function (Blueprint $table): void {
            $table->dropColumn(['latest_published_version_id', 'current_version_id']);
        });

        Schema::dropIfExists('skill_versions');
    }
};

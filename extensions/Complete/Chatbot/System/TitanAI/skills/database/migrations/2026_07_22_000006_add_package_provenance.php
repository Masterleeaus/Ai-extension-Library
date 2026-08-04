<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('skills')) {
            Schema::table('skills', function (Blueprint $table): void {
                if (! Schema::hasColumn('skills', 'package_hash')) {
                    $table->string('package_hash', 64)->nullable()->after('content_hash')->index();
                }
                if (! Schema::hasColumn('skills', 'verification_status')) {
                    $table->string('verification_status', 32)->default('unverified')->after('package_hash')->index();
                }
            });

            DB::table('skills')
                ->whereNull('package_hash')
                ->update([
                    'package_hash' => DB::raw('content_hash'),
                    'verification_status' => 'unverified',
                ]);
        }

        if (Schema::hasTable('skill_versions')) {
            Schema::table('skill_versions', function (Blueprint $table): void {
                if (! Schema::hasColumn('skill_versions', 'package_hash')) {
                    $table->string('package_hash', 64)->nullable()->after('content_hash')->index();
                }
                if (! Schema::hasColumn('skill_versions', 'manifest_hash')) {
                    $table->string('manifest_hash', 64)->nullable()->after('package_hash');
                }
                if (! Schema::hasColumn('skill_versions', 'instructions_hash')) {
                    $table->string('instructions_hash', 64)->nullable()->after('manifest_hash');
                }
                if (! Schema::hasColumn('skill_versions', 'source_repository')) {
                    $table->string('source_repository')->nullable()->after('source_url');
                }
                if (! Schema::hasColumn('skill_versions', 'source_branch')) {
                    $table->string('source_branch', 255)->nullable()->after('source_repository');
                }
                if (! Schema::hasColumn('skill_versions', 'source_commit_sha')) {
                    $table->string('source_commit_sha', 64)->nullable()->after('source_branch')->index();
                }
                if (! Schema::hasColumn('skill_versions', 'imported_at')) {
                    $table->timestamp('imported_at')->nullable()->after('source_commit_sha');
                }
                if (! Schema::hasColumn('skill_versions', 'imported_by')) {
                    $table->unsignedBigInteger('imported_by')->nullable()->after('imported_at')->index();
                }
                if (! Schema::hasColumn('skill_versions', 'verification_status')) {
                    $table->string('verification_status', 32)->default('unverified')->after('imported_by')->index();
                }
                if (! Schema::hasColumn('skill_versions', 'verification_details')) {
                    $table->json('verification_details')->nullable()->after('verification_status');
                }
                if (! Schema::hasColumn('skill_versions', 'verified_at')) {
                    $table->timestamp('verified_at')->nullable()->after('verification_details');
                }
            });

            DB::table('skill_versions')
                ->whereNull('package_hash')
                ->update([
                    'package_hash' => DB::raw('content_hash'),
                    'verification_status' => 'unverified',
                ]);
        }

        if (! Schema::hasTable('skill_version_resources')) {
            Schema::create('skill_version_resources', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('skill_version_id')->constrained('skill_versions')->cascadeOnDelete();
                $table->string('path', 1024);
                $table->string('sha256', 64)->index();
                $table->unsignedBigInteger('size');
                $table->string('mime_type', 255)->nullable();
                $table->timestamps();

                $table->unique(['skill_version_id', 'path'], 'skill_version_resource_path_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('skill_version_resources');

        if (Schema::hasTable('skill_versions')) {
            Schema::table('skill_versions', function (Blueprint $table): void {
                foreach ([
                    'package_hash',
                    'manifest_hash',
                    'instructions_hash',
                    'source_repository',
                    'source_branch',
                    'source_commit_sha',
                    'imported_at',
                    'imported_by',
                    'verification_status',
                    'verification_details',
                    'verified_at',
                ] as $column) {
                    if (Schema::hasColumn('skill_versions', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('skills')) {
            Schema::table('skills', function (Blueprint $table): void {
                foreach (['package_hash', 'verification_status'] as $column) {
                    if (Schema::hasColumn('skills', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};

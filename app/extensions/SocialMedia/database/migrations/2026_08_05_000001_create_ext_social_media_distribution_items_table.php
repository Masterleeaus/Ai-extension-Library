<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_social_media_distribution_items', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('company_id')->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->bigInteger('social_media_post_id')->nullable()->unique();
            $table->string('content_type');
            $table->string('status')->default('draft');
            $table->string('approval_status')->default('not_required');
            $table->string('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'content_type', 'status'], 'ext_social_distribution_owner_type_status');
            $table->index(['source_type', 'source_id'], 'ext_social_distribution_source');
        });

        Schema::create('ext_social_media_distribution_audits', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('social_media_platform_id')->nullable();
            $table->string('destination');
            $table->string('action');
            $table->json('snapshot');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'destination'], 'ext_social_distribution_audit_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_social_media_distribution_audits');
        Schema::dropIfExists('ext_social_media_distribution_items');
    }
};

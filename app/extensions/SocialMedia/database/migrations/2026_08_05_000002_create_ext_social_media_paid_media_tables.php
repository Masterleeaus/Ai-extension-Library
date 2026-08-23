<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_social_media_paid_media_campaigns', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('social_media_platform_id');
            $table->bigInteger('distribution_item_id')->nullable();
            $table->string('ad_account_id');
            $table->string('name');
            $table->string('objective');
            $table->string('status')->default('draft');
            $table->string('budget_type');
            $table->unsignedBigInteger('budget_minor');
            $table->unsignedBigInteger('spend_cap_minor')->nullable();
            $table->string('currency', 3);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('campaign_payload');
            $table->json('adset_payload');
            $table->json('creative_payload');
            $table->json('ad_payload');
            $table->string('payload_fingerprint', 64);
            $table->string('meta_campaign_id')->nullable();
            $table->string('meta_adset_id')->nullable();
            $table->string('meta_creative_id')->nullable();
            $table->string('meta_ad_id')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'ext_social_paid_media_owner_status');
            $table->index(['social_media_platform_id', 'ad_account_id'], 'ext_social_paid_media_account');
            $table->unique('distribution_item_id', 'ext_social_paid_media_distribution_unique');
        });

        Schema::create('ext_social_media_paid_media_approvals', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('paid_media_campaign_id');
            $table->bigInteger('user_id');
            $table->bigInteger('actor_id');
            $table->string('decision');
            $table->string('payload_fingerprint', 64);
            $table->unsignedBigInteger('budget_minor');
            $table->unsignedBigInteger('spend_cap_minor')->nullable();
            $table->string('currency', 3);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->bigInteger('supersedes_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['paid_media_campaign_id', 'created_at'], 'ext_social_paid_media_approval_history');
            $table->index(['user_id', 'actor_id'], 'ext_social_paid_media_approval_actor');
        });

        Schema::create('ext_social_media_paid_media_receipts', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('paid_media_campaign_id');
            $table->bigInteger('user_id');
            $table->string('action');
            $table->string('idempotency_key', 128)->nullable();
            $table->string('request_hash', 64);
            $table->json('request_snapshot');
            $table->json('response_snapshot')->nullable();
            $table->string('provider_object_id')->nullable();
            $table->string('status');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['paid_media_campaign_id', 'created_at'], 'ext_social_paid_media_receipt_history');
            $table->unique(
                ['user_id', 'paid_media_campaign_id', 'action', 'idempotency_key'],
                'ext_social_paid_media_receipt_idempotency'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_social_media_paid_media_receipts');
        Schema::dropIfExists('ext_social_media_paid_media_approvals');
        Schema::dropIfExists('ext_social_media_paid_media_campaigns');
    }
};

<?php

namespace App\Extensions\SocialMedia\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaidMediaCampaign extends Model
{
    protected $table = 'ext_social_media_paid_media_campaigns';

    protected $fillable = [
        'user_id',
        'social_media_platform_id',
        'distribution_item_id',
        'ad_account_id',
        'name',
        'objective',
        'status',
        'budget_type',
        'budget_minor',
        'spend_cap_minor',
        'currency',
        'starts_at',
        'ends_at',
        'campaign_payload',
        'adset_payload',
        'creative_payload',
        'ad_payload',
        'payload_fingerprint',
        'meta_campaign_id',
        'meta_adset_id',
        'meta_creative_id',
        'meta_ad_id',
        'activated_at',
    ];

    protected $casts = [
        'budget_minor'      => 'integer',
        'spend_cap_minor'   => 'integer',
        'starts_at'         => 'datetime',
        'ends_at'           => 'datetime',
        'activated_at'      => 'datetime',
        'campaign_payload'  => 'array',
        'adset_payload'     => 'array',
        'creative_payload'  => 'array',
        'ad_payload'        => 'array',
    ];

    public function scopeOwnedBy(Builder $builder, User|int $user): Builder
    {
        return $builder->where(
            'user_id',
            $user instanceof User ? $user->getKey() : $user
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SocialMediaPlatform::class, 'social_media_platform_id');
    }

    public function distributionItem(): BelongsTo
    {
        return $this->belongsTo(DistributionItem::class, 'distribution_item_id');
    }
}

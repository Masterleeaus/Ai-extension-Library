<?php

namespace App\Extensions\SocialMedia\System\Models;

use App\Models\Company;
use App\Models\User;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class DistributionItem extends Model
{
    public const MODE_DIRECT = 'direct';

    public const MODE_PARTNER = 'partner';

    public const MODE_ASSISTED = 'assisted';

    public const MODE_EXPORT_ONLY = 'export_only';

    public const TYPE_SOCIAL_POST = 'social_post';

    public const TYPE_MARKETPLACE_LISTING = 'marketplace_listing';

    public const TYPE_CLASSIFIED_LISTING = 'classified_listing';

    public const TYPE_PRODUCT_OFFER = 'product_offer';

    public const TYPE_SERVICE_PROMOTION = 'service_promotion';

    public const TYPE_BUSINESS_UPDATE = 'business_update';

    public const TYPE_PAID_CREATIVE = 'paid_creative';

    public const TYPE_EVENT = 'event';

    public const TYPE_PROPERTY_LISTING = 'property_listing';

    public const TYPE_VEHICLE_LISTING = 'vehicle_listing';

    public const TYPE_JOB_LISTING = 'job_listing';

    protected $table = 'ext_social_media_distribution_items';

    protected $fillable = [
        'user_id',
        'company_id',
        'campaign_id',
        'social_media_post_id',
        'content_type',
        'status',
        'approval_status',
        'title',
        'content',
        'source_type',
        'source_id',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    public static function contentTypes(): array
    {
        return [
            self::TYPE_SOCIAL_POST,
            self::TYPE_MARKETPLACE_LISTING,
            self::TYPE_CLASSIFIED_LISTING,
            self::TYPE_PRODUCT_OFFER,
            self::TYPE_SERVICE_PROMOTION,
            self::TYPE_BUSINESS_UPDATE,
            self::TYPE_PAID_CREATIVE,
            self::TYPE_EVENT,
            self::TYPE_PROPERTY_LISTING,
            self::TYPE_VEHICLE_LISTING,
            self::TYPE_JOB_LISTING,
        ];
    }

    public static function modes(): array
    {
        return [
            self::MODE_DIRECT,
            self::MODE_PARTNER,
            self::MODE_ASSISTED,
            self::MODE_EXPORT_ONLY,
        ];
    }

    public static function createForUser(User $user, array $attributes): self
    {
        $contentType = (string) ($attributes['content_type'] ?? '');

        if (! in_array($contentType, self::contentTypes(), true)) {
            throw new InvalidArgumentException('Unsupported Titan Reach distribution content type.');
        }

        $attributes['user_id'] = $user->getKey();

        return self::query()->create($attributes);
    }

    public static function fromSocialMediaPost(SocialMediaPost $post): self
    {
        $status = $post->status instanceof BackedEnum
            ? (string) $post->status->value
            : (string) $post->status;

        return self::query()->firstOrCreate(
            ['social_media_post_id' => $post->getKey()],
            [
                'user_id'         => $post->user_id,
                'company_id'      => $post->company_id,
                'campaign_id'     => $post->campaign_id,
                'content_type'    => self::TYPE_SOCIAL_POST,
                'status'          => $status,
                'approval_status' => 'not_required',
                'source_type'     => self::TYPE_SOCIAL_POST,
                'source_id'       => (string) $post->getKey(),
            ]
        );
    }

    public function resolvedContent(): ?string
    {
        return $this->socialMediaPost?->content ?? $this->content;
    }

    public function scopeOwnedBy(Builder $builder, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $builder->where('user_id', $userId);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SocialMediaCampaign::class, 'campaign_id');
    }

    public function socialMediaPost(): BelongsTo
    {
        return $this->belongsTo(SocialMediaPost::class, 'social_media_post_id');
    }
}

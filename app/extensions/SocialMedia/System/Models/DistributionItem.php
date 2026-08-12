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

    public const TYPE_ROOM_STAY_OFFER = 'room_stay_offer';

    public const TYPE_MEMBERSHIP_OFFER = 'membership_offer';

    public const TYPE_CLASS_SESSION_OFFER = 'class_session_offer';

    public const TYPE_BOOKING_OFFER = 'booking_offer';

    public const TYPE_HIRE_RENTAL_LISTING = 'hire_rental_listing';

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
            self::TYPE_ROOM_STAY_OFFER,
            self::TYPE_MEMBERSHIP_OFFER,
            self::TYPE_CLASS_SESSION_OFFER,
            self::TYPE_BOOKING_OFFER,
            self::TYPE_HIRE_RENTAL_LISTING,
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

        if ($contentType === self::TYPE_SOCIAL_POST) {
            throw new InvalidArgumentException('Use fromSocialMediaPost() for canonical social post mappings.');
        }

        unset($attributes['social_media_post_id']);
        $attributes['user_id'] = $user->getKey();

        return self::query()->create($attributes);
    }

    public static function fromCanonicalSource(User $user, array $transformation, array $attributes = []): self
    {
        if (($transformation['status'] ?? null) !== 'ready') {
            throw new InvalidArgumentException('A ready canonical source transformation is required.');
        }

        $contentType = trim((string) ($transformation['content_type'] ?? ''));
        if (! in_array($contentType, self::contentTypes(), true)) {
            throw new InvalidArgumentException('Unsupported Titan Reach distribution content type.');
        }

        if ($contentType === self::TYPE_SOCIAL_POST) {
            throw new InvalidArgumentException('Use fromSocialMediaPost() for canonical social post mappings.');
        }

        $canonicalSource = (array) ($transformation['canonical_source'] ?? []);
        $sourceSystem = trim((string) ($canonicalSource['source_system'] ?? ''));
        $sourceType = trim((string) ($canonicalSource['source_type'] ?? ''));
        $sourceId = trim((string) ($canonicalSource['source_id'] ?? ''));

        if ($sourceSystem === '' || $sourceType === '' || $sourceId === '') {
            throw new InvalidArgumentException('Canonical source system, type and ID are required.');
        }

        if (! array_key_exists('provenance', $canonicalSource)
            || ! array_key_exists('authority', $canonicalSource)) {
            throw new InvalidArgumentException('Canonical source provenance and authority are required.');
        }

        $fields = (array) ($transformation['fields'] ?? []);
        $payload = array_replace_recursive(
            (array) ($attributes['payload'] ?? []),
            [
                'canonical_source' => $canonicalSource,
                'vertical' => $transformation['vertical'] ?? 'generic-business',
                'business_subtype' => $transformation['business_subtype'] ?? null,
                'profile_version' => $transformation['profile_version'] ?? null,
                'profile_provenance' => array_values((array) ($transformation['profile_provenance'] ?? [])),
                'attribution_confidence' => $transformation['attribution_confidence'] ?? 1.0,
                'destination' => $transformation['destination'] ?? null,
                'destination_fields' => array_values((array) ($transformation['destination_fields'] ?? [])),
                'fields' => $fields,
            ]
        );

        $attributes['content_type'] = $contentType;
        $attributes['status'] = $attributes['status'] ?? 'draft';
        $attributes['approval_status'] = $attributes['approval_status'] ?? 'pending';
        $attributes['title'] = $attributes['title'] ?? ($fields['title'] ?? null);
        $attributes['content'] = $attributes['content'] ?? ($fields['content'] ?? $fields['description'] ?? null);
        $attributes['company_id'] = $attributes['company_id'] ?? ($canonicalSource['company_id'] ?? null);
        $attributes['source_type'] = $sourceSystem . ':' . $sourceType;
        $attributes['source_id'] = $sourceId;
        $attributes['payload'] = $payload;

        return self::createForUser($user, $attributes);
    }

    public static function fromSocialMediaPost(SocialMediaPost $post): self
    {
        if (! $post->user_id) {
            throw new InvalidArgumentException('A canonical social post owner is required.');
        }

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

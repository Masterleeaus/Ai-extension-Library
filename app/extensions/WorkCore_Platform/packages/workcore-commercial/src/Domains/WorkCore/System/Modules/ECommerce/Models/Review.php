<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'customer_id',
        'customer_name',
        'rating',
        'title',
        'comment',
        'moderation_status',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'helpful_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function moderate(string $status): void
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new \InvalidArgumentException("Invalid moderation status: {$status}");
        }

        $this->update(['moderation_status' => $status]);
    }

    public function publish(): void
    {
        $this->moderate('approved');
    }

    public function reject(): void
    {
        $this->moderate('rejected');
    }

    public function getRatingLabel(): string
    {
        $labels = [
            1 => '1 Star - Poor',
            2 => '2 Stars - Fair',
            3 => '3 Stars - Good',
            4 => '4 Stars - Very Good',
            5 => '5 Stars - Excellent',
        ];

        return $labels[$this->rating] ?? 'Unrated';
    }

    public function isApproved(): bool
    {
        return $this->moderation_status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->moderation_status === 'pending';
    }
}

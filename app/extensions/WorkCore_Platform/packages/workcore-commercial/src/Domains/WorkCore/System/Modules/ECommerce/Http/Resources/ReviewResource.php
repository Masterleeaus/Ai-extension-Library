<?php

declare(strict_types=1);


namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'rating' => $this->rating,
            'rating_label' => $this->getRatingLabel(),
            'title' => $this->title,
            'comment' => $this->comment,
            'moderation_status' => $this->moderation_status,
            'helpful_count' => $this->helpful_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

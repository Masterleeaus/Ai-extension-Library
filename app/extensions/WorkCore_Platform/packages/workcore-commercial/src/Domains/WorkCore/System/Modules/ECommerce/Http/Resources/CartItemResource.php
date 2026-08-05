<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'price_at_time' => (float) $this->price_at_time,
            'total_price' => $this->getTotalPrice(),
            'attributes' => $this->attributes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderFulfillmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if ($this->resource === null) {
            return [];
        }

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'shipping_method' => $this->shipping_method,
            'shipping_provider' => $this->shipping_provider,
            'tracking_number' => $this->tracking_number,
            'tracking_url' => $this->tracking_url,
            'shipping_cost' => (float) $this->shipping_cost,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
        ];
    }
}

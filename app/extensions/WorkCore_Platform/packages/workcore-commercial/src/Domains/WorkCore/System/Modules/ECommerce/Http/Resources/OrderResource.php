<?php

namespace WorkCore\Domains\WorkCore\System\Modules\ECommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'tenant_id' => $this->tenant_id,
            'customer_id' => $this->customer_id,
            'customer_email' => $this->customer_email,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'items' => OrderItemResource::collection($this->items),
            'subtotal' => (float) $this->subtotal,
            'tax' => (float) $this->tax,
            'shipping' => (float) $this->shipping,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'fulfillment' => new OrderFulfillmentResource($this->fulfillment),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

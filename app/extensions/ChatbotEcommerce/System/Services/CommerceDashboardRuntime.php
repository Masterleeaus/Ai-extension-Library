<?php

declare(strict_types=1);

namespace App\\Extensions\\ChatbotEcommerce\\System\\Services;

use App\\Extensions\\Chatbot\\System\\Models\\Chatbot;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\CommerceBooking;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\CommerceBookingSlot;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\CommerceCommunicationThread;\nuse App\\Extensions\\ChatbotEcommerce\\System\\Models\\CommerceCredential;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\MarketplaceConnection;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\OrderException;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\Product;
use App\\Extensions\\ChatbotEcommerce\\System\\Models\\UnifiedCommerceOrder;

final class CommerceDashboardRuntime
{
    /** @return array<string,int> */
    public function summary(Chatbot $chatbot): array
    {
        $chatbotId = (int) $chatbot->getKey();

        return [
            'products' => Product::query()->where('chatbot_id', $chatbotId)->where('active', true)->count(),
            'channels' => MarketplaceConnection::query()->where('chatbot_id', $chatbotId)->where('active', true)->count(),\n            'storefront_connections' => CommerceCredential::query()->where('chatbot_id', $chatbotId)->whereIn('provider', ['shopify', 'woocommerce'])->where('status', 'active')->count(),
            'open_orders' => UnifiedCommerceOrder::query()
                ->where('chatbot_id', $chatbotId)
                ->whereNotIn('status', ['cancelled', 'completed', 'refunded'])
                ->count(),
            'available_bookings' => CommerceBookingSlot::query()
                ->where('chatbot_id', $chatbotId)
                ->where('status', 'open')
                ->where('starts_at', '>', now())
                ->count(),
            'active_bookings' => CommerceBooking::query()
                ->where('chatbot_id', $chatbotId)
                ->whereIn('status', ['confirmed', 'reserved'])
                ->count(),
            'support_attention' => CommerceCommunicationThread::query()
                ->where('chatbot_id', $chatbotId)
                ->whereIn('status', ['open', 'handoff'])
                ->whereIn('priority', ['high', 'urgent'])
                ->count(),
            'exceptions' => OrderException::query()
                ->where('chatbot_id', $chatbotId)
                ->whereIn('status', ['open', 'acknowledged'])
                ->count(),
        ];
    }
}

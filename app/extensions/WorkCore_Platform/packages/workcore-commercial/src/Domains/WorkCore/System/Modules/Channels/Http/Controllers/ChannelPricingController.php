<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Http\Controllers;

use App\Domains\WorkCore\System\Modules\Channels\Models\Channel;
use App\Domains\WorkCore\System\Modules\Channels\Services\PricingSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ChannelPricingController extends Controller
{
    public function __construct(private PricingSyncService $pricingService) {}

    public function index(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $productId = $request->query('product_id');
        $priceHistory = $this->pricingService->getPriceHistory($channel->id, $productId);

        return response()->json([
            'success' => true,
            'data' => $priceHistory,
        ]);
    }

    public function show(string $channelId, string $productId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $priceHistory = $this->pricingService->getPriceHistory($channel->id, $productId);

        return response()->json([
            'success' => true,
            'data' => $priceHistory,
        ]);
    }

    public function sync(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $result = $this->pricingService->syncPrices($channel);

        return response()->json([
            'success' => true,
            'message' => 'Pricing sync initiated',
            'data' => $result,
        ]);
    }

    public function anomalies(string $channelId, Request $request): JsonResponse
    {
        $channel = Channel::where('id', $channelId)
            ->where('tenant_id', $request->user()->tenant_id)
            ->firstOrFail();

        $threshold = $request->query('threshold', 10);
        $anomalies = $this->pricingService->getPriceAnomalies($channel->id, (float) $threshold);

        return response()->json([
            'success' => true,
            'data' => $anomalies,
        ]);
    }
}

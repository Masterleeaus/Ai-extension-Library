<?php

namespace App\Extensions\FluxPro\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegacyFalWebhookAdapter extends Controller
{
    /**
     * Adapter for legacy FAL webhook route: generator/webhook/fal-ai
     *
     * This maintains backward compatibility with the old route name and ANY method.
     * It forwards to the unified webhook handler.
     *
     * DEPRECATION: This endpoint will be removed after 2026-12-31.
     * Please update to use POST /api/webhooks/fal/{provider}
     */
    public function handle(Request $request): JsonResponse
    {
        // Only accept POST from now on
        if ($request->method() !== 'POST') {
            return response()->json([
                'error' => 'Method not allowed. Only POST is supported.',
                'migration_notice' => 'Please use POST /api/webhooks/fal/{provider} instead',
            ], 405);
        }

        // Try to determine the provider from the request
        // This is a best-effort migration - ideally clients should move to the new endpoint
        $provider = $this->detectProviderFromRequest($request);

        if (!$provider) {
            return response()->json([
                'error' => 'Unable to determine FAL provider',
                'migration_notice' => 'Please use POST /api/webhooks/fal/{provider} instead',
            ], 400);
        }

        logger()->warning('Legacy FAL webhook route used', [
            'provider' => $provider,
            'ip' => $request->ip(),
            'message' => 'Please migrate to POST /api/webhooks/fal/{provider}',
        ]);

        // Forward to unified webhook handler
        $controller = new UnifiedFalWebhookController(
            app(\App\Extensions\FluxPro\System\Services\FalWebhookVerificationService::class)
        );

        return $controller->handle($provider, $request);
    }

    /**
     * Detect provider from request payload or headers
     */
    private function detectProviderFromRequest(Request $request): ?string
    {
        // Check for provider hint in headers
        $provider = $request->header('X-Fal-Provider');
        if ($provider) {
            return strtolower($provider);
        }

        // Try to detect from request_id pattern or payload hints
        // This is a heuristic and might not work for all cases
        $payload = $request->json()->all();

        // If we can't determine the provider, default to flux-pro (most common)
        // This is a migration aid - clients should use the new endpoint
        return 'flux-pro';
    }
}

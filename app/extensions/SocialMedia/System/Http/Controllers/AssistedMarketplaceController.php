<?php

namespace App\Extensions\SocialMedia\System\Http\Controllers;

use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Services\AssistedMarketplaceService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class AssistedMarketplaceController extends Controller
{
    public function __construct(
        private readonly AssistedMarketplaceService $service
    ) {}

    public function prepare(
        Request $request,
        DistributionItem $item,
        string $destination
    ): JsonResponse {
        $this->assertOwned($item);
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:128',
            'listing' => 'required|array',
            'listing.vertical' => 'nullable|string|max:100',
            'listing.business_subtype' => 'nullable|string|max:150',
            'listing.title' => 'required|string|max:255',
            'listing.description' => 'required|string|max:20000',
            'listing.category' => 'required|string|max:255',
            'listing.condition' => 'nullable|string|max:255',
            'listing.price_minor' => 'required|integer|min:0',
            'listing.currency' => 'required|string|size:3',
            'listing.location' => 'required|string|max:500',
            'listing.call_to_action' => 'nullable|string|max:255',
            'listing.image_urls' => 'required|array|min:1',
            'listing.image_urls.*' => 'required|string|max:2048',
            'listing.image_overlays' => 'nullable|array',
            'listing.image_overlays.*' => 'string|max:500',
            'listing.attributes' => 'nullable|array',
            'listing.vertical_fields' => 'nullable|array',
            'listing.delivery_details' => 'nullable|array',
            'listing.contact_preferences' => 'nullable|array',
            'listing.response_templates' => 'nullable|array',
            'listing.response_templates.*' => 'string|max:2000',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            $destination,
            fn () => $this->service->preparePackage(
                $request->user(),
                $item,
                $destination,
                (array) $validated['listing'],
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function open(
        Request $request,
        DistributionItem $item,
        string $destination
    ): JsonResponse {
        $this->assertOwned($item);
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            $destination,
            fn () => $this->service->openOfficialDestination(
                $request->user(),
                $item,
                $destination,
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function complete(
        Request $request,
        DistributionItem $item,
        string $destination
    ): JsonResponse {
        $this->assertOwned($item);
        $validated = $request->validate([
            'human_confirmed' => 'required|accepted',
            'external_url' => 'required|string|max:2048',
            'external_listing_id' => 'nullable|string|max:255',
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            $destination,
            fn () => $this->service->markCompleted(
                $request->user(),
                $item,
                $destination,
                (bool) $validated['human_confirmed'],
                (string) $validated['external_url'],
                (string) $validated['idempotency_key'],
                $validated['external_listing_id'] ?? null
            )
        ));
    }

    public function renew(
        Request $request,
        DistributionItem $item,
        string $destination
    ): JsonResponse {
        $this->assertOwned($item);
        $validated = $request->validate([
            'idempotency_key' => 'required|string|max:128',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            $destination,
            fn () => $this->service->prepareRenewal(
                $request->user(),
                $item,
                $destination,
                (string) $validated['idempotency_key']
            )
        ));
    }

    public function enquiryHandoff(
        Request $request,
        DistributionItem $item,
        string $destination
    ): JsonResponse {
        $this->assertOwned($item);
        $validated = $request->validate([
            'enquiry.enquiry_id' => 'required|string|max:255',
            'enquiry.buyer_alias' => 'nullable|string|max:255',
            'enquiry.subject' => 'nullable|string|max:500',
            'enquiry.message' => 'required|string|max:10000',
            'enquiry.received_at' => 'nullable|date',
        ]);

        return $this->respond(fn () => $this->locked(
            $item,
            $destination,
            fn () => $this->service->enquiryHandoff(
                $request->user(),
                $item,
                $destination,
                (array) $validated['enquiry']
            )
        ));
    }

    public function status(
        Request $request,
        DistributionItem $item,
        string $destination
    ): JsonResponse {
        $this->assertOwned($item);

        return $this->respond(fn () => $this->service->status(
            $request->user(),
            $item,
            $destination
        ));
    }

    private function assertOwned(DistributionItem $item): void
    {
        if ((int) $item->user_id !== (int) Auth::id()) {
            abort(404);
        }
    }

    private function locked(
        DistributionItem $item,
        string $destination,
        callable $callback
    ): mixed {
        $lockName = implode(':', [
            'titan-reach',
            'assisted-marketplace',
            Auth::id(),
            $item->getKey(),
            hash('sha256', $destination),
        ]);

        try {
            return Cache::lock($lockName, 120)->block(5, function () use ($item, $callback) {
                $item->refresh();

                return $callback();
            });
        } catch (LockTimeoutException $exception) {
            throw new RuntimeException(
                'Another request is already processing this assisted marketplace listing.',
                previous: $exception
            );
        }
    }

    private function respond(callable $callback): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $callback(),
            ]);
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => trans('The assisted marketplace operation could not be completed.'),
            ], 500);
        }
    }
}

<?php

namespace App\Extensions\SocialMedia\System\Services;

use App\Extensions\SocialMedia\System\Enums\PlatformEnum;
use App\Extensions\SocialMedia\System\Helpers\Ebay;
use App\Extensions\SocialMedia\System\Models\DistributionItem;
use App\Extensions\SocialMedia\System\Models\SocialMediaPlatform;
use App\Models\User;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;

class EbayListingService
{
    public function __construct(
        private readonly Ebay $ebay,
        private readonly DistributionCapabilityService $capabilities
    ) {}

    public function readiness(User $user, SocialMediaPlatform $account): array
    {
        $this->assertAccount($user, $account);
        $marketplaceId = (string) ($account->credentials['marketplace_id']
            ?? config('social-media.ebay.marketplace_id', 'EBAY_AU'));

        $locations = $this->ebay->getInventoryLocations($account);
        $paymentPolicies = $this->ebay->getPaymentPolicies($account, $marketplaceId);
        $fulfillmentPolicies = $this->ebay->getFulfillmentPolicies($account, $marketplaceId);
        $returnPolicies = $this->ebay->getReturnPolicies($account, $marketplaceId);

        $result = [
            'ready' => $locations->successful()
                && $paymentPolicies->successful()
                && $fulfillmentPolicies->successful()
                && $returnPolicies->successful()
                && count((array) $locations->json('locations', [])) > 0
                && count((array) $paymentPolicies->json('paymentPolicies', [])) > 0
                && count((array) $fulfillmentPolicies->json('fulfillmentPolicies', [])) > 0
                && count((array) $returnPolicies->json('returnPolicies', [])) > 0,
            'environment' => $this->ebay->environment(),
            'marketplace_id' => $marketplaceId,
            'locations' => (array) $locations->json('locations', []),
            'payment_policies' => (array) $paymentPolicies->json('paymentPolicies', []),
            'fulfillment_policies' => (array) $fulfillmentPolicies->json('fulfillmentPolicies', []),
            'return_policies' => (array) $returnPolicies->json('returnPolicies', []),
        ];

        $this->audit($user, $account, 'ebay_readiness', $result);

        return $result;
    }

    public function syncDraft(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $listing,
        string $idempotencyKey
    ): array {
        $this->assertContext($user, $item, $account, true);

        if ($cached = $this->cachedOperation($item, 'draft', $idempotencyKey)) {
            return $cached;
        }

        $listing = $this->validatedListing($listing);
        $inventoryResponse = $this->ebay->createOrReplaceInventoryItem(
            $account,
            $listing['sku'],
            $this->inventoryPayload($listing)
        );
        $this->assertSuccessful($inventoryResponse, 'eBay inventory item synchronization failed.');

        $state = (array) data_get($item->payload, 'ebay', []);
        $offerId = (string) ($state['offer_id'] ?? '');
        $offerPayload = $this->offerPayload($listing);

        if ($offerId === '') {
            $offerResponse = $this->ebay->createOffer($account, $offerPayload);
            $this->assertSuccessful($offerResponse, 'eBay offer creation failed.');
            $offerId = (string) $offerResponse->json('offerId', '');

            if ($offerId === '') {
                throw new RuntimeException('eBay did not return an offer ID.');
            }
        } else {
            $offerResponse = $this->ebay->updateOffer($account, $offerId, $offerPayload);
            $this->assertSuccessful($offerResponse, 'eBay offer update failed.');
        }

        $result = [
            'status' => 'draft',
            'sku' => $listing['sku'],
            'offer_id' => $offerId,
            'marketplace_id' => $listing['marketplace_id'],
            'environment' => $this->ebay->environment(),
        ];

        $this->persistOperation($item, 'draft', $idempotencyKey, $listing, $result);
        $this->audit($user, $account, 'ebay_draft_synced', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'result' => $result,
        ]);

        return $result;
    }

    public function publish(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $listing,
        string $idempotencyKey
    ): array {
        $this->assertContext($user, $item, $account, true);

        if ($cached = $this->cachedOperation($item, 'publish', $idempotencyKey)) {
            return $cached;
        }

        $draft = $this->syncDraft(
            $user,
            $item,
            $account,
            $listing,
            $idempotencyKey . ':draft'
        );
        $response = $this->ebay->publishOffer($account, (string) $draft['offer_id']);
        $this->assertSuccessful($response, 'eBay offer publication failed.');

        $listingId = (string) $response->json('listingId', '');
        $result = [
            'status' => 'published',
            'sku' => $draft['sku'],
            'offer_id' => $draft['offer_id'],
            'listing_id' => $listingId,
            'environment' => $this->ebay->environment(),
        ];

        $this->persistOperation($item, 'publish', $idempotencyKey, $listing, $result);
        $item->update(['status' => 'published']);
        $this->audit($user, $account, 'ebay_offer_published', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'result' => $result,
        ]);

        return $result;
    }

    public function revise(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $listing,
        string $idempotencyKey
    ): array {
        $this->assertContext($user, $item, $account, true);

        if ($cached = $this->cachedOperation($item, 'revise', $idempotencyKey)) {
            return $cached;
        }

        $state = (array) data_get($item->payload, 'ebay', []);

        if (empty($state['offer_id'])) {
            throw new InvalidArgumentException('An eBay offer must exist before it can be revised.');
        }

        $listing = $this->validatedListing($listing);
        $inventoryResponse = $this->ebay->createOrReplaceInventoryItem(
            $account,
            $listing['sku'],
            $this->inventoryPayload($listing)
        );
        $this->assertSuccessful($inventoryResponse, 'eBay inventory item revision failed.');

        $offerResponse = $this->ebay->updateOffer(
            $account,
            (string) $state['offer_id'],
            $this->offerPayload($listing)
        );
        $this->assertSuccessful($offerResponse, 'eBay offer revision failed.');

        $result = [
            'status' => (string) $item->status,
            'sku' => $listing['sku'],
            'offer_id' => (string) $state['offer_id'],
            'listing_id' => (string) ($state['listing_id'] ?? ''),
            'revised_at' => now()->toIso8601String(),
        ];

        $this->persistOperation($item, 'revise', $idempotencyKey, $listing, $result);
        $this->audit($user, $account, 'ebay_offer_revised', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'result' => $result,
        ]);

        return $result;
    }

    public function withdraw(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        string $idempotencyKey
    ): array {
        $this->assertContext($user, $item, $account, true);

        if ($cached = $this->cachedOperation($item, 'withdraw', $idempotencyKey)) {
            return $cached;
        }

        $state = (array) data_get($item->payload, 'ebay', []);
        $offerId = (string) ($state['offer_id'] ?? '');

        if ($offerId === '') {
            throw new InvalidArgumentException('An eBay offer must exist before it can be withdrawn.');
        }

        $response = $this->ebay->withdrawOffer($account, $offerId);
        $this->assertSuccessful($response, 'eBay offer withdrawal failed.');

        $result = [
            'status' => 'withdrawn',
            'offer_id' => $offerId,
            'listing_id' => (string) ($state['listing_id'] ?? ''),
            'withdrawn_at' => now()->toIso8601String(),
        ];

        $this->persistOperation($item, 'withdraw', $idempotencyKey, [], $result);
        $item->update(['status' => 'withdrawn']);
        $this->audit($user, $account, 'ebay_offer_withdrawn', [
            'distribution_item_id' => $item->getKey(),
            'idempotency_key' => $idempotencyKey,
            'result' => $result,
        ]);

        return $result;
    }

    public function reconcile(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account
    ): array {
        $this->assertContext($user, $item, $account, false);
        $state = (array) data_get($item->payload, 'ebay', []);
        $offerId = (string) ($state['offer_id'] ?? '');

        if ($offerId === '') {
            throw new InvalidArgumentException('An eBay offer must exist before status reconciliation.');
        }

        $response = $this->ebay->getOffer($account, $offerId);
        $this->assertSuccessful($response, 'eBay offer reconciliation failed.');
        $result = [
            'offer_id' => $offerId,
            'listing_id' => (string) $response->json('listing.listingId', $state['listing_id'] ?? ''),
            'status' => (string) $response->json('status', 'unknown'),
            'available_quantity' => $response->json('availableQuantity'),
            'last_reconciled_at' => now()->toIso8601String(),
        ];

        $payload = (array) $item->payload;
        data_set($payload, 'ebay.reconciliation', $result);
        $item->update(['payload' => $payload]);
        $this->audit($user, $account, 'ebay_offer_reconciled', [
            'distribution_item_id' => $item->getKey(),
            'result' => $result,
        ]);

        return $result;
    }

    public function buyerQuestionHandoff(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        array $question
    ): array {
        $this->assertContext($user, $item, $account, false);
        $handoff = [
            'question_id' => (string) ($question['question_id'] ?? ''),
            'buyer_alias' => (string) ($question['buyer_alias'] ?? ''),
            'subject' => (string) ($question['subject'] ?? ''),
            'message' => (string) ($question['message'] ?? ''),
            'received_at' => (string) ($question['received_at'] ?? now()->toIso8601String()),
            'status' => 'human_handoff_required',
        ];

        $payload = (array) $item->payload;
        $handoffs = (array) data_get($payload, 'ebay.buyer_question_handoffs', []);
        $handoffs[] = $handoff;
        data_set($payload, 'ebay.buyer_question_handoffs', array_slice($handoffs, -100));
        $item->update(['payload' => $payload]);
        $this->audit($user, $account, 'ebay_buyer_question_handoff', [
            'distribution_item_id' => $item->getKey(),
            'handoff' => $handoff,
        ]);

        return $handoff;
    }

    private function assertContext(
        User $user,
        DistributionItem $item,
        SocialMediaPlatform $account,
        bool $requiresApproval
    ): void {
        if ((int) $item->user_id !== (int) $user->getKey()
            || (int) $account->user_id !== (int) $user->getKey()) {
            throw new RuntimeException('The eBay listing context is outside the current tenant.');
        }

        if (! in_array($item->content_type, [
            DistributionItem::TYPE_MARKETPLACE_LISTING,
            DistributionItem::TYPE_PRODUCT_OFFER,
            DistributionItem::TYPE_VEHICLE_LISTING,
        ], true)) {
            throw new InvalidArgumentException('This distribution item cannot be sent to eBay.');
        }

        $this->assertAccount($user, $account);
        $capability = $this->capabilities->forDestination($user, 'ebay', $account);

        if (! $capability['available']) {
            throw new RuntimeException('eBay is unavailable for this account: ' . ($capability['reason'] ?? 'unknown'));
        }

        if ($requiresApproval && $item->approval_status !== 'approved') {
            throw new RuntimeException('The eBay listing requires approval before external changes.');
        }
    }

    private function assertAccount(User $user, SocialMediaPlatform $account): void
    {
        if ((int) $account->user_id !== (int) $user->getKey()
            || (string) $account->platform !== PlatformEnum::ebay->value
            || ! $account->isConnected()) {
            throw new RuntimeException('A connected eBay seller account is required.');
        }
    }

    private function validatedListing(array $listing): array
    {
        $required = [
            'sku',
            'marketplace_id',
            'merchant_location_key',
            'category_id',
            'condition',
            'quantity',
            'price.value',
            'price.currency',
            'title',
            'description',
            'image_urls',
            'fulfillment_policy_id',
            'payment_policy_id',
            'return_policy_id',
        ];

        foreach ($required as $key) {
            $value = data_get($listing, $key);

            if ($value === null || $value === '' || $value === []) {
                throw new InvalidArgumentException("Missing required eBay listing field: {$key}.");
            }
        }

        $listing['quantity'] = max(0, (int) $listing['quantity']);
        $listing['image_urls'] = array_values(array_unique(array_filter((array) $listing['image_urls'])));
        $listing['format'] = (string) ($listing['format'] ?? 'FIXED_PRICE');
        $listing['listing_duration'] = (string) ($listing['listing_duration'] ?? 'GTC');
        $listing['aspects'] = (array) ($listing['aspects'] ?? []);

        return $listing;
    }

    private function inventoryPayload(array $listing): array
    {
        return [
            'availability' => [
                'shipToLocationAvailability' => [
                    'quantity' => $listing['quantity'],
                ],
            ],
            'condition' => $listing['condition'],
            'product' => [
                'title' => $listing['title'],
                'description' => $listing['description'],
                'aspects' => $listing['aspects'],
                'imageUrls' => $listing['image_urls'],
                ...Arr::only($listing, ['brand', 'mpn', 'upc', 'ean', 'isbn']),
            ],
        ];
    }

    private function offerPayload(array $listing): array
    {
        return [
            'sku' => $listing['sku'],
            'marketplaceId' => $listing['marketplace_id'],
            'format' => $listing['format'],
            'availableQuantity' => $listing['quantity'],
            'categoryId' => (string) $listing['category_id'],
            'merchantLocationKey' => $listing['merchant_location_key'],
            'listingDescription' => $listing['description'],
            'listingDuration' => $listing['listing_duration'],
            'listingPolicies' => [
                'fulfillmentPolicyId' => $listing['fulfillment_policy_id'],
                'paymentPolicyId' => $listing['payment_policy_id'],
                'returnPolicyId' => $listing['return_policy_id'],
            ],
            'pricingSummary' => [
                'price' => [
                    'value' => (string) data_get($listing, 'price.value'),
                    'currency' => (string) data_get($listing, 'price.currency'),
                ],
            ],
        ];
    }

    private function cachedOperation(
        DistributionItem $item,
        string $operation,
        string $idempotencyKey
    ): ?array {
        $stored = data_get($item->payload, "ebay.operations.{$operation}");

        if (is_array($stored)
            && hash_equals((string) ($stored['idempotency_key'] ?? ''), $idempotencyKey)
            && ($stored['status'] ?? null) === 'succeeded') {
            return (array) ($stored['result'] ?? []);
        }

        return null;
    }

    private function persistOperation(
        DistributionItem $item,
        string $operation,
        string $idempotencyKey,
        array $listing,
        array $result
    ): void {
        $payload = (array) $item->payload;
        data_set($payload, 'ebay.sku', $result['sku'] ?? data_get($payload, 'ebay.sku'));
        data_set($payload, 'ebay.offer_id', $result['offer_id'] ?? data_get($payload, 'ebay.offer_id'));
        data_set($payload, 'ebay.listing_id', $result['listing_id'] ?? data_get($payload, 'ebay.listing_id'));

        if ($listing !== []) {
            data_set($payload, 'ebay.listing_snapshot', $listing);
        }

        data_set($payload, "ebay.operations.{$operation}", [
            'idempotency_key' => $idempotencyKey,
            'status' => 'succeeded',
            'result' => $result,
            'completed_at' => now()->toIso8601String(),
        ]);

        $item->update(['payload' => $payload]);
        $item->refresh();
    }

    private function assertSuccessful(Response $response, string $message): void
    {
        if ($response->successful()) {
            return;
        }

        $errorId = (string) ($response->json('errors.0.errorId') ?? $response->status());
        $errorMessage = (string) ($response->json('errors.0.message') ?? $message);

        throw new RuntimeException("{$message} [{$errorId}] {$errorMessage}");
    }

    private function audit(
        User $user,
        SocialMediaPlatform $account,
        string $action,
        array $snapshot
    ): void {
        if (! Schema::hasTable('ext_social_media_distribution_audits')) {
            return;
        }

        DB::table('ext_social_media_distribution_audits')->insert([
            'user_id' => $user->getKey(),
            'social_media_platform_id' => $account->getKey(),
            'destination' => 'ebay',
            'action' => $action,
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }
}

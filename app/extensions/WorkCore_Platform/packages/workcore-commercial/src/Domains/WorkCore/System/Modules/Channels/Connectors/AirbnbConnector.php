<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Connectors;

use App\Domains\WorkCore\System\Modules\Channels\Models\{
    ChannelInventory,
    ChannelOrder,
    ChannelPricing,
};

class AirbnbConnector extends BaseChannelConnector
{
    protected string $baseUrl = 'https://api.airbnb.com/v2';

    public function authenticate(): bool
    {
        $auth = $this->getAuth();

        if (!$auth['api_token']) {
            $this->logError('Missing API token for Airbnb authentication');
            return false;
        }

        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/user", [
                'Authorization' => "Bearer {$auth['api_token']}",
            ]);

            if (isset($response['user'])) {
                $this->channel->markAuthenticated();
                $this->log('Airbnb authentication successful');
                return true;
            }

            $this->channel->markAuthenticationFailed();
            $this->logError('Airbnb authentication failed', ['response' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->channel->markAuthenticationFailed();
            $this->logError('Airbnb authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function test(): array
    {
        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/listings", [
                'Authorization' => "Bearer {$this->channel->api_token}",
            ]);

            if (isset($response['listings'])) {
                return [
                    'success' => true,
                    'message' => 'Airbnb connection test successful',
                    'listings_count' => count($response['listings']),
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Unknown error',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function syncInventory(): array
    {
        $this->log('Starting Airbnb inventory sync');
        $synced = 0;
        $errors = 0;

        try {
            $listings = $this->fetchListings();

            foreach ($listings as $listing) {
                try {
                    $availability = $this->fetchAvailability($listing['id']);
                    $this->updateInventory($listing['id'], $availability);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync listing {$listing['id']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            $this->channel->update(['last_sync_at' => now()]);

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Airbnb inventory sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncOrders(): array
    {
        $this->log('Starting Airbnb orders sync');
        $synced = 0;
        $errors = 0;

        try {
            $reservations = $this->fetchReservations();

            foreach ($reservations as $reservation) {
                try {
                    $this->importOrder($reservation);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to import reservation {$reservation['id']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Airbnb orders sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncPricing(): array
    {
        $this->log('Starting Airbnb pricing sync');
        $synced = 0;
        $errors = 0;

        try {
            $listings = $this->fetchListings();

            foreach ($listings as $listing) {
                try {
                    $pricing = $this->fetchPricing($listing['id']);
                    $this->updatePricing($listing['id'], $pricing);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync pricing for {$listing['id']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Airbnb pricing sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchListings(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/listings", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['listings'] ?? [];
    }

    private function fetchAvailability(string $listingId): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/listings/{$listingId}/availability", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response ?? [];
    }

    private function fetchPricing(string $listingId): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/listings/{$listingId}/pricing", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response ?? [];
    }

    private function fetchReservations(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/reservations", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['reservations'] ?? [];
    }

    private function updateInventory(string $listingId, array $availability): void
    {
        $inventory = ChannelInventory::firstOrCreate(
            [
                'channel_id' => $this->channel->id,
                'product_id' => $listingId,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
            ]
        );

        $available = $availability['available_count'] ?? 0;
        $inventory->updateAvailable($available);
    }

    private function updatePricing(string $listingId, array $pricingData): void
    {
        $pricing = ChannelPricing::firstOrCreate(
            [
                'channel_id' => $this->channel->id,
                'product_id' => $listingId,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'currency' => 'USD',
            ]
        );

        $price = $pricingData['price'] ?? $pricingData['nightly_price'] ?? 0;
        $pricing->updatePrice((float)$price);
    }

    private function importOrder(array $reservation): void
    {
        ChannelOrder::updateOrCreate(
            [
                'channel_id' => $this->channel->id,
                'order_id_remote' => $reservation['id'],
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'status' => 'imported',
                'order_data' => $reservation,
            ]
        );
    }
}

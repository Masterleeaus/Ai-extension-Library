<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Connectors;

use App\Domains\WorkCore\System\Modules\Channels\Models\{
    ChannelInventory,
    ChannelOrder,
    ChannelPricing,
};

class BookingConnector extends BaseChannelConnector
{
    protected string $baseUrl = 'https://api.booking.com/v1';

    public function authenticate(): bool
    {
        $auth = $this->getAuth();

        if (!$auth['api_token']) {
            $this->logError('Missing API token for Booking.com authentication');
            return false;
        }

        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/account", [
                'Authorization' => "Bearer {$auth['api_token']}",
            ]);

            if (isset($response['account'])) {
                $this->channel->markAuthenticated();
                $this->log('Booking.com authentication successful');
                return true;
            }

            $this->channel->markAuthenticationFailed();
            $this->logError('Booking.com authentication failed', ['response' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->channel->markAuthenticationFailed();
            $this->logError('Booking.com authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function test(): array
    {
        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/properties", [
                'Authorization' => "Bearer {$this->channel->api_token}",
            ]);

            if (isset($response['properties'])) {
                return [
                    'success' => true,
                    'message' => 'Booking.com connection test successful',
                    'properties_count' => count($response['properties']),
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
        $this->log('Starting Booking.com inventory sync');
        $synced = 0;
        $errors = 0;

        try {
            $properties = $this->fetchProperties();

            foreach ($properties as $property) {
                try {
                    $availability = $this->fetchAvailability($property['id']);
                    $this->updateInventory($property['id'], $availability);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync property {$property['id']}", ['error' => $e->getMessage()]);
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
            $this->logError('Booking.com inventory sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncOrders(): array
    {
        $this->log('Starting Booking.com orders sync');
        $synced = 0;
        $errors = 0;

        try {
            $bookings = $this->fetchBookings();

            foreach ($bookings as $booking) {
                try {
                    $this->importOrder($booking);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to import booking {$booking['id']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Booking.com orders sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncPricing(): array
    {
        $this->log('Starting Booking.com pricing sync');
        $synced = 0;
        $errors = 0;

        try {
            $properties = $this->fetchProperties();

            foreach ($properties as $property) {
                try {
                    $pricing = $this->fetchPricing($property['id']);
                    $this->updatePricing($property['id'], $pricing);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync pricing for {$property['id']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Booking.com pricing sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchProperties(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/properties", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['properties'] ?? [];
    }

    private function fetchAvailability(string $propertyId): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/properties/{$propertyId}/availability", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response ?? [];
    }

    private function fetchPricing(string $propertyId): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/properties/{$propertyId}/rates", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response ?? [];
    }

    private function fetchBookings(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/bookings", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['bookings'] ?? [];
    }

    private function updateInventory(string $propertyId, array $availability): void
    {
        $inventory = ChannelInventory::firstOrCreate(
            [
                'channel_id' => $this->channel->id,
                'product_id' => $propertyId,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
            ]
        );

        $available = $availability['available_rooms'] ?? 0;
        $inventory->updateAvailable($available);
    }

    private function updatePricing(string $propertyId, array $pricingData): void
    {
        $pricing = ChannelPricing::firstOrCreate(
            [
                'channel_id' => $this->channel->id,
                'product_id' => $propertyId,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'currency' => $pricingData['currency'] ?? 'USD',
            ]
        );

        $price = $pricingData['price'] ?? $pricingData['daily_rate'] ?? 0;
        $pricing->updatePrice((float)$price);
    }

    private function importOrder(array $booking): void
    {
        ChannelOrder::updateOrCreate(
            [
                'channel_id' => $this->channel->id,
                'order_id_remote' => $booking['id'],
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'status' => 'imported',
                'order_data' => $booking,
            ]
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Connectors;

use App\Domains\WorkCore\System\Modules\Channels\Models\{
    ChannelInventory,
    ChannelOrder,
    ChannelPricing,
};

class EbayConnector extends BaseChannelConnector
{
    protected string $baseUrl = 'https://api.ebay.com';

    public function authenticate(): bool
    {
        $auth = $this->getAuth();

        if (!$auth['api_token'] || !$auth['api_secret']) {
            $this->logError('Missing API credentials for eBay authentication');
            return false;
        }

        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/sell/account/v1/account_summary", [
                'Authorization' => "Bearer {$auth['api_token']}",
            ]);

            if (isset($response['sellerId'])) {
                $this->channel->markAuthenticated();
                $this->log('eBay authentication successful');
                return true;
            }

            $this->channel->markAuthenticationFailed();
            $this->logError('eBay authentication failed', ['response' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->channel->markAuthenticationFailed();
            $this->logError('eBay authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function test(): array
    {
        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/sell/inventory/v1/inventory_item", [
                'Authorization' => "Bearer {$this->channel->api_token}",
            ]);

            if (isset($response['inventoryItems'])) {
                return [
                    'success' => true,
                    'message' => 'eBay connection test successful',
                    'items_count' => count($response['inventoryItems']),
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
        $this->log('Starting eBay inventory sync');
        $synced = 0;
        $errors = 0;

        try {
            $items = $this->fetchInventory();

            foreach ($items as $item) {
                try {
                    $quantity = $item['quantity'] ?? $item['available'] ?? 0;
                    $this->updateInventory($item['sku'], $quantity);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync item {$item['sku']}", ['error' => $e->getMessage()]);
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
            $this->logError('eBay inventory sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncOrders(): array
    {
        $this->log('Starting eBay orders sync');
        $synced = 0;
        $errors = 0;

        try {
            $orders = $this->fetchOrders();

            foreach ($orders as $order) {
                try {
                    $this->importOrder($order);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to import order {$order['orderId']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('eBay orders sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncPricing(): array
    {
        $this->log('Starting eBay pricing sync');
        $synced = 0;
        $errors = 0;

        try {
            $listings = $this->fetchListings();

            foreach ($listings as $listing) {
                try {
                    $price = $listing['price'] ?? 0;
                    $this->updatePricing($listing['sku'] ?? $listing['itemId'], $price);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync pricing for {$listing['sku']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('eBay pricing sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchInventory(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/sell/inventory/v1/inventory_item", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['inventoryItems'] ?? [];
    }

    private function fetchOrders(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/sell/fulfillment/v1/order", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['orders'] ?? [];
    }

    private function fetchListings(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/sell/inventory/v1/listing", [
            'Authorization' => "Bearer {$this->channel->api_token}",
        ]);

        return $response['listings'] ?? [];
    }

    private function updateInventory(string $sku, int $quantity): void
    {
        $inventory = ChannelInventory::firstOrCreate(
            [
                'channel_id' => $this->channel->id,
                'product_id' => $sku,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
            ]
        );

        $inventory->updateAvailable($quantity);
    }

    private function updatePricing(string $sku, float $price): void
    {
        $pricing = ChannelPricing::firstOrCreate(
            [
                'channel_id' => $this->channel->id,
                'product_id' => $sku,
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'currency' => 'USD',
            ]
        );

        $pricing->updatePrice($price);
    }

    private function importOrder(array $order): void
    {
        ChannelOrder::updateOrCreate(
            [
                'channel_id' => $this->channel->id,
                'order_id_remote' => $order['orderId'],
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'status' => 'imported',
                'order_data' => $order,
            ]
        );
    }
}

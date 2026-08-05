<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Connectors;

use App\Domains\WorkCore\System\Modules\Channels\Models\{
    ChannelInventory,
    ChannelOrder,
    ChannelPricing,
};

class AmazonConnector extends BaseChannelConnector
{
    protected string $baseUrl = 'https://sellingpartnerapi-na.amazon.com';

    public function authenticate(): bool
    {
        $auth = $this->getAuth();

        if (!$auth['api_token'] || !$auth['api_secret']) {
            $this->logError('Missing API credentials for Amazon authentication');
            return false;
        }

        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/sellers/v1/account/information", [
                'x-amzn-access-token' => $auth['api_token'],
            ]);

            if (isset($response['sellerId'])) {
                $this->channel->markAuthenticated();
                $this->log('Amazon authentication successful');
                return true;
            }

            $this->channel->markAuthenticationFailed();
            $this->logError('Amazon authentication failed', ['response' => $response]);
            return false;
        } catch (\Exception $e) {
            $this->channel->markAuthenticationFailed();
            $this->logError('Amazon authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function test(): array
    {
        try {
            $response = $this->makeRequest('get', "{$this->baseUrl}/catalog/v0/items", [
                'x-amzn-access-token' => $this->channel->api_token,
            ]);

            if (isset($response['items'])) {
                return [
                    'success' => true,
                    'message' => 'Amazon connection test successful',
                    'items_count' => count($response['items']),
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
        $this->log('Starting Amazon inventory sync');
        $synced = 0;
        $errors = 0;

        try {
            $skus = $this->fetchInventory();

            foreach ($skus as $sku) {
                try {
                    $this->updateInventory($sku['sku'], $sku['quantity'] ?? 0);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync SKU {$sku['sku']}", ['error' => $e->getMessage()]);
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
            $this->logError('Amazon inventory sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncOrders(): array
    {
        $this->log('Starting Amazon orders sync');
        $synced = 0;
        $errors = 0;

        try {
            $orders = $this->fetchOrders();

            foreach ($orders as $order) {
                try {
                    $this->importOrder($order);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to import order {$order['AmazonOrderId']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Amazon orders sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function syncPricing(): array
    {
        $this->log('Starting Amazon pricing sync');
        $synced = 0;
        $errors = 0;

        try {
            $pricing = $this->fetchPricing();

            foreach ($pricing as $item) {
                try {
                    $this->updatePricing($item['sku'], $item['price'] ?? 0);
                    $synced++;
                } catch (\Exception $e) {
                    $this->logError("Failed to sync pricing for {$item['sku']}", ['error' => $e->getMessage()]);
                    $errors++;
                }
            }

            return [
                'status' => 'completed',
                'synced' => $synced,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            $this->logError('Amazon pricing sync failed', ['error' => $e->getMessage()]);
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function fetchInventory(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/fba/inventory/v1/summaries", [
            'x-amzn-access-token' => $this->channel->api_token,
        ]);

        return $response['inventorySummaries'] ?? [];
    }

    private function fetchOrders(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/orders/v0/orders", [
            'x-amzn-access-token' => $this->channel->api_token,
        ]);

        return $response['Orders'] ?? [];
    }

    private function fetchPricing(): array
    {
        $response = $this->makeRequest('get', "{$this->baseUrl}/pricing/v0/items", [
            'x-amzn-access-token' => $this->channel->api_token,
        ]);

        return $response['Items'] ?? [];
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
                'order_id_remote' => $order['AmazonOrderId'],
            ],
            [
                'tenant_id' => $this->channel->tenant_id,
                'status' => 'imported',
                'order_data' => $order,
            ]
        );
    }
}

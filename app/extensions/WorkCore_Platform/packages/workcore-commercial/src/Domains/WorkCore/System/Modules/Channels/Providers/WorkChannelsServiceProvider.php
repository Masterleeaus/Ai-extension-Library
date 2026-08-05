<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Channels\Providers;

use App\Domains\WorkCore\System\Actions\{ActionDefinition, BusinessActionRegistry};
use App\Domains\WorkCore\System\Modules\Channels\Actions\{
    DisconnectChannel,
    RegisterChannel,
    SearchChannels,
    SyncChannelInventory,
    SyncChannelOrders,
    SyncChannelPricing,
    TestChannelConnection,
    UpdateChannel,
};
use App\Domains\WorkCore\System\Modules\Channels\Contracts\ChannelRepositoryContract;
use App\Domains\WorkCore\System\Modules\Channels\Repositories\EloquentChannelRepository;
use App\Domains\WorkCore\System\Modules\Channels\Services\{
    ChannelService,
    InventorySyncService,
    OrderSyncService,
    PricingSyncService,
};
use App\Domains\WorkCore\System\ReadModels\{ReadModelDefinition, ReadModelRegistry};
use Illuminate\Support\ServiceProvider;

final class WorkChannelsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register services
        $this->app->singleton(ChannelService::class);
        $this->app->singleton(InventorySyncService::class);
        $this->app->singleton(OrderSyncService::class);
        $this->app->singleton(PricingSyncService::class);

        // Register repository
        $this->app->bind(ChannelRepositoryContract::class, EloquentChannelRepository::class);

        // Register business actions
        $actions = $this->app->make(BusinessActionRegistry::class);
        $definitions = [
            ['workcore.channels.channel.register', RegisterChannel::class, 'medium', 'manage_channels'],
            ['workcore.channels.channel.update', UpdateChannel::class, 'medium', 'manage_channels'],
            ['workcore.channels.channel.disconnect', DisconnectChannel::class, 'high', 'manage_channels'],
            ['workcore.channels.channel.test', TestChannelConnection::class, 'low', 'view_channels'],
            ['workcore.channels.inventory.sync', SyncChannelInventory::class, 'high', 'sync_inventory'],
            ['workcore.channels.orders.sync', SyncChannelOrders::class, 'high', 'sync_orders'],
            ['workcore.channels.pricing.sync', SyncChannelPricing::class, 'medium', 'sync_pricing'],
        ];

        foreach ($definitions as [$key, $handler, $risk, $permission]) {
            $actions->register(
                new ActionDefinition(
                    $key,
                    $handler,
                    $risk,
                    true,
                    'workcore.channels',
                    (string) config("workcore.channels.permissions.{$permission}"),
                    [
                        'domain' => 'channels',
                        'canonical_owner' => 'WorkCore Channels',
                    ]
                )
            );
        }

        // Register read models
        $reads = $this->app->make(ReadModelRegistry::class);
        $viewPermission = (string) config('workcore.channels.permissions.view');
        $reads->register(new ReadModelDefinition('workcore.channels.search', SearchChannels::class, 'workcore.channels', permission: $viewPermission));
    }

    public function boot(): void
    {
        if ((bool) config('workcore.channels.routes_enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        }
    }
}

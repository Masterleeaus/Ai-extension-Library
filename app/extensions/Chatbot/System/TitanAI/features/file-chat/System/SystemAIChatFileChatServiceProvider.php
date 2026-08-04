<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatFileChat\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Author: MagicAI Team <info@liquid-themes.com>
 *
 * @note When you create a new service provider, make sure to add it to the "MarketplaceServiceProvider". Otherwise, your Laravel application won’t recognize this provider, and the related functions won’t work properly.
 * @note If you want to perform a specific action when an extension is uninstalled, you can use the UninstallExtensionServiceProviderInterface. By implementing this interface, you can define custom operations that will be triggered during the uninstallation of the extension.
 * @note The registerKey() method is used to provide a unique identifier for the extension, which is essential for the healthy check and other functionalities.
 */
use App\Extensions\SystemAIChatFileChat\System\Support\RegistersSystemAIChatCapability;

class SystemAIChatFileChatServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface, UninstallExtensionServiceProviderInterface
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(Kernel $kernel): void
    {
        RegistersSystemAIChatCapability::register();
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->publishAssets()
            ->registerComponents();

    }

    public function registerComponents(): static
    {
        //        $this->loadViewComponentsAs('system-ai-chat-file-chat', []);

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            //            __DIR__ . '/../resources/assets/js' => public_path('vendor/system-ai-chat-file-chat/js'),
            //            __DIR__ . '/../resources/assets/images' => public_path('vendor/system-ai-chat-file-chat/images'),
        ], 'extension');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/systemaichatfilechat.php', 'system-ai-chat-file-chat');
        $this->publishes([__DIR__ . '/../config' => config_path('extensions/system-ai-chat-file-chat')], 'extension-config');

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'system-ai-chat-file-chat');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'system-ai-chat-file-chat');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
        // TODO: Implement uninstall() method.
    }

    public function registerKey(): string
    {
        return 'system-ai-chat-file-chat';
    }
}

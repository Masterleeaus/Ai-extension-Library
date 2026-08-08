<?php

declare(strict_types=1);

namespace App\Extensions\SocialMedia\System;

use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\SocialMedia\System\Http\Controllers\AssistedMarketplaceController;
use App\Extensions\SocialMedia\System\Http\Controllers\Common\DemoDataController;
use App\Extensions\SocialMedia\System\Http\Controllers\Common\SocialMediaCampaignCommonController;
use App\Extensions\SocialMedia\System\Http\Controllers\Common\SocialMediaCompanyCommonController;
use App\Extensions\SocialMedia\System\Http\Controllers\EbayListingController;
use App\Extensions\SocialMedia\System\Http\Controllers\EngagementController;
use App\Extensions\SocialMedia\System\Http\Controllers\GoogleBusinessProfileController;
use App\Extensions\SocialMedia\System\Http\Controllers\ImageStatusController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\EbayController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\FacebookController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\GoogleBusinessProfileController as GoogleBusinessProfileOauthController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\InstagramController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\LinkedinController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\PinterestController as PinterestOauthController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\TiktokController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\XController;
use App\Extensions\SocialMedia\System\Http\Controllers\Oauth\YoutubeController;
use App\Extensions\SocialMedia\System\Http\Controllers\PinterestController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaCalendarController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaCampaignController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaPlatformController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaPostController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaSettingController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaUploadController;
use App\Extensions\SocialMedia\System\Http\Controllers\SocialMediaVideoController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SocialMediaServiceProvider extends ServiceProvider implements UninstallExtensionServiceProviderInterface
{
    public function register(): void
    {
        $this->registerConfig();
    }

    public function boot(Kernel $kernel): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->publishAssets()
            ->registerComponents()
            ->registerCommand();
    }

    public function registerCommand(): static
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\PublishedCommand::class,
                Console\Commands\XRefreshTokenCommand::class,
                Console\Commands\XPostMetricsCommand::class,
                Console\Commands\FacebookPostMetricsCommand::class,
                Console\Commands\InstagramPostMetricsCommand::class,
                Console\Commands\SocialMediaDailyMetricsCommand::class,
                Console\Commands\SocialMediaFollowersSyncCommand::class,
            ]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('app:social-media-published-command')->everyTwoMinutes();
                $schedule->command('app:social-media-x-refresh')->everyThreeMinutes();
                $schedule->command('app:social-media-facebook-post-metrics')->everyThreeMinutes();
                $schedule->command('app:social-media-instagram-post-metrics')->everyThreeMinutes();
                $schedule->command('app:social-media-daily-metrics')->hourly();
                $schedule->command('php artisan app:social-media-sync-followers')->hourly();
            });
        }

        return $this;
    }

    public function registerComponents(): static
    {
        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => public_path('vendor/social-media'),
        ], 'extension');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/social-media.php', 'social-media');
        $this->mergeConfigFrom(__DIR__ . '/../config/engagement.php', 'social-media.engagement');
        $this->mergeConfigFrom(__DIR__ . '/../config/ebay.php', 'social-media.ebay');
        $this->mergeConfigFrom(
            __DIR__ . '/../config/assisted-marketplaces.php',
            'social-media.assisted_marketplaces'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/../config/google-business-profile.php',
            'social-media.google_business_profile'
        );
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pinterest.php',
            'social-media.pinterest'
        );
        config()->set('social-media.distribution.destinations.ebay', config('social-media.ebay.destination'));
        config()->set(
            'social-media.distribution.destinations.google-business-profile',
            config('social-media.google_business_profile.destination')
        );
        config()->set(
            'social-media.distribution.destinations.pinterest',
            config('social-media.pinterest.destination')
        );

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'social-media');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'social-media');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router->get('tiktok/verify', [TiktokController::class, 'verify'])->name('tiktok.verify');
                $router->get('social-media-demo-data', DemoDataController::class)->name('demo-data');

                $router->any('social-media/webhook/instagram', [InstagramController::class, 'webhook'])->name('social-media.oauth.webhook.facebook')->withoutMiddleware('auth');
                $router->any('social-media/webhook/facebook', [FacebookController::class, 'webhook'])->name('social-media.oauth.webhook.facebook')->withoutMiddleware('auth');

                $router->group([
                    'prefix' => 'social-media/oauth',
                ], function (Router $router) {
                    $router->get('redirect/tiktok', [TiktokController::class, 'redirect'])->name('social-media.oauth.connect.tiktok');
                    $router->get('callback/tiktok', [TiktokController::class, 'callback'])->name('social-media.oauth.callback.tiktok');

                    $router->get('redirect/instagram', [InstagramController::class, 'redirect'])->name('social-media.oauth.connect.instagram');
                    $router->get('callback/instagram', [InstagramController::class, 'callback'])->name('social-media.oauth.callback.instagram');

                    $router->get('redirect/x', [XController::class, 'redirect'])->name('social-media.oauth.connect.x');
                    $router->get('callback/x', [XController::class, 'callback'])->name('social-media.oauth.callback.x');

                    $router->get('redirect/facebook', [FacebookController::class, 'redirect'])->name('social-media.oauth.connect.facebook');
                    $router->get('callback/facebook', [FacebookController::class, 'callback'])->name('social-media.oauth.callback.facebook');

                    $router->get('redirect/linkedin', [LinkedinController::class, 'redirect'])->name('social-media.oauth.connect.linkedin');
                    $router->get('callback/linkedin', [LinkedinController::class, 'callback'])->name('social-media.oauth.callback.linkedin');

                    $router->get('redirect/youtube', [YoutubeController::class, 'redirectYoutube'])->name('social-media.oauth.connect.youtube');
                    $router->get('callback/youtube', [YoutubeController::class, 'callbackYoutube'])->name('social-media.oauth.callback.youtube');

                    $router->get('redirect/youtube-shorts', [YoutubeController::class, 'redirectYoutubeShorts'])->name('social-media.oauth.connect.youtube-shorts');
                    $router->get('callback/youtube-shorts', [YoutubeController::class, 'callbackYoutubeShorts'])->name('social-media.oauth.callback.youtube-shorts');

                    $router->get('redirect/ebay', [EbayController::class, 'redirect'])->name('social-media.oauth.connect.ebay');
                    $router->get('callback/ebay', [EbayController::class, 'callback'])->name('social-media.oauth.callback.ebay');

                    $router->get('redirect/google-business-profile', [GoogleBusinessProfileOauthController::class, 'redirect'])->name('social-media.oauth.connect.google-business-profile');
                    $router->get('callback/google-business-profile', [GoogleBusinessProfileOauthController::class, 'callback'])->name('social-media.oauth.callback.google-business-profile');

                    $router->get('redirect/pinterest', [PinterestOauthController::class, 'redirect'])->name('social-media.oauth.connect.pinterest');
                    $router->get('callback/pinterest', [PinterestOauthController::class, 'callback'])->name('social-media.oauth.callback.pinterest');
                });

                $router
                    ->name('dashboard.user.social-media.')
                    ->prefix('dashboard/user/social-media')
                    ->group(function (Router $router) {
                        $router->get('post', [SocialMediaPostController::class, 'index'])->name('post.index');
                        $router->get('post/create', [SocialMediaPostController::class, 'create'])->name('post.create');
                        $router->get('post/{post}/edit', [SocialMediaPostController::class, 'edit'])->name('post.edit');
                        $router->post('post/{post}/update', [SocialMediaPostController::class, 'update'])->name('post.update');
                        $router->get('post/{id}', [SocialMediaPostController::class, 'show'])->name('post.show');
                        $router->post('post', [SocialMediaPostController::class, 'store'])->name('post.store');
                        $router->post('post/{post}/duplicate', [SocialMediaPostController::class, 'duplicate'])->name('post.duplicate');
                        $router->get('post/{post}/delete', [SocialMediaPostController::class, 'destroy'])->name('post.delete');
                        $router->post('upload/image', [SocialMediaUploadController::class, 'image'])->name('upload.image');
                        $router->post('upload/video', [SocialMediaUploadController::class, 'video'])->name('upload.video');

                        $router->get('', SocialMediaController::class)->name('index');
                        $router->get('platforms', SocialMediaPlatformController::class)->name('platforms');
                        $router->get('platforms/{platform}/disconnect', [SocialMediaPlatformController::class, 'disconnect'])->name('platforms.disconnect');
                        $router->post('campaign/generate', [SocialMediaCampaignController::class, 'generate'])->name('campaign.generate');
                        $router->any('image/get-status', ImageStatusController::class)->name('image.get.status');

                        $router->get('campaign/{campaign}/delete', [SocialMediaCampaignController::class, 'destroy'])->name('campaign.destroy');
                        $router->resource('campaign', SocialMediaCampaignController::class)->only('index', 'store');

                        $router->get('calendar', SocialMediaCalendarController::class)->name('calendar');
                        $router->post('video/generate', SocialMediaVideoController::class)->name('video.generate');
                        $router->get('video/status', [SocialMediaVideoController::class, 'status'])->name('video.status');

                        $router->get('engagement/{account}/capabilities', [EngagementController::class, 'capabilities'])->name('engagement.capabilities');
                        $router->get('engagement/{account}/inbox', [EngagementController::class, 'inbox'])->name('engagement.inbox');
                        $router->post('engagement/{account}/proposal', [EngagementController::class, 'proposal'])->name('engagement.proposal');
                        $router->post('engagement/{account}/reply', [EngagementController::class, 'reply'])->name('engagement.reply');
                        $router->post('engagement/{account}/private-reply', [EngagementController::class, 'privateReply'])->name('engagement.private-reply');
                        $router->post('engagement/{account}/edit', [EngagementController::class, 'edit'])->name('engagement.edit');
                        $router->post('engagement/{account}/delete', [EngagementController::class, 'delete'])->name('engagement.delete');
                        $router->post('engagement/{account}/handoff', [EngagementController::class, 'handoff'])->name('engagement.handoff');

                        $router->post('ebay/readiness', [EbayListingController::class, 'readiness'])->name('ebay.readiness');
                        $router->post('distribution/{item}/ebay/draft', [EbayListingController::class, 'draft'])->name('ebay.draft');
                        $router->post('distribution/{item}/ebay/publish', [EbayListingController::class, 'publish'])->name('ebay.publish');
                        $router->put('distribution/{item}/ebay/revise', [EbayListingController::class, 'revise'])->name('ebay.revise');
                        $router->post('distribution/{item}/ebay/withdraw', [EbayListingController::class, 'withdraw'])->name('ebay.withdraw');
                        $router->post('distribution/{item}/ebay/reconcile', [EbayListingController::class, 'reconcile'])->name('ebay.reconcile');
                        $router->post('distribution/{item}/ebay/buyer-question-handoff', [EbayListingController::class, 'buyerQuestionHandoff'])->name('ebay.buyer-question-handoff');

                        $router->post('distribution/{item}/assisted/{destination}/prepare', [AssistedMarketplaceController::class, 'prepare'])->name('assisted.prepare');
                        $router->post('distribution/{item}/assisted/{destination}/open', [AssistedMarketplaceController::class, 'open'])->name('assisted.open');
                        $router->post('distribution/{item}/assisted/{destination}/complete', [AssistedMarketplaceController::class, 'complete'])->name('assisted.complete');
                        $router->post('distribution/{item}/assisted/{destination}/renew', [AssistedMarketplaceController::class, 'renew'])->name('assisted.renew');
                        $router->post('distribution/{item}/assisted/{destination}/enquiry-handoff', [AssistedMarketplaceController::class, 'enquiryHandoff'])->name('assisted.enquiry-handoff');
                        $router->get('distribution/{item}/assisted/{destination}/status', [AssistedMarketplaceController::class, 'status'])->name('assisted.status');

                        $router->post('google-business-profile/readiness', [GoogleBusinessProfileController::class, 'readiness'])->name('google-business-profile.readiness');
                        $router->post('distribution/{item}/google-business-profile/publish', [GoogleBusinessProfileController::class, 'publish'])->name('google-business-profile.publish');
                        $router->post('distribution/{item}/google-business-profile/photo', [GoogleBusinessProfileController::class, 'uploadPhoto'])->name('google-business-profile.photo');
                        $router->post('distribution/{item}/google-business-profile/reconcile', [GoogleBusinessProfileController::class, 'reconcile'])->name('google-business-profile.reconcile');
                        $router->post('google-business-profile/reviews', [GoogleBusinessProfileController::class, 'reviews'])->name('google-business-profile.reviews');
                        $router->post('distribution/{item}/google-business-profile/review-reply', [GoogleBusinessProfileController::class, 'replyToReview'])->name('google-business-profile.review-reply');
                        $router->post('distribution/{item}/google-business-profile/review-handoff', [GoogleBusinessProfileController::class, 'reviewHandoff'])->name('google-business-profile.review-handoff');
                        $router->post('google-business-profile/performance', [GoogleBusinessProfileController::class, 'performance'])->name('google-business-profile.performance');

                        $router->post('pinterest/readiness', [PinterestController::class, 'readiness'])->name('pinterest.readiness');
                        $router->post('pinterest/boards', [PinterestController::class, 'boards'])->name('pinterest.boards');
                        $router->post('distribution/{item}/pinterest/publish', [PinterestController::class, 'publish'])->name('pinterest.publish');
                        $router->post('distribution/{item}/pinterest/reconcile', [PinterestController::class, 'reconcile'])->name('pinterest.reconcile');
                        $router->post('distribution/{item}/pinterest/analytics', [PinterestController::class, 'analytics'])->name('pinterest.analytics');
                        $router->post('distribution/{item}/pinterest/engagement-handoff', [PinterestController::class, 'engagementHandoff'])->name('pinterest.engagement-handoff');
                    });

                $router
                    ->name('dashboard.user.social-media.common.')
                    ->prefix('dashboard/user/social-media/common')
                    ->group(function (Router $router) {
                        $router->get('companies', SocialMediaCompanyCommonController::class)->name('companies');
                        $router->post('campaigns', SocialMediaCampaignCommonController::class)->name('campaigns');
                        $router->get('generate-content', [SocialMediaCampaignCommonController::class, 'generate'])->name('campaigns.generate.content');
                    });

                $router->post(
                    'genContent', [SocialMediaCampaignCommonController::class, 'generate']
                )
                    ->name('dashboard.user.automation.campaign.genContent')
                    ->prefix('dashboard/user/automation/campaign');

                $router
                    ->middleware('admin')
                    ->prefix('dashboard/admin/social-media/setting')
                    ->name('dashboard.admin.social-media.setting.')
                    ->controller(SocialMediaSettingController::class)
                    ->group(function () {
                        Route::get('', 'index')->name('index');
                        Route::post('{platform}/update', 'update')->name('update');
                    });
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public static function uninstall(): void
    {
        $path = public_path('vendor/socialmedia');
        if (is_dir($path)) {
            array_map(static fn ($f) => @unlink($f), glob("$path/*.*"));
            @rmdir($path);
        }
    }
}

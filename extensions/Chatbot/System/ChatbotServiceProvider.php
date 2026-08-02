<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System;

use App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI\ProjectArchitectureDiagnosticsController;

use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use App\Extensions\Chatbot\System\Models\ChatbotCustomer;
use App\Extensions\Chatbot\System\Models\ChatbotHistory;
use App\Extensions\Chatbot\System\Observers\Sync\ChatbotSyncObserver;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Extensions\Chatbot\System\Http\Controllers\GenerativeUiController;
use App\Extensions\Chatbot\System\GenerativeUI\GenerativeUiSpecValidator;
use App\Extensions\Chatbot\System\GenerativeUI\GenerativeUiSpecNormaliser;
use App\Extensions\Chatbot\System\GenerativeUI\GenerativeUiResponseComposer;
use App\Extensions\Chatbot\System\GenerativeUI\BuilderRegistry;
use App\Extensions\Chatbot\System\Http\Controllers\Api\ChatbotApplicationController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI\TitanAIExecutionController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI\FieldServiceSkillController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI\FieldServiceToolController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\TitanAI\TitanAIRuntimeDiagnosticsController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\ChatbotFrameController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\TitanController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\Sync\ChatbotSyncController;
use App\Extensions\Chatbot\System\Http\Controllers\Api\Sync\ChatbotDeviceController;
use App\Extensions\Chatbot\System\Titan\TitanRegistry;
use App\Extensions\Chatbot\System\Http\Controllers\AvatarController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotAnalyticsController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotCannedResponseController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotCustomerController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotKnowledgeBaseArticleController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotMultiChannelController;
use App\Extensions\Chatbot\System\Http\Controllers\ChatbotTrainController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\RealtimeSettingsController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\CustomerTagController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\StaffInboxController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat\TeamConversationController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat\BusinessChannelController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat\TeamMembershipController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat\TeamRealtimeController;
use App\Extensions\Chatbot\System\Http\Controllers\StaffInbox\TeamChat\TeamMessageController;
use App\Extensions\Chatbot\System\Models\TeamChat\TeamConversation;
use App\Extensions\Chatbot\System\Policies\TeamChat\TeamConversationPolicy;
use App\Extensions\Chatbot\System\TitanAI\WorkCoreApps\WorkCoreAppsIntegrationServiceProvider;
use App\Extensions\Chatbot\System\TitanAI\Runtime\TitanAIRuntimeServiceProvider;
use Illuminate\Support\Facades\Broadcast;
use App\Extensions\Chatbot\System\Http\Middleware\LanguageMiddleware;
use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotCannedResponse;
use App\Extensions\Chatbot\System\Models\ChatbotKnowledgeBaseArticle;
use App\Extensions\Chatbot\System\Policies\ChatbotCannedResponsePolicy;
use App\Extensions\Chatbot\System\Policies\ChatbotKnowledgeBaseArticlePolicy;
use App\Extensions\Chatbot\System\Policies\ChatbotPolicy;
use App\Helpers\Classes\Helper;
use App\Http\Middleware\CheckTemplateTypeAndPlan;
use App\Support\TitanZero\TitanZeroFeatureFlags;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ChatbotServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/titan_project_architecture.php', 'titan_project_architecture');
        $this->app->singleton(BuilderRegistry::class);
        $this->app->singleton(GenerativeUiSpecNormaliser::class);
        $this->app->singleton(GenerativeUiSpecValidator::class);
        $this->app->singleton(GenerativeUiResponseComposer::class);
        $this->registerTitanAIAutoloader();
        $this->registerConfig();
        TitanRegistry::init();
        $flags = $this->featureFlags();
        $this->registerTitanAIProviders($flags);
        if ($flags->workCoreEnabled()) {
            $this->app->register(WorkCoreAppsIntegrationServiceProvider::class);
        }
    }

    private function registerTitanAIAutoloader(): void
    {
        $maps = [
            'App\\Services\\AI\\Contracts\\' => __DIR__.'/TitanAI/contracts/worker-kernel/System/',
            'App\\Services\\AI\\Tier1\\Managers\\' => __DIR__.'/TitanAI/tier1/managers/',
            'App\\Services\\AI\\Tier2\\Assistants\\' => __DIR__.'/TitanAI/tier2/assistants/',
            'App\\Services\\AI\\Tier2\\Residential\\' => __DIR__.'/TitanAI/tier2/specialists/verticals/residential/',
            'App\\Services\\AI\\Tier2\\Commercial\\' => __DIR__.'/TitanAI/tier2/specialists/verticals/commercial/',
            'App\\Services\\AI\\Tier2\\Pool\\' => __DIR__.'/TitanAI/tier2/specialists/verticals/pool/',
            'App\\Services\\AI\\Tier2\\Pressure\\' => __DIR__.'/TitanAI/tier2/specialists/verticals/pressure/',
            'App\\Services\\AI\\Tier2\\Car\\' => __DIR__.'/TitanAI/tier2/specialists/verticals/car/',
            'App\\Services\\AI\\Tier3\\Agents\\Actions\\' => __DIR__.'/TitanAI/tier3/Actions/',
            'App\\Services\\AI\\Tier3\\Agents\\Contracts\\' => __DIR__.'/TitanAI/tier3/Contracts/',
            'App\\Services\\AI\\Tier3\\Agents\\DTO\\' => __DIR__.'/TitanAI/tier3/DTO/',
            'App\\Services\\AI\\Tier3\\Agents\\Results\\' => __DIR__.'/TitanAI/tier3/Results/',
            'App\\Services\\AI\\Tier3\\Agents\\Services\\' => __DIR__.'/TitanAI/tier3/Services/',
            'App\\Services\\AI\\Integration\\WorkCore\\' => __DIR__.'/TitanAI/workcore-runtime/System/',
            'App\\Extensions\\TitanAIGovernance\\System\\' => __DIR__.'/TitanAI/governance/System/',
            'App\\Extensions\\SystemAIChat\\System\\Capabilities\\' => __DIR__.'/TitanAI/capabilities/System/Capabilities/',
        ];
        // The canonical MagicAI WorkCore domain owns the App\Domains\WorkCore namespace.
        // The embedded runtime is legacy compatibility only and must never shadow the host domain.
        if ((bool) config('titan_project_architecture.legacy_embedded_workcore_enabled', false)
            && ! class_exists(\App\Domains\WorkCore\WorkCoreServiceProvider::class)) {
            $maps['App\\Domains\\WorkCore\\'] = __DIR__.'/TitanAI/workcore-runtime/native-runtime/app/Domains/WorkCore/';
        }

        spl_autoload_register(static function (string $class) use ($maps): void {
            foreach ($maps as $prefix => $base) {
                if (! str_starts_with($class, $prefix)) continue;
                $file = $base.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
                if (is_file($file)) require_once $file;
                return;
            }
        }, true, true);
    }

    private function registerTitanAIProviders(TitanZeroFeatureFlags $flags): void
    {
        $providers = [
            \App\Extensions\TitanAIGovernance\System\TitanAIGovernanceServiceProvider::class,
            TitanAIRuntimeServiceProvider::class,
        ];

        if ($flags->workCoreEnabled()) {
            array_unshift(
                $providers,
                \App\Domains\WorkCore\System\AI\Providers\WorkCoreAIServiceProvider::class,
                \App\Services\AI\Integration\WorkCore\TitanAIWorkCoreRuntimeServiceProvider::class,
            );
        }
        foreach ($providers as $provider) {
            if (class_exists($provider)) $this->app->register($provider);
        }
    }

    private function featureFlags(): TitanZeroFeatureFlags
    {
        if ($this->app->bound(TitanZeroFeatureFlags::class)) {
            return $this->app->make(TitanZeroFeatureFlags::class);
        }

        return TitanZeroFeatureFlags::fromArray((array) config('titan-zero', []));
    }

    public function boot(Kernel $kernel): void
    {
        ChatbotConversation::observe(ChatbotSyncObserver::class);
        ChatbotHistory::observe(ChatbotSyncObserver::class);
        ChatbotCustomer::observe(ChatbotSyncObserver::class);

        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->publishAssets()
            ->registerPolicies()
            ->registerTeamChatBroadcastChannels()
            ->registerCommand();

    }

    public function registerPolicies(): self
    {
        Gate::policy(Chatbot::class, ChatbotPolicy::class);
        Gate::policy(ChatbotKnowledgeBaseArticle::class, ChatbotKnowledgeBaseArticlePolicy::class);
        Gate::policy(ChatbotCannedResponse::class, ChatbotCannedResponsePolicy::class);
        Gate::policy(TeamConversation::class, TeamConversationPolicy::class);

        return $this;
    }


    public function registerTeamChatBroadcastChannels(): self
    {
        Broadcast::channel('chatbot.team.{tenantId}.conversation.{conversationUuid}', function ($user, int $tenantId, string $conversationUuid): bool {
            $userTenantId = (int) ($user->tenant_id ?? $user->company_id ?? $user->getAuthIdentifier());
            return $userTenantId === $tenantId && TeamConversation::query()->where('tenant_id',$tenantId)->where('uuid',$conversationUuid)->whereHas('participants',fn($q)=>$q->where('user_id',(int)$user->getAuthIdentifier())->whereNull('left_at'))->exists();
        });
        return $this;
    }

    public function registerCommand(): static
    {
        if (Helper::appIsDemo()) {
            $this->commands([
                Console\Commands\ClearDemoModeCommand::class,
            ]);

            //            if ($this->app->runningInConsole()) {
            //                $this->app->booted(function () {
            //                    $schedule = $this->app->make(Schedule::class);
            //                    $schedule->command('app:clear-chatbot-demo-mode')->everyMinute();
            //                });
            //            }
        }

        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../resources/assets/js'     => public_path('vendor/chatbot/js'),
            __DIR__ . '/../resources/assets/css'    => public_path('vendor/chatbot/css'),
            __DIR__ . '/../resources/assets/images' => public_path('vendor/chatbot/images'),
            __DIR__ . '/../resources/assets/icons'  => public_path('vendor/chatbot/icons'),
            __DIR__ . '/../resources/titan-apps'    => public_path('vendor/chatbot/titan-apps'),
            __DIR__ . '/../resources/pwa/chatbot-pwa' => public_path('chatbot-pwa'),
            __DIR__ . '/../resources/pwa/chatbot-sw.js' => public_path('chatbot-sw.js'),
            __DIR__ . '/../resources/pwa/chatbot.webmanifest' => public_path('chatbot.webmanifest'),
        ], 'extension');


        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chatbot.php', $this->registerKey());

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', $this->registerKey());

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], $this->registerKey());

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
                'middleware' => ['api', 'auth', 'throttle:60,1'],
                'prefix' => 'api/v2/titan',
                'as' => 'api.v2.titan.',
                'controller' => TitanController::class,
            ], function (Router $router) {
                $router->get('apps', 'index')->name('apps');
                $router->get('apps/{app}', 'show')->name('show');
                $router->post('install/{app}', 'install')->name('install');
                $router->get('{app}/manifest.json', 'manifest')->name('manifest');
            })

            ->group([
                'middleware' => ['api', 'auth', 'throttle:60,1'],
                'prefix' => 'api/v2/chatbot/ai',
                'as' => 'api.v2.chatbot.ai.',
            ], function (Router $router) {
                $router->post('execute', [TitanAIExecutionController::class, 'execute'])->name('execute');
                $router->get('runtime/diagnostics', TitanAIRuntimeDiagnosticsController::class)->name('runtime.diagnostics');
                $router->get('runtime/project-architecture', ProjectArchitectureDiagnosticsController::class)->name('runtime.project-architecture');
                $router->get('skills', [FieldServiceSkillController::class, 'index'])->name('skills.index');
                $router->post('skills/match', [FieldServiceSkillController::class, 'match'])->name('skills.match');
                $router->get('tools', [FieldServiceToolController::class, 'index'])->name('tools.index');
                $router->post('tools/match', [FieldServiceToolController::class, 'match'])->name('tools.match');
            })
            ->group([
                'middleware' => ['api', 'auth', 'throttle:120,1'],
                'prefix' => 'api/v2/chatbot',
                'as' => 'api.v2.chatbot.sync.',
            ], function (Router $router) {
                $router->post('sync/bootstrap', [ChatbotSyncController::class, 'bootstrap'])->name('bootstrap');
                $router->post('sync/push', [ChatbotSyncController::class, 'push'])->name('push');
                $router->get('sync/pull', [ChatbotSyncController::class, 'pull'])->name('pull');
                $router->post('sync/acknowledge', [ChatbotSyncController::class, 'acknowledge'])->name('acknowledge');
                $router->get('sync/status', [ChatbotSyncController::class, 'status'])->name('status');
                $router->post('sync/conflicts/{conflict}/resolve', [ChatbotSyncController::class, 'resolve'])->name('conflicts.resolve');
                $router->post('devices/register', [ChatbotDeviceController::class, 'register'])->name('devices.register');
                $router->post('devices/{device}/revoke', [ChatbotDeviceController::class, 'revoke'])->name('devices.revoke');
            })
            ->group([
                'middleware' => ['api', 'throttle:60,1'],
                'prefix' => 'api/v2/titan/public',
                'as' => 'api.v2.titan.public.',
                'controller' => TitanController::class,
            ], function (Router $router) {
                $router->get('apps', 'index')->name('apps');
            })
            ->group([
                'middleware' => 'web',
            ], function (Router $router) {
                $router
                    ->controller(ChatbotFrameController::class)
                    ->group(function (Router $router) {
                        $router->get('chatbot/{chatbot:uuid}/frame', 'frame')->name('chatbot.frame');
                    });
            })
            ->group([
                'middleware'     => ['api', LanguageMiddleware::class],
                'prefix'         => 'api/v2/chatbot',
                'as'             => 'api.v2.chatbot.',
                'controller'     => ChatbotApplicationController::class,
            ], function (Router $router) {
                $router->get('{chatbot:uuid}', 'index')->name('index');
                $router->get('{chatbot:uuid}/articles', 'articles')->name('articles');
                $router->get('{chatbot:uuid}/articles/{id}/show', 'showArticles')->name('articles.show');
                $router->get('{chatbot:uuid}/session/{sessionId}', 'indexSession')->name('index.session');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation', 'conversionStore')->name('conversion.store');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/connect', 'connectSupport')->name('conversion.connect.support');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}', 'conversion')->name('conversion.show');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages', 'messages')->name('conversion.messages');
                $router->get('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/export', 'export')->name('conversion.export');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages', 'storeMessage')->name('conversion.store.message');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/messages/append', 'appendMessage')->name('conversion.store.message.append');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/file', 'storeFile')->name('conversion.store.file');
                $router->post('{chatbot:uuid}/session/{sessionId}/conversation/{chatbotConversation}/review', 'review')->name('conversion.review');
                $router->post('{chatbot:uuid}/session/{sessionId}/send-email', 'sendEmail')->name('send-email.store');
                $router->any('{chatbot:uuid}/session/{sessionId}/enable-sound', 'enableSound')->name('enable-sound');
                $router->post('{chatbot:uuid}/session/{sessionId}/collect-email', 'collectEmail')->name('collect.email');
            })

            ->group([
                'middleware' => ['api'],
                'prefix'     => 'api/v2/chatbot',
                'as'         => 'api.v2.chatbot.',
                'controller' => ChatbotFrameController::class,
            ], function (Router $router) {
                $router->post('{chatbot:uuid}/session/{sessionId}/page-visit', 'recordPageVisit')->name('page-visit.store');
                $router->put('{chatbot:uuid}/session/{sessionId}/page-visit', 'leavePageVisit')->name('page-visit.leave');
            })


            ->group([
                'middleware' => ['web', 'auth'],
                'prefix' => 'dashboard/chatbot/inbox/team',
                'as' => 'dashboard.chatbot.inbox.team.',
            ], function (Router $router) {
                $router->get('conversations', [TeamConversationController::class, 'index'])->name('conversations.index');
                $router->post('conversations', [TeamConversationController::class, 'store'])->name('conversations.store');
                $router->get('conversations/{conversation}', [TeamConversationController::class, 'show'])->name('conversations.show');
                $router->patch('conversations/{conversation}', [TeamConversationController::class, 'update'])->name('conversations.update');
                $router->post('conversations/{conversation}/leave', [TeamConversationController::class, 'leave'])->name('conversations.leave');
                $router->patch('conversations/{conversation}/archive', [TeamConversationController::class, 'archive'])->name('conversations.archive');
                $router->get('conversations/{conversation}/messages', [TeamMessageController::class, 'index'])->name('messages.index');
                $router->post('conversations/{conversation}/messages', [TeamMessageController::class, 'store'])->name('messages.store');
                $router->post('messages/{message}/read', [TeamMessageController::class, 'read'])->name('messages.read');
                $router->post('conversations/{conversation}/typing', [TeamRealtimeController::class, 'typing'])->name('typing');
                $router->post('conversations/{conversation}/participants', [TeamMembershipController::class, 'store'])->name('participants.store');
                $router->patch('conversations/{conversation}/participants/{participant}', [TeamMembershipController::class, 'update'])->name('participants.update');
                $router->delete('conversations/{conversation}/participants/{participant}', [TeamMembershipController::class, 'destroy'])->name('participants.destroy');
                $router->get('channels', [BusinessChannelController::class, 'index'])->name('channels.index');
                $router->post('channels', [BusinessChannelController::class, 'store'])->name('channels.store');
                $router->patch('channels/{conversation}', [BusinessChannelController::class, 'update'])->name('channels.update');
                $router->post('channels/{conversation}/join', [BusinessChannelController::class, 'join'])->name('channels.join');
                $router->patch('channels/{conversation}/archive', [BusinessChannelController::class, 'archive'])->name('channels.archive');
            })

            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $route) {

                $route->controller(GenerativeUiController::class)
                    ->prefix('dashboard/chatbot/generative-ui')
                    ->name('dashboard.chatbot.generative-ui.')
                    ->group(function (Router $router) {
                        $router->get('', 'lab')->name('lab');
                        $router->get('catalogue', 'catalogue')->name('catalogue');
                        $router->get('examples/{slug}', 'example')->name('example');
                        $router->post('validate', 'validateSpec')->name('validate');
                        $router->post('repair', 'repairSpec')->name('repair');
                    });

                $route->controller(StaffInboxController::class)
                    ->prefix('dashboard/chatbot/inbox')
                    ->name('dashboard.chatbot.inbox.')
                    ->group(function (Router $router) {
                        $router->get('', 'index')->name('index');
                        $router->get('notification/count', 'notification')->name('notification.count');
                        $router->post('conversations/name', 'name')->name('conversations.name.update');
                        $router->post('conversations/pinned', 'pinned')->name('conversations.pinned');
                        $router->post('conversations/closed', 'closed')->name('conversations.closed');
                        $router->get('conversations', 'conversations')->name('conversations');
                        $router->put('conversations', 'update')->name('conversations.update');
                        $router->post('conversations/search', 'searchConversation')->name('conversations.search');
                        $router->get('conversations-with-paginate', 'conversationsWithPaginate')->name('conversations.with.paginate');
                        $router->get('conversations-history-session', 'conversationsHistorySession')->name('conversations.history.session');
                        $router->get('history', 'history')->name('history');
                        $router->post('history', 'store');
                        $router->delete('destroy', 'destroy')->name('destroy');
                        $router->get('canned-responses', 'cannedResponses')->name('canned-responses');
                        $router->post('message-rewrite', 'rewriteMessage')->name('message-rewrite');
                    });
                if (\App\Helpers\Classes\MarketplaceHelper::isRegistered('chatbot-customer-tag')) {
                    $route->controller(CustomerTagController::class)->prefix('dashboard/chatbot/inbox/customer-tags')->name('dashboard.chatbot.inbox.customer-tags.')->group(function (Router $router) {
                        $router->get('', 'index')->name('index'); $router->post('', 'store')->name('store'); $router->post('sync', 'assign')->name('sync');
                    });
                }
                $route->controller(RealtimeSettingsController::class)->prefix('dashboard/admin/settings/chatbot-realtime')->name('dashboard.admin.settings.chatbot-realtime.')->group(function (Router $router) {
                    $router->get('', 'index')->name('index'); $router->post('', 'update')->name('update');
                });
                $route->controller(ChatbotMultiChannelController::class)
                    ->name('dashboard.chatbot-multi-channel.')
                    ->prefix('dashboard/chatbot-multi-channel')
                    ->group(function () {
                        Route::any('', 'index')->name('index');
                        Route::POST('delete', 'delete')->name('delete');
                    });
                $route->group([
                    'prefix'         => 'dashboard/chatbot',
                    'as'             => 'dashboard.chatbot.',
                ], function (Router $router) {
                    $router->resource('knowledge-base-article', ChatbotKnowledgeBaseArticleController::class);
                    $router->resource('canned-response', ChatbotCannedResponseController::class);
                    $router->resource('chatbot-customer', ChatbotCustomerController::class);
                });
                $route
                    ->controller(ChatbotAnalyticsController::class)
                    ->prefix('dashboard/chatbot/analytics')
                    ->name('dashboard.chatbot.analytics.')
                    ->group(function (Router $route) {
                        $route->get('', 'index')->name('index');
                    });
                $route
                    ->controller(ChatbotController::class)
                    ->prefix('dashboard/chatbot')
                    ->name('dashboard.chatbot.')
                    ->group(function (Router $route) {
                        $route->get('', 'index')
                            ->name('index')
                            ->middleware(CheckTemplateTypeAndPlan::class);
                        $route->post('', 'store')->name('store');
                        $route->post('update', 'update')->name('update');
                        $route->post('delete', 'delete')->name('delete');

                        // conversation
                        $route->get('conversations', 'conversations')->name('conversations');
                        $route->get('conversations-with-paginate', 'conversationsWithPaginate')->name('conversations.with.paginate');
                        $route->post('conversations/search', 'searchConversation')->name('conversations.search');

                        // ended routes
                        $route->get('{chatbot}/enbed', 'enbed')->name('enbed');
                    });
                $route
                    ->controller(ChatbotTrainController::class)
                    ->prefix('dashboard/chatbot/train')
                    ->name('dashboard.chatbot.train.')
                    ->group(function (Router $route) {
                        // train routes
                        $route->get('data', 'trainData')->name('data');
                        $route->post('delete-embedding', 'deleteEmbedding')->name('delete');
                        $route->post('generate-embedding', 'generateEmbedding')->name('generate.embedding');
                        $route->get('{chatbot}', 'train')->name('index');
                        $route->post('url', 'trainUrl')->name('url');
                        $route->post('file', 'trainFile')->name('file');
                        $route->post('text', 'trainText')->name('text');
                        $route->post('qa', 'trainQa')->name('qa');
                    });
                $route->post('dashboard/chatbot/avatar/upload', AvatarController::class)
                    ->name('dashboard.chatbot.upload.avatar');
            });

        return $this;
    }

    private function router(): Router|Route
    {
        return $this->app['router'];
    }

    public function registerKey(): string
    {
        return 'chatbot';
    }
}

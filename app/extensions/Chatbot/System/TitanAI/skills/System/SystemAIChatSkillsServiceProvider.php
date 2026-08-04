<?php

declare(strict_types=1);

namespace App\Extensions\SystemAIChatSkills\System;

use App\Domains\Marketplace\Contracts\ExtensionRegisterKeyProviderInterface;
use App\Domains\Marketplace\Contracts\UninstallExtensionServiceProviderInterface;
use App\Extensions\SystemAIChatSkills\System\Contracts\CompanyContextResolverInterface;
use App\Extensions\SystemAIChatSkills\System\Contracts\CompanySkillInstallationLookupInterface;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillArchiveInspector;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportFileSetInspector;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportSecurityPolicy;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportService;
use App\Extensions\SystemAIChatSkills\System\Imports\SkillImportStagingArea;
use App\Extensions\SystemAIChatSkills\System\Http\Controllers\AdminSkillController;
use App\Extensions\SystemAIChatSkills\System\Http\Controllers\SkillVersionController;
use App\Extensions\SystemAIChatSkills\System\Http\Controllers\SkillToolController;
use App\Extensions\SystemAIChatSkills\System\Authorization\SkillAccessEvaluator;
use App\Extensions\SystemAIChatSkills\System\Http\Controllers\UserSkillController;
use App\Extensions\SystemAIChatSkills\System\Models\Skill;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillPackageHasher;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillPackageManifestSerializer;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillPackageVerifier;
use App\Extensions\SystemAIChatSkills\System\Packages\SkillResourceScanner;
use App\Extensions\SystemAIChatSkills\System\Policies\SkillPolicy;
use App\Extensions\SystemAIChat\System\Capabilities\CapabilityDefinition;
use App\Extensions\SystemAIChat\System\Capabilities\CapabilityRegistry;
use App\Extensions\SystemAIChatSkills\System\Skills\SkillManifestValidator;
use App\Extensions\SystemAIChatSkills\System\Skills\SkillManifestSerializer;
use App\Extensions\SystemAIChatSkills\System\Services\SkillReleaseService;
use App\Extensions\SystemAIChatSkills\System\Tenancy\CompanyContextResolver;
use App\Extensions\SystemAIChatSkills\System\Tenancy\EloquentCompanySkillInstallationLookup;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolDefinition;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolExecutionService;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolInstructionCompiler;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolHostAdapter;
use App\Extensions\SystemAIChatSkills\System\Tools\SkillToolRegistry;
use App\Extensions\SystemAIChatSkills\System\Tools\Bridges\SystemAIChatConnectorToolBridge;
use App\Extensions\SystemAIChatSkills\System\Tools\Bridges\SystemAIChatCoreToolBridge;
use App\Extensions\SystemAIChatSkills\System\Tools\Bridges\AIAgentToolCatalogBridge;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SystemAIChatSkillsServiceProvider extends ServiceProvider implements ExtensionRegisterKeyProviderInterface, UninstallExtensionServiceProviderInterface
{
    public function register(): void
    {
        $this->registerConfig();
        $this->app->singleton(SkillManifestValidator::class);
        $this->app->singleton(SkillManifestSerializer::class);
        $this->app->singleton(SkillImportSecurityPolicy::class, static fn (): SkillImportSecurityPolicy => SkillImportSecurityPolicy::fromConfig());
        $this->app->singleton(SkillImportFileSetInspector::class);
        $this->app->singleton(SkillArchiveInspector::class);
        $this->app->singleton(SkillImportStagingArea::class, static fn (): SkillImportStagingArea => new SkillImportStagingArea(
            storage_path('app'),
            (int) config('system-ai-chat-skills.imports.staging_ttl_minutes', 60),
        ));
        $this->app->singleton(SkillImportService::class);
        $this->app->singleton(SkillPackageHasher::class);
        $this->app->singleton(SkillPackageVerifier::class);
        $this->app->singleton(SkillResourceScanner::class);
        $this->app->singleton(SkillPackageManifestSerializer::class);
        $this->app->singleton(SkillAccessEvaluator::class);
        $this->app->singleton(SkillReleaseService::class);
        $this->app->singleton(SkillToolRegistry::class);
        $this->app->singleton(SkillToolInstructionCompiler::class);
        $this->app->singleton(SkillToolHostAdapter::class);
        $this->app->singleton(SkillToolExecutionService::class);
        $this->app->singleton(SystemAIChatConnectorToolBridge::class);
        $this->app->singleton(SystemAIChatCoreToolBridge::class);
        $this->app->singleton(AIAgentToolCatalogBridge::class);
        $this->app->bind(CompanyContextResolverInterface::class, CompanyContextResolver::class);
        $this->app->bind(CompanySkillInstallationLookupInterface::class, EloquentCompanySkillInstallationLookup::class);
    }

    public function boot(): void
    {
        $this->registerTranslations()
            ->registerViews()
            ->registerRoutes()
            ->registerMigrations()
            ->registerPolicies()
            ->registerConfiguredTools()
            ->registerToolBridges()
            ->registerCapabilities()
            ->publishAssets()
            ->registerComponents();
    }

    private function registerPolicies(): static
    {
        Gate::policy(Skill::class, SkillPolicy::class);

        return $this;
    }

    private function registerConfiguredTools(): static
    {
        /** @var SkillToolRegistry $registry */
        $registry = $this->app->make(SkillToolRegistry::class);
        $catalog = config('system-ai-chat-skills.tools.catalog', []);

        if (! is_array($catalog)) {
            return $this;
        }

        foreach ($catalog as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $registry->register(SkillToolDefinition::fromArray($definition));
        }

        return $this;
    }

    private function registerToolBridges(): static
    {
        $registry = $this->app->make(SkillToolRegistry::class);
        $this->app->make(AIAgentToolCatalogBridge::class)->register($registry);
        $this->app->make(SystemAIChatCoreToolBridge::class)->register($registry);
        $this->app->make(SystemAIChatConnectorToolBridge::class)->register($registry);

        return $this;
    }

    private function registerCapabilities(): static
    {
        if (! $this->app->bound(CapabilityRegistry::class)) {
            return $this;
        }

        $this->app->make(CapabilityRegistry::class)->register(new CapabilityDefinition(
            key: 'system-ai-chat.skills',
            label: 'SystemAIChat Skills',
            description: 'Versioned reusable capability packages with governed tool declarations, approvals, imports, and activation.',
            version: '1.9.0',
            dependencies: ['system-ai-chat.runtime'],
            metadata: [
                'supports_imports' => true,
                'supports_versioning' => true,
                'supports_user_activation' => true,
                'supports_policy_authorization' => true,
                'supports_company_tenancy' => true,
                'supports_company_installations' => true,
                'supports_immutable_versions' => true,
                'supports_release_lifecycle' => true,
                'supports_version_pinning' => true,
                'supports_rollback' => true,
                'supports_structured_tools' => true,
                'supports_tool_approval' => true,
                'supports_tool_executors' => true,
                'supports_core_tool_bridge' => true,
                'supports_connector_tool_bridge' => true,
                'supports_tool_catalog_ui' => true,
                'supports_canonical_package_hashing' => true,
                'supports_resource_integrity' => true,
                'supports_provenance' => true,
                'supports_package_verification' => true,
                'supports_transactional_imports' => true,
                'supports_archive_bomb_protection' => true,
                'supports_import_staging_cleanup' => true,
            ],
        ));

        return $this;
    }

    public function registerComponents(): static
    {
        return $this;
    }

    public function publishAssets(): static
    {
        $this->publishes([
            __DIR__ . '/../config/system-ai-chat-skills.php' => config_path('system-ai-chat-skills.php'),
        ], 'system-ai-chat-skills-config');

        return $this;
    }

    public function registerConfig(): static
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/system-ai-chat-skills.php', 'system-ai-chat-skills');

        return $this;
    }

    protected function registerTranslations(): static
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'system-ai-chat-skills');

        return $this;
    }

    public function registerViews(): static
    {
        $this->loadViewsFrom([__DIR__ . '/../resources/views'], 'system-ai-chat-skills');

        return $this;
    }

    public function registerMigrations(): static
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        return $this;
    }

    private function registerRoutes(): static
    {
        // Guest-accessible routes
        $this->router()
            ->group([
                'middleware' => ['web'],
            ], function (Router $router) {
                $router
                    ->controller(UserSkillController::class)
                    ->prefix('dashboard/user/skills')
                    ->name('dashboard.guest.skills.')
                    ->group(function (Router $router) {
                        $router->get('/public-search', 'publicSearch')->name('public-search');
                        $router->get('/public-list', 'publicList')->name('public-list');
                        $router->get('/public-show/{skill}', 'publicShow')->name('public-show');
                    });
            });

        // Admin routes
        $this->router()
            ->group([
                'middleware' => ['web', 'auth'],
            ], function (Router $router) {
                $router
                    ->controller(AdminSkillController::class)
                    ->prefix('dashboard/admin/skills')
                    ->name('dashboard.admin.skills.')
                    ->middleware(['admin'])
                    ->group(function (Router $router) {
                        $router->get('/', 'index')->name('index');
                        $router->post('/', 'store')->name('store');
                        $router->post('/import-github', 'importFromGithub')->name('import-github');
                        $router->post('/scan-github', 'scanGithub')->name('scan-github');
                        $router->post('/import-github-bulk', 'importGithubBulk')->name('import-github-bulk');
                        $router->get('/{skill}', 'show')->name('show');
                        $router->put('/{skill}', 'update')->name('update');
                        $router->delete('/{skill}', 'destroy')->name('destroy');
                    });

                $router
                    ->controller(SkillVersionController::class)
                    ->prefix('dashboard/user/skills')
                    ->name('dashboard.user.skills.versions.')
                    ->group(function (Router $router) {
                        $router->get('/{skill}/versions', 'index')->name('index');
                        $router->post('/{skill}/versions/draft', 'createDraft')->name('draft');
                        $router->put('/{skill}/versions/{skillVersion}', 'updateDraft')->name('update');
                        $router->post('/{skill}/versions/{skillVersion}/publish', 'publish')->name('publish');
                        $router->post('/{skill}/versions/{skillVersion}/rollback', 'rollback')->name('rollback');
                        $router->post('/{skill}/versions/{skillVersion}/verify', 'verify')->name('verify');
                        $router->post('/{skill}/versions/{skillVersion}/transition', 'transition')->name('transition');
                        $router->get('/{skill}/versions/{skillVersion}/compare/{otherVersion}', 'compare')->name('compare');
                    });

                $router
                    ->controller(SkillToolController::class)
                    ->prefix('dashboard/user/skills')
                    ->name('dashboard.user.skills.tools.')
                    ->group(function (Router $router) {
                        $router->get('/tool-catalog', 'index')->name('catalog');
                    });

                // User routes
                $router
                    ->controller(UserSkillController::class)
                    ->prefix('dashboard/user/skills')
                    ->name('dashboard.user.skills.')
                    ->group(function (Router $router) {
                        $router->get('/', 'index')->name('index');
                        $router->post('/add/{skill}', 'addSkill')->name('add');
                        $router->post('/remove/{skill}', 'removeSkill')->name('remove');
                        $router->post('/toggle-auto/{skill}', 'toggleAutoUse')->name('toggle-auto');
                        $router->put('/configure/{skill}', 'configureInstallation')->name('configure');
                        $router->post('/company/install/{skill}', 'installForCompany')->name('company.install');
                        $router->delete('/company/remove/{skill}', 'removeFromCompany')->name('company.remove');
                        $router->put('/company/configure/{skill}', 'configureForCompany')->name('company.configure');
                        $router->post('/upload', 'upload')->name('upload');
                        $router->post('/import-github', 'importFromGithub')->name('import-github');
                        $router->post('/scan-github', 'scanGithub')->name('scan-github');
                        $router->post('/import-github-bulk', 'importGithubBulk')->name('import-github-bulk');
                        $router->get('/search', 'search')->name('search');
                        $router->post('/create-from-content', 'createFromContent')->name('create-from-content');
                        $router->get('/export/{skill}', 'export')->name('export');
                        $router->get('/file-content/{skill}', 'fileContent')->name('file-content');
                        $router->delete('/delete/{skill}', 'deleteSkill')->name('delete');
                        $router->get('/{skill}', 'show')->name('show');
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
        // Database records are preserved intentionally; removal is handled by explicit migration rollback.
    }

    public function registerKey(): string
    {
        return 'system-ai-chat-skills';
    }
}

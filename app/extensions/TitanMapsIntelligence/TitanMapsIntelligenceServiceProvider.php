<?php

declare(strict_types=1);

namespace App\Extensions\TitanMapsIntelligence;

use App\Extensions\TitanMapsIntelligence\Contracts\AuditRecorder;
use App\Extensions\TitanMapsIntelligence\Contracts\AuthorisedCompanyContext;
use App\Extensions\TitanMapsIntelligence\Contracts\CandidateRepository;
use App\Extensions\TitanMapsIntelligence\Contracts\CapabilityRegistrar;
use App\Extensions\TitanMapsIntelligence\Contracts\PermissionAuthorizer;
use App\Extensions\TitanMapsIntelligence\Contracts\ProviderHttpTransport;
use App\Extensions\TitanMapsIntelligence\Contracts\PrivateExportStore;
use App\Extensions\TitanMapsIntelligence\Contracts\SecretResolver;
use App\Extensions\TitanMapsIntelligence\Contracts\WorkCoreCandidateGateway;
use App\Extensions\TitanMapsIntelligence\Contracts\WorkCoreCandidateLookup;
use App\Extensions\TitanMapsIntelligence\Exceptions\ProviderException;
use App\Extensions\TitanMapsIntelligence\Models\DiscoveryCandidate;
use App\Extensions\TitanMapsIntelligence\Models\DiscoverySearch;
use App\Extensions\TitanMapsIntelligence\Models\MapProviderConnection;
use App\Extensions\TitanMapsIntelligence\Policies\DiscoveryCandidatePolicy;
use App\Extensions\TitanMapsIntelligence\Policies\DiscoverySearchPolicy;
use App\Extensions\TitanMapsIntelligence\Providers\GooglePlacesProvider;
use App\Extensions\TitanMapsIntelligence\Providers\LaravelProviderHttpTransport;
use App\Extensions\TitanMapsIntelligence\Providers\PlacesProviderRegistry;
use App\Extensions\TitanMapsIntelligence\Repositories\EloquentCandidateRepository;
use App\Extensions\TitanMapsIntelligence\Services\MapsCapabilityService;
use App\Extensions\TitanMapsIntelligence\Support\GooglePlacesNormalizer;
use App\Extensions\TitanMapsIntelligence\Support\RequiredHostContract;
use App\Titan\Maps\MapsAuditGateway;
use App\Titan\Maps\MapsCapabilityRegistrar;
use App\Titan\Maps\MapsCompanyContext;
use App\Titan\Maps\MapsCredentialResolver;
use App\Titan\Maps\MapsPermissionGateway;
use App\Titan\Maps\MapsPrivateExportStore;
use App\Titan\Maps\MapsWorkCoreGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class TitanMapsIntelligenceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/titan_maps_intelligence.php', 'extensions.titan_maps_intelligence');
        $this->app->scoped(AuthorisedCompanyContext::class, MapsCompanyContext::class);
        $this->app->scoped(PermissionAuthorizer::class, MapsPermissionGateway::class);
        $this->app->bind(SecretResolver::class, MapsCredentialResolver::class);
        $this->app->bind(AuditRecorder::class, MapsAuditGateway::class);
        $this->app->singleton(CapabilityRegistrar::class, MapsCapabilityRegistrar::class);
        $this->app->scoped(WorkCoreCandidateGateway::class, MapsWorkCoreGateway::class);
        $this->app->scoped(WorkCoreCandidateLookup::class, MapsWorkCoreGateway::class);
        $this->app->scoped(PrivateExportStore::class, MapsPrivateExportStore::class);
        $this->app->bind(ProviderHttpTransport::class, LaravelProviderHttpTransport::class);
        $this->app->bind(CandidateRepository::class, EloquentCandidateRepository::class);
        $this->app->singleton(PlacesProviderRegistry::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'titan-maps-intelligence');
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'titan-maps-intelligence');

        $required = new RequiredHostContract(fn (string $contract): mixed => $this->app->make($contract));
        foreach ([
            AuthorisedCompanyContext::class,
            PermissionAuthorizer::class,
            SecretResolver::class,
            AuditRecorder::class,
            CapabilityRegistrar::class,
            WorkCoreCandidateGateway::class,
            WorkCoreCandidateLookup::class,
            PrivateExportStore::class,
        ] as $contract) {
            $required->assertBound($contract, fn (string $requiredContract): bool => $this->app->bound($requiredContract));
        }

        $this->registerProviders();
        $this->registerCapabilities($required->resolve(CapabilityRegistrar::class));
        Gate::policy(DiscoverySearch::class, DiscoverySearchPolicy::class);
        Gate::policy(DiscoveryCandidate::class, DiscoveryCandidatePolicy::class);
        $this->loadRoutesFrom(__DIR__.'/routes/api.php');
    }

    private function registerProviders(): void
    {
        $registry = $this->app->make(PlacesProviderRegistry::class);
        $registry->registerFactory('google-places', function (?string $companyId): GooglePlacesProvider {
            if ($companyId === null || $companyId === '') {
                throw ProviderException::fromCode('MAPS_TENANT_DENIED', 'A company context is required to resolve a places provider.');
            }
            $connection = MapProviderConnection::query()->where('company_id', $companyId)->where('provider', 'google-places')->where('enabled', true)->first();
            if ($connection === null) {
                throw ProviderException::fromCode('MAPS_PROVIDER_NOT_CONFIGURED', 'Google Places is not configured for the authorised company.');
            }
            $config = array_replace_recursive((array) config('extensions.titan_maps_intelligence.providers.google-places', []), (array) ($connection->configuration ?? []));
            $config['credential_reference'] = $connection->credential_reference;
            return new GooglePlacesProvider(
                $this->app->make(ProviderHttpTransport::class),
                $this->app->make(SecretResolver::class),
                $config,
                $this->app->make(GooglePlacesNormalizer::class),
            );
        });
    }

    private function registerCapabilities(CapabilityRegistrar $registrar): void
    {
        foreach ($this->app->make(MapsCapabilityService::class)->definitions() as $definition) {
            $registrar->register($definition);
        }
    }
}

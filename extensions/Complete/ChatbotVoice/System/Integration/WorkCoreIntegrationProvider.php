<?php
declare(strict_types=1);
namespace App\Extensions\ChatbotVoice\System\Integration;

use Illuminate\Support\ServiceProvider;
use App\Domains\WorkCore\System\Tenancy\TenantContext;
use App\Domains\WorkCore\System\Authorization\CompanyRecordAuthorizer;

class WorkCoreIntegrationProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantContext::class, function () {
            return new TenantContext();
        });

        $this->app->singleton(CompanyRecordAuthorizer::class, function () {
            return new CompanyRecordAuthorizer();
        });
    }
}

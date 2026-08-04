<?php

declare(strict_types=1);

namespace App\Extensions\TitanAIGovernance\System;

use App\Extensions\TitanAIGovernance\System\Contracts\WorkCoreGateway;
use App\Extensions\TitanAIGovernance\System\Approvals\ApprovalQueue;
use App\Extensions\TitanAIGovernance\System\Approvals\DatabaseApprovalQueue;
use App\Extensions\TitanAIGovernance\System\Booking\WorkCoreBookingCoordinator;
use App\Extensions\TitanAIGovernance\System\Council\ConfiguredModelReviewer;
use App\Extensions\TitanAIGovernance\System\Council\OperationalCouncil;
use App\Extensions\TitanAIGovernance\System\Council\RiskClassifier;
use App\Extensions\TitanAIGovernance\System\Tools\GovernedToolExecutor;
use Illuminate\Support\ServiceProvider;

final class TitanAIGovernanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/titan-ai-governance.php', 'titan-ai-governance');
        $this->app->singleton(WorkCoreGateway::class, WorkCoreAccessGateway::class);
        $this->app->singleton(ApprovalQueue::class, DatabaseApprovalQueue::class);
        $this->app->singleton(RiskClassifier::class);
        $this->app->singleton(OperationalCouncil::class, function ($app) {
            $reviewers = [];
            foreach ((array) config('titan-ai-governance.reviewers', []) as $reviewer) {
                if (is_array($reviewer) && isset($reviewer['model'])) {
                    $reviewers[] = new ConfiguredModelReviewer((string) $reviewer['model'], (string) ($reviewer['role'] ?? 'verifier'));
                } elseif (is_string($reviewer) && $app->bound($reviewer)) {
                    $reviewers[] = $app->make($reviewer);
                }
            }
            return new OperationalCouncil($reviewers);
        });
        $this->app->singleton(GovernedToolExecutor::class);
        $this->app->singleton(WorkCoreBookingCoordinator::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->publishes([
            __DIR__.'/../config/titan-ai-governance.php' => config_path('titan-ai-governance.php'),
        ], 'titan-ai-governance-config');
    }
}

<?php

declare(strict_types=1);

namespace App\Domains\WorkCore\System\Modules\Wizards\Providers;

use App\Domains\WorkCore\System\Actions\ActionDefinition;
use App\Domains\WorkCore\System\Actions\BusinessActionRegistry;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\ApproveWizardSection;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\CompleteWizardRun;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\CreateWizardDefinition;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\PauseWizardRun;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\PublishWizardDefinition;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\ResumeWizardRun;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\SaveWizardAnswer;
use App\Domains\WorkCore\System\Modules\Wizards\Actions\StartWizardRun;
use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardAIEnrichmentDispatcherContract;
use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardRecompositionRepositoryContract;
use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardRepositoryContract;
use App\Domains\WorkCore\System\Modules\Wizards\Contracts\WizardVerticalContextRepositoryContract;
use App\Domains\WorkCore\System\Modules\Wizards\ReadModels\GetNextWizardQuestion;
use App\Domains\WorkCore\System\Modules\Wizards\ReadModels\GetWizardRun;
use App\Domains\WorkCore\System\Modules\Wizards\ReadModels\ListWizardDefinitions;
use App\Domains\WorkCore\System\Modules\Wizards\Repositories\DatabaseWizardRecompositionRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Repositories\DatabaseWizardVerticalContextRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Repositories\EloquentWizardRepository;
use App\Domains\WorkCore\System\Modules\Wizards\Services\LaravelWizardAIEnrichmentDispatcher;
use App\Domains\WorkCore\System\Modules\Wizards\Services\LayeredWizardQuestionCatalogue;
use App\Domains\WorkCore\System\Modules\Wizards\Services\LayeredWizardQuestionComposer;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerRecompositionService;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardAnswerValidator;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardBranchEvaluator;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDefinitionRegistry;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardDependencyResolver;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardQuestionPlanner;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRiskPolicy;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardRuntime;
use App\Domains\WorkCore\System\Modules\Wizards\Services\WizardVerticalContextAdapter;
use App\Domains\WorkCore\System\ReadModels\ReadModelDefinition;
use App\Domains\WorkCore\System\ReadModels\ReadModelRegistry;
use Illuminate\Support\ServiceProvider;
use TitanZero\Interaction\Vertical\AI\VerticalAIProposalBridge;
use TitanZero\Interaction\Vertical\VerticalContextComposer;

final class WorkWizardsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WizardDefinitionRegistry::class);
        $this->app->singleton(WizardBranchEvaluator::class);
        $this->app->singleton(WizardRiskPolicy::class);
        $this->app->singleton(WizardAnswerValidator::class);
        $this->app->singleton(WizardDependencyResolver::class);
        $this->app->singleton(
            LayeredWizardQuestionComposer::class,
            static fn (): LayeredWizardQuestionComposer => new LayeredWizardQuestionComposer(
                LayeredWizardQuestionCatalogue::defaults(),
            ),
        );
        $this->app->singleton(WizardQuestionPlanner::class);
        $this->app->bind(WizardRepositoryContract::class, EloquentWizardRepository::class);
        $this->app->scoped(WizardRuntime::class);

        if (class_exists(VerticalContextComposer::class)) {
            $this->app->bind(
                WizardVerticalContextRepositoryContract::class,
                DatabaseWizardVerticalContextRepository::class,
            );
            $this->app->scoped(WizardVerticalContextAdapter::class);
        }

        if (class_exists(VerticalContextComposer::class) && class_exists(VerticalAIProposalBridge::class)) {
            $this->app->bind(
                WizardRecompositionRepositoryContract::class,
                DatabaseWizardRecompositionRepository::class,
            );
            $this->app->bind(
                WizardAIEnrichmentDispatcherContract::class,
                LaravelWizardAIEnrichmentDispatcher::class,
            );
            $this->app->scoped(WizardAnswerRecompositionService::class);
        }

        $actions = $this->app->make(BusinessActionRegistry::class);
        foreach ([
            new ActionDefinition('workcore.wizard_definition.create', CreateWizardDefinition::class, 'medium', true, 'workcore.wizards', (string) config('workcore.wizards.permissions.design')),
            new ActionDefinition('workcore.wizard_definition.publish', PublishWizardDefinition::class, 'high', true, 'workcore.wizards', (string) config('workcore.wizards.permissions.publish')),
            new ActionDefinition('workcore.wizard_run.start', StartWizardRun::class, 'low', false, 'workcore.wizards', (string) config('workcore.wizards.permissions.use')),
            new ActionDefinition('workcore.wizard_answer.save', SaveWizardAnswer::class, 'low', false, 'workcore.wizards', (string) config('workcore.wizards.permissions.use')),
            new ActionDefinition('workcore.wizard_section.approve', ApproveWizardSection::class, 'high', true, 'workcore.wizards', (string) config('workcore.wizards.permissions.approve')),
            new ActionDefinition('workcore.wizard_run.pause', PauseWizardRun::class, 'low', false, 'workcore.wizards', (string) config('workcore.wizards.permissions.use')),
            new ActionDefinition('workcore.wizard_run.resume', ResumeWizardRun::class, 'low', false, 'workcore.wizards', (string) config('workcore.wizards.permissions.use')),
            new ActionDefinition('workcore.wizard_run.complete', CompleteWizardRun::class, 'high', true, 'workcore.wizards', (string) config('workcore.wizards.permissions.approve')),
        ] as $definition) {
            $actions->register($definition);
        }

        $readModels = $this->app->make(ReadModelRegistry::class);
        $viewPermission = (string) config('workcore.wizards.permissions.view');
        $readModels->register(new ReadModelDefinition('workcore.wizard.list', ListWizardDefinitions::class, 'workcore.wizards', permission: $viewPermission));
        $readModels->register(new ReadModelDefinition('workcore.wizard.run', GetWizardRun::class, 'workcore.wizards', permission: $viewPermission));
        $readModels->register(new ReadModelDefinition('workcore.wizard.next_question', GetNextWizardQuestion::class, 'workcore.wizards', permission: $viewPermission));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'workcore-wizards');
        if ((bool) config('workcore.wizards.routes_enabled', false)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
            $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        }
    }
}

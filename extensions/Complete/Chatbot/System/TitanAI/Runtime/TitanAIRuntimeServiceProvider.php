<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Runtime;

use App\Extensions\Chatbot\System\TitanAI\Contracts\TitanAIRuntimeContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\GeneralConversationEngineContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\AssistantDeploymentResolverContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\MemoryContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\Contracts\KnowledgeContextProviderContract;
use App\Extensions\Chatbot\System\TitanAI\Deployments\AssistantDeploymentResolver;
use App\Extensions\Chatbot\System\TitanAI\Context\MemoryContextProvider;
use App\Extensions\Chatbot\System\TitanAI\Context\KnowledgeContextProvider;
use App\Extensions\Chatbot\System\TitanAI\Capabilities\SharedCapabilityRegistry;
use App\Extensions\Chatbot\System\TitanAI\Runtime\Skills\FieldServiceSkillRegistry;
use App\Extensions\Chatbot\System\TitanAI\Runtime\Skills\FieldServiceSkillContextProvider;
use App\Extensions\Chatbot\System\TitanAI\Runtime\Tools\FieldServiceToolRegistry;
use App\Extensions\Chatbot\System\TitanAI\Runtime\Tools\FieldServiceToolContextProvider;
use App\Services\AI\Integration\WorkCore\Diagnostics\WorkCoreBindingDiagnostics;
use Illuminate\Support\ServiceProvider;

final class TitanAIRuntimeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__, 3).'/config/titan-ai.php', 'titan-ai');
        $this->app->singleton(IntentGateway::class);
        $this->app->singleton(FieldServiceSkillRegistry::class);
        $this->app->singleton(FieldServiceSkillContextProvider::class);
        $this->app->singleton(FieldServiceToolRegistry::class);
        $this->app->singleton(FieldServiceToolContextProvider::class);
        $this->app->singleton(WorkCoreBindingDiagnostics::class);
        $this->app->singleton(Tier3AgentCapabilityCatalog::class);
        $this->app->singleton(WorkerRouteRegistry::class);
        $this->app->singleton(WorkerPermissionGuard::class);
        $this->app->singleton(DynamicWorkerSelector::class);
        $this->app->singleton(AgentExecutionPathResolver::class);
        $this->app->singleton(AssistantDelegationGraph::class);
        $this->app->singleton(ConfidenceFallbackPolicy::class);
        $this->app->singleton(AssistantChainPlanner::class);
        $this->app->singleton(FiveTierOrchestrator::class);
        $this->app->singleton(AssistantDeploymentResolver::class);
        $this->app->alias(AssistantDeploymentResolver::class, AssistantDeploymentResolverContract::class);
        $this->app->singleton(MemoryContextProvider::class);
        $this->app->alias(MemoryContextProvider::class, MemoryContextProviderContract::class);
        $this->app->singleton(KnowledgeContextProvider::class);
        $this->app->alias(KnowledgeContextProvider::class, KnowledgeContextProviderContract::class);
        $this->app->singleton(SharedCapabilityRegistry::class);
        $this->app->singleton(LaravelGeneralConversationEngine::class);
        $this->app->alias(LaravelGeneralConversationEngine::class, GeneralConversationEngineContract::class);
        $this->app->singleton(UnifiedTitanAIRuntime::class);
        $this->app->alias(UnifiedTitanAIRuntime::class, TitanAIRuntimeContract::class);
    }
}

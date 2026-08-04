<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\TitanAI\Architecture;

/**
 * Safe catalogue of Interaction Engine contracts. The donor engine is not booted
 * inside Chatbot; contracts are promoted only after an adapter and authority review.
 */
final class InteractionEngineContractCatalog
{
    /** @return array<string,list<string>> */
    public function groups(): array
    {
        return [
            'cognitive' => ['Reasoning','Inference','Semantic','Reflection','Explainability','Abstraction','Analogy','Creativity','Concept'],
            'planning' => ['Planning','TaskDecomposition','Scheduling','Execution','ResourceAllocation','Recovery','Workflow','Retry','CapabilityRouting','Synchronization'],
            'memory' => ['Memory','EpisodicMemory','SemanticMemory','ProceduralMemory','Context','KnowledgeGraph','WorldModel','Consolidation','Forgetting','KnowledgeExtraction'],
            'human_interaction' => ['Conversation','Dialogue','Intent','Clarification','ResponseGeneration','Sentiment','Emotion','Empathy','Personality','Trust'],
            'ai_infrastructure' => ['Evaluation','ModelSelection','Retrieval','ToolCalling','FunctionCalling','Embedding','VectorSearch','Prompt','PromptOptimization','Guardrail'],
            'business_intelligence' => ['Operations','Dispatch','CRMIntelligence','FinancialInsight','Compliance','Audit','Monitoring','Analytics','Automation','BusinessBuilder'],
            'learning' => ['Learning','Feedback','PatternRecognition','Similarity','Prediction','Generalization','Recommendation','PreferenceLearning','BehaviourLearning','ReinforcementLearning'],
        ];
    }

    /** @return array<string,string> */
    public function adoptionPolicy(): array
    {
        return [
            'status' => 'donor_contracts_only',
            'runtime_authority' => 'Titan Zero remains the sole orchestrator',
            'operational_writes' => 'WorkCore API only',
            'promotion_rule' => 'typed adapter + tests + governance + measured benefit',
            'forbidden' => 'parallel orchestrator, direct database writes, duplicate memory authority',
        ];
    }
}

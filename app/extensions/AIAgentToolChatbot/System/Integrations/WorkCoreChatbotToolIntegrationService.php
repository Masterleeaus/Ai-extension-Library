<?php

namespace Extensions\AIAgentToolChatbot\System\Integrations;

use Extensions\WorkCore_Platform\Contracts\WorkCoreGateway;

class WorkCoreChatbotToolIntegrationService
{
    protected $workCoreGateway;

    public function __construct(WorkCoreGateway $workCoreGateway)
    {
        $this->workCoreGateway = $workCoreGateway;
    }

    public function initializeChatbotToolOperations(string $tenantId, string $userId): array
    {
        return [
            'chatbots' => $this->getChatbots($tenantId),
            'conversations' => $this->getConversations($tenantId),
            'training_data' => $this->getTrainingData($tenantId),
            'intents' => $this->getIntents($tenantId),
            'responses' => $this->getResponses($tenantId),
            'analytics' => $this->getAnalytics($tenantId),
        ];
    }

    public function getChatbots(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('tools/chatbots', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getConversations(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('tools/chatbot_conversations', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getTrainingData(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('tools/chatbot_training', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getIntents(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('tools/chatbot_intents', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getResponses(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('tools/chatbot_responses', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function getAnalytics(string $tenantId): array
    {
        $response = $this->workCoreGateway->query('tools/chatbot_analytics', [
            'tenant_id' => $tenantId,
        ]);
        return $response->data ?? [];
    }

    public function createChatbot(string $tenantId, array $chatbotData): array
    {
        $response = $this->workCoreGateway->action('tools/create_chatbot', [
            'tenant_id' => $tenantId,
            'chatbot_data' => $chatbotData,
        ]);
        return $response->data ?? [];
    }

    public function trainChatbot(string $tenantId, string $chatbotId, array $trainingData): array
    {
        $response = $this->workCoreGateway->action('tools/train_chatbot', [
            'tenant_id' => $tenantId,
            'chatbot_id' => $chatbotId,
            'training_data' => $trainingData,
        ]);
        return $response->data ?? [];
    }

    public function addIntent(string $tenantId, string $chatbotId, array $intentData): array
    {
        $response = $this->workCoreGateway->action('tools/add_intent', [
            'tenant_id' => $tenantId,
            'chatbot_id' => $chatbotId,
            'intent_data' => $intentData,
        ]);
        return $response->data ?? [];
    }

    public function addResponse(string $tenantId, string $intentId, string $response): array
    {
        $response = $this->workCoreGateway->action('tools/add_response', [
            'tenant_id' => $tenantId,
            'intent_id' => $intentId,
            'response' => $response,
        ]);
        return $response->data ?? [];
    }

    public function processConversation(string $tenantId, string $chatbotId, string $userMessage): array
    {
        $response = $this->workCoreGateway->action('tools/process_conversation', [
            'tenant_id' => $tenantId,
            'chatbot_id' => $chatbotId,
            'user_message' => $userMessage,
        ]);
        return $response->data ?? [];
    }

    public function deployChatbot(string $tenantId, string $chatbotId): array
    {
        $response = $this->workCoreGateway->action('tools/deploy_chatbot', [
            'tenant_id' => $tenantId,
            'chatbot_id' => $chatbotId,
        ]);
        return $response->data ?? [];
    }

    public function getConversationHistory(string $tenantId, string $conversationId): array
    {
        $response = $this->workCoreGateway->query('tools/conversation_history', [
            'tenant_id' => $tenantId,
            'conversation_id' => $conversationId,
        ]);
        return $response->data ?? [];
    }

    public function updateChatbotConfig(string $tenantId, string $chatbotId, array $config): array
    {
        $response = $this->workCoreGateway->action('tools/update_chatbot_config', [
            'tenant_id' => $tenantId,
            'chatbot_id' => $chatbotId,
            'config' => $config,
        ]);
        return $response->data ?? [];
    }
}

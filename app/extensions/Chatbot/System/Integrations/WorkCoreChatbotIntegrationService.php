<?php

namespace Extensions\Chatbot\System\Integrations;

use Extensions\Chatbot\System\Integrations\WorkCore\Foundation\PWAFoundationAdapter;
use Extensions\Chatbot\System\Integrations\WorkCore\CRM\CRMAssistantAdapter;
use Extensions\Chatbot\System\Integrations\WorkCore\Commerce\CommerceOperationsAdapter;
use Extensions\Chatbot\System\Integrations\WorkCore\Operations\JobDispatchAdapter;
use Extensions\Chatbot\System\Integrations\WorkCore\Assets\PropertyAssistantAdapter;
use Extensions\Chatbot\System\Integrations\WorkCore\HR\HRAssistantAdapter;

class WorkCoreChatbotIntegrationService
{
    protected $foundationAdapter;
    protected $crmAdapter;
    protected $commerceAdapter;
    protected $operationsAdapter;
    protected $propertyAdapter;
    protected $hrAdapter;

    public function __construct(
        PWAFoundationAdapter $foundationAdapter,
        CRMAssistantAdapter $crmAdapter,
        CommerceOperationsAdapter $commerceAdapter,
        JobDispatchAdapter $operationsAdapter,
        PropertyAssistantAdapter $propertyAdapter,
        HRAssistantAdapter $hrAdapter
    ) {
        $this->foundationAdapter = $foundationAdapter;
        $this->crmAdapter = $crmAdapter;
        $this->commerceAdapter = $commerceAdapter;
        $this->operationsAdapter = $operationsAdapter;
        $this->propertyAdapter = $propertyAdapter;
        $this->hrAdapter = $hrAdapter;
    }

    public function initializePWA(string $tenantId, string $userId): array
    {
        return [
            'foundation' => $this->foundationAdapter->initializePWA($tenantId, $userId),
        ];
    }

    public function enrichConversationContext(string $tenantId, array $conversationContext): array
    {
        $enriched = [];

        // Detect conversation type and load relevant context
        if ($this->isAboutCRM($conversationContext)) {
            $enriched['crm'] = $this->crmAdapter->getCRMContext($tenantId, $conversationContext);
        }

        if ($this->isAboutCommerce($conversationContext)) {
            $enriched['commerce'] = $this->commerceAdapter->getCommerceContext($tenantId, $conversationContext);
        }

        if ($this->isAboutOperations($conversationContext)) {
            $enriched['operations'] = $this->operationsAdapter->getJobContext($tenantId, $conversationContext);
        }

        if ($this->isAboutProperty($conversationContext)) {
            $enriched['property'] = $this->propertyAdapter->getPropertyContext($tenantId, $conversationContext);
        }

        if ($this->isAboutHR($conversationContext)) {
            $enriched['hr'] = $this->hrAdapter->getHRContext($tenantId, $conversationContext);
        }

        return $enriched;
    }

    protected function isAboutCRM(array $context): bool
    {
        $keywords = ['customer', 'crm', 'contact', 'lead', 'account', 'catalogue', 'knowledge'];
        return $this->hasKeywords($context, $keywords);
    }

    protected function isAboutCommerce(array $context): bool
    {
        $keywords = ['order', 'purchase', 'inventory', 'price', 'payment', 'product', 'procurement'];
        return $this->hasKeywords($context, $keywords);
    }

    protected function isAboutOperations(array $context): bool
    {
        $keywords = ['job', 'dispatch', 'schedule', 'fleet', 'repair', 'form', 'service'];
        return $this->hasKeywords($context, $keywords);
    }

    protected function isAboutProperty(array $context): bool
    {
        $keywords = ['property', 'asset', 'maintenance', 'document', 'building', 'premises'];
        return $this->hasKeywords($context, $keywords);
    }

    protected function isAboutHR(array $context): bool
    {
        $keywords = ['staff', 'hr', 'attendance', 'roster', 'shift', 'compliance', 'leave'];
        return $this->hasKeywords($context, $keywords);
    }

    protected function hasKeywords(array $context, array $keywords): bool
    {
        $text = strtolower(json_encode($context));
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                return true;
            }
        }
        return false;
    }
}

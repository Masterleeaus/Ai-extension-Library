<?php

namespace Extensions\VerticalCustomization\System\Customization;

/**
 * Vertical Customization Framework
 * Issues #205-#210: Multi-tenant vertical customization
 */
class VerticalCustomizationFramework
{
    protected $tenantId;
    protected $verticalId;
    protected $customizations = [];

    public function __construct(string $tenantId, string $verticalId)
    {
        $this->tenantId = $tenantId;
        $this->verticalId = $verticalId;
    }

    // Issue #205: Prompt Customization Framework
    public function getPrompts(): array
    {
        return $this->customizations['prompts'] ?? [];
    }

    public function setPrompt(string $key, string $prompt): void
    {
        $this->customizations['prompts'][$key] = $prompt;
    }

    // Issue #206: Template Management Framework
    public function getTemplates(): array
    {
        return $this->customizations['templates'] ?? [];
    }

    public function setTemplate(string $name, array $template): void
    {
        $this->customizations['templates'][$name] = $template;
    }

    // Issue #207: Forms Builder Framework
    public function getForms(): array
    {
        return $this->customizations['forms'] ?? [];
    }

    public function setForm(string $name, array $formSchema): void
    {
        $this->customizations['forms'][$name] = $formSchema;
    }

    // Issue #208: Localization Framework
    public function getLocalizations(): array
    {
        return $this->customizations['localization'] ?? [];
    }

    public function setLocalization(string $locale, array $translations): void
    {
        $this->customizations['localization'][$locale] = $translations;
    }

    // Issue #209: Branding & Theming Framework
    public function getBranding(): array
    {
        return $this->customizations['branding'] ?? [];
    }

    public function setBranding(array $branding): void
    {
        $this->customizations['branding'] = array_merge(
            $this->customizations['branding'] ?? [],
            $branding
        );
    }

    // Issue #210: Behavior Configuration Framework
    public function getBehaviorConfig(): array
    {
        return $this->customizations['behavior'] ?? [];
    }

    public function setBehaviorConfig(array $config): void
    {
        $this->customizations['behavior'] = array_merge(
            $this->customizations['behavior'] ?? [],
            $config
        );
    }

    public function getAllCustomizations(): array
    {
        return $this->customizations;
    }
}

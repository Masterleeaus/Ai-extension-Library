<?php

namespace Tests\Extensions\VerticalCustomization\Unit\Conformance;

use PHPUnit\Framework\TestCase;

class VerticalCustomizationConformanceTest extends TestCase
{
    public function testExtensionStructureExists(): void
    {
        $this->assertTrue(
            is_dir(__DIR__ . '/../../../../System'),
            'VerticalCustomization System directory must exist'
        );
    }

    public function testExtensionJsonExists(): void
    {
        $extensionJson = __DIR__ . '/../../../../extension.json';
        $this->assertFileExists($extensionJson, 'extension.json must exist');

        $content = json_decode(file_get_contents($extensionJson), true);
        $this->assertNotNull($content, 'extension.json must be valid JSON');
        $this->assertArrayHasKey('name', $content);
        $this->assertEquals('VerticalCustomization', $content['name']);
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('completed', $content['status']);
    }

    public function testReadmeExists(): void
    {
        $readme = __DIR__ . '/../../../../README.md';
        $this->assertFileExists($readme, 'README.md must exist');
        $this->assertNotEmpty(file_get_contents($readme), 'README.md must not be empty');
    }

    public function testPromptsProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/Prompts/PromptCustomizationProvider.php',
            'Prompt customization provider must exist'
        );
    }

    public function testTemplatesProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/Templates/TemplateManagementProvider.php',
            'Template management provider must exist'
        );
    }

    public function testFormsProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/Forms/FormsBuilderProvider.php',
            'Forms builder provider must exist'
        );
    }

    public function testLocalizationProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/Localization/LocalizationProvider.php',
            'Localization provider must exist'
        );
    }

    public function testBrandingProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/Branding/BrandingCustomizationProvider.php',
            'Branding customization provider must exist'
        );
    }

    public function testBehaviorProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/Behavior/BehaviorCustomizationProvider.php',
            'Behavior customization provider must exist'
        );
    }

    public function testServiceProviderExists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../../System/VerticalCustomizationServiceProvider.php',
            'Service provider must exist'
        );
    }
}

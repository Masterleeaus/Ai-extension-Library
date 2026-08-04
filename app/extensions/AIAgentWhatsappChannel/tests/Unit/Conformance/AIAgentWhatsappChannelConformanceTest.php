<?php

namespace Tests\Extensions\AIAgentWhatsappChannel\Unit\Conformance;

use PHPUnit\Framework\TestCase;

class AIAgentWhatsappChannelConformanceTest extends TestCase
{
    public function testExtensionStructureExists(): void
    {
        $this->assertTrue(
            is_dir(__DIR__ . '/../../../../System'),
            'AIAgentWhatsappChannel System directory must exist'
        );
    }

    public function testExtensionJsonExists(): void
    {
        $extensionJson = __DIR__ . '/../../../../extension.json';
        $this->assertFileExists($extensionJson, 'extension.json must exist');

        $content = json_decode(file_get_contents($extensionJson), true);
        $this->assertNotNull($content, 'extension.json must be valid JSON');
        $this->assertArrayHasKey('name', $content);
        $this->assertEquals('AIAgentWhatsappChannel', $content['name']);
        $this->assertArrayHasKey('status', $content);
        $this->assertEquals('completed', $content['status']);
    }

    public function testReadmeExists(): void
    {
        $readme = __DIR__ . '/../../../../README.md';
        $this->assertFileExists($readme, 'README.md must exist');
        $this->assertNotEmpty(file_get_contents($readme), 'README.md must not be empty');
    }

    public function testDatabaseMigrationsExist(): void
    {
        $this->assertTrue(
            is_dir(__DIR__ . '/../../../../database/migrations'),
            'Database migrations directory must exist'
        );
    }

    public function testWhatsappIntegrationExists(): void
    {
        $this->assertTrue(
            is_dir(__DIR__ . '/../../../../System'),
            'System directory with WhatsApp integration must exist'
        );
    }
}

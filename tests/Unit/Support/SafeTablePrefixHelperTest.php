<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Foundation\Support\SafeTablePrefixHelper;
use PHPUnit\Framework\TestCase;

class SafeTablePrefixHelperTest extends TestCase
{
    public function testIsApprovedPrefixWithValidPrefix(): void
    {
        $this->assertTrue(SafeTablePrefixHelper::isApprovedPrefix('extension_lifecycle_'));
        $this->assertTrue(SafeTablePrefixHelper::isApprovedPrefix('access_control_'));
        $this->assertTrue(SafeTablePrefixHelper::isApprovedPrefix('audit_trail_'));
        $this->assertTrue(SafeTablePrefixHelper::isApprovedPrefix('workflow_engine_'));
    }

    public function testIsApprovedPrefixWithInvalidPrefix(): void
    {
        $this->assertFalse(SafeTablePrefixHelper::isApprovedPrefix('invalid_'));
        $this->assertFalse(SafeTablePrefixHelper::isApprovedPrefix('malicious_'));
        $this->assertFalse(SafeTablePrefixHelper::isApprovedPrefix(''));
    }

    public function testBuildTableNameWithValidInputs(): void
    {
        $result = SafeTablePrefixHelper::buildTableName('extension_lifecycle_', 'extensions');
        $this->assertEquals('extension_lifecycle_extensions', $result);
    }

    public function testBuildTableNameWithInvalidPrefixThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SafeTablePrefixHelper::buildTableName('invalid_', 'extensions');
    }

    public function testBuildTableNameWithInvalidTableNameThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SafeTablePrefixHelper::buildTableName('extension_lifecycle_', 'extensions;DROP TABLE');
    }

    public function testBuildTableNamePreventsSQLInjection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SafeTablePrefixHelper::buildTableName('extension_lifecycle_', 'extensions\'; DROP TABLE users; --');
    }
}

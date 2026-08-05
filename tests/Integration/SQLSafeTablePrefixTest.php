<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SQLSafeTablePrefixTest extends TestCase
{
    public function testNoStringInterpolationInImplementationFiles(): void
    {
        $baseDir = dirname(__DIR__, 2) . '/foundation/Implementations';
        $files = glob($baseDir . '/*.php');

        $this->assertNotEmpty($files, 'No implementation files found');

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Verify no unsafe interpolation patterns remain
            $this->assertStringNotContainsString('{$this->tablePrefix}', $content,
                basename($file) . ' still contains unsafe table prefix interpolation');
        }
    }

    public function testAllImplementationsUseTableConstants(): void
    {
        $baseDir = dirname(__DIR__, 2) . '/foundation/Implementations';
        $files = glob($baseDir . '/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Should define TABLE_PREFIX constant
            if (preg_match('/private const TABLE_PREFIX = /', $content)) {
                // Should use self::TABLE_ for referencing tables
                $this->assertTrue(
                    preg_match('/self::TABLE_/', $content) > 0 || preg_match('/self::TABLE_PREFIX/', $content) > 0,
                    basename($file) . ' defines TABLE_PREFIX but does not use self::TABLE_ constants'
                );
            }
        }
    }

    public function testTableConstantsFollowNamingConvention(): void
    {
        $baseDir = dirname(__DIR__, 2) . '/foundation/Implementations';
        $files = glob($baseDir . '/*.php');

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Extract all TABLE_ constants
            if (preg_match_all('/private const (TABLE_\w+) =/', $content, $matches)) {
                foreach ($matches[1] as $const) {
                    $this->assertTrue(
                        preg_match('/^TABLE_[A-Z_]+$/', $const) > 0,
                        "{$const} in " . basename($file) . " should be all uppercase with TABLE_ prefix"
                    );
                }
            }
        }
    }
}

<?php

namespace Tests\Unit\Foundation\Implementations;

use PDO;
use PHPUnit\Framework\TestCase;
use Mockery;

/**
 * Base test case for all Foundation implementation classes
 *
 * Provides common mocking utilities, database helpers, and tenant isolation testing
 */
abstract class FoundationImplementationTestCase extends TestCase
{
    /**
     * @var PDO|Mockery\MockInterface
     */
    protected $mockPdo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockPdo = Mockery::mock(PDO::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Create a mock PDO statement
     */
    protected function createMockStatement(
        $willReturn = null,
        $shouldExecute = true,
        $rowCount = 1
    ): Mockery\MockInterface {
        $stmt = Mockery::mock('PDOStatement');

        if ($shouldExecute) {
            $stmt->shouldReceive('execute')->andReturn(true);
        }

        if ($willReturn !== null) {
            $stmt->shouldReceive('fetch')->andReturn($willReturn);
            $stmt->shouldReceive('fetchAll')->andReturn([$willReturn]);
        }

        $stmt->shouldReceive('rowCount')->andReturn($rowCount);

        return $stmt;
    }

    /**
     * Helper to create a full mock PDO with prepared statements
     */
    protected function setupMockPdo($expectations = []): void
    {
        foreach ($expectations as $sql => $config) {
            $stmt = $this->createMockStatement(
                $config['return'] ?? null,
                $config['execute'] ?? true,
                $config['rowCount'] ?? 1
            );

            $this->mockPdo
                ->shouldReceive('prepare')
                ->with($sql)
                ->andReturn($stmt);
        }
    }

    /**
     * Validate that a UUID-like string is properly formatted
     */
    protected function isValidId(string $id, int $expectedLength = 32): bool
    {
        return strlen($id) === $expectedLength && ctype_xdigit($id);
    }
}

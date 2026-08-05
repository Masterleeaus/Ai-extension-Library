<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GooglePinterestProviderRequestShapeContractTest extends TestCase
{
    private function source(string $path): string
    {
        $absolute = dirname(__DIR__, 2) . '/' . ltrim($path, '/');

        return is_file($absolute) ? (string) file_get_contents($absolute) : '';
    }

    public function test_oauth_scope_discovery_falls_back_only_to_previously_verified_scopes(): void
    {
        foreach ([
            'System/Http/Controllers/Oauth/GoogleBusinessProfileController.php',
            'System/Http/Controllers/Oauth/PinterestController.php',
        ] as $path) {
            $controller = $this->source($path);

            $this->assertStringContainsString(
                "(array) (\$existingCredentials['authorized_scopes'] ?? [])",
                $controller
            );
            $this->assertStringNotContainsString(
                "(array) config('social-media.",
                $controller
            );
        }
    }

    public function test_google_review_sort_and_performance_query_match_official_request_shapes(): void
    {
        $helper = $this->source('System/Helpers/GoogleBusinessProfile.php');

        $this->assertStringContainsString("'orderBy' => 'update_time desc'", $helper);
        $this->assertStringContainsString("'dailyMetrics='", $helper);
        $this->assertStringContainsString('dailyRange.start_date.year', $helper);
        $this->assertStringContainsString('dailyRange.end_date.year', $helper);
        $this->assertStringNotContainsString('dailyRange.startDate.year', $helper);
        $this->assertStringNotContainsString('dailyRange.endDate.year', $helper);
    }
}

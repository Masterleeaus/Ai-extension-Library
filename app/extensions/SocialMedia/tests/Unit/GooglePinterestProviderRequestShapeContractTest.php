<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GooglePinterestProviderRequestShapeContractTest extends TestCase
{
    private function absolute(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    private function source(string $path): string
    {
        $absolute = $this->absolute($path);

        return is_file($absolute) ? (string) file_get_contents($absolute) : '';
    }

    private function config(string $path): array
    {
        $absolute = $this->absolute($path);

        return is_file($absolute) ? (array) require $absolute : [];
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

    public function test_destination_required_fields_use_canonical_aliases_consumed_by_services(): void
    {
        $google = $this->config('config/google-business-profile.php');
        $pinterest = $this->config('config/pinterest.php');
        $googleService = $this->source('System/Services/GoogleBusinessProfileService.php');
        $pinterestService = $this->source('System/Services/PinterestService.php');

        $this->assertContains('location', (array) data_get($google, 'destination.required_fields', []));
        $this->assertNotContains('location_name', (array) data_get($google, 'destination.required_fields', []));
        $this->assertStringContainsString("'location' => \$locationName", $googleService);

        $this->assertContains('media', (array) data_get($pinterest, 'destination.required_fields', []));
        $this->assertNotContains('image_url', (array) data_get($pinterest, 'destination.required_fields', []));
        $this->assertStringContainsString("'media' => [\$imageUrl]", $pinterestService);
    }
}

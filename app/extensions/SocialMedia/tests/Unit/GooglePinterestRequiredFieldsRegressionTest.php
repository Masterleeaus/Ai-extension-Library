<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GooglePinterestRequiredFieldsRegressionTest extends TestCase
{
    private function extensionPath(string $path): string
    {
        return dirname(__DIR__, 2) . '/' . ltrim($path, '/');
    }

    public function test_provider_required_fields_use_canonical_aliases(): void
    {
        $google = require $this->extensionPath('config/google-business-profile.php');
        $pinterest = require $this->extensionPath('config/pinterest.php');
        $googleService = file_get_contents(
            $this->extensionPath('System/Services/GoogleBusinessProfileService.php')
        );
        $pinterestService = file_get_contents(
            $this->extensionPath('System/Services/PinterestService.php')
        );

        $this->assertSame(['content', 'location'], data_get($google, 'destination.required_fields'));
        $this->assertSame(['title', 'board_id', 'images'], data_get($pinterest, 'destination.required_fields'));
        $this->assertStringContainsString("'location' => \$locationName", $googleService);
        $this->assertStringContainsString("'images' => [\$imageUrl]", $pinterestService);
    }

    public function test_google_readiness_requires_explicit_local_post_support(): void
    {
        $readiness = file_get_contents(
            $this->extensionPath('System/Services/GoogleBusinessProfileReadinessService.php')
        );
        $guard = file_get_contents(
            $this->extensionPath('System/Services/GoogleBusinessProfileResourceGuard.php')
        );
        $controller = file_get_contents(
            $this->extensionPath('System/Http/Controllers/GoogleBusinessProfileController.php')
        );

        $this->assertStringContainsString('postable_locations', $readiness);
        $this->assertStringContainsString("=== true", $readiness);
        $this->assertStringContainsString("!== true", $guard);
        $this->assertStringContainsString('GoogleBusinessProfileReadinessService', $controller);
        $this->assertStringContainsString('$this->readiness->readiness(', $controller);
    }
}

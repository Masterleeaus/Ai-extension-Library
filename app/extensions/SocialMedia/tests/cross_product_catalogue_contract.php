<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$source = static function (string $relative) use ($root): string {
    $path = $root . '/' . ltrim($relative, '/');

    return is_file($path) ? (string) file_get_contents($path) : '';
};

$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (! $condition) {
        $failures[] = $message;
    }
};

$assertContains = static function (string $needle, string $haystack, string $message) use ($assert): void {
    $assert(str_contains($haystack, $needle), $message);
};

$assertNotContains = static function (string $needle, string $haystack, string $message) use ($assert): void {
    $assert(! str_contains($haystack, $needle), $message);
};

$contract = $source('System/Contracts/CanonicalSourceAdapterContract.php');
$registry = $source('System/Services/CanonicalSourceRegistry.php');
$service = $source('System/Services/CanonicalCatalogueService.php');
$catalogues = $source('config/catalogues.php');
$model = $source('System/Models/DistributionItem.php');
$verticals = $source('config/vertical-distribution.php');

$assert($contract !== '', 'Missing CanonicalSourceAdapterContract.');
foreach (['sourceKey', 'available', 'supports', 'fetch', 'search', 'health', 'handoff'] as $method) {
    $assertContains("function {$method}", $contract, "CanonicalSourceAdapterContract missing {$method}().");
}
foreach (['function save(', 'function update(', 'function delete(', 'function create(', 'function setPrice(', 'function setInventory(', 'function setAvailability('] as $forbidden) {
    $assertNotContains($forbidden, $contract, "Read-only source contract must not expose {$forbidden}.");
}

$assert($catalogues !== '', 'Missing cross-product catalogue config.');
foreach (['crm', 'workcore', 'commerce', 'marketing', 'bookings', 'property', 'automotive', 'hire'] as $sourceKey) {
    $assertContains("'{$sourceKey}' => [", $catalogues, "Missing source catalogue {$sourceKey}.");
}
foreach (['generic-business', 'field-home-services', 'accommodation', 'real-estate', 'salons-personal-care', 'fitness-membership', 'automotive-services', 'ecommerce-retail', 'hire-rental', 'booking-capacity'] as $vertical) {
    $assertContains("'{$vertical}' => [", $catalogues, "Missing catalogue mapping for {$vertical}.");
}
$assertContains("'facilities-maintenance'", $verticals, 'Facilities maintenance must remain a field-home-services subtype.');
$assertNotContains("'facilities-management' => [", $catalogues, 'Facilities management must not become a tenth top-level catalogue vertical.');

$assert($registry !== '', 'Missing CanonicalSourceRegistry.');
foreach (['adapter', 'capabilities', 'health'] as $method) {
    $assertContains("function {$method}", $registry, "CanonicalSourceRegistry missing {$method}().");
}
foreach (['CanonicalSourceAdapterContract', 'app()->bound', 'class_exists', 'misconfigured', 'unavailable', "require dirname(__DIR__, 2) . '/config/catalogues.php'", 'array_intersect_key'] as $needle) {
    $assertContains($needle, $registry, "Canonical source registry missing {$needle} fail-closed boundary.");
}

$assert($service !== '', 'Missing CanonicalCatalogueService.');
foreach (['resolve', 'search', 'transform', 'health', 'handoff'] as $method) {
    $assertContains("function {$method}", $service, "CanonicalCatalogueService missing {$method}().");
}
foreach (['resolveVerticalProfile', 'canonical_source', 'source_system', 'source_type', 'source_id', 'source_version', 'source_updated_at', 'provenance', 'authority', 'attribution_confidence', 'missing_canonical_fields', 'tenant_mismatch', 'handoff_unavailable', 'ext_social_media_distribution_audits'] as $needle) {
    $assertContains($needle, $service, "Canonical catalogue service missing {$needle} contract.");
}
foreach (['boundedCanonicalFields', 'boundedStringList', "array_intersect_key(\$configuredSection, \$baseSection)", "'source_version', 'source_updated_at'"] as $needle) {
    $assertContains($needle, $service, "Canonical catalogue service missing hardening boundary {$needle}.");
}
$assertNotContains('::query()', $service, 'Canonical catalogue service must not directly query source-product Eloquent models.');

$assertContains('function fromCanonicalSource', $model, 'DistributionItem missing canonical source factory.');
$assertContains("'canonical_source'", $model, 'DistributionItem canonical source factory must persist provenance in payload.');
$assertContains("'source_type'", $model, 'DistributionItem must retain source type identity.');
$assertContains("'source_id'", $model, 'DistributionItem must retain source ID identity.');
$assertContains('createForUser($user, $attributes)', $model, 'Canonical source factory must reuse the existing tenant-owned DistributionItem factory.');

if ($failures !== []) {
    fwrite(STDERR, "Issue #275 contract failed:\n - " . implode("\n - ", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "Issue #275 cross-product catalogue contract passed.\n");
